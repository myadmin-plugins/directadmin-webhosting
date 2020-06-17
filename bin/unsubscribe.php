<?php

// Script to allow automatic majordomo unsubscription via DirectAdmin API, using optional/recommended Login Keys.

//******************
// 1) First, create a Login Key for the User that controls the domains.
//Use options:
// Expires On = Never
// Uses = 0
// Clear Key = unchecked
// Allow HTM = unchecked
// Commands: Allow: CMD_API_EMAIL_LIST
// Allows IPs: 127.0.0.1

//******************
// 2) Edit the information below

$domain = "domain.com";		// The domain part from list@domain.com
$list = "list";			// The list name part from list@domain.com

$server_login="username";	// DirectAdmin Username
$server_pass="yourloginkey";	// Key Password from Login Key section
$server_host="127.0.0.1";	// Where the API connects to
$server_ssl="Y";		// Y for https, N for http
$server_port=2222;		// port 2222, the default

// end Edit
//******************

include 'httpsocket.php';
$email = "";

if (isset($_POST['action']) && $_POST['action'] == 'remove')
{
	if (isset($_POST['email']))
		$email = $_POST['email'];

	if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))
	{
		show_form("Invalid E-Mail address");
		exit(0);
	}

	$sock = new HTTPSocket;
	$sock->connect(($server_ssl?"ssl://":"").$server_host, $server_port);
	$sock->set_login($server_login,$server_pass);
	$sock->set_method('POST');
	$sock->query('/CMD_API_EMAIL_LIST',
		array(
			'action' => 'delete_subscriber',
			'domain' => $domain,
			'name' => $list,
			'select0' => $email
		));

	$result = $sock->fetch_parsed_body();

	if ($result['error'] != "0")
	{
		echo "<b>Error removing E-Mail from mailing list:<br>\n";
		echo $result['text']."<br>\n";
		echo $result['details']."<br></b>\n";
	}
	else
	{
		show_form("", "E-Mail Deleted from list");
	}

	exit(0);
}

show_form();
exit(0);

function show_form($error="", $result="")
{
	global $email, $domain, $list;
?>

	<html>
        <style>
		* { font-family: verdana; font-size: 10pt; COLOR: gray; }
		b { font-weight: bold; }
		table {
			border-radius:40px;
			box-shadow: 10px 10px 50px #000000;
			background: #28619c;
		}
		td {
			background: #eef6ff;
			text-align: left;
			padding: 25;
			border-radius:30px;
			box-shadow: 2px 2px 15px #000000;
		}
		html {
			background: -webkit-linear-gradient(#224672, #3973b9); /* For Safari */
			background: -o-linear-gradient(#224672, #3973b9); /* For Opera 11.1 to 12.0 */
			background: -moz-linear-gradient(#224672, #3973b9); /* For Firefox 3.6 to 15 */
			background: linear-gradient(#224672, #3973b9); /* Standard syntax (must be last) */
			//background: #224672;
			padding-top: 50px;
		}
		h1 {
			font-size: 12pt;
			text-decoration:underline;
			color: rgba(59,48,42, 0.7);
			text-shadow: 1px 4px 6px #def, 0 0 0 #000, 1px 4px 6px #def;
			padding-bottom: 10px;
		}
		.error, .success {
			width: 100%;
			text-align: center;
			padding: 10px;
		}
		.error {
			font-weight: bold;
			color: #ab3035;
		}
        </style>
	<center>
	<table width=600 cellspacing=20px>
	<tr><td>

	<?php
	if ($result != "")
	{
		echo "<div class=success>";
		echo $result;
		echo "</div>";
	}
	else
	{
		?>


		<h1>Remove your address from <?php echo "$list@$domain"; ?> mailing list</h1>
		<form action=? method=POST>
		<input type=hidden name=action value="remove">
		<b>E-Mail Address to remove</b>: <input type=text name=email value="<?php echo htmlspecialchars(stripslashes($email));?>" placeholder="email@domain.com">
		<input type=submit value="Remove">
		<?php
		if ($error != "")
		{
			echo "<div class=error>";
			echo $error;
			echo "</div>";
		}
		?>
		</form>
		</td>
		<?php
	} ?>
</tr></table></html><?php
}
