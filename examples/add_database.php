#!/usr/local/bin/php
<?php

//This is a sample scripts/custom/user_create_post.sh script, to create a database upon User creation.
//Must use DA 1.43.1 or later because of: http://www.directadmin.com/features.php?id=1460

include 'httpsocket.php';

$sock = new HTTPSocket;
$sock->connect('127.0.0.1',2222);

$user = getenv('username');
$pass = getenv('passwd');

$sock->set_login("${user}","${pass}");

$sock->set_method('POST');

$sock->query('/CMD_API_DATABASES',
        array(
                'action' => 'create',
                'name' => "db",
                'user' => "dbuser",
                'passwd' => "$pass",
                'passwd2' => "$pass",
    ));

$result = $sock->fetch_body();

echo $result;

?>
