<?php

include 'httpsocket.php';

$sock = new HTTPSocket;

$sock->connect('yoursite.com',2222);
$sock->set_login('admin','password');

$sock->query('/CMD_API_SHOW_ALL_USERS');
$result = $sock->fetch_parsed_body();

print_r($result);

?>