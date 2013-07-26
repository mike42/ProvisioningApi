#!/usr/bin/env php
<?php
/* Example of moving an account to a different orgUnit*/

require_once("../ProvisioningApi.php");

if(count($argv) < 5) {
	die("Usage: ". $argv[0] . " admin@example.com password joebloggs@example.com path/to/orgunit\n");
}

/* Admin */
$username = $argv[1];
$adminPassword = $argv[2];

/* User details */
$userEmail = $argv[3];
$orgUnitPath = $argv[4];

try {
	echo "Logging in ...\n";
	$prov = new ProvisioningApi($username, $adminPassword);
	$orgUser = $prov -> retrieveOrganizationUser($userEmail);
	echo "Before:\n";
	print_r($orgUser);

	$orgUser -> setorgUnitPath($orgUnitPath);
	$prov -> updateOrganizationUser($orgUser);
	
	
	echo "After:\n";
	print_r($orgUser);
} catch(Exception $e) {
	die("Error: " . $e -> getMessage()."\n");
}

?>
