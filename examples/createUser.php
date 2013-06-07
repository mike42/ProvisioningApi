#!/usr/bin/env php
<?php
/* Example of creating an account */

require_once("../ProvisioningApi.php");

if(count($argv) < 7) {
	die("Usage: ". $argv[0] . " admin@example.com password \"Joe Bloggs\" joebloggs@example.com (SHA hash of new user password)\nGenerate the password with:\n\techo -n verysecretpassword | sha1sum\n");
}

/* Admin */
$username = $argv[1];
$adminPassword = $argv[2];

/* User details */
$firstName = $argv[3];
$lastName = $argv[4];
$userEmail = $argv[5];
$password = $argv[6];

try {
	echo "Logging in ...\n";
	$prov = new ProvisioningApi($username, $adminPassword);
	$user = $prov -> createUser($userEmail, $firstName, $lastName, $password);
	print_r($user);
} catch(Exception $e) {
	die("Error: " . $e -> getMessage()."\n");
}

?>
