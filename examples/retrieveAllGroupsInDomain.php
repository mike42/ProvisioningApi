#!/usr/bin/env php
<?php
/* Example of retrieving a list of email groups in a domain */

require_once("../ProvisioningApi.php");

if(count($argv) < 4) {
	die("Usage: ". $argv[0] . " admin@example.com password example.com\n");
}

/* Admin */
$username = $argv[1];
$adminPassword = $argv[2];

/* User details */
$domain = $argv[3];

try {
	echo "Logging in ...\n";
	$prov = new ProvisioningApi($username, $adminPassword);
	$groups = $prov -> retrieveAllGroupsInDomain($domain);
	print_r($groups);
} catch(Exception $e) {
	die("Error: " . $e -> getMessage()."\n");
}

?>
