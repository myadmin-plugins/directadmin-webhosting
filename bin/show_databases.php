<?php

use \Detain\MyAdminDirectAdminWeb\HTTPSocket;

require_once('../vendor/autoload.php');

$server_name = 'da1.is.cc';
$server_port = 2222;
$password = 'admin_password';
$sock = new HTTPSocket();
//$sock->connect("ssl://{$server_name}", $server_port);
$sock->connect($server_name, $server_port);
$sock->set_login("admin", $password);
//$sock->set_login("admin|{$user}", $password);
$sock->query('/CMD_API_DATABASES');
parse_str($sock::unhtmlentities($sock->fetch_body()), $result);
print_r($result);
