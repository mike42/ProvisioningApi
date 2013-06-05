#!/usr/bin/env php
<?php
/* Example of creating an organizationUnit */

require_once("../ProvisioningApi.php");

if(count($argv) < 7) {
	die("Usage: ". $argv[0] . " admin@example.com password name description parentOrgUnitPath blockInheritance\n");
}

/* Admin */
$username = $argv[1];
$adminPassword = $argv[2];

/* User details */
$name = $argv[3];
$description = $argv[4];
$parentOrgUnitPath = $argv[5];
$blockInheritance = $argv[6] == 'true';

try {
	echo "Logging in ...\n";
	$prov = new ProvisioningApi($username, $adminPassword);
	$ou = $prov -> createOrganizationUnit($name, $description, $parentOrgUnitPath, $blockInheritance);
	print_r($ou);
} catch(Exception $e) {
	die("Error: " . $e -> getMessage()."\n");
}

?>
