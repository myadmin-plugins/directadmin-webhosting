<?php

include 'httpsocket.php';

$sock = new HTTPSocket;
$sock->connect('yoursite.com',2222);

$sock->set_login("admin|{username}","{admin_password}");

$sock->set_method('POST');

$sock->query('/CMD_DOMAIN',
	array(
		'action' => 'create',
		'domain' => '{new_domain}',
		'ubandwidth' => 'unlimited',
		'uquota' => 'unlimited',
		'ssl' => 'ON',
		'cgi' => 'ON',
		'php' => 'ON',
		'create' => 'Create'
    ));
$result = $sock->fetch_body();

echo $result;

?>