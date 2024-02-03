<?php

use Detain\MyAdminDirectAdminWeb\HTTPSocket;

require_once('../vendor/autoload.php');

$server_login="admin";
$server_pass="admin_password";
$server_host="da1.is.cc"; //where the API connects to
$server_ssl="Y";
$server_port=2222;

$username='firstsit';

$sock = new HTTPSocket();
if ($server_ssl == 'Y') {
    $sock->connect("ssl://".$server_host, $server_port);
} else {
    $sock->connect($server_host, $server_port);
}

$sock->set_login($server_login, $server_pass);

$sock->query(
    '/CMD_API_SELECT_USERS',
    [
        'location' => 'CMD_SELECT_USERS',
        'suspend' => 'Suspend', // note - this can also be 'Unsuspend'
        'select0' => $username
    ]
);
$result = $sock->fetch_parsed_body();
print_r($result);
