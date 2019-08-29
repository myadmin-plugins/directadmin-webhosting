<?php
//coded by www.webvanced.nl
//generates a csv with account settings of multiple servers

include 'httpsocket.php';

$sock = new HTTPSocket;
$servers = array(
	'some.server.com' => array('admin','password'),
	'other.server.com' => array('admin','password')
);

foreach($servers as $server => $credentials) {
	
	$sock->connect($server,2222);
	$sock->set_login($credentials[0],$credentials[1]);

	$sock->query('/CMD_API_SHOW_ALL_USERS');
	$result = $sock->fetch_parsed_body();

	foreach($result['list'] as $user) {
	
		$sock->query('/CMD_API_SHOW_USER_CONFIG?user='.$user);
		$result2 = $sock->fetch_parsed_body();
	
		if(!$header) {
			$cols = array();
			$h = array('server','user');
			foreach($result2 as $k=>$v) {
				$h[] = $k;
				$cols[] = $k;
			}
			$header = 1;
			echo join("\t",$h)."\n";
		}

		$row = array($server,$user);
		foreach($cols as $col) {
			$row[] = $result2[$col];
		}
		echo join("\t",$row)."\n";

	}

}

?>
