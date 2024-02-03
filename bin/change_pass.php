<?php

//Change a password for a User via command line, through the API.

//download the following file to the same directory:
//http://files.directadmin.com/services/all/httpsocket/httpsocket.php

use Detain\MyAdminDirectAdminWeb\HTTPSocket;

require_once('../vendor/autoload.php');

$server_ip="da1.is.cc"; //IP that User is assigned to
$server_login="admin";
$server_pass="admin_password";
$server_ssl="N";

if ($argc < 3) {
    echo "Usage: $argv[0] <username> <password>\n";
    exit(1);
}

$username=$argv[1];
$pass=$argv[2];

echo "changing password for user $username\n";

$sock = new HTTPSocket();
if ($server_ssl == 'Y') {
    $sock->connect("ssl://".$server_ip, 2222);
} else {
    $sock->connect($server_ip, 2222);
}

$sock->set_login($server_login, $server_pass);
$sock->set_method('POST');

$sock->query(
    '/CMD_API_USER_PASSWD',
    [
        'username' => $username,
        'passwd' => $pass,
        'passwd2' => $pass,
        'options' => 'yes',
        'system' => 'yes',
        'ftp' => 'yes',
        'database' => 'yes'
    ]
);

$result = $sock->fetch_parsed_body();

if ($result['error'] != "0") {
    echo "\n*****\n";
    echo "Error setting password for $username:\n";
    echo "  ".$result['text']."\n";
    echo "  ".$result['details']."\n";
} else {
    echo "$user password set to $pass\n";
}

exit(0);
