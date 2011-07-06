<?php

class Session extends Model
{	
	private static $instance = null;
	protected $userSession;

	const TTL = 7200;
	
	/**
	*	@param $variables is an array that consists of the database fields: ID, sessionID, ipAddress, createdAt, lastUpdatedAt
	*/
	public function __construct($variables) {
		parent::__construct($variables);
	}
	
	/**
	 * 
	 */
	private function updateSession() {
		session_regenerate_id();
		$this->sessionID = session_id();
		$this->lastUpdatedAt = time();
		$this->saveToDatabase();
 	}
	
	public function getUserSession(){
		if (!isset($this->userSession)) {
			$this->userSession = UserSession::find(array("conditions" => "sessionID=" . $this->ID));
		}
		return $this->userSession;
	}
	
	public function getUser() {
		if (self::loggedIn()) {
			return $this->getUserSession()->getUser();
		}
		return null;
	}
	
	
	/**
	 * @param String $ipAddress The visitors IP address
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
			// If the session still is viable. (Not longer than <TTL seconds> since last update.)
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
	 * 
	 */
	public static function get() {
		return self::$instance;
	}
	
	public static function loggedIn() {
		return $this->getUserSession() != null;
	}
	/**
	* Do not use!
	*/
	/*
	public static function destroy() {
		self::$instance = null;
		session_regenerate_id();
		session_destroy();
	}
	*/
}