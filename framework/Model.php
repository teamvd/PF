<?php
// Require the BLSClass so that we can extend it:
//require_once(__DIR__ . '/../BLSClass.php');

/**
 *	The super class that all Models should inherit from. 
 */
abstract class Model {

	// Variabler för att hålla reda på vilka instanser från databasen som finns i minnet:
	private static $allInstances = array();
	// Associativ lista med boolska värden (ifall alla instanser av klassen finns i minnet):
	private static $isEveryInstanceInMemory = null;

	// Variabler för att hålla koll på när det blir fel i systemet.
	private static $objectsCopysAmount = 0;
	private static $objectCopysWhere = array();
	private static $objectCopys = array();
	
	// Array för att översätta mysql fälttyper till php variabletyper.
	private static $variableDictionary = array('text' => "string", 'varchar' => "string", "int" => "integer", "tinyint" => "Boolean");
	
	protected $fields = array();
	protected $variables = array();
	protected $creator = null;

	// Varje modell ska känna till namnet på sin databastabell:
	const DATABASE_TABLE_NAME = null;
	
	
	//private $ID;
	
	/**
	 *	@param $variables Array with the models attributes
	 */
	function __construct($variables) {
		$this->getTableInfo();
		$this->populateVariables($variables);
		
		// Används för att lägga till instanser i minnet: 
		$this->saveInMemory();
		
		// Används för att spara till databasen om det är ett nytt objekt.
		if ($this->ID == null) {
			$this->saveToDatabase();
		}
	}
	
	//////////// INSTANCE METHODS:
	
	private function populateVariables($variables) {
		if (count($variables) != count($this->fields)) {
			static::invalidInitiationOfObject($this->fields, $variables, "Fel antal parametrar skickades in i en model!");
		}
		$i = 0;
		foreach($variables as $variable) {
			if (is_numeric($variable)) {
				$variable = (int)$variable;
			}
			preg_match("/[A-Za-z]+/", $this->fields[$i]['type'], $matches);
			if (self::$variableDictionary[$matches[0]] != gettype($variable) && gettype($variable) != "NULL" && !(gettype($variable) == "integer" && self::$variableDictionary[$matches[0]] == "string")) {
				static::invalidInitiationOfObject($this->fields, $variables, "Fältet: " . $this->fields[$i]['field'] . " var av typen: " .  self::$variableDictionary[$matches[0]] . " (" . $this->fields[$i]['type'] .") medens variablen som skickades in var av typen: " . gettype($variable) . ", variablen var inskickad på position: " . $i . ".<br />\n Variablens värde var: " . $variable);
			}
			$this->fields[$i]['phpType'] = self::$variableDictionary[$matches[0]];
			//TODO: behöver fixa så att det översätts från t.ex. int(11) till integer och t.ex. varchar och text översätts till string. Och tinyint eller smalint görs om till rätt saker med.
			/*if ($this->fields[$i]['type'] != gettype($variable)) {
				static::invalidInitiationOfObject($this->fields, $variables, "Fältet: " . $this->fields[$i]['field'] . " var av typen: " . $this->fields[$i]['type'] ." medens variablen som skickades in var av typen: " . gettype($variable) . ", variablen var inskickad på position: " . $i . ".");
			}*/
			$this->variables[$this->fields[$i++]['field']] = $variable;
		}
	}
	
	private function getTableInfo() {
		$query = "DESCRIBE " . self::getDatabaseTableName();
		$result = DatabaseConnection::get()->query($query);
		$i = 0;
		while($row = $result->mysqli_fetch_assoc()) {
			$this->fields[$i++] = array('field' => $row['Field'], 'type' => $row['Type']);
		}
	}
	
	public function __get($name)
	{
		if(array_key_exists($name, $this->variables))
		{
			return $this->variables[$name];
		}
		else
		{
			trigger_error("Försökte anropa en variable med namn: '" . $name . "', som inte existerar. Klassen som kallade på variablen är: '" . get_called_class() ."'", E_USER_ERROR);
		}
	}
	
	public function __set($name, $value)
	{
		if(isset($this->variables[$name]))
		{
			$this->variables[$name] = $value;
		}
		else
		{
			trigger_error("Försökte sätta en variable med namn: '" . $name . "', som inte existerar. Klassen som kallade på variablen är: '" . get_called_class() ."'", E_USER_ERROR);
		}
	}
	
	// DEBUG FUNCTIONS - do not use plz
	public function getFields() {
		return $this->fields;
	}
	
	public function getVariables() {
		return $this->variables;
	}
	// END DEBUG FUNCTIONS
	
	
	public function isSaved(){
		// Uttrycket "!!" vänder på uttrycket två gånger och ger ett boolskt värde, så 1 blir true och 0 blir false.
		return !!$this->getID();
	}
	
	/**
	 * Sparar instansen i minnet.
	 */
	public function saveInMemory(){
		
		// Spara bara i minnet om instansen har ett ID (dvs finns i databasen):
		if(!is_numeric($this->getID())){
			return false; 
		}
		
		
		if (!array_key_exists(get_class($this), self::$allInstances)) {
			self::$allInstances[get_class($this)] = array();
		}
		
		// Varna för dubletter ifall instanser redan fanns i minnet:
		if(self::inMemory($this->getID()))
		{
			if(!isset($GLOBALS['objectCopys'])){
				$GLOBALS['objectCopys'] = 0;
			}
			// Öka antalet kopior av alla modeller:
			$GLOBALS['objectCopys']++;
			
			if(!isset($GLOBALS['objectCopysWhere'])){
				$GLOBALS['objectCopysWhere'] = array();
			}
			if(!isset($GLOBALS['objectCopysWhere'][get_class($this)])){
				$GLOBALS['objectCopysWhere'][get_class($this)] = 0;
			}
			// Öka antalet kopior av denna modellen:
			$GLOBALS['objectCopysWhere'][get_class($this)]++;
			
			// Det gick inte bra att spara (fanns redan). 
			// --> ELLER BORDE DEN RETURNERA TRUE ÄNDÅ?
			return false;
		} else {
			// Spara det i minnet:
			self::$allInstances[get_class($this)][$this->getID()] = $this;
		}
		
		// Det gick bra att spara:
		return true;
	}
	
	public function saveToDatabase()
	{
		// Shove it into the database:
	
		if($this->variables['ID'] == null)
		{
			$query = "INSERT INTO " . self::getDatabaseTableName() . "(";
			$valuesStringed = "";
			foreach($this->fields as $field) {
				if ($field['field'] == "createdAt" && !isset($this->variables[$field['field']])) {
					$this->variables[$field['field']] = time();
				}
				if ($field['field'] != "ID") {
					$query .= $field['field'] . ", ";
					if (is_null($this->variables[$field['field']])) {
						$valuesStringed .= "NULL, ";
					} else if ($field['phpType'] == "string") {
						$valuesStringed .= "'" . mysqli_real_escape_string($this->variables[$field['field']]) . "', ";	
					} else {
						$valuesStringed .= $this->variables[$field['field']] . ", ";
					}
				}
			}
			$query = substr($query, 0, -2);
			$valuesStringed = substr($valuesStringed, 0, -2);
			$query .= ") VALUES(" . $valuesStringed . ")";
		}
		else
		{
			$query = "UPDATE " . self::getDatabaseTableName() . " SET ";
			foreach($this->fields as $field) {
				if ($field['field'] != "ID") {
					if (is_null($this->variables[$field['field']])) {
						$query .= $field['field'] . " = NULL, ";
					} else if ($field['phpType'] == "string") {
						$query .= $field['field'] . " = '" . mysqli_real_escape_string($this->variables[$field['field']]) . "', ";
					} else {
						$query .= $field['field'] . " = " . $this->variables[$field['field']] . ", ";
					}
				}
			}
			$query = substr($query, 0, -2) . " WHERE ID = " . $this->variables['ID'];
		}
		
		$result = DatabaseConnection::get()->query($query);
	
		// Save the ID of the just inserted row:
		$mysqli_insert_id = mysqli_insert_id();
	
		if($mysqli_insert_id != 0)
		{
			$this->variables['ID'] = $mysqli_insert_id;
		}
	
		return $result;
	
	}

	public function getID() {
		return $this->variables['ID'];
	}


	public function __call($name, $arguments)
	{
		echo("Tried to call undefined instance method '" . $name . "'");
	}
	
	public function convertToStdClass() {
		$stdClass = new stdClass();
		foreach($this->fields as $field) {
			$fieldName = $field['field'];
			$stdClass->$fieldName = $this->variables[$fieldName]; 
		}
		return $stdClass;
	}
	
	function getCreator() {
		if ($this->creator == null) {
			$this->creator = User::findByID($this->createdBy);
		}
		return $this->creator;
	}
	
	//////////// CLASS METHODS:
	
	protected static function inMemory($ID) {
		if (!array_key_exists(get_called_class(), self::$allInstances)) {
			self::$allInstances[get_called_class()] = array();
		}
		if (isset(self::$allInstances[get_called_class()][$ID])) {
			return true;
		}
		return false;
	}
	
	/**
	 * 
	 */
	protected static function setIsEveryInstanceInMemory($bool) {
		self::$isEveryInstanceInMemory[get_called_class()] = $bool;
	}
	
	protected static function isEveryInstanceInMemory() {
		return self::$isEveryInstanceInMemory[get_called_class()];
	}
	
	/**
	 * Hittar instansen med det matchande ID:t
	 * @param $ID integer Det unika numret instansen har i databasens tabell
	 * @return Model Den matchande instansen
	 */
	public static function findByID($ID){
		if (!array_key_exists(get_called_class(), self::$allInstances)) {
			self::$allInstances[get_called_class()] = array();
		}
		// Finns den i minnet? Returnera instansen direkt istället för att hämta från databas:
		if(is_array(self::$allInstances[get_called_class()]) && array_key_exists($ID, self::$allInstances[get_called_class()]))
		{
			return self::$allInstances[get_called_class()][$ID];
		}
		// Kontrollera att vi fick ett ID som är en siffra:
		if(isset($ID) && is_numeric($ID))
		{
			$query = "SELECT * FROM " . static::getDatabaseTableName() . " WHERE ID = $ID LIMIT 1";
			$result = DatabaseConnection::get()->query($query);

			// Fick vi något resultat?
			if ($result->num_rows < 1) {
				return null;
			}
			
			$row = $result->fetch_assoc();
			
			// Hade tabellen några attribut?
			if(sizeOf($row) < 1)
			{
				return null;
			}
			$calledClass = get_called_class();
			return new $calledClass($row);
		}
		else
		{
			return null;
		}
	}
	
	/**
	 * Mer avancerad sökning
	 * Exempel:
	 * $membersInGroup4 = GroupMember::find(array('conditions' => array('groupID' => 4), 'order' => 'userID ASC'));
	 * TODO: LÅNGT FRÅN FÄRDIG, TESTA GÄRNA MEN DET HÄR VAR EN VARNING
	 * Förslag på möjliga parametrar:
	 * 	conditions
	 * 	first
	 * 	all
	 * 	order
	 * 	select
	 * 	from
	 * Inspiration:
	 * http://ar.rubyonrails.org/classes/ActiveRecord/Base.html#M000333
	 * @param $parameters Lista med sökparametrar
	 */
	public static function find($parameters){
		// Lokala variabler:
		$select = "*";
		$from = static::getDatabaseTableName();
		$response = null;	// Defaultvärde ifall inget hittades.
		
		// Fick vi ett ID? Kör findByID() istället:
		if(isset($parameters) && is_numeric($parameters)){
			return self::findByID($parameters);
		}
		
		// Tillåt att man hämtar specifika värden istället för SELECT * FROM:
		if(isset($parameters['select'])){
			$select = $parameters['select'];
		}
		
		// Tillåt att man hämtar från andra tabeller:
		if(isset($parameters['from'])){
			$from = $parameters['from'];
		}
		
		// Basfrågan:
		$query = "SELECT $select FROM $from";
		
		// Hantera 'conditions' (WHERE-sats):
		if(isset($parameters['conditions'])){
			$query .= ' WHERE ';
			if(is_string($parameters['conditions'])){
				$query .= $parameters['conditions'];
			}
			elseif(is_array($parameters['conditions'])){
				/* 
					Gör om:
						array('userID' => 4, 'age' => 15)
					till
						"userID='4', age='15'"
				*/
				$query .= implode(' AND ', array_map(
					function($key, $value){
						return $key . "='" . $value . "'";
					}, 
					array_keys($parameters['conditions']), 
					$parameters['conditions'])
				);
			}
		}
		
		if(isset($parameters['order'])){
			$query .= " ORDER BY " . $parameters['order'];
		}
		
		elseif(isset($parameters['limit'])){
			$query .= " LIMIT " . $parameters['limit'];
		}
		$result = DatabaseConnection::get()->query($query);
		if($result){
			$rowcount = $result->num_rows;
			if($rowcount > 0)
			{
				if ($select == "count(*)") {
					$response = $result->current_field;
				} else {
					if ($rowcount == 1) {
						if(!is_array(self::$allInstances[get_called_class()]) || !array_key_exists($row['ID'], self::$allInstances[get_called_class()]))
						{
							$response = new $calledClass($row);
						}
						else{
							$response = self::$allInstances[get_called_class()][$row['ID']];
						}
					} else {
						$calledClass = get_called_class();
						if (!isset(self::$allInstances[get_called_class()])) {
							self::$allInstances[get_called_class()] = array();
						}
		
						for($i = 0; $i < $rowcount; $i++)
						{
							$row = $result->fetch_assoc();
							// Skapa bara instanser igen om den inte redan finns i minnet:
							if(!is_array(self::$allInstances[get_called_class()]) || !array_key_exists($row['ID'], self::$allInstances[get_called_class()]))
							{
								$response[] = new $calledClass($row);
							}
							else{
								$response[] = self::$allInstances[get_called_class()][$row['ID']];
							}
						}
					}
				}
			}
		}
		
		return $response;
	}
	
	/**
	 * Hämtar alla instanser av klassen
	 * Försöker först via minnet och sen via databasen.
	 * @param $parameters Array Lista med parametrar, exempelvis sorteringsordning.
	 * @return Array Lista med instanser
	 */
	public static function all($parameters = array()){
		if (!array_key_exists(get_called_class(), self::$allInstances)) {
			self::$allInstances[get_called_class()] = array();
		}
		if(empty(self::$allInstances[get_called_class()]) || !self::$isEveryInstanceInMemory[get_called_class()]){
			$query = "SELECT * FROM " . static::getDatabaseTableName();
			$result = DatabaseConnection::get()->query($query);
			if($result){
				$rowcount = $result->num_rows;
				if($rowcount > 0)
				{	
					$calledClass = get_called_class();
					for($i = 0; $i < $rowcount; $i++)
					{
						$row = $result->fetch_assoc();
						// Skapa bara instanser igen om den inte redan finns i minnet:
						if(!is_array(self::$allInstances[get_called_class()]) || !array_key_exists($row['ID'], self::$allInstances[get_called_class()]))
						{
							new $calledClass($row);
						}
					}
				}
				// Markera att alla instanser finns i minnet:
				self::$isEveryInstanceInMemory[get_called_class()] = true;
			}
		}
		return self::$allInstances[get_called_class()];
	}
	
	/**
	 * Skapar en ny instans av modellen utifrån en array som innehåller dess attribut.
	 * Exempel på vad metoden ska innehålla (när man overridar denna):
	 *   return new Group($array['ID'], $array['name'], $array['createdAt']);
	 * @param $array array En associativ array med attributen för modellen.
	 * @return Model Den nya instansen av modellen.
	 */
	// Bortkommenterad till full implementering (krävs i ALLA eller INGA subklasser pga ABSTRACT):
	//public abstract static function createFromArray($array);
	
	/**
	 * Returnerar namnet på databastabellen för modellen
	 * TODO: Borde kanske vara abstract och låtas implementeras av varje klass istf. konstant?
	 * Se fråga på StackOverflow:
	 * http://stackoverflow.com/questions/5019998/php-const-static-variables-not-usable-in-static-context-by-parent-class
	 * @author MPV
	 * @return String Namnet på tabellen
	 */
	public static function getDatabaseTableName(){
		return (static::DATABASE_TABLE_NAME == null ? get_called_class() : static::DATABASE_TABLE_NAME);
	}
	
	/**
	 * Räknar antalet instanser i databasen
	 * Inspirerad av:
	 * http://api.rubyonrails.org/classes/ActiveRecord/Calculations.html#method-i-count
	 * @author MPV
	 */
	public static function count(){
		$query = "SELECT COUNT(*) FROM " . static::getDatabaseTableName();
		$result = DatabaseConnection::get()->query($query);
		$row = $result->fetch_array();
		return $row[0];
	}
	
	/**
	 * Fångar upp anrop till saknade klassmetoder.
	 */
	public static function __callStatic($name, $arguments)
	{
		echo("Tried to call undefined static method '" . $name . "' in clas '" . get_called_class() . "' <br />");
	}
	
	public static function invalidInitiationOfObject($fields, $variables, $errorMessage)
	{
		$buffer  = "<strong>Det Uppstod ett fel när du initierade en model!</strong><br />\n";
		$buffer  .= "Felmedelande: " . $errorMessage . "<br />\n";
		$buffer .= "I modelen: " . get_called_class() . "<br />\n";
		$buffer .= "Antal parametrar inskickade: " . count($variables) . "<br />\n";
		$buffer .= "Antal Fält i databasen: " . count($fields) . "<br />\n";
//		$buffer .= "File: " . __FILE__ . "<br />\n";

		die($buffer);
	}

	/**
	 * TODO: PHPDOC
	 */
	public static function dumpAllInstances() {
		echo "<pre>";
		print_r(self::$allInstances);
		echo "</pre>";
	}

	public static function getObjectsCopysAmount() {
		return self::$objectsCopysAmount;
	}

	public static function getObjectCopysWhere() {
		return self::$objectCopysWhere;
	}

	public static function getObjectCopys() {
		return self::$objectCopys;
	}
}