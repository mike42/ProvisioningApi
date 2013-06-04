#!/usr/bin/env php
<?php
/* Example of logging in (via ClientLogin) to the provisioning API */

require_once("../ProvisioningApi.php");

if(count($argv) < 3) {
	die("Usage: ". $argv[0] . " username@example.com password\n");
}
$username = $argv[1];
$password = $argv[2];

try {
	echo "Logging in ...\n";
	$prov = new ProvisioningApi($username, $password);
	echo "Login token is " . $prov -> token;
	
} catch(Exception $e) {
	die("Error: " . $e -> getMessage()."\n");
}

?>