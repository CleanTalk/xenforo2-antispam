<?php

namespace Cleantalk\Custom;

require_once \XF::getRootDirectory() . '/src/addons/CleanTalk/lib/autoload.php';

define('APBCT_TBL_FIREWALL_DATA', 'cleantalk_sfw');      // Table with firewall data.
define('APBCT_TBL_FIREWALL_LOG', 'cleantalk_sfw_logs'); // Table with firewall logs.
define('APBCT_TBL_AC_LOG', 'cleantalk_ac_log');   // Table with firewall logs.
define('APBCT_TBL_AC_UA_BL', 'cleantalk_ua_bl');    // Table with User-Agents blacklist.
define('APBCT_TBL_SESSIONS', 'cleantalk_sessions'); // Table with session data.
define('APBCT_SPAMSCAN_LOGS', 'cleantalk_spamscan_logs'); // Table with session data.
define('APBCT_SELECT_LIMIT', 5000); // Select limit for logs.
define('APBCT_WRITE_LIMIT', 5000); // Write limit for firewall data.

// Cron handlers specific names
if ( !defined('APBCT_CRON_HANDLER__SFW_UPDATE') ) {
    define('APBCT_CRON_HANDLER__SFW_UPDATE', '\Cleantalk\Custom\Funcs::sfwUpdate');
}
if ( !defined('APBCT_CRON_HANDLER__SFW_LOGS') ) {
    define('APBCT_CRON_HANDLER__SFW_LOGS', '\Cleantalk\Custom\Funcs::sfwSendLogs');
}

use Cleantalk\Common\Firewall\Firewall;
use Cleantalk\Common\Firewall\Modules\SFW;
use Cleantalk\Common\Mloader\Mloader;

class Funcs
{
    public static function getXF()
    {
        if ( !\XF::app() ) {
            $fileDir = $_SERVER["DOCUMENT_ROOT"];

            if ( !file_exists($fileDir . '/src/XF.php') ) {
                return false;
            }

            require_once($fileDir . '/src/XF.php');

            \XF::start($fileDir);

            $app = \XF::setupApp('XF\Pub\App');
            $app->start();
            return $app;
        }

        return \XF::app();
    }

    public static function apbctRunCron()
    {
        /** @var \Cleantalk\Common\RemoteCalls\RemoteCalls $rc_class */
        $rc_class = Mloader::get('RemoteCalls');
        // Cron
        /** @var \Cleantalk\Common\Cron\Cron $cron_class */
        $cron_class = Mloader::get('Cron');
        $cron = new $cron_class();
        $cron_option_name = $cron->getCronOptionName();
        $cron_option = json_decode(self::getXF()->options()->$cron_option_name, true);
        if ( empty($cron_option) ) {
            $cron->saveTasks($cron->getDefaultTasks());
        }
        $tasks_to_run = $cron->checkTasks(); // Check for current tasks. Drop tasks inner counters.

        if (
            !empty($tasks_to_run) && // There is tasks to run
            !$rc_class::check() && // Do not doing CRON in remote call action
            (
                !defined('DOING_CRON') ||
                (defined('DOING_CRON') && DOING_CRON !== true)
            )
        ) {
            $cron_res = $cron->runTasks($tasks_to_run);
            // Handle the $cron_res for errors here.
        }
    }

    public static function ctSetCookie()
    {
        // Cookie names to validate
        $cookie_test_value = array(
            'cookies_names' => array(),
            'check_value' => trim(self::getXF()->options()->ct_apikey),
        );
        // Pervious referer
        if ( !empty($_SERVER['HTTP_REFERER']) ) {
            setcookie('ct_prev_referer', $_SERVER['HTTP_REFERER'], 0, '/');
            $cookie_test_value['cookies_names'][] = 'ct_prev_referer';
            $cookie_test_value['check_value'] .= $_SERVER['HTTP_REFERER'];
        }

        // Cookies test
        $cookie_test_value['check_value'] = md5($cookie_test_value['check_value']);
        setcookie('ct_cookies_test', json_encode($cookie_test_value), 0, '/');
    }

    public static function sfwUpdate($api_key = '')
    {
        if ( empty($api_key) ) {
            $api_key = trim(self::getXF()->options()->ct_apikey);
            if ( empty($api_key) ) {
                return false;
            }
        }

        $fw_logs_table_name = APBCT_TBL_FIREWALL_LOG;

        $firewall = new Firewall($api_key, $fw_logs_table_name);

        $fw_updater = $firewall->getUpdater();

        if ( defined('APBCT_SFW_DIRECT_UPDATE') && APBCT_SFW_DIRECT_UPDATE ) {
            return $fw_updater->directUpdate();
        }

        $upd_res = $fw_updater->update();

        return $upd_res;
    }

    public static function sfwSendLogs($api_key = '')
    {
        if ( empty($api_key) ) {
            $api_key = trim(self::getXF()->options()->ct_apikey);
            if ( empty($api_key) ) {
                return false;
            }
        }

        $firewall = new Firewall($api_key, APBCT_TBL_FIREWALL_LOG);
        return $firewall->sendLogs();
    }

    public static function ctRemoteCalls()
    {
        // Remote calls
        /** @var \Cleantalk\Common\RemoteCalls\RemoteCalls $rc_class */
        $rc_class = Mloader::get('RemoteCalls');
        /** @var \Cleantalk\Common\StorageHandler\StorageHandler $storage_handler */
        $storage_handler = Mloader::get('StorageHandler');
        if ( $rc_class::check() ) {
            $remote_calls = new $rc_class(trim(self::getXF()->options()->ct_apikey), new $storage_handler());
            try {
                die($remote_calls->process());
            } catch ( \Cleantalk\Common\RemoteCalls\Exceptions\RemoteCallsException $exception ) {
                die('FAIL ' . json_encode(array('error' => $exception->getMessage())));
            }
        }
    }

    public static function sfwCheck()
    {
        $api_key = trim(self::getXF()->options()->ct_apikey);
        $fw_logs_table_name = APBCT_TBL_FIREWALL_LOG;
        $fw_data_table_name = APBCT_TBL_FIREWALL_DATA;

        try {
            $firewall = new Firewall($api_key, $fw_logs_table_name);

            $firewall->loadFwModule(
                new SFW($fw_logs_table_name, $fw_data_table_name)
            );

            $firewall->run();
        } catch ( \Exception $e ) {
            error_log('CleanTalk Firewall is not loaded: ' . $e->getMessage());
        }
    }
}
