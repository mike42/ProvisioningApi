#!/usr/bin/env php
<?php
/* Example of creating an account */

require_once("../ProvisioningApi.php");

if(count($argv) < 6) {
	die("Usage: ". $argv[0] . " admin@example.com password orgUnitPath [name | description | parentOrgUnitPath | blockInheritance] value\n");
}

/* Admin */
$username = $argv[1];
$adminPassword = $argv[2];

/* User details */
$orgUnitPath = $argv[3];
$field = $argv[4];
$value = $argv[5];

try {
	echo "Logging in ...\n";
	$prov = new ProvisioningApi($username, $adminPassword);
	$ou = $prov -> retrieveOrganizationUnit($orgUnitPath);
	echo "Before:\n";
	print_r($ou);

	switch($field) {
		case "name":
			$ou -> setname($value);
			break;
		case "description":
			$ou -> setdescription($value);
			break;
		case "parentOrgUnitPath";
			$ou -> setparentOrgUnitPath($value);
			break;
		case "blockInheritance":
			$ou -> setblockInheritance($value == 'true');
			break;
		default:
			throw new Exception("Nothing to update");
	}

	$prov -> updateOrganizationUnit($ou);
	echo "After:\n";
	print_r($ou);
} catch(Exception $e) {
	die("Error: " . $e -> getMessage()."\n");
}

?>
