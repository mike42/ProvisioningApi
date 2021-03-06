<?php
require_once(dirname(__FILE__) . "/types/Provisioning_DomainUser.php");
require_once(dirname(__FILE__) . "/types/Provisioning_OrganizationUnit.php");
require_once(dirname(__FILE__) . "/types/Provisioning_OrganizationUser.php");
require_once(dirname(__FILE__) . "/types/Provisioning_Group.php");
require_once(dirname(__FILE__) . "/types/Provisioning_GroupMember.php");
require_once(dirname(__FILE__) . "/types/Provisioning_Email.php");

/**
 * PHP client for the Google Apps Provisioning API: https://developers.google.com/google-apps/provisioning/
 * 
 * @author Michael Billington <michael.billington@gmail.com>
 */
class ProvisioningApi {
	/**
	 * @var string ClientLogin token, sent with each request.
	 */
	public $token;

	/**
	 * @var string Username of admin account for logging in
	 */
	private $username;
	
	
	/**
	 * @var string Admin password
	 */
	private $password;
	
	/**
	 * @var string customerId, as set by retrieveCustomerId()
	 */
	private $customerId;
	
	/**
	 * @var int cURL handle for requests.
	 */
	private $ch;

	/**
	 * @var ssl_verifypeer set to true to verify SSL certificates (the default)
     */
	private $ssl_verifypeer;
	
	/**
	 * @var string Base URL for API requests.
	 */
	const base ="https://apps-apis.google.com/a/feeds/";

	/**
	 * Initialise with the given credentials
	 *
	 * @param string $username The username to log in with
	 * @param string $password The password to log in with
	 * @param string $token If you've got it, an old login token -- The login process can be skipped 
	 */
	function __construct($username = null, $password = null, $token = false, $ssl_verifypeer = true) {
		if($username == null || $password == null) {
			throw new Exception("Username and password are required.");
		}

		$this -> username = $username;
		$this -> password = $password;
		$this -> ssl_verifypeer = $ssl_verifypeer;
		
		if(!function_exists('curl_init')) {
			throw new Exception(__CLASS__ . " requires cURL extension to be loaded.");
		}
		
		$this -> ch_init();
		
		/* If there is no token set, then figure it out */
		if(!$token) {
			$this -> login();
		} else {
			/* If a token is passed, try to use it, otherwise log in */
			try {
				$this -> token = $token;
				try {
					$this -> retrieveCustomerId();
				} catch(Exception $e) {
					/* Failed. Log in now, token could be rubbish */
					$this -> login();
				}
			} catch(Exception $e) {
				$this -> login();
			}
		}

		/* This data is needed for Ou stuff, but it is mainly to verify the login has API access */
		try {
			$this -> retrieveCustomerId();
		} catch(Exception $e) {
			throw new Exception("Error retrieving customerId. Is this a google apps administrator on a domain with API access enabled?\n".$e -> getMessage());
		}
	}

	/**
	 * Close resources
	 */
	function __destruct() {
		$this -> ch_close();
	}
	
	
	/**
	 * Init cURL handle
	 */
	private function ch_init() {
		$this -> ch = curl_init();

		if(!$this -> ssl_verifypeer) {
			/* Throw security out the window if this option is enabled*/
			curl_setopt($this -> ch, CURLOPT_SSL_VERIFYPEER, 0);
		}
	}

	/**
	 * Close cURL handle
	 */
	private function ch_close() {
		curl_close($this -> ch);
	}

	/**
	 * Make a new cURL handle, to be sure all options are reset correctly
	 */
	private function ch_reinit() {
		$this -> ch_close();
		$this -> ch_init();
	}
	
	/**
	 * Create a user for a domain
	 * https://developers.google.com/google-apps/provisioning/#creating_a_user_for_a_domain
	 * 
	 * @param string $userEmail
	 * @param string $firstName
	 * @param string $lastName
	 * @param string $password
	 * @param string $hashFunction
	 * @param string $isAdmin
	 */
	public function createUser($userEmail, $firstName, $lastName, $password, $hashFunction = "SHA-1", $isAdmin = false, $isSuspended = false) {
		/* Break up the email address */
		$pe = new Provisioning_Email($userEmail);
		
		/* Figure out XML user creation code */
		$du = new Provisioning_DomainUser($userEmail, $firstName, $lastName, $password, $hashFunction, $isAdmin, $isSuspended);
		$xml = $du -> createXML();
		
		/* Create user and return user details */
		$dom = $this -> post_xml_feed("user/2.0/".urlencode($pe -> domain), $xml);
		$properties = $this -> get_properties($dom);
		return new Provisioning_DomainUser($properties -> userEmail, $properties -> firstName, $properties -> lastName, $properties -> password, $properties -> hashFunction, $properties -> isAdmin == 'true', $properties -> isSuspended == 'true');
	}
	
	/**
	 * Retrieve a single user account
	 * https://developers.google.com/google-apps/provisioning/#retrieving_users
	 * 
	 * @param string $userEmail
	 * @throws Exception
	 */
	public function retrieveUser($userEmail) {
		$pe = new Provisioning_Email($userEmail);
		$dom = $this -> get_xml_feed("user/2.0/".urlencode($pe -> domain)."/".urlencode($pe -> address));
		$properties = $this -> get_properties($dom);
		return new Provisioning_DomainUser($properties -> userEmail, $properties -> firstName, $properties -> lastName, "", "", $properties -> isAdmin == 'true', $properties -> isSuspended == 'true');
	}
	
	/**
	 * Rename this user account to another address
	 * https://developers.google.com/google-apps/provisioning/#renaming_a_users_account
	 * 
	 * @param string $userEmail
	 * @param string $newEmail
	 * @return boolean True on success
	 */
	public function renameUser($userEmail, $newEmail) {
		$pe = new Provisioning_Email($userEmail);
		$xml = "<atom:entry xmlns:atom='http://www.w3.org/2005/Atom'\n" .
				"xmlns:apps='http://schemas.google.com/apps/2006'>\n" .
				"<apps:property name=\"newEmail\" value=\"" . self::escapeXML_Attr($newEmail) . "\" />\n" .
				"</atom:entry>\n";
		$dom = $this -> put_xml_feed("user/userEmail/2.0/".urlencode($pe -> domain)."/".urlencode($pe -> address), $xml);
		return true;
	}
	
	/**
	 * Delete a user account from the domain
	 * https://developers.google.com/google-apps/provisioning/#deleting_a_user_from_a_domain
	 * 
	 * @param string $userEmail
	 */
	public function deleteUser($userEmail) {
		$pe = new Provisioning_Email($userEmail);
		$this -> delete_feed("user/2.0/".urlencode($pe -> domain)."/".urlencode($pe -> address));
		return true;
	}

	/**
	 * Commit changes to a Provisioning_DomainUser object
	 * https://developers.google.com/google-apps/provisioning/#updating_a_domain_users_account
	 * 
	 * @param Provisioning_DomainUser $user
	 * @return Provisioning_DomainUser
	 */
	public function updateUser(Provisioning_DomainUser $user) {
		/* Break up the email address */
		$pe = new Provisioning_Email($user -> getuserEmail());
	
		/* Figure out XML user creation code */
		$xml = $user -> modifyXML();
	
		/* Modify and return user details */
		$dom = $this -> put_xml_feed("user/2.0/".urlencode($pe -> domain)."/".urlencode($pe -> address), $xml);
		return $user; // Easier than looking for changes in the result
	}
	
	/**
	 * Retrieve the customer ID - needed for organizationalUnit operations
	 * https://developers.google.com/google-apps/provisioning/#retrieving_a_customerid
	 * 
	 * @return stdClass
	 */
	public function retrieveCustomerId() {
		$dom = $this -> get_xml_feed("customer/2.0/customerId");
		$properties = $this -> get_properties($dom);
		$this -> customerId = $properties -> customerId;
		return $properties;
	}

	/**
	 * Create an organziation unit
	 * https://developers.google.com/google-apps/provisioning/#creating_an_organization_unit
	 * 
	 * @param string $name
	 * @param string $description
	 * @param string $parentOrgUnitPath
	 * @param string $blockInheritance
	 */
	public function createOrganizationUnit($name, $description, $parentOrgUnitPath, $blockInheritance = false) {
		$ou = new Provisioning_OrganizationUnit($name, $description, null, $parentOrgUnitPath, $blockInheritance = false);
		$xml = $ou -> createXML($this -> customerId);
		$dom = $this -> post_xml_feed("orgunit/2.0/".urlencode($this -> customerId), $xml);
		return $ou; // Return data misses some fields
	}
	
	/**
	 * Retrieve an organization unit
	 * https://developers.google.com/google-apps/provisioning/#retrieving_organization_units
	 * 
	 * @param string $orgUnitPath
	 * @return Provisioning_OrganizationUnit
	 */
	public function retrieveOrganizationUnit($orgUnitPath) {
		$dom = $this -> get_xml_feed("orgunit/2.0/".urlencode($this -> customerId) . "/" . $orgUnitPath);
		$properties = $this -> get_properties($dom);
		return new Provisioning_OrganizationUnit($properties -> name, $properties -> description, $properties -> orgUnitPath, $properties -> parentOrgUnitPath, $properties -> blockInheritance == 'true');
	}
	
	/**
	 * Update an organization unit
	 * https://developers.google.com/google-apps/provisioning/#updating_an_organization_unit
	 *
	 * @param Provisioning_OrganizationUnit $ou
	 * @return Provisioning_OrganizationUnit
	 */
	public function updateOrganizationUnit(Provisioning_OrganizationUnit $ou) {
		$xml = $ou -> modifyXML($this -> customerId);
		$dom = $this -> put_xml_feed("orgunit/2.0/".urlencode($this -> customerId) . "/" . $ou -> getorgUnitPath(), $xml);
		return $ou;
	}
	
	/**
	 * Delete an organizationUnit
	 *
	 * @param string $orgUnitPath
	 * @return boolean
	 */
	public function deleteOrganizationUnit($orgUnitPath) {
		$this -> delete_feed("orgunit/2.0/".urlencode($this -> customerId) . "/" . $orgUnitPath);
		return true;
	}
	
	/**
	 * Retrieve An Organization Unit's Immediate Sub-Organizations
	 * https://developers.google.com/google-apps/provisioning/#retrieving_organization_units
	 * 
	 * @param strign $orgUnitPath
	 */
	public function listChildOrganizationUnits($orgUnitPath) {
		$entries = $this -> get_xml_feed_entries_paginated("orgunit/2.0/".urlencode($this -> customerId) . "?get=children&orgUnitPath=" . urlencode($orgUnitPath));
		$ret = array();
		foreach($entries as $properties) {
			$ret[] = new Provisioning_OrganizationUnit($properties -> name, $properties -> description, $properties -> orgUnitPath, $properties -> parentOrgUnitPath, $properties -> blockInheritance == 'true');
		}
		return $ret;
	}	
	
	/**
	 * Get a single organizationUser by email. Used to find out which organizationUnit they are in,
	 * all other data should be fetched with retrieveUser().
	 * https://developers.google.com/google-apps/provisioning/#retrieving_organization_users
	 * 
	 * @param string $userEmail
	 */
	public function retrieveOrganizationUser($userEmail) {
		$dom = $this -> get_xml_feed("orguser/2.0/".urlencode($this -> customerId) . "/" . urlencode($userEmail));
		$properties = $this -> get_properties($dom);
		return new Provisioning_OrganizationUser($properties -> orgUserEmail, $properties -> orgUnitPath);
	}
	
	/**
	 * Get a list of accounts in an orgUnit.
	 * Strangely called "Retrieving An Organization Unit's Immediate Sibling Users" in the API.
	 * https://developers.google.com/google-apps/provisioning/#retrieving_organization_users
	 * 
	 * @param string $orgUnitPath
	 */
	public function listChildOrganizationUsers($orgUnitPath) {
		$entries = $this -> get_xml_feed_entries_paginated("orguser/2.0/".urlencode($this -> customerId) . "?get=children&orgUnitPath=" . urlencode($orgUnitPath));
		$ret = array();
		foreach($entries as $properties) {
			$ret[] = new Provisioning_OrganizationUser($properties -> orgUserEmail, $properties -> orgUnitPath);
		}
		return $ret;
	}
	
	/**
	 * Retrieve a group
	 * https://developers.google.com/google-apps/provisioning/#retrieving_a_group
	 *  
	 * @param string $groupEmail
	 * @return Provisioning_Group
	 */
	public function retrieveGroup($groupEmail) {
		$pe = new Provisioning_Email($groupEmail);
		$dom = $this -> get_xml_feed("group/2.0/".urlencode($pe -> domain) . "/" . urlencode($groupEmail));
		$properties = $this -> get_properties($dom);
		
		/* Filling with defaults if not sent along */
		if(!property_exists($properties, 'emailPermission')) {
			$properties -> emailPermission = "Domain";
		}
		if(!property_exists($properties, 'permissionPreset')) {
			$properties -> permissionPreset = "TeamDomain";
		}
		return new Provisioning_Group($properties -> groupId, $properties -> groupName, $properties -> description, $properties -> emailPermission, $properties -> permissionPreset);
	}
	
	/**
	 * Update a group's details
	 * https://developers.google.com/google-apps/provisioning/#updating_a_group
	 * 
	 * @param Provisioning_Group $group
	 * @throws Exception
	 * @return Provisioning_Group
	 */
	public function updateGroup(Provisioning_Group $group) {
		$pe = new Provisioning_Email($group -> getgroupId());
		$xml = $group -> modifyXML();
		$dom = $this -> put_xml_feed("group/2.0/".urlencode($pe -> domain) . "/" . urlencode($pe -> address), $xml);
		return $group;
	}

	/**
	 * Create a new group
	 * https://developers.google.com/google-apps/provisioning/#creating_a_group
	 * 
	 * @param string $groupId
	 * @param string $groupName
	 * @param string $description
	 * @param string $emailPermission
	 * @param string $permissionPreset
	 * @return Provisioing_Group
	 */
	public function createGroup($groupEmail, $groupName, $description = "", $emailPermission = "Domain", $permissionPreset = "TeamDomain") {
		$pe = new Provisioning_Email($groupEmail);
		$group = new Provisioning_Group($groupEmail, $groupName, $description, $emailPermission, $permissionPreset);
		$xml = $group -> createXML();
		$dom = $this -> post_xml_feed("group/2.0/".urlencode($pe -> domain), $xml);
		return $group;
	}
	
	/**
	 * Delete a group by email address
	 * https://developers.google.com/google-apps/provisioning/#deleting_a_group
	 * 
	 * @param string $groupEmail
	 * @return boolean
	 */
	public function deleteGroup($groupEmail) {
		$pe = new Provisioning_Email($groupEmail);
		$this -> delete_feed("group/2.0/".urlencode($pe -> domain) . "/" . urlencode($groupEmail));
		return true;
	}
	
	/**
	 * Retrieve all members of a group
	 * https://developers.google.com/google-apps/provisioning/#retrieving_all_members_of_a_group
	 * 
	 * @param string $groupEmail

	 */
	public function retrieveMembersOfGroup($groupEmail, $includeSuspendedUsers = false) {
		$pe = new Provisioning_Email($groupEmail);
		$entries = $this -> get_xml_feed_entries_paginated("group/2.0/".urlencode($pe -> domain) . "/" . urlencode($groupEmail) . "/member?includeSuspendedUsers=".($includeSuspendedUsers ? 'true' : 'false'));
		$ret = array();
		foreach($entries as $properties) {
			$ret[] = new Provisioning_GroupMember($properties -> memberId, $properties -> memberType, $properties -> directMember == 'true');
		}
		return $ret;
	}
	
	/**
	 * Retrieve a list of groups that a user is in.
	 * https://developers.google.com/google-apps/provisioning/#retrieving_all_groups_for_a_member
	 * 
	 * @param string $memberId
	 * @param boolean $directOnly
	 * @return multitype:Provisioning_Group 
	 */
	public function retrieveGroupsOfMember($memberId, $directOnly = true) {
		$pe = new Provisioning_Email($memberId);
		$entries = $this -> get_xml_feed_entries_paginated("group/2.0/" . urlencode($pe -> domain) . "/?member=" . urlencode($pe -> address) . "&directOnly=" . ($directOnly ? 'true' : 'false'));
		
		/* Convert to array of groups */
		$ret = array();
		foreach($entries as $properties) {
			$ret[] = new Provisioning_Group($properties -> groupId, $properties -> groupName, $properties -> description, (isset($properties -> emailPermission) ? $properties -> emailPermission : null), $properties -> permissionPreset);
		}
		return $ret;
	}
	
	/**
	 * Retrieve all groups in a domain -- This actually seems to retrieve every group.
	 * Uses the domain-part of the admin user's account if none is set.
	 * https://developers.google.com/google-apps/provisioning/#retrieving_all_groups_in_a_domain
	 * 
	 * @param string $domain
	 * @return multitype:Provisioning_Group 
	 */
	public function retrieveAllGroupsInDomain($domain = null) {
		if($domain == null) {
			/* No domain set, use the admin's demain */
			$pe = new Provisioning_Email($this -> username);
			$domain = $pe -> domain;
		}
		
		$entries = $this -> get_xml_feed_entries_paginated("group/2.0/" . urlencode($domain));
		$ret = array();
		foreach($entries as $properties) {
			$ret[] = new Provisioning_Group($properties -> groupId, $properties -> groupName, $properties -> description, (isset($properties -> emailPermission) ? $properties -> emailPermission : null), $properties -> permissionPreset);
		}
		return $ret;
	}
	
	/**
	 * Add a member to a group
	 * https://developers.google.com/google-apps/provisioning/#adding_a_member_to_a_group
	 * 
	 * @param string $userEmail
	 * @param string $groupEmail
	 */
	public function addMemberToGroup($memberEmail, $groupEmail) {
		$pe = new Provisioning_Email($groupEmail);
		$member = new Provisioning_GroupMember($memberEmail);
		$xml = $member -> createXML($groupEmail);		
		$dom = $this -> post_xml_feed("group/2.0/".urlencode($pe -> domain) . "/" . urlencode($groupEmail) . "/member", $xml);
		// Result of POST is not really useful (just has the input echoed back).
		return true;
	}
	
	/**
	 * Delete a member from a group
	 * https://developers.google.com/google-apps/provisioning/#deleting_a_member_from_an_group
	 * 
	 * @param string $memberEmail
	 * @param string $groupEmail
	 * @return boolean
	 */
	public function removeMemberFromGroup($memberEmail, $groupEmail) {
		$pe = new Provisioning_Email($groupEmail);
		$dom = $this -> delete_feed("group/2.0/".urlencode($pe -> domain) . "/" . urlencode($groupEmail) . "/member/" . urlencode($memberEmail));
		return true;
	}
	
	/**
	 * Update an OrganizationUser
	 * https://developers.google.com/google-apps/provisioning/#updating_an_organization_user
	 * 
	 * @param Provisioning_OrganizationUser $orgUser
	 * @return Provisioning_OrganizationUser
	 */
	public function updateOrganizationUser(Provisioning_OrganizationUser $orgUser) {
		$xml = $orgUser -> modifyXML();
		$dom = $this -> put_xml_feed("orguser/2.0/".urlencode($this -> customerId) . "/" . $orgUser -> getorgUserEmail(), $xml);
		return $orgUser;
	}

	/**
	 * Perform a ClientLogin with the values given.
	 *
	 * @throws Exception
	 * @return boolean true on success
	 */
	private function login() {
		/* Data to send */
		$account = array(
				'accountType' => 'GOOGLE',
				'Email' => $this -> username,
				'Passwd' => $this -> password,
				'service' => 'apps');
	
		/* Log in to google apps */
		curl_setopt($this -> ch, CURLOPT_URL, "https://www.google.com/accounts/ClientLogin");
		curl_setopt($this -> ch, CURLOPT_POST, true);
		curl_setopt($this -> ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this -> ch, CURLOPT_POSTFIELDS, $account);
		$responseTxt = curl_exec($this -> ch);
		$info = curl_getinfo($this -> ch);
		curl_setopt($this -> ch, CURLOPT_POST, false); // Reset
	
		/* Parse response */
		$responseLines = split('=', $responseTxt);
		$lastkey = false;
	
		for($i = 0; $i < count($responseLines); $i++) {
			$line = $responseLines[$i];
			if($i == 0) {
				/* First line is just a key */
				$lastkey = $line;
			} else {
				$vals = split("\n", $line);
				if($i != count($responseLines) - 1) {
					/* Split into value and next key */
					$nextkey = $vals[count($vals) - 1];
					unset($vals[count($vals) - 1]);
				} else {
					$nextkey = false;
				}
				$response[$lastkey] = implode("\n", $vals);
				$lastkey = $nextkey;
			}
		}
	
		/* Figure out what we're looking at */
		if(isset($response['Error'])) {
			throw new Exception("Google login returned ".$response['Error']);
		}
	
		if($info['http_code'] != '200') {
			throw new Exception("Login returned  HTTP ".$info['http_code']);
		}
	
		if(isset($response['Auth'])) {
			$this -> token = $response['Auth'];
			return true;
		}
	
		throw new Exception("No login token received");
	}
	
	/**
	 * POST XML to a feed, and get the result back.
	 * 
	 * @param string $feed Feed name
	 * @param string $xml Block of XML to post
	 * @throws Exception If an error is encountered
	 * @return DOMElement representing return value
	 */
	private function post_xml_feed($feed, $xml) {
		$url = self::base . $feed;
		curl_setopt($this -> ch, CURLOPT_URL, $url);
		curl_setopt($this -> ch, CURLOPT_POST, true);
		curl_setopt($this -> ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this -> ch, CURLOPT_HTTPHEADER, array('Content-type: application/atom+xml', 'Authorization: GoogleLogin auth="'.trim($this -> token).'"'));
		curl_setopt($this -> ch, CURLOPT_POSTFIELDS, $xml);
		
		$responseTxt = curl_exec($this -> ch);
		$info = curl_getinfo($this -> ch);

		$this -> ch_reinit(); // Reset everything

		switch($info['http_code']) {
			case '200':
			case '201':
				return $this -> process_feed($responseTxt);
			default:
				throw new Exception("HTTP ".$info['http_code']." posting to ". $url);
		}
	}
	
	/**
	 * @param unknown_type $feed
	 * @throws Exception
	 * @return DOMElement
	 */
	private function get_xml_feed($feed) {
		$url = self::base . $feed;
		curl_setopt($this -> ch, CURLOPT_URL, $url);
		curl_setopt($this -> ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this -> ch, CURLOPT_HTTPHEADER, array('Authorization: GoogleLogin auth="'.trim($this -> token).'"'));
		
		$responseTxt = curl_exec($this -> ch);
		$info = curl_getinfo($this -> ch);
		
		switch($info['http_code']) {
			case '200':
				return $this -> process_feed($responseTxt);
			default:
				throw new Exception("HTTP ".$info['http_code']." getting from ". $url);
		}
	}
	
	/**
	 * Load a feed, grab a list of entries, load the next page, and so on. Return array of objects representing properties of the entries
	 * 
	 * @param string $feed
	 */
	private function get_xml_feed_entries_paginated($feed) {
		$ret = array();
		while($feed != null) {
			$dom = $this -> get_xml_feed($feed);
			$nodelist = $dom -> getElementsByTagName('entry');
			for($i = 0; $i < $nodelist -> length; $i++) {
				$item = $nodelist -> item($i);
				$ret[] = $this -> get_properties($item);
			}
			
			/* Get next page */
			$link = $dom -> getElementsByTagName('link');
			$feed = null;
			for($i = 0; $i < $nodelist -> length; $i++) {
				$item = $link -> item($i);
				if($item -> attributes -> getNamedItem("rel") -> value == "next") {
					$feed = $item -> attributes -> getNamedItem("href") -> value;
					/* Chop off base */
					$feed = substr($feed, strlen(self::base), strlen($feed) - strlen(self::base));
				}
			}
		}
		
		return $ret;
	}
	
	/**
	 * Issue a PUT request with the given XML to the feed.
	 * 
	 * @param string $feed
	 * @param string $xml
	 * @throws Exception
	 * @return DOMElement
	 */
	private function put_xml_feed($feed, $xml) {
		$url = self::base . $feed;
		curl_setopt($this -> ch, CURLOPT_URL, $url);
		curl_setopt($this -> ch, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($this -> ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this -> ch, CURLOPT_HTTPHEADER, array('Content-type: application/atom+xml', 'Authorization: GoogleLogin auth="'.trim($this -> token).'"'));
		curl_setopt($this -> ch, CURLOPT_POSTFIELDS, $xml);
	
		$responseTxt = curl_exec($this -> ch);
		$info = curl_getinfo($this -> ch);
		$this -> ch_reinit(); // Reset everything
	
		switch($info['http_code']) {
			case '200':
			case '201':
				return $this -> process_feed($responseTxt);
			default:
				throw new Exception("HTTP ".$info['http_code']." putting to ". $url);
		}
	}
	
	/**
	 * Issue a DELETE request to the given feed.
	 * 
	 * @param unknown_type $feed
	 * @throws Exception
	 * @return boolean
	 */
	private function delete_feed($feed) {
		$url = self::base . $feed;
		curl_setopt($this -> ch, CURLOPT_URL, $url);
		curl_setopt($this -> ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($this -> ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this -> ch, CURLOPT_HTTPHEADER, array('Authorization: GoogleLogin auth="'.trim($this -> token).'"'));
		
		$responseTxt = curl_exec($this -> ch);
		$info = curl_getinfo($this -> ch);
		$this -> ch_reinit(); // Reset everything

		switch($info['http_code']) {
			case '200':
				return true;
			default:
				throw new Exception("HTTP ".$info['http_code']." deleting ". $url);
		}
	}
	
	/**
	 * Turn the text-response of the feed into a DOMElement.
	 * 
	 * @param string $text
	 * @return DOMElement
	 */
	private function process_feed($text) {
		$xml = simplexml_load_string($text);
		$dom = dom_import_simplexml($xml);
		return $dom;
	}
	
	/**
	 * Returns an object from the properties in a DOMElement properties:
	 * 
	 * 	Input:
	 * 		<property name="name" value="Sales" />
	 *		<property name="description" value="Sales Dept" />
	 * 
	 * Output:
	 *  	stdClass Object
	 *		(
	 *  		[name] => Sales,
	 *			[description] => Sales Dept
	 *
	 * @param DOMElement $dom
	 * @return stdClass
	 */
	private function get_properties(DOMElement $dom) {
		$nodelist = $dom -> getElementsByTagName('property');
		$properties = new stdClass();
		for($i = 0; $i < $nodelist -> length; $i++) {
			$item = $nodelist -> item($i);
			if($item -> localName == 'property') {
				$name = $item -> attributes -> getNamedItem("name") -> value;
				$value = $item -> attributes -> getNamedItem("value") -> value;
				$properties -> $name = $value;
			}
		}
		
		return $properties;
	}
	
	/**
	 * Escape XML attribute
	 * 
	 * @param string $value
	 */
	public static function escapeXML_Attr($value) {
		return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
	}
	
	/**
	 * Escape XML element content
	 *
	 * @param string $value
	 */
	public static function escapeXML_ElementContent($value) {
		return self::escapeXML_Attr($value);
	}
	
	/**
	 * Construct a URL-encoded orgUnitPath from a list of orgUnits.
	 * 
	 * @param array $units list of organizationUnit names
	 * @return string representing the organizationUnit path
	 */
	public static function constructOrgUnitPath(array $units) {
		foreach($units as $key => $unit) {
			$units[$key] = urlencode($unit);
		}
		return implode("/" ,$units);
	}
}
?>
