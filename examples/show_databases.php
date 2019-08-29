<?php

require_once('../httpsocket.php');
$sock = new HTTPSocket();
$sock->connect("ssl://{$server_name}", $server_port);
$sock->set_login("admin|{$user}", $password);
$sock->query('CMD_API_DATABASES');
$result = $sock->fetch_body();
print $result; 
