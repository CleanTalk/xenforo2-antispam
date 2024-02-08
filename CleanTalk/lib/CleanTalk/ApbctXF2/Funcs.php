<?php

namespace CleanTalk\ApbctXF2;

require_once \XF::getRootDirectory().'/src/addons/CleanTalk/lib/autoload.php';

define('APBCT_TBL_FIREWALL_DATA', 'cleantalk_sfw');      // Table with firewall data.
define('APBCT_TBL_FIREWALL_LOG',  'cleantalk_sfw_logs'); // Table with firewall logs.
define('APBCT_TBL_AC_LOG',        'cleantalk_ac_log');   // Table with firewall logs.
define('APBCT_TBL_AC_UA_BL',      'cleantalk_ua_bl');    // Table with User-Agents blacklist.
define('APBCT_TBL_SESSIONS',      'cleantalk_sessions'); // Table with session data.
define('APBCT_SPAMSCAN_LOGS',     'cleantalk_spamscan_logs'); // Table with session data.
define('APBCT_SELECT_LIMIT',      5000); // Select limit for logs.
define('APBCT_WRITE_LIMIT',       5000); // Write limit for firewall data.
define('APBCT_SFW_DIRECT_UPDATE',       false); // Write limit for firewall data.

use CleanTalk\ApbctXF2\Helper as CleantalkHelper;
use CleanTalk\Common\Firewall\Firewall;
use CleanTalk\ApbctXF2\RemoteCalls;
use CleanTalk\ApbctXF2\Cron;
use CleanTalk\ApbctXF2\DB;
use CleanTalk\Common\Firewall\Modules\SFW;
use CleanTalk\Common\Variables\Server;

class Funcs {

    static public function getXF() {
        if (!\XF::app()) {
            $fileDir = $_SERVER["DOCUMENT_ROOT"];

            if ( ! file_exists( $fileDir . '/src/XF.php'  ) ) {
            return false;
            }

            require_once( $fileDir . '/src/XF.php' );

            \XF::start($fileDir);

            $app = \XF::setupApp('XF\Pub\App');
            $app->start();
            return $app;
        }

        return \XF::app();
    }

    static public function apbctRunCron() {
        $cron = new Cron();
        $cron_option_name = $cron->getCronOptionName();
        if (isset(self::getXF()->options()->$cron_option_name)) {
            $cron_option = json_decode(self::getXF()->options()->$cron_option_name,true);
            if (empty($cron_option)) {
                $cron->addTask( 'sfw_update', '\CleanTalk\ApbctXF2\Funcs::apbct_sfw_update', 86400, time() + 60 );
                $cron->addTask( 'sfw_send_logs', '\CleanTalk\ApbctXF2\Funcs::apbct_sfw_send_logs', 3600 );
            }
            $tasks_to_run = $cron->checkTasks(); // Check for current tasks. Drop tasks inner counters.

            if(
                ! empty( $tasks_to_run ) && // There is tasks to run
                ! RemoteCalls::check() && // Do not doing CRON in remote call action
                (
                    ! defined( 'DOING_CRON' ) ||
                    ( defined( 'DOING_CRON' ) && DOING_CRON !== true )
                )
            ){
                $cron_res = $cron->runTasks( $tasks_to_run );
                // Handle the $cron_res for errors here.
            }
        }
    }

    static public function ctSetCookie() {
        // Cookie names to validate
        $cookie_test_value = array(
            'cookies_names' => array(),
            'check_value' => trim(self::getXF()->options()->ct_apikey),
        );
        // Pervious referer
        if(!empty($_SERVER['HTTP_REFERER'])){
            setcookie('ct_prev_referer', $_SERVER['HTTP_REFERER'], 0, '/');
            $cookie_test_value['cookies_names'][] = 'ct_prev_referer';
            $cookie_test_value['check_value'] .= $_SERVER['HTTP_REFERER'];
        }

        // Cookies test
        $cookie_test_value['check_value'] = md5($cookie_test_value['check_value']);
        setcookie('ct_cookies_test', json_encode($cookie_test_value), 0, '/');
    }

    static public function apbct_sfw_update($access_key = '') {
        if( empty( $access_key ) ){
            $access_key = trim(self::getXF()->options()->ct_apikey);
            if (empty($access_key)) {
                return false;
            }
        }
        $firewall = new Firewall(
            $access_key,
            DB::getInstance(),
            APBCT_TBL_FIREWALL_LOG
        );
        $firewall->setSpecificHelper( new CleantalkHelper() );
        $fw_updater = $firewall->getUpdater( APBCT_TBL_FIREWALL_DATA );
        if (defined('APBCT_SFW_DIRECT_UPDATE') && APBCT_SFW_DIRECT_UPDATE === true) {
            $result = $fw_updater->directUpdate();
            if (true !== $result) {
                \XF::logError('CleanTalk: SFW direct update failed: ' . isset($result['error']) ? $result['error'] : 'unknown reason');
            } else {
                \XF::logError('CleanTalk: SFW direct update finished. All OK.');
            }
            //do not handle directupdate fail
            return true;
        } else {
            $result = $fw_updater->update();
        }
        return $result;
    }

    static public function apbct_sfw_send_logs($access_key = '') {
        if( empty( $access_key ) ){
            $access_key = trim(self::getXF()->options()->ct_apikey);
            if (empty($access_key)) {
                return false;
            }
        }

        $firewall = new Firewall( $access_key, DB::getInstance(), APBCT_TBL_FIREWALL_LOG );
        $firewall->setSpecificHelper( new CleantalkHelper() );
        $result = $firewall->sendLogs();

        return true;
    }

    static public function ctRemoteCalls()
    {
        // Remote calls
        if( RemoteCalls::check() ) {
            $rc = new RemoteCalls( trim(self::getXF()->options()->ct_apikey) );
            $rc->perform();
        }
    }
    static public function sfwCheck() {
        $ct_key = trim(self::getXF()->options()->ct_apikey);

        $firewall = new Firewall(
            $ct_key,
            DB::getInstance(),
            APBCT_TBL_FIREWALL_LOG
        );

        $firewall->loadFwModule( new SFW(
            APBCT_TBL_FIREWALL_DATA,
            array(
                'sfw_counter'   => 0,
                'cookie_domain' => Server::get('HTTP_HOST'),
                'set_cookies'    => 1,
            )
        ) );

        $firewall->run();
    }
}
