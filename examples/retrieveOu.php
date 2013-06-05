#!/usr/bin/env php
<?php
/* Example of creating an account */

require_once("../ProvisioningApi.php");

if(count($argv) < 4) {
	die("Usage: ". $argv[0] . " orgUnitPath\n");
}

/* Admin */
$username = $argv[1];
$adminPassword = $argv[2];

/* User details */
$orgUnitPath = $argv[3];

try {
	echo "Logging in ...\n";
	$prov = new ProvisioningApi($username, $adminPassword);
	$ou = $prov -> retrieveOrganizationUnit($orgUnitPath);
	print_r($ou);
} catch(Exception $e) {
	die("Error: " . $e -> getMessage()."\n");
}

?>
