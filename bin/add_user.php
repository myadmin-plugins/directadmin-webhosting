<?php

use Detain\MyAdminDirectAdminWeb\HTTPSocket;

require_once('../vendor/autoload.php');

$server_ip="da1.is.cc"; //IP that User is assigned to
$server_login="admin";
$server_pass="admin_password";
$server_host="da1.is.cc"; //where the API connects to
$server_ssl="Y";
$server_port=2222;

$username='firstsite';
$domain='firstsite.com';
$email='detain@interserver.net';
$pass='something';
$package='Standard';


echo "Creating user $username on $server_ip.... <br>\n";

$sock = new HTTPSocket();
if ($server_ssl == 'Y') {
    $sock->connect("ssl://".$server_host, $server_port);
} else {
    $sock->connect($server_host, $server_port);
}

$sock->set_login($server_login, $server_pass);

$sock->query(
    '/CMD_API_ACCOUNT_USER',
    array(
        'action' => 'create',
        'add' => 'Submit',
        'username' => $username,
        'email' => $email,
        'passwd' => $pass,
        'passwd2' => $pass,
        'domain' => $domain,
        'package' => $package,
        'ip' => $server_ip,
        'notify' => 'yes'
    )
);

$result = $sock->fetch_parsed_body();
print_r($result);

if ($result['error'] != "0") {
    echo "<b>Error Creating user $username on server $server_ip:<br>\n";
    echo $result['text']."<br>\n";
    echo $result['details']."<br></b>\n";
} else {
    echo "User $username created on server $server_ip<br>\n";
}

exit(0);
