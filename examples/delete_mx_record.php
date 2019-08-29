<?php

include 'httpsocket.php';

$sock = new HTTPSocket;

$sock->connect('127.0.0.1',2222);
$sock->set_login('admin','adminpass');
$sock->set_method('POST');

$sock->query('/CMD_API_DNS_ADMIN',
        array(
                'domain' => 'domain.com',
                'action' => 'select',
                'mxrecs0' => 'name=domain.com.&value=10 mail',
                'delete' => 'Delete Selected'
    ));
$result = $sock->fetch_body();

echo $result;

?>

