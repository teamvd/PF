<?php

/**
 * @property int ID
 * @property int sessionID
 * @property int userID
 * @property int createdAt
 * @property int lastUpdatedAt
 */
class UserSession extends Model{
	
	/** @var Session $session */
	protected $session;

	/** @var User $user */
	protected $user;
	
	/**
	 * @param array $dbAttributes The database attributes, see @property documentation.
	 */
	public __construct($dbAttributes) {
		parent::__construct($dbAttributes);
	}

	/**
	 * @return Session
	 */
	public function getSession() {
		if (!isset($this->session)) {
			$this->session = Session::findByID($this->sessionID);
		}
		return $this->session;
	}
	
	/**
	 * @return User
	 */
	public function getUser() {
		if (!isset($this->user)) {
			$this->user = User::findByID($this->userID);
		}
		return $this->user;
	}
	
}