<?php

include 'httpsocket.php';

$sock = new HTTPSocket;

$sock->connect('yoursite.com',2222);
$sock->set_login('admin','password');

$show_user='fred';

$sock->query('/CMD_API_SHOW_USER_CONFIG?user='.$show_user);
$result = $sock->fetch_parsed_body();

print_r($result);

?>
