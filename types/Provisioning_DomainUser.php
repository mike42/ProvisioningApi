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
		$this -> isAdmin = (boolean)$isAdmin;
		$this -> isSuspended = (boolean)$isSuspended;
	}
	
	/**
	 * Return XML to create this account
	 * https://developers.google.com/google-apps/provisioning/#creating_a_user_for_a_domain
	 */
	public function createXML() {
		return "<atom:entry xmlns:atom='http://www.w3.org/2005/Atom'\n" .
			"xmlns:apps='http://schemas.google.com/apps/2006'>\n" .
			"<apps:property name=\"password\" value=\"".ProvisioningApi::escapeXML_Attr($this -> password)."\"/>\n" .
			"<apps:property name=\"hashFunction\" value=\"".ProvisioningApi::escapeXML_Attr($this -> hashFunction)."\"/>\n" .
			"<apps:property name=\"userEmail\" value=\"".ProvisioningApi::escapeXML_Attr($this -> userEmail)."\"/>\n" .
			"<apps:property name=\"firstName\" value=\"".ProvisioningApi::escapeXML_Attr($this -> firstName)."\"/>\n" .
			"<apps:property name=\"lastName\" value=\"".ProvisioningApi::escapeXML_Attr($this -> lastName)."\"/>\n" .
			"<apps:property name=\"isAdmin\" value=\"" . ($this -> isAdmin == true ? 'true' : 'false') . "\"/>\n" .
			"<apps:property name=\"isSuspended\" value=\"" . ($this -> isSuspended == true ? 'true' : 'false') . "\"/>\n" .
			"</atom:entry>";
	}
	
}