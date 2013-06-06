#!/usr/bin/env php
<?php
/* Example of updating a group's properties */

require_once("../ProvisioningApi.php");

if(count($argv) < 6) {
	die("Usage: ". $argv[0] . " admin@example.com password group@example.com [groupId | groupName | description | emailPermission | permissionPreset] value\n");
}

/* Admin */
$username = $argv[1];
$adminPassword = $argv[2];

/* User details */
$groupEmail = $argv[3];
$name = $argv[4];
$value = $argv[5];

try {
	echo "Logging in ...\n";
	$prov = new ProvisioningApi($username, $adminPassword);
	$group = $prov -> retrieveGroup($groupEmail);
	echo "Before:\n";
	print_r($group);

	switch($name) {
		case "groupName":
			$group -> setgroupName($value);
			break;
		case "description":
			$group -> setdescription($value);
			break;
		case "emailPermission":
			$group -> setemailPermission($value);
			break;
		case "permissionPreset":
			$group -> setpermissionPreset($value);
			break;
	default:
		throw new Exception("Nothing to change");
	}				
	$prov -> updateGroup($group);	
	echo "After:\n";
	print_r($group);
} catch(Exception $e) {
	die("Error: " . $e -> getMessage()."\n");
}

?>
