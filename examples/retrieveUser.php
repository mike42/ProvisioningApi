#!/usr/bin/env php
<?php
/* Example of retrieving an account */

require_once("../ProvisioningApi.php");

if(count($argv) < 4) {
	die("Usage: ". $argv[0] . " admin@example.com password joebloggs@example.com\n");
}

/* Admin */
$username = $argv[1];
$adminPassword = $argv[2];

/* User details */
$userEmail = $argv[3];

try {
	echo "Logging in ...\n";
	$prov = new ProvisioningApi($username, $adminPassword);
	$user = $prov -> retrieveUser($userEmail);
	print_r($user);

	$organizationUser = $prov -> retrieveOrganizationUser($userEmail);
	print_r($organizationUser);

	$groups = $prov -> retrieveGroupsOfMember($userEmail);
	print_r($groups);
} catch(Exception $e) {
	die("Error: " . $e -> getMessage()."\n");
}

?>
