<?php
/**
 * Encapsulate domain user, to track changes, and generate XML to create / modify the user.
 *
 * @author Michael Billington <michael.billington@gmail.com>
 */
class Provisioning_DomainUser {
	private $userEmail;
	private $firstName;
	private $lastName;
	private $password;
	private $hashFunction;
	private $isAdmin;
	private $isSuspended;

	/* Track updated fields */
	private $changes;

	/**
	 * Initialise with given parameters
	 *
	 * @param string $userEmail
	 * @param string $firstName
	 * @param string $lastName
	 * @param string $password Hashed password
	 * @param string $hashFunction Function used to hash the password
	 * @param boolean $isAdmin True if this is an admin account
	 */
	public function __construct($userEmail, $firstName, $lastName, $password, $hashFunction = "SHA-1", $isAdmin = false, $isSuspended = false) {
		/* Set variables */
		$this -> userEmail = $userEmail;
		$this -> firstName = $firstName;
		$this -> lastName = $lastName;
		$this -> password = $password;
		$this -> hashFunction = $hashFunction;
		$this -> isAdmin = (boolean)$isAdmin? 'true' : 'false';
		$this -> isSuspended = (boolean)$isSuspended? 'true' : 'false';
		$this -> changes = array();
	}

	/**
	 * Return XML to create this account
	 * https://developers.google.com/google-apps/provisioning/#creating_a_user_for_a_domain
	 */
	public function createXML() {
		return "<atom:entry xmlns:atom='http://www.w3.org/2005/Atom'\n" .
				"xmlns:apps='http://schemas.google.com/apps/2006'>\n" .
				"<apps:property name=\"password\" value=\"" . ProvisioningApi::escapeXML_Attr($this -> password) . "\"/>\n" .
				"<apps:property name=\"hashFunction\" value=\"" . ProvisioningApi::escapeXML_Attr($this -> hashFunction) . "\"/>\n" .
				"<apps:property name=\"userEmail\" value=\"" . ProvisioningApi::escapeXML_Attr($this -> userEmail) . "\"/>\n" .
				"<apps:property name=\"firstName\" value=\"" . ProvisioningApi::escapeXML_Attr($this -> firstName) . "\"/>\n" .
				"<apps:property name=\"lastName\" value=\"" . ProvisioningApi::escapeXML_Attr($this -> lastName) . "\"/>\n" .
				"<apps:property name=\"isAdmin\" value=\"" . ProvisioningApi::escapeXML_Attr($this -> isAdmin) . "\"/>\n" .
				"<apps:property name=\"isSuspended\" value=\"" . ProvisioningApi::escapeXML_Attr($this -> isSuspended) . "\"/>\n" .
				"</atom:entry>\n";
	}

	/**
	 * Return XML to modify this account
	 * https://developers.google.com/google-apps/provisioning/#updating_a_domain_users_account
	 */
	public function modifyXML() {
		$str = "<atom:entry xmlns:atom='http://www.w3.org/2005/Atom'\n" .
				"xmlns:apps='http://schemas.google.com/apps/2006'>\n";
		foreach($this -> changes as $name => $value) {
			$str .= "<apps:property name=\"" . ProvisioningApi::escapeXML_Attr($name) . "\" value=\"".ProvisioningApi::escapeXML_Attr($value)."\"/>\n";
		}
		return $str . "</atom:entry>\n";
	}

	public function getuserEmail() {
		return $this -> userEmail;
	}

	public function setfirstName($firstName) {
		$this -> firstName = $firstName;
		$this -> changes['firstName'] = $firstName;
	}

	public function getfirstName() {
		return $this -> firstName;
	}

	public function setlastName($lastName) {
		$this -> lastName = $lastName;
		$this -> changes['lastName'] = $lastName;
	}

	public function getlastName() {
		return $this -> lastName;
	}

	public function setpassword($password) {
		$this -> password = $password;
		$this -> changes['password'] = $password;
	}

	public function getpassword() {
		return $this -> password;
	}

	public function sethashFunction($hashFunction) {
		$this -> hashFunction = $hashFunction;
		$this -> changes['hashFunction'] = $hashFunction;
	}

	public function gethashFunction() {
		return $this -> hashFunction;
	}

	public function setisAdmin($isAdmin) {
		$this -> isAdmin = (boolean)$isAdmin? 'true' : 'false';
		$this -> changes['isAdmin'] = $this -> isAdmin;
	}

	public function getisAdmin() {
		return $this -> isAdmin == 'true';
	}

	public function setisSuspended($isSuspended) {
		$this -> isSuspended = (boolean)$isSuspended? 'true' : 'false';
		$this -> changes['isSuspended'] = $this -> isSuspended;
	}

	public function getisSuspended() {
		return $this -> isSuspended;
	}

}