<?php

include 'httpsocket.php';

$sock = new HTTPSocket;
$sock->connect('yoursite.com',2222);

$sock->set_login("admin|{username}","{admin_password}");

$sock->set_method('POST');

$sock->query('/CMD_API_SUBDOMAINS',
	array(
		'action' => 'create',
		'domain' => '{parent_domain}',
		'subdomain' => '{subdomain_name}'
	));
$result = $sock->fetch_body();

echo $result;

?>
