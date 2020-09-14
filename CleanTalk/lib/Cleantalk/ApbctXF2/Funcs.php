<?php

namespace CleanTalk\ApbctXF2;

require_once \XF::getRootDirectory().'/src/addons/CleanTalk/lib/Cleantalk/ApbctXF2/SFW.php';
require_once \XF::getRootDirectory().'/src/addons/CleanTalk/lib/Cleantalk/Common/Helper.php';

use CleanTalk\ApbctXF2\SFW as CleantalkSFW;
use CleanTalk\Common\Helper as CleantalkHelper;

class Funcs {

	private $app;

	public function __construct($app) {
		$this->app = $app;
	}
	
	public function ctSetCookie() {
        // Cookie names to validate
        $cookie_test_value = array(
            'cookies_names' => array(),
            'check_value' => trim($this->app->options()->ct_apikey),
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

	public function ctSFWUpdate($access_key) {

	    $sfw = new CleantalkSFW($access_key);

    	$file_url_hash = isset($_GET['file_url_hash']) ? urldecode($_GET['file_url_hash']) : null;   
    	$file_url_nums = isset($_GET['file_url_nums']) ? urldecode($_GET['file_url_nums']) : null;
    	$file_url_nums = isset($file_url_nums) ? explode(',', $file_url_nums) : null;

	    if( ! isset( $file_url_hash, $file_url_nums ) ){
      		$result = $sfw->sfw_update();

			return ! empty( $result['error'] )
				? $result
				: true;
    	} elseif( $file_url_hash && is_array( $file_url_nums ) && count( $file_url_nums ) ){

        	$result = $sfw->sfw_update($file_url_hash, $file_url_nums[0]);

        	if(empty($result['error'])){
            
          		array_shift($file_url_nums);  

          		if (count($file_url_nums)) {

		            CleantalkHelper::http__request(
		              (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://".$_SERVER['HTTP_HOST'], 
			              array(
			                'spbc_remote_call_token'  => md5($access_key),
			                'spbc_remote_call_action' => 'sfw_update__write_base',
			                'plugin_name'             => 'apbct',
			                'file_url_hash'           => $file_url_hash,
			                'file_url_nums'           => implode(',', $file_url_nums),
			              ),
			              array('get', 'async')
		            );              
				} else {
              		$this->app->repository('XF:Option')->updateOption('ct_sfw_last_check',time());
              		return $result;
          		}
        	} else {
        		return array('error' => 'ERROR_WHILE_INSERTING_SFW_DATA');       
        	} 	
    	}

    	return $result;
	}

	public function ctSFWSendLogs($access_key) {
		$sfw = new CleantalkSFW($access_key);
		$result = $sfw->send_logs();
		if (empty($result['error'])) {
			$this->app->repository('XF:Option')->updateOption('ct_sfw_last_send_log',time());
		}
	}

	public function ctRemoteCalls()
	{
		$remote_action = $_GET['spbc_remote_call_action'];

		$remote_calls_config = json_decode($this->app->options()->ct_remote_calls,true);

		if ($remote_calls_config && is_array($remote_calls_config))
		{
			if (array_key_exists($remote_action, $remote_calls_config))
			{
				if (time() - $remote_calls_config[$remote_action] > 10 || ($remote_action == 'sfw_update__write_base' && isset($_GET['file_url_hash'])))
				{
					$remote_calls_config[$remote_action] = time();
					$this->app->repository('XF:Option')->updateOption('ct_remote_calls',json_encode($remote_calls_config));
					if (strtolower($_GET['spbc_remote_call_token']) == strtolower(md5($this->app->options()->ct_apikey)))
					{
						// Close renew banner
						if ($remote_action == 'close_renew_banner')
						{
							die('OK');
							// SFW update
						}
						elseif ($remote_action == 'sfw_update')
						{
							$result = $this->ctSFWUpdate(trim($this->app->options()->ct_apikey));
							die(empty($result['error']) ? 'OK' : 'FAIL ' . json_encode(array('error' => $result['error_string'])));
							// SFW send logs
						}
						elseif ($remote_action == 'sfw_update__write_base')
						{
							$result = $this->ctSFWUpdate(trim($this->app->options()->ct_apikey));
							die(empty($result['error']) ? 'OK' : 'FAIL ' . json_encode(array('error' => $result['error_string'])));
						}
						elseif ($remote_action == 'sfw_send_logs')
						{
							$result = $this->ctSFWSendLogs(trim($this->app->options()->ct_apikey));
							die(empty($result['error']) ? 'OK' : 'FAIL ' . json_encode(array('error' => $result['error_string'])));
							// Update plugin
						}
						elseif ($remote_action == 'update_plugin')
						{
							//add_action('wp', 'apbct_update', 1);
						}
						else
							die('FAIL ' . json_encode(array('error' => 'UNKNOWN_ACTION_2')));
					}
					else
						die('FAIL ' . json_encode(array('error' => 'WRONG_TOKEN')));
				}
				else
					die('FAIL ' . json_encode(array('error' => 'TOO_MANY_ATTEMPTS')));
			}
			else
				die('FAIL ' . json_encode(array('error' => 'UNKNOWN_ACTION')));
		}
	}
	
}