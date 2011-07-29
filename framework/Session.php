<?php

/**
* @property int $ID
* @property int $sessionID The PHP session ID.
* @property string $ipAddress The visitors IP address.
* @property int $createdAt
* @property int $lastUpdatedAt
*/
class Session extends Model
{	
	/** @var int TTL (TimeToLive), how long to keep a session alive. */
	const TTL = 7200;
	
	/** @var Session The singleton instance. */
	private static $instance = null;
	
	/** @var UserSession */
	protected $userSession;
	
	/**
	* @param array $dbAttributes The database attributes, see @property in class definition.
	*/
	public function __construct($dbAttributes) {
		parent::__construct($dbAttributes);
	}
	
	/**
	 * @todo Document when to use and when not to use this function.
	 */
	private function updateSession() {
		session_regenerate_id();
		$this->sessionID = session_id();
		$this->lastUpdatedAt = time();
		$this->saveToDatabase();
 	}
	
	/**
	* @return UserSession
	*/
	public function getUserSession(){
		if (!isset($this->userSession)) {
			$this->userSession = UserSession::find(array("conditions" => "sessionID=" . $this->ID));
		}
		return $this->userSession;
	}
	
	/**
	* @return User
	*/
	public function getUser() {
		if ($this->loggedIn()) {
			return $this->getUserSession()->getUser();
		}
		return null;
	}
	
	/**
	 * @return bool True if user logged in (Session has UserSession), otherwise false.
	 */
	public function isLoggedIn() {
		return $this->getUserSession() != null;
	}
	
	/**
	 * @param string $ipAddress The visitors IP address
	 */
	public static function create($ipAddress) {
		session_regenerate_id();
		self::$instance = new Session(array(null, session_id(), $ipAddress, time(), time()));
		return self::$instance;
	}
	
	/**
	 * @return bool True if new session, false if using existing session
	 */
	public static function start($ipAddress){
		session_start();
		$sessionID = session_id();
		$tempSession = Session::find(array("conditions" => "sessionID='$sessionID' ORDER BY lastUpdatedAt DESC LIMIT 1"));
		// If we found a corresponding session in the database.
		if ($tempSession != null) {
			self::$instance = $tempSession[0];
			// If the session still is viable. (Not longer than <TTL> seconds since last update.)
			if(time()-self::$instance->lastUpdatedAt > self::TTL) {
				self::create($ipAddress);
				$retval = true;
			} else {
				self::$instance->updateSession();
				$retval = false;
			}
		} else {
			self::create($ipAddress);
			$retval = true;
		}
		return $retval;
	}
	
	/**
	 * @return Session The singleton instance.
	 */
	public static function get() {
		return self::$instance;
	}

	/**
	 * Do not use!
	 * @todo Document/rewrite so it's correctly implemented/used.
	 */
	/*
	public static function destroy() {
		self::$instance = null;
		session_regenerate_id();
		session_destroy();
	}
	*/
}