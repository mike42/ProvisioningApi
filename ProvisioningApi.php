<?php

class ProvisioningApi {
	public $token;

	private $username;
	private $password;
	private $customerId;
	private $ch; // cURL handle

	/**
	 * Initialise with the given credentials
	 *
	 * @param string $username
	 * @param string $password
	 * @param string $token
	 */
	function __construct($username = null, $password = null, $token = false) {
		if($username == null || $password == null) {
			throw new Exception("Username and password are required.");
		}

		$this -> username = $username;
		$this -> password = $password;

		if(!function_exists('curl_init')) {
			throw new Exception(__CLASS__ . " requires cURL extension to be loaded.");
		}
		$this -> ch = curl_init();

		/* If there is no token set, then figure it out */
		if(!$token) {
			$this -> login();
		} else {
			/* If a token is passed, try to use it, otherwise log in */
			try {
				$this -> token = $token;
				$this -> testLogin();
			} catch(Exception $e) {
				$this -> login();
			}
		}
	}

	function __destruct() {
		curl_close($this -> ch);
	}

	private function login() {
		/* Data to send */
		$account = array(
				'accountType' => 'GOOGLE',
				'Email' => $this -> username,
				'Passwd' => $this -> password,
				'service' => 'apps');

		/* Log in to google apps */
		curl_setopt($this -> ch, CURLOPT_URL, "https://www.google.com/accounts/ClientLogin");
		curl_setopt($this -> ch, CURLOPT_SSL_VERIFYPEER, 0);
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

	private function testLogin() {

	}

	//private function
}

?>
