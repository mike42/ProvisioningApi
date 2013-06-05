<?php
class Provisioning_OrganizationUser {
	private $orgUserEmail;
	private $orgUnitPath;
	
	/* Track updated orgUnitPath */
	private $changes;
	
	public function __construct($orgUserEmail, $orgUnitPath) {
		$this -> orgUserEmail = $orgUserEmail;
		$this -> orgUnitPath = $orgUnitPath;
		
		$this -> orgUnitPath = array();
	}
	
	public function getorgUserEmail() {
		return $this -> orgUserEmail;
	}
	
	public function setorgUserPath() {
		return $this -> orgUserEmail;
	}
}