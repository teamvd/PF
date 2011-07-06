<?php

class DatabaseConnection{
	
	private $connection;
	
	private static $instance;
	
	public function __construct(){
		$hostname	= Configuration::get()->database->hostname;
		$username 	= Configuration::get()->database->username;
		$password 	= Configuration::get()->database->password;
		$database 	= Configuration::get()->database->database;
		$port 		= Configuration::get()->database->port;
		
		$this->connection = new mysqli($hostname, $username, $password, $database, $port);
	}
	
	public function query($query){
		return $this->connection->query($query) or $this->logError($query);
	}
	
	// TODO: Move logging to a logger class.
	/**
	 * Loggar/skriver ut felmeddelanden från MySQL-frågor
	 * @param $query String Frågan som försöktes köras och misslyckades. 
	 */
	private function logError($query = null)
	{
		$debugArray = debug_backtrace();
		$buffer  = "<strong>Database error occured!</strong><br />\n";
		$buffer .= "In model: " . $debugArray[2]['class'] . "<br />\n";
		$buffer .= "In function: " . $debugArray[2]['function'] . "<br />\n";
		$buffer .= "Error number: " . $this->connection->errno . "<br />\n";
		$buffer .= "Error message: " . $this->connection->error . "<br />\n";
		$buffer .= "File: " . $debugArray[2]['file'] . "<br />\n";
		$buffer .= "Line: " . $debugArray[2]['line'] . "<br />\n";
		if($query){
			$buffer .= "Query: \"" . $query . "\"<br />\n";
		}
		// TODO: Save the errors somewhere (so we can know about them in production), for example:
		// http://php.net/manual/en/function.error-log.php
		// http://www.getexceptional.com/features
		die($buffer);
	}
	
	public static function get(){
		if(!isset(self::$instance)) {
			self::$instance = new DatabaseConnection();
		}
		return self::$instance; 
	}
	
}