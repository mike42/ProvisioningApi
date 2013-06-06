#!/usr/bin/env php
<?php
/* Example of adding a user/group to a group */

require_once("../ProvisioningApi.php");

if(count($argv) < 5) {
	die("Usage: ". $argv[0] . " admin@example.com password memberName@example.com group@example.com\n");
}

/* Admin */
$username = $argv[1];
$adminPassword = $argv[2];

/* User details */
$memberEmail = $argv[3];
$groupEmail = $argv[4];

try {
	echo "Logging in ...\n";
	$prov = new ProvisioningApi($username, $adminPassword);
	$prov -> addMemberToGroup($memberEmail, $groupEmail);
} catch(Exception $e) {
	die("Error: " . $e -> getMessage()."\n");
}

?>
