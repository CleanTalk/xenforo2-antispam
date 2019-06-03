<?php
namespace CleanTalk\XF\Spam\Checker;

require_once \XF::getRootDirectory().'/src/addons/CleanTalk/Cleantalk.php';
require_once \XF::getRootDirectory().'/src/addons/CleanTalk/CleantalkHelper.php';
require_once \XF::getRootDirectory().'/src/addons/CleanTalk/CleantalkRequest.php';
require_once \XF::getRootDirectory().'/src/addons/CleanTalk/CleantalkResponse.php';

use CleanTalk\Cleantalk;
use CleanTalk\CleantalkRequest;
use CleanTalk\CleantalkResponse;
use CleanTalk\CleantalkHelper;

class CleanTalkContent extends \XF\Spam\Checker\AbstractProvider implements \XF\Spam\Checker\ContentCheckerInterface
{
	protected function getType()
	{
		return 'CleanTalkContent';
	}

	public function check(\XF\Entity\User $user, $message, array $extraParams = [])
	{
		$decision = 'allowed';

		try
		{
			$isSpam = $this->isSpam($user, $message, $extraParams);
			if ($isSpam['decision'])
			{
				$decision = 'denied';
				$ct_matches[] = "Reason: ".$isSpam['reason'];
				$this->logDetail('cleantalk_matched_x', [
					'matches' => implode(', ', $ct_matches)
				]);
			}
		}
		catch (\GuzzleHttp\Exception\RequestException $e) { $this->app()->logException($e, false, 'CleanTalk error: '); }
		catch (\InvalidArgumentException $e) { $this->app()->logException($e, false, 'CleanTalk service error: '); }

		$this->logDecision($decision);
	}

	protected function isSpam(\XF\Entity\User $user, $message, $extraParams)
	{
        $decision = null;

        $page_set_timestamp = (isset($_COOKIE['ct_ps_timestamp']) ? $_COOKIE['ct_ps_timestamp'] : 0);
        $js_timezone = (isset($_COOKIE['ct_timezone']) ? $_COOKIE['ct_timezone'] : '');
        $first_key_timestamp = (isset($_COOKIE['ct_fkp_timestamp']) ? $_COOKIE['ct_fkp_timestamp'] : '');
        $pointer_data = (isset($_COOKIE['ct_pointer_data']) ? json_decode($_COOKIE['ct_pointer_data']) : '');

        $sender_info = json_encode(
            array(
                'REFFERRER' => (isset($_SERVER['HTTP_REFERER']))?htmlspecialchars((string) $_SERVER['HTTP_REFERER']):null,
                'post_url' => (isset($_SERVER['HTTP_REFERER']))?htmlspecialchars((string) $_SERVER['HTTP_REFERER']):null,
                'USER_AGENT' => (isset($_SERVER['HTTP_USER_AGENT']))?htmlspecialchars((string) $_SERVER['HTTP_USER_AGENT']):null,
                'js_timezone' => $js_timezone,
                'mouse_cursor_positions' => $pointer_data,
                'key_press_timestamp' => $first_key_timestamp,
                'page_set_timestamp' => $page_set_timestamp,
                'cookies_enabled' => $this->ctCookiesTest(),
                'REFFERRER_PREVIOUS' => isset($_COOKIE['ct_prev_referer']) ? $_COOKIE['ct_prev_referer'] : null,                
            )
        ); 

        $ct = new Cleantalk();
        $ct->server_url = $this->app->options()->ct_server_url;
        $ct->work_url = $this->app->options()->ct_work_url;
        $ct->server_ttl = $this->app->options()->ct_server_ttl;
        $ct->server_changed = $this->app->options()->ct_server_changed;
                
        $ct_request = new CleantalkRequest();
        $ct_request->auth_key = $this->getApiKey();
        $ct_request->sender_email = $user->email;
        $ct_request->sender_nickname = $user->username;
        $ct_request->message = $message;
        $ct_request->sender_ip = CleantalkHelper::ip_get(array('real'), false);
        $ct_request->x_forwarded_for = CleantalkHelper::ip_get(array('x_forwarded_for'), false);
        $ct_request->x_real_ip       = CleantalkHelper::ip_get(array('x_real_ip'), false);
        $ct_request->agent = 'xenforo2-19';
        $ct_request->js_on = (isset($_POST['ct_checkjs']) && $_POST['ct_checkjs'] == date("Y")) ? 1 : 0;
        $ct_request->submit_time = time() - intval($page_set_timestamp);
        $ct_request->sender_info = $sender_info;
        $ct_result = $ct->isAllowMessage($ct_request);

        //Set fastest server
        if ($ct->server_change)
        {
            $this->app->repository('XF:Option')->updateOption('ct_work_url',$ct->work_url);
            $this->app->repository('XF:Option')->updateOption('ct_server_ttl',$ct->server_ttl); 
            $this->app->repository('XF:Option')->updateOption('ct_server_changed',time());                       
        }

        if ($ct_result->errno == 0 && $ct_result->allow == 0)
        {
        	$decision['decision'] = true;
        	$decision['reason'] = $ct_result->comment;   	
        }
        
        return $decision;
	}

	public function submitHam($contentType, $contentIds)
	{
		foreach ($this->getContentSpamCheckParams($contentType, $contentIds) AS $contentId => $params)
		{
			if ($params)
			{
				$this->_submitHam($params);
			}
		}
	}

	public function submitSpam($contentType, $contentIds)
	{
		foreach ($this->getContentSpamCheckParams($contentType, $contentIds) AS $contentId => $params)
		{
			if ($params)
			{
				$this->_submitSpam($params);
			}
		}
	}

    protected function ctCookiesTest()
    {   
        if(isset($_COOKIE['ct_cookies_test'])){
            
            $cookie_test = json_decode(stripslashes($_COOKIE['ct_cookies_test']), true);
            
            $check_srting = $this->getApiKey();
            foreach($cookie_test['cookies_names'] as $cookie_name){
                $check_srting .= isset($_COOKIE[$cookie_name]) ? $_COOKIE[$cookie_name] : '';
            } unset($cokie_name);
            
            if($cookie_test['check_value'] == md5($check_srting)){
                return 1;
            }else{
                return 0;
            }
        }else{
            return null;
        }
    }  

    protected function getApiKey()
    {
        return trim($this->app->options()->ct_apikey);
    }        	
}