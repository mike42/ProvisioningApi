#!/usr/bin/env php
<?php
/* Example of retrieving an organizationUnit */

require_once("../ProvisioningApi.php");

if(count($argv) < 4) {
	die("Usage: ". $argv[0] . " admin@example.com password orgUnitPath\n");
}

/* Admin */
$username = $argv[1];
$adminPassword = $argv[2];

/* User details */
$orgUnitPath = $argv[3];

try {
	echo "Logging in ...\n";
	$prov = new ProvisioningApi($username, $adminPassword);
	if($orgUnitPath != "") {
		$ou = $prov -> retrieveOrganizationUnit($orgUnitPath);
		print_r($ou);
	}

	$ou_list = 	$prov -> listChildOrganizationUnits($orgUnitPath);
	echo "Sub organizations:\n";
	print_r($ou_list);

} catch(Exception $e) {
	die("Error: " . $e -> getMessage()."\n");
}

?>
