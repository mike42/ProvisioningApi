#!/usr/bin/env php
<?php
/* Example of creating a group */

require_once("../ProvisioningApi.php");

if(count($argv) < 5) {
	die("Usage: ". $argv[0] . " admin@example.com password group@domain.example \"group name\" [description] [emailPermission] [permissionPreset]\n\tDefault emailPermission is \"Domain\", default permissionPreset is \"TeamDomain\"\n" );
}

/* Admin */
$username = $argv[1];
$adminPassword = $argv[2];

/* Group details */
$groupId = $argv[3];
$groupName = $argv[4];
$description = isset($argv[5]) ? $argv[5] : "";
$emailPermission = isset($argv[6]) ? $argv[6] : 'Domain';
$permissionPreset = isset($argv[7]) ? $argv[7] : 'TeamDomain';

try {
	echo "Logging in ...\n";
	$prov = new ProvisioningApi($username, $adminPassword);
	$group = $prov -> createGroup($groupId, $groupName, $description, $emailPermission, $permissionPreset);
	print_r($group);
} catch(Exception $e) {
	die("Error: " . $e -> getMessage()."\n");
}

?>
