<?php

use \Detain\MyAdminDirectAdminWeb\HTTPSocket;

require_once('../vendor/autoload.php');

$server_name = 'da1.is.cc';
$server_port = 2222;
$password = 'admin_password';
$sock = new HTTPSocket();
$sock->connect("ssl://{$server_name}", $server_port);
//$sock->connect("http://{$server_name}", $server_port);
//$sock->connect('http://'.$server_name, $server_port);
$sock->set_login("admin", $password);

$sock->query('/CMD_API_SHOW_ALL_USERS');
$result = $sock->fetch_parsed_body();

print_r($result);
