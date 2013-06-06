#!/usr/bin/env php
<?php
/* Example of deletimga group */

require_once("../ProvisioningApi.php");

if(count($argv) < 4) {
	die("Usage: ". $argv[0] . " admin@example.com password groupName@example.com\n");
}

/* Admin */
$username = $argv[1];
$adminPassword = $argv[2];

/* User details */
$groupEmail = $argv[3];

try {
	echo "Logging in ...\n";
	$prov = new ProvisioningApi($username, $adminPassword);
	$group = $prov -> deleteGroup($groupEmail);
} catch(Exception $e) {
	die("Error: " . $e -> getMessage()."\n");
}

?>
