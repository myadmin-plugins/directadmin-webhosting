<?php

include 'httpsocket.php';

$sock = new HTTPSocket;

$sock->connect('yoursite.com',2222);
$sock->set_login('admin','{admin_password}');

$sock->query('/CMD_SELECT_USERS',
	array(
		'location' => 'CMD_SELECT_USERS',
		'suspend' => 'Suspend', // note - this can also be 'Unsuspend'
		'select0' => '{user}'
    ));
$result = $sock->fetch_body();

echo $result;

?>