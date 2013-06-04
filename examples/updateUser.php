#!/usr/bin/env php
<?php
/* Example of creating an account */

require_once("../ProvisioningApi.php");

if(count($argv) < 6) {
	die("Usage: ". $argv[0] . " admin@example.com password joebloggs@example.com [userEmail | firstName | lastName | password | isAdmin | isSuspended] value\nGenerate the password with:\n\techo -n verysecretpassword | sha1sum\n");
}

/* Admin */
$username = $argv[1];
$adminPassword = $argv[2];

/* User details */
$userEmail = $argv[3];
$name = $argv[4];
$value = $argv[5];

try {
	echo "Logging in ...\n";
	$prov = new ProvisioningApi($username, $adminPassword);
	$user = $prov -> retrieveUser($userEmail);

	echo "Before: \n";
	print_r($user);

	if($name == 'userEmail') {
		/* Done via 'rename' function', not update */
		$prov -> renameUser($userEmail, $value);
		$user = $prov -> retrieveUser($value); // Get new user account instead (can't set userEmail)
	} else {
		switch($name) {
			case 'firstName':
				$user -> setfirstName($value);
				break;
			case 'lastName':
				$user -> setlastName($value);
				break;
			case 'password':
				$user -> setpassword($value);
				$user -> sethashFunction("SHA-1"); // Goes with above.
				break;
				break;
			case 'isAdmin':
				$user -> setisAdmin($value == 'true');
				break;
			case 'isSuspended':
				$user -> setisSuspended($value == 'true');
				break;
			default:
				throw new Exception("Nothing to change");
		}
		$user = $prov -> updateUser($user);
	}
	echo "After: \n";
	print_r($user);
} catch(Exception $e) {
	die("Error: " . $e -> getMessage()."\n");
}

?>
