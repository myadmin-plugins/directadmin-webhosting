<?php
/**
* API Functions
* @author    Joe Huss <detain@interserver.net>
* @copyright 2019
* @package   MyAdmin
* @category  API
*/

use Detain\MyAdminDirectAdminWeb\HTTPSocket;

/**
 * Automatic DirectAdmin Login to Client Web Interface
 *
 * @param int $id id of website
 * @return array array containing status of error or ok and status_text with description of error or the unique logged in url
 * @throws \Exception
 */
function api_auto_directadmin_login($id)
{
    $return = ['status' => 'error', 'status_text' => ''];
    $module = 'webhosting';
    $custid = get_custid($GLOBALS['tf']->session->account_id, 'vps');
    $settings = get_module_settings($module);
    $db = get_module_db($module);
    $id = (int)$id;
    $service = get_service($id, $module);
    if ($service === false) {
        $return['status_text'] = 'Invalid Website Passed';
        return $return;
    }
    $username = $service[$settings['PREFIX'].'_username'];
    $hostname = $service[$settings['PREFIX'].'_hostname'];
    $serviceMaster = get_service_master($service[$settings['PREFIX'].'_server'], $module);
    $host = $serviceMaster[$settings['PREFIX'].'_name'];
    if ($host && $username) {
        $server_ssl='Y';
        $sock = new HTTPSocket();
        $sock->connect(($server_ssl == 'Y' ? 'ssl://' : '').$serviceMaster[$settings['PREFIX'].'_name'], 2222);
        $sock->set_login('admin|'.$serviceInfo[$settings['PREFIX'].'_username'], $serviceMaster[$settings['PREFIX'].'_key']);
        $sock->set_method('POST');
        $apiCmd = '/CMD_API_LOGIN_KEYS';
        /*
        $keyName = $serviceInfo[$settings['PREFIX'].'_username'].'key';
        $key = substr(md5($keyName), 0, 65);
        $expire = time() + (60 * 10);
        $apiOptions = [
            'action' => 'create',
            'type' => 'key',
            'json' => 'yes',
            'keyname' => $keyName,
            'key' => $key,
            'key2' => $key,
            'never_expires' => 'no',
            'hour' => date('H', $expire),
            'minute' => date('i', $expire),
            'month' => date('m', $expire),
            'day' => date('d', $expire),
            'year' => date('Y', $expire),
            'max_uses' => 2,
            'clear_key' => 'no',
            'allow_html' => 'yes',
            'passwd' => $serviceMaster[$settings['PREFIX'].'_key'],
            'ips' => $GLOBALS['tf']->session::get_client_ip(),
        ];
        */
        $apiOptions = [
            'action' => 'create',
            'type' => 'one_time_url',
            'json' => 'yes',
            'max_uses' => 2,
            'clear_key' => 'yes',
            'allow_html' => 'yes',
            'passwd' => $serviceMaster[$settings['PREFIX'].'_key'],
            //'ips' => $GLOBALS['tf']->session::get_client_ip(),
            'login_keys_notify_on_creation' => 0,
            //'redirect-url' => '/user/email/accounts',
        ];
        $sock->query($apiCmd, $apiOptions);
        $response = $sock->fetch_body();
        $result = json_decode($response, true);
        $return['status'] = 'ok';
        $return['status_text'] = $result['result'];
        request_log($module, $serviceInfo[$settings['PREFIX'].'_custid'], __FUNCTION__, 'directadmin', $apiCmd, $apiOptions, $result, $id);
        myadmin_log($module, 'info', 'DirectAdmin '.$apiCmd.' '.json_encode($apiOptions).' : '.json_encode($result), __LINE__, __FILE__, $module, $id);
        return $return;
    }
    $return['status_text'] = 'Sorry! something went wrong, couldn\'t able to connect to cPanel!';
    return $return;
}
