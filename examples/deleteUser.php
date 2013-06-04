#!/usr/bin/env php
<?php
/* Example of creating an account */

require_once("../ProvisioningApi.php");

if(count($argv) < 4) {
	die("Usage: ". $argv[0] . " admin@example.com password Joe Bloggs joebloggs@example.com (SHA hash of new user password)\nGenerate the password with:\n\techo -n verysecretpassword | sha1sum\n");
}

/* Admin */
$username = $argv[1];
$adminPassword = $argv[2];

/* User details */
$userEmail = $argv[3];

try {
	echo "Logging in ...\n";
	$prov = new ProvisioningApi($username, $adminPassword);
	$prov -> deleteUser($userEmail);
	echo "User deleted. Trying to retrieve it to show that it's gone..\n";
	$user = $prov -> retrieveUser($userEmail);
	print_r($user);
} catch(Exception $e) {
	die("Error: " . $e -> getMessage()."\n");
}

?>
