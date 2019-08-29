<?php

include 'httpsocket.php';

$server_ip="11.22.33.44"; //IP that User is assigned to
$server_login="admin";
$server_pass="yourpass";
$server_host="127.0.0.1"; //where the API connects to
$server_ssl="N";
$server_port=2222;

if (isset($_POST['action']) && $_POST['action'] == "add")
{

	$username=$_POST['username'];
	$domain=$_POST['domain'];
	$email=$_POST['email'];
	$pass=$_POST['pass'];
	$package=$_POST['package'];


	echo "Creating user $username on $server_ip.... <br>\n";
 
	$sock = new HTTPSocket;
	if ($server_ssl == 'Y')
	{
		$sock->connect("ssl://".$server_host, $server_port);
	}
	else
	{ 
		$sock->connect($server_host, $server_port);
	}
 
	$sock->set_login($server_login,$server_pass);
 
	$sock->query('/CMD_API_ACCOUNT_USER',
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
		));
 
	$result = $sock->fetch_parsed_body();
 
	if ($result['error'] != "0")
	{
		echo "<b>Error Creating user $username on server $server_ip:<br>\n";
		echo $result['text']."<br>\n";
		echo $result['details']."<br></b>\n";
	}
	else
	{
		echo "User $username created on server $server_ip<br>\n";
	}

	exit(0);
}

echo "Will connect to: ".($server_ssl == "Y" ? "https" : "http")."://".$server_host.":".$server_port."<br>\n";

?>


<form action='?' method="POST">
<input type=hidden name=action value="add">
Username: <input type=text name=username><br>
Domain:<input type=text name=domain><br>
Email: <input type=text name=email><br>
Pass: <input type=password name=pass><br>
Packge: <input type=text name=package><br>
</form>


**Note:  do not use this php file exactly as it is.  It's only to demonstrate the basics of the api.
You *must* do form checking to ensure safe values are passed.
Also, it's a really bad and very insecure practice to put a form like this publicly on your website for anyone to use.
If you do, you'll end up with a server full users you did not create (this script creates accounts without any involvment with an admin: bad)
