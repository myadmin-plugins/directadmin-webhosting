<?php

use Detain\MyAdminDirectAdminWeb\HTTPSocket;

include_once __DIR__.'/../../../../include/functions.inc.php';


$db = get_module_db('webhosting');
if (count($_SERVER['argv']) < 2) {
    die("Call like {$_SERVER['argv'][0]} <hostname>\nwhere <hostname> is a webhosting server such as webhosting2004.interserver.net");
}
$db->query("select * from website_masters where website_name='".$db->real_escape($_SERVER['argv'][1])."'", __LINE__, __FILE__);
function_requirements('whm_api');
if ($db->num_rows() == 0) {
    die("Invalid Server {$_SERVER['argv'][1]} passed, did not match any webhosting server name");
}
$db->next_record(MYSQL_ASSOC);
echo "processing {$db->Record['website_name']}\n";
$server_name = $db->Record['website_ip'];;
$server_port = 2222;
$password = $db->Record['website_key'];
$sock = new HTTPSocket();
$sock->connect("ssl://{$server_name}", $server_port);
//$sock->connect("http://{$server_name}", $server_port);
//$sock->connect('http://'.$server_name, $server_port);
$sock->set_login("admin", $password);

$sock->query('/CMD_API_ADMIN_STATS');
$result = $sock->fetch_parsed_body();
print_r($sock);
print_r($result);
