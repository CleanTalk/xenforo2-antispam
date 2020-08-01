<?php

namespace CleanTalk\XF\Template;

require_once \XF::getRootDirectory().'/src/addons/CleanTalk/CleantalkSFW.php';

use XF\App;
use XF\Language;
use XF\Mvc\Entity\AbstractCollection;
use XF\Mvc\Router;
use XF\Util\Arr;
use CleanTalk\CleantalkSFW;

class Templater extends XFCP_Templater
{
	public function form($contentHtml, array $options)
	{
		$form = parent::form($contentHtml, $options);

        $input = '<input type="hidden" name="ct_checkjs" id="ct_checkjs" value="0" /><script>var date = new Date(); document.getElementById("ct_checkjs").value = date.getFullYear(); var d = new Date(), 
			ctTimeMs = new Date().getTime(),
			ctMouseEventTimerFlag = true, //Reading interval flag
			ctMouseData = "[",
			ctMouseDataCounter = 0;
		
		function ctSetCookie(c_name, value) {
			document.cookie = c_name + "=" + encodeURIComponent(value) + "; path=/";
		}
		
		ctSetCookie("ct_ps_timestamp", Math.floor(new Date().getTime()/1000));
		ctSetCookie("ct_fkp_timestamp", "0");
		ctSetCookie("ct_pointer_data", "0");
		ctSetCookie("ct_timezone", "0");
		setTimeout(function(){
			ctSetCookie("ct_timezone", d.getTimezoneOffset()/60*(-1));
		},1000);
		
		//Reading interval
		var ctMouseReadInterval = setInterval(function(){
				ctMouseEventTimerFlag = true;
			}, 150);
			
		//Writting interval
		var ctMouseWriteDataInterval = setInterval(function(){
				var ctMouseDataToSend = ctMouseData.slice(0,-1).concat("]");
				ctSetCookie("ct_pointer_data", ctMouseDataToSend);
			}, 1200);
		
		//Stop observing function
		function ctMouseStopData(){
			if(typeof window.addEventListener == "function")
				window.removeEventListener("mousemove", ctFunctionMouseMove);
			else
				window.detachEvent("onmousemove", ctFunctionMouseMove);
			clearInterval(ctMouseReadInterval);
			clearInterval(ctMouseWriteDataInterval);				
		}
		
		//Logging mouse position each 300 ms
		var ctFunctionMouseMove = function output(event){
			if(ctMouseEventTimerFlag == true){
				var mouseDate = new Date();
				ctMouseData += "[" + Math.round(event.pageY) + "," + Math.round(event.pageX) + "," + Math.round(mouseDate.getTime() - ctTimeMs) + "],";
				ctMouseDataCounter++;
				ctMouseEventTimerFlag = false;
				if(ctMouseDataCounter >= 100)
					ctMouseStopData();
			}
		}
		
		//Stop key listening function
		function ctKeyStopStopListening(){
			if(typeof window.addEventListener == "function"){
				window.removeEventListener("mousedown", ctFunctionFirstKey);
				window.removeEventListener("keydown", ctFunctionFirstKey);
			}else{
				window.detachEvent("mousedown", ctFunctionFirstKey);
				window.detachEvent("keydown", ctFunctionFirstKey);
			}
		}
		
		//Writing first key press timestamp
		var ctFunctionFirstKey = function output(event){
			var KeyTimestamp = Math.floor(new Date().getTime()/1000);
			ctSetCookie("ct_fkp_timestamp", KeyTimestamp);
			ctKeyStopStopListening();
		}

		if(typeof window.addEventListener == "function"){
			window.addEventListener("mousemove", ctFunctionMouseMove);
			window.addEventListener("mousedown", ctFunctionFirstKey);
			window.addEventListener("keydown", ctFunctionFirstKey);
		}else{
			window.attachEvent("onmousemove", ctFunctionMouseMove);
			window.attachEvent("mousedown", ctFunctionFirstKey);
			window.attachEvent("keydown", ctFunctionFirstKey);
		}</script>';

        $form = str_replace('</form>', $input . '</form>', $form);

        return $form;
	}

	public function renderTemplate($template, array $params = [], $addDefaultParams = true, \XF\Template\ExtensionSet $extensionOverrides = null)
	{
		$output = parent::renderTemplate($template, $params, $addDefaultParams);
		static $show_flag = true;

		if ($show_flag)
		{
			$show_flag = false;

			if (!headers_sent())
				$this->ctSetCookie();

			if (isset($_GET['spbc_remote_call_token'], $_GET['spbc_remote_call_action'], $_GET['plugin_name']) && in_array($_GET['plugin_name'], array('antispam', 'anti-spam', 'apbct')))
				$this->ctRemoteCalls();

			if ($this->app->options()->ct_sfw && $_SERVER["REQUEST_METHOD"] == 'GET' && $_SERVER['SCRIPT_NAME'] !== '/admin.php')
			{
			   	$is_sfw_check = true;
				$sfw = new CleantalkSFW();
				$sfw->ip_array = (array)CleantalkSFW::ip_get(array('real'), true);	
					
	            foreach($sfw->ip_array as $key => $value)
	            {
			        if(isset($_COOKIE['ct_sfw_pass_key']) && $_COOKIE['ct_sfw_pass_key'] == md5($value . trim($this->app->options()->ct_apikey)))
			        {
			          $is_sfw_check=false;
			          if(isset($_COOKIE['ct_sfw_passed']))
			          {
			            @setcookie ('ct_sfw_passed'); //Deleting cookie
			            $sfw->sfw_update_logs($value, 'passed');
			          }
			        }
		      	} unset($key, $value);	

				if($is_sfw_check)
				{
					$sfw->check_ip();
					if($sfw->result)
					{
						$sfw->sfw_update_logs($sfw->blocked_ip, 'blocked');
						$sfw->sfw_die(trim($this->app->options()->ct_apikey));
					}
				}

				if (time() - $this->app->options()->ct_sfw_last_send_log > $this->app->options()->ct_sfw_send_log_interval)
				{
					$sfw->send_logs(trim($this->app->options()->ct_apikey));
					$this->app->repository('XF:Option')->updateOption('ct_sfw_last_send_log',time());
				}

				if (time() - $this->app->options()->ct_sfw_last_check > $this->app->options()->ct_sfw_check_interval)
				{
					$sfw->sfw_update(trim($this->app->options()->ct_apikey));
					$this->app->repository('XF:Option')->updateOption('ct_sfw_last_check',time());
				}	      				
			}
		}
		
		if ($this->app->options()->ct_footerlink)
		{
			$footer = "<li><div id='cleantalk_footer_link' style='width:100%;margin-right:250px;'><a href='https://cleantalk.org/xenforo-antispam-addon'>Anti-spam by CleanTalk</a> for Xenforo!</div></li>";
			$output = str_replace('<ul class="p-footer-linkList">', '<ul class="p-footer-linkList">' . $footer, $output);			
		}


		return $output;
	}

	protected function ctSetCookie()
	{
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

	protected function ctRemoteCalls()
	{
		$remote_action = $_GET['spbc_remote_call_action'];

		$remote_calls_config = json_decode($this->app->options()->ct_remote_calls,true);

		if ($remote_calls_config && is_array($remote_calls_config))
		{
			if (array_key_exists($remote_action, $remote_calls_config))
			{
				if (time() - $remote_calls_config[$remote_action] > 10)
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
							$sfw = new CleantalkSFW();
							$result = $sfw->sfw_update(trim($this->app->options()->ct_apikey));
							die(empty($result['error']) ? 'OK' : 'FAIL ' . json_encode(array('error' => $result['error_string'])));
							// SFW send logs
						}
						elseif ($remote_action == 'sfw_send_logs')
						{
							$sfw = new CleantalkSFW();
							$result = $sfw->send_logs(trim($this->app->options()->ct_apikey));
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