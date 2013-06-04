<?php

require_once(dirname(__FILE__) . "/types/Provisioning_DomainUser.php");
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
	 * @var string Base URL for API requests.
	 */
	private $base;

	/**
	 * Initialise with the given credentials
	 *
	 * @param string $username The username to log in with
	 * @param string $password The password to log in with
	 * @param string $token If you've got it, an old login token -- The login process can be skipped 
	 */
	function __construct($username = null, $password = null, $token = false) {
		if($username == null || $password == null) {
			throw new Exception("Username and password are required.");
		}

		$this -> username = $username;
		$this -> password = $password;
		$this -> base = "https://apps-apis.google.com/a/feeds/";
		
		if(!function_exists('curl_init')) {
			throw new Exception(__CLASS__ . " requires cURL extension to be loaded.");
		}
		$this -> ch = curl_init();
		curl_setopt($this -> ch, CURLOPT_SSL_VERIFYPEER, 0);

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
			print_r($this -> retrieveCustomerId());
		} catch(Exception $e) {
			throw new Exception("Error retrieving customerId. Is this a google apps administrator on a domain with API access enabled?\n".$e -> getMessage);
		}
	}

	/**
	 * Close resources
	 */
	function __destruct() {
		curl_close($this -> ch);
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
	public function createUser($userEmail, $firstName, $lastName, $password, $hashFunction = "SHA-1", $isAdmin = false) {
		/* Break up the email address */
		$pe = new Provisioning_Email($userEmail);
		$du = new Provisioning_DomainUser($userEmail, $firstName, $lastName, $password, $hashFunction, $isAdmin);
		$xml = $du -> createXML();
		echo $xml;
		$dom = $this -> post_xml_feed("user/2.0/".urlencode($pe -> domain), $xml);
		$properties = $this -> get_properties($dom);
		return new Provisioning_DomainUser($properties -> userEmail, $properties -> firstName, $properties -> lastName, $properties -> password, $properties -> hashFunction, $properties -> isAdmin);
	}
	
	public function retrieveUser($userEmail) {
		throw new Exception("Unimplemented");
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
		$url = $this -> base . $feed;
		curl_setopt($this -> ch, CURLOPT_URL, $this -> base.$feed);
		curl_setopt($this -> ch, CURLOPT_POST, true);
		curl_setopt($this -> ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this -> ch, CURLOPT_HTTPHEADER, array('Content-type: application/atom+xml', 'Authorization: GoogleLogin auth="'.trim($this -> token).'"'));
		curl_setopt($this -> ch, CURLOPT_POSTFIELDS, $xml);
		
		$responseTxt = curl_exec($this -> ch);
		$info = curl_getinfo($this -> ch);
		
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
		$url = $this -> base . $feed;
		curl_setopt($this -> ch, CURLOPT_URL, $url);
		curl_setopt($this -> ch, CURLOPT_POST, false);
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
}

?>
