<?php
//Sample php script for adding an API key.
//Only change values within the EDIT sections.

include 'httpsocket.php';

// *************
// EDIT start

$username = "username";
$password = "password";

$keyname = "test";
$key_value = "keyvalue";

$max_uses = 0;
$clear_key = "yes";
$allow_htm = "yes";

$never_expires = "yes";
$hour = 0;
$minute = 0;
$month = 12;
$day = 14;
$year = 2012;

$ips = "127.0.0.1\n1.2.3.4\n";

// EDIT end
// *************

$values  = array(
		'action' => 'create',
		'keyname' => $keyname,
		'key' => $key_value,
		'key2' => $key_value,		
		'hour' => $hour,
		'minute' => $minute,
		'month' => $month,
		'day' => $day,
		'year' => $year,
		'max_uses' => $max_uses,
		'ips' => $ips,
		'passwd' => $password,
		'create' => 'Create'
	);

// *************
// EDIT start

$count_allow = 0;
//add/remove values as needed.

$values["select_allow".($count_allow++)] = "CMD_LOGIN_KEYS";
$values["select_allow".($count_allow++)] = "CMD_API_LOGIN_KEYS";
//$values["select_allow".($count_allow++)] = "CMD_...";

$count_deny = 0;
//add/remove values as needed.
//$values["select_deny".($count_deny++)] = "CMD_LOGIN_KEYS";
//$values["select_deny".($count_deny++)] = "CMD_API_LOGIN_KEYS";
//$values["select_deny".($count_deny++)] = "CMD_...";

// EDIT end
// *************


//checkboxes must not exist in array if value is no.
if ($never_expires == "yes")
	$values['never_expires'] = 'yes';
if ($allow_htm == "yes")
	$values['allow_htm'] = 'yes';
if ($clear_key == "yes")
	$values['clear_key'] = 'yes';



$sock = new HTTPSocket;
$sock->connect('localhost',2222);

$sock->set_login($username, $password);

$sock->set_method('POST');

$sock->query('/CMD_LOGIN_KEYS', $values);

$result = $sock->fetch_body();

?>


