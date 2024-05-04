<?php

namespace Detain\MyAdminDirectAdminWeb;

use Detain\MyAdminDirectAdminWeb\HTTPSocket;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminDirectAdminWeb
 */
class Plugin
{
    public static $name = 'DirectAdmin Webhosting';
    public static $description = 'web-based control panel makes site management a piece of cake. Empower your customers and offer them the ability to administer every facet of their website using simple, point-and-click software.  More info at https://DirectAdmin.com/';
    public static $help = '';
    public static $module = 'webhosting';
    public static $type = 'service';

    /**
     * Plugin constructor.
     */
    public function __construct()
    {
    }

    /**
     * @return array
     */
    public static function getHooks()
    {
        return [
            self::$module.'.settings' => [__CLASS__, 'getSettings'],
            self::$module.'.activate' => [__CLASS__, 'getActivate'],
            self::$module.'.reactivate' => [__CLASS__, 'getReactivate'],
            self::$module.'.deactivate' => [__CLASS__, 'getDeactivate'],
            self::$module.'.terminate' => [__CLASS__, 'getTerminate'],
            'api.register' => [__CLASS__, 'apiRegister'],
            'function.requirements' => [__CLASS__, 'getRequirements'],
            'ui.menu' => [__CLASS__, 'getMenu']
        ];
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     */
    public static function apiRegister(GenericEvent $event)
    {
        /**
         * @var \ServiceHandler $subject
         */
        //$subject = $event->getSubject();
        api_register('api_auto_directadmin_login', ['id' => 'int'], ['return' => 'result_status'], 'Logs into DirectAdmin for the given website id and returns a unique logged-in url.  The status will be "ok" if successful, or "error" if there was any problems status_text will contain a description of the problem if any.');
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     * @throws \Exception
     */
    public static function getActivate(GenericEvent $event)
    {
        if (in_array($event['type'], [get_service_define('WEB_DIRECTADMIN'), get_service_define('WEB_STORAGE')])) {
            $serviceClass = $event->getSubject();
            myadmin_log('myadmin', 'info', 'DirectAdmin Activation', __LINE__, __FILE__, self::$module, $serviceClass->getId());
            $serviceTypes = run_event('get_service_types', false, self::$module);
            $settings = get_module_settings(self::$module);
            $extra = run_event('parse_service_extra', $serviceClass->getExtra(), self::$module);
            $serverdata = get_service_master($serviceClass->getServer(), self::$module);
            $hash = $serverdata[$settings['PREFIX'].'_key'];
            $ip = $serverdata[$settings['PREFIX'].'_ip'];
            $hostname = $serviceClass->getHostname();
            if (trim($hostname) == '') {
                $hostname = $serviceClass->getId().'.server.com';
            }
            $password = website_get_password($serviceClass->getId(), $serviceClass->getCustid());
            $username = get_new_webhosting_username($serviceClass->getId(), $hostname, $serviceClass->getServer());
            if (in_array('reseller', explode(',', $event['field1']))) {
                $reseller = true;
                $apiCmd = '/CMD_API_ACCOUNT_RESELLER';
                $siteIp = 'shared';
            } else {
                $reseller = false;
                $apiCmd = '/CMD_API_ACCOUNT_USER';
                $siteIp = $ip;
                $siteIp = 'shared_random';
            }
            $server_ssl="Y";
            $sock = new HTTPSocket();
            $sock->connect(($server_ssl == 'Y' ? 'ssl://' : '').$ip, 2222);
            $sock->set_login('admin', $hash);
            /*
            $sock->query('/CMD_API_SHOW_RESELLER_IPS');
            $result = $sock->fetch_parsed_body();
            if (!isset($result['list'])) {
                $event['success'] = false;
                myadmin_log('directadmin', 'error', 'Error Gettting Reseller IPS '.json_encode($result), __LINE__, __FILE__, self::$module, $serviceClass->getId());
                $event->stopPropagation();
                return;
            }
            if (!in_array($siteIp, $result['list'])) {
                $siteIp = $result['list'][0];                                     
            }
            */
            $apiOptions = [
                'action' => 'create',
                'add' => 'Submit',
                'username' => $username,
                'email' => $event['email'],
                'passwd' => $password,
                'passwd2' => $password,
                'domain' => $hostname,
                'ip' => $siteIp,
                'notify' => 'no'
            ];
            if (strpos($serviceTypes[$serviceClass->getType()]['services_field2'], ',') === false) {
                $apiOptions['package'] = $serviceTypes[$serviceClass->getType()]['services_field2'];
            } else {
                $fields = explode(',', $serviceTypes[$serviceClass->getType()]['services_field2']);
                foreach ($fields as $field) {
                    [$key, $value] = explode('=', $field);
                    $apiOptions[$key] = $value;
                }
            }
            $sock->query($apiCmd, $apiOptions);
            $rawResult = $sock->fetch_body();
            $result = $sock->fetch_parsed_body();
            request_log(self::$module, $serviceClass->getCustid(), __FUNCTION__, 'directadmin', $apiCmd, $apiOptions, $rawResult, $serviceClass->getId());
            myadmin_log('myadmin', 'info', 'DirectAdmin '.$apiCmd.' '.json_encode($apiOptions).' : '.json_encode($result), __LINE__, __FILE__, self::$module, $serviceClass->getId());
            if ($result['error'] != "0") {
                if ((isset($result['text']) && trim($result['text']) != '') || (isset($result['details']) && trim($result['details']) != '')) {
                    $event['success'] = false;
                    getcurlpage('https://chat.is.cc/hooks/BAckHdSAoMsPieCof/CHQ3bKKo5Kh2HeHFJWDpxqBFyj2i7WZwmsLpLM7PmHK5D2fR', json_encode([
                        'username' => 'Interesting Guy',
                        'text' => 'Failed [Website '.$serviceClass->getId().'](https://my.interserver.net/admin/view_website?id='.$serviceClass->getId().') Activation Text:'.$result['text'].' Details:'.$result['details'],
                    ]), [CURLOPT_HTTPHEADER => ['Content-type: application/json']]);
                    myadmin_log('directadmin', 'error', 'Error Creating User '.$username.' Site '.$hostname.' Text:'.$result['text'].' Details:'.$result['details'], __LINE__, __FILE__, self::$module, $serviceClass->getId());
                    $event->stopPropagation();
                    return;
                }
            }
            }
            /* if ($serviceTypes[$serviceClass->getType()]['services_field2'] != '') {
                $fields = explode(',', $serviceTypes[$serviceClass->getType()]['services_field2']);
                foreach ($fields as $field) {
                    list($key, $value) = explode('=', $field);
                    if ($key == 'script') {
                        $extra[$key] = $value;
                    } else {
                        $options[$key] = $value;
                    }
                }
            } */
            if (isset($result['result']) && mb_substr($result['result'][0]['statusmsg'], 0, 19) == 'Sorry, the password') {
                while (mb_substr($result['result'][0]['statusmsg'], 0, 19) == 'Sorry, the password') {
                    $password = generateRandomString(10, 2, 2, 2, 1);
                    $apiOptions['passwd'] = $password;
                    $apiOptions['passwd2'] = $password;
                    myadmin_log('myadmin', 'info', "Trying Password {$apiOptions['password']}", __LINE__, __FILE__, self::$module, $serviceClass->getId());
                    $sock->query($apiCmd, $apiOptions);
                    $rawResult = $sock->fetch_body();
                    $result = $sock->fetch_parsed_body();
                    request_log(self::$module, $serviceClass->getCustid(), __FUNCTION__, 'directadmin', $apiCmd, $apiOptions, $rawResult, $serviceClass->getId());
                    myadmin_log('myadmin', 'info', 'DirectAdmin '.$apiCmd.' : '.json_encode($result), __LINE__, __FILE__, self::$module, $serviceClass->getId());
                    if ($result['error'] != "0") {
                    }
                }
                $GLOBALS['tf']->history->add($settings['PREFIX'], 'password', $serviceClass->getId(), $options['password']);
            }
            if (isset($result['result']) && $result['result'][0]['statusmsg'] == 'Sorry, a group for that username already exists.') {
                while ($result['result'][0]['statusmsg'] == 'Sorry, a group for that username already exists.') {
                    $username .= 'a';
                    $username = mb_substr($username, 1);
                    $apiOptions['username'] = $username;
                    myadmin_log('myadmin', 'info', 'Trying Username '.$apiOptions['username'], __LINE__, __FILE__, self::$module, $serviceClass->getId());
                    $sock->query($apiCmd, $apiOptions);
                    $rawResult = $sock->fetch_body();
                    $result = $sock->fetch_parsed_body();
                    request_log(self::$module, $serviceClass->getCustid(), __FUNCTION__, 'directadmin', $apiCmd, $apiOptions, $rawResult, $serviceClass->getId());
                    myadmin_log('myadmin', 'info', 'DirectAdmin '.$apiCmd.' : '.json_encode($result), __LINE__, __FILE__, self::$module, $serviceClass->getId());
                    if ($result['error'] != "0") {
                    }
                }
            }
            if (isset($result['result']) && (preg_match("/^.*This system already has an account named .{1,3}{$username}.{1,3}\.$/m", $result['result'][0]['statusmsg']) || preg_match('/^.*The name of another account on this server has the same initial/m', $result['result'][0]['statusmsg']))) {
                while (preg_match("/^.*This system already has an account named .{1,3}{$username}.{1,3}\.$/m", $result['result'][0]['statusmsg']) || preg_match('/^.*The name of another account on this server has the same initial/m', $result['result'][0]['statusmsg'])) {
                    $username .= 'a';
                    $username = mb_substr($username, 1);
                    $apiOptions['username'] = $username;
                    myadmin_log('myadmin', 'info', 'Trying Username '.$apiOptions['username'], __LINE__, __FILE__, self::$module, $serviceClass->getId());
                    $sock->query($apiCmd, $apiOptions);
                    $rawResult = $sock->fetch_body();
                    $result = $sock->fetch_parsed_body();
                    request_log(self::$module, $serviceClass->getCustid(), __FUNCTION__, 'directadmin', $apiCmd, $apiOptions, $rawResult, $serviceClass->getId());
                    myadmin_log('myadmin', 'info', 'DirectAdmin '.$apiCmd.' : '.json_encode($result), __LINE__, __FILE__, self::$module, $serviceClass->getId());
                    if ($result['error'] != "0") {
                    }
                }
            }
            $db = get_module_db(self::$module);
            $username = $db->real_escape($username);
            if (!isset($result['error']) || $result['error'] == '0' || $result['error'] == 0) {
                $sock->query('/CMD_API_SHOW_USER_CONFIG', ['user'=> $username]);
                $userConfigDetails = $sock->fetch_parsed_body();
                $siteIp = $userConfigDetails['ip'];
            }
            $db->query("update {$settings['TABLE']} set {$settings['PREFIX']}_ip='{$siteIp}', {$settings['PREFIX']}_username='{$username}' where {$settings['PREFIX']}_id='{$serviceClass->getId()}'", __LINE__, __FILE__);
            website_welcome_email($serviceClass->getId());
            function_requirements('add_dns_record');
            $result = add_dns_record(14426, 'wh'.$serviceClass->getId(), $siteIp, 'A', 86400, 0, true);
            $apiOption = [
                'action' => 'create',
                'domain' => 'wh'.$serviceClass->getId().'.ispot.cc',
                'ubandwidth' => 'unlimited',
                'uquota' => 'unlimited',
                'ssl' => 'ON',
                'cgi' => 'ON',
                'php' => 'ON'
            ];
            $sock = new HTTPSocket();
            $sock->connect('ssl://'.$siteIp, 2222);
            $sock->set_login($username, $password);
            $sock->set_method('POST');
            $sock->query('/CMD_API_DOMAIN', $apiOption);
            $rawResult = $sock->fetch_body();
            $result = $sock->fetch_parsed_body();
            request_log(self::$module, $serviceClass->getCustid(), __FUNCTION__, 'directadmin', '/CMD_API_DOMAIN', $apiOption, $rawResult, $serviceClass->getId());
            myadmin_log('myadmin', 'info', 'DirectAdmin /CMD_API_DOMAIN : '.json_encode($result), __LINE__, __FILE__, self::$module, $serviceClass->getId());
            $event['success'] = true;
            $event->stopPropagation();
            return true;
        }
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     * @throws \Exception
     */
    public static function getReactivate(GenericEvent $event)
    {
        if (in_array($event['type'], [get_service_define('WEB_DIRECTADMIN'), get_service_define('WEB_STORAGE')])) {
            $serviceClass = $event->getSubject();
            $settings = get_module_settings(self::$module);
            $serverdata = get_service_master($serviceClass->getServer(), self::$module);
            $hash = $serverdata[$settings['PREFIX'].'_key'];
            $ip = $serverdata[$settings['PREFIX'].'_ip'];
            $server_ssl="Y";
            $sock = new HTTPSocket();
            $sock->connect(($server_ssl == 'Y' ? 'ssl://' : '').$ip, 2222);
            $sock->set_login('admin', $hash);
            $apiCmd = '/CMD_API_SELECT_USERS';
            $apiOptions = [
                'location' => 'CMD_SELECT_USERS',
                'dounsuspend' => 'y',
                'select0' => $serviceClass->getUsername()
            ];
            $sock->query($apiCmd, $apiOptions);
            $rawResult = $sock->fetch_body();
            $result = $sock->fetch_parsed_body();
            request_log(self::$module, $serviceClass->getCustid(), __FUNCTION__, 'directadmin', $apiCmd, $apiOptions, $rawResult, $serviceClass->getId());
            myadmin_log('myadmin', 'info', 'DirectAdmin '.$apiCmd.' Response: '.json_encode($result), __LINE__, __FILE__, self::$module, $serviceClass->getId());
            $event['success'] = true;
            $event->stopPropagation();
            return true;
        }
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     * @throws \Exception
     */
    public static function getDeactivate(GenericEvent $event)
    {
        if (in_array($event['type'], [get_service_define('WEB_DIRECTADMIN'), get_service_define('WEB_STORAGE')])) {
            $serviceClass = $event->getSubject();
            myadmin_log('myadmin', 'info', 'DirectAdmin Deactivation', __LINE__, __FILE__, self::$module, $serviceClass->getId());
            $settings = get_module_settings(self::$module);
            if ($serviceClass->getServer() > 0) {
                $serverdata = get_service_master($serviceClass->getServer(), self::$module);
                $hash = $serverdata[$settings['PREFIX'].'_key'];
                $ip = $serverdata[$settings['PREFIX'].'_ip'];
                $server_ssl="Y";
                $sock = new HTTPSocket();
                $sock->connect(($server_ssl == 'Y' ? 'ssl://' : '').$ip, 2222);
                $sock->set_login('admin', $hash);
                $apiCmd = '/CMD_API_SELECT_USERS';
                $apiOptions = [
                    'location' => 'CMD_SELECT_USERS',
                    'suspend' => 'Suspend',
                    'select0' => $serviceClass->getUsername()
                ];
                $sock->query($apiCmd, $apiOptions);
                $rawResult = $sock->fetch_body();
                $result = $sock->fetch_parsed_body();
                request_log(self::$module, $serviceClass->getCustid(), __FUNCTION__, 'directadmin', $apiCmd, $apiOptions, $rawResult, $serviceClass->getId());
                myadmin_log('myadmin', 'info', 'DirectAdmin '.$apiCmd.' Response: '.json_encode($result), __LINE__, __FILE__, self::$module, $serviceClass->getId());
            }
            $event['success'] = true;
            $event->stopPropagation();
            return true;
        }
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     * @return boolean|null
     * @throws \Exception
     */
    public static function getTerminate(GenericEvent $event)
    {
        if (in_array($event['type'], [get_service_define('WEB_DIRECTADMIN'), get_service_define('WEB_STORAGE')])) {
            $serviceClass = $event->getSubject();
            myadmin_log('myadmin', 'info', 'DirectAdmin Termination', __LINE__, __FILE__, self::$module, $serviceClass->getId());
            $settings = get_module_settings(self::$module);
            $serverdata = get_service_master($serviceClass->getServer(), self::$module);
            $hash = $serverdata[$settings['PREFIX'].'_key'];
            $ip = $serverdata[$settings['PREFIX'].'_ip'];
            if (trim($serviceClass->getUsername()) != '') {
                $server_ssl="Y";
                $sock = new HTTPSocket();
                $sock->connect(($server_ssl == 'Y' ? 'ssl://' : '').$ip, 2222);
                $sock->set_login('admin', $hash);
                $apiCmd = '/CMD_API_SELECT_USERS';
                $apiOptions = [
                    'confirmed' => 'Confirm',
                    'delete' => 'yes',
                    'select0' => $serviceClass->getUsername()
                ];
                $sock->query($apiCmd, $apiOptions);
                $rawResult = $sock->fetch_body();
                $result = $sock->fetch_parsed_body();
                request_log(self::$module, $serviceClass->getCustid(), __FUNCTION__, 'directadmin', $apiCmd, $apiOptions, $rawResult, $serviceClass->getId());
                myadmin_log('myadmin', 'info', 'DirectAdmin '.$apiCmd.' Response: '.json_encode($result), __LINE__, __FILE__, self::$module, $serviceClass->getId());
            }
            $event['success'] = true;
            $event->stopPropagation();
            if (trim($serviceClass->getUsername()) == '') {
                return true;
            } elseif (isset($result['error']) && $result['error'] == "0") {
                return true;
            } elseif (isset($result['text']) && $result['text'] == "System user {$serviceClass->getUsername()} does not exist!") {
                return true;
            } else {
                $event['success'] = false;
                return false;
            }
        }
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     */
    public static function getChangeIp(GenericEvent $event)
    {
        if (in_array($event['type'], [get_service_define('WEB_DIRECTADMIN'), get_service_define('WEB_STORAGE')])) {
            $serviceClass = $event->getSubject();
            $settings = get_module_settings(self::$module);
            $directadmin = new DirectAdmin(FANTASTICO_USERNAME, FANTASTICO_PASSWORD);
            myadmin_log('myadmin', 'info', 'IP Change - (OLD:'.$serviceClass->getIp().") (NEW:{$event['newip']})", __LINE__, __FILE__, self::$module, $serviceClass->getId());
            $result = $directadmin->editIp($serviceClass->getIp(), $event['newip']);
            if (isset($result['faultcode'])) {
                myadmin_log('myadmin', 'error', 'DirectAdmin editIp('.$serviceClass->getIp().', '.$event['newip'].') returned Fault '.$result['faultcode'].': '.$result['fault'], __LINE__, __FILE__, self::$module, $serviceClass->getId());
                $event['status'] = 'error';
                $event['status_text'] = 'Error Code '.$result['faultcode'].': '.$result['fault'];
            } else {
                $GLOBALS['tf']->history->add($settings['TABLE'], 'change_ip', $event['newip'], $serviceClass->getId(), $serviceClass->getCustid());
                $serviceClass->set_ip($event['newip'])->save();
                $event['status'] = 'ok';
                $event['status_text'] = 'The IP Address has been changed.';
            }
            $event->stopPropagation();
        }
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     */
    public static function getMenu(GenericEvent $event)
    {
        $menu = $event->getSubject();
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     */
    public static function getRequirements(GenericEvent $event)
    {
        /**
         * @var \MyAdmin\Plugins\Loader $this->loader
         */
        $loader = $event->getSubject();
        $loader->add_requirement('api_auto_directadmin_login', '/../vendor/detain/myadmin-directadmin-webhosting/src/api_auto_directadmin_login.php');
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     */
    public static function getSettings(GenericEvent $event)
    {
        /**
         * @var \MyAdmin\Settings $settings
         **/
        $settings = $event->getSubject();
        $settings->setTarget('module');
        $settings->add_select_master(_(self::$module), _('Default Servers'), self::$module, 'new_website_directadmin_server', _('Default DirectAdmin Setup Server'), NEW_WEBSITE_DIRECTADMIN_SERVER, get_service_define('WEB_DIRECTADMIN'));
        $settings->add_dropdown_setting(self::$module, _('Out of Stock'), 'outofstock_webhosting_directadmin', _('Out Of Stock DirectAdmin Webhosting'), _('Enable/Disable Sales Of This Type'), $settings->get_setting('OUTOFSTOCK_WEBHOSTING_DIRECTADMIN'), ['0', '1'], ['No', 'Yes']);
    }
}
