<?php

class UserSession extends Model{
	
	protected $session;
	
	/**
	 * @param array $dbAttributes The database attributes: ID, sessionID, userID, createdAt, lastUpdatedAt
	 */	
	public __construct($dbAttributes) {
		parent::__construct($dbAttributes);
	}
	
	public function getSession() {
		if (!isset($this->session)) {
			$this->session = Session::findByID($this->sessionID);
		}
		return $this->session;
	}
	
	public function getUser() {
		if (!isset($this->user)) {
			$this->user = User::findByID($this->userID);
		}
		return $this->user;
	}
	
}