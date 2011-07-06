<?php

class UserSession extends Model{
	
	protected $session;
	
	/**
	 * @param $variables is an array that consists of the database fields: ID, sessionID, userID, createdAt, lastUpdatedAt
	 */	
	public __construct($variables) {
		parent::__construct($variables);
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