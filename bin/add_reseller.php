<?php

use Detain\MyAdminDirectAdminWeb\HTTPSocket;

require_once('../vendor/autoload.php');


$server_login="admin";
$server_pass="admin_password";
$server_host="da1.is.cc"; //where the API connects to
$server_ssl="Y";
$server_port=2222;

$username='firstres';
$domain='firstreseller.com';
$email='detain@interserver.net';
$pass='something';
$package='RSONE';


echo "Creating reseller $username .... <br>\n";

$sock = new HTTPSocket();
if ($server_ssl == 'Y') {
    $sock->connect("ssl://".$server_host, $server_port);
} else {
    $sock->connect($server_host, $server_port);
}

$sock->set_login($server_login, $server_pass);

$sock->query(
    '/CMD_API_ACCOUNT_RESELLER',
    array(
        'action' => 'create',
        'add' => 'Submit',
        'username' => $username,
        'email' => $email,
        'passwd' => $pass,
        'passwd2' => $pass,
        'domain' => $domain,
        'package' => $package,
        'ip' => 'shared',
        'notify' => 'yes'
    )
);

$result = $sock->fetch_parsed_body();
print_r($result);

if ($result['error'] != "0") {
    echo "<b>Error Creating user $username :<br>\n";
    echo $result['text']."<br>\n";
    echo $result['details']."<br></b>\n";
} else {
    echo "User $username created <br>\n";
}

exit(0);
