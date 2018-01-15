<?php

namespace CleanTalk\XF\Spam\Checker;
require_once \XF::getRootDirectory().'/src/addons/CleanTalk/Cleantalk.php';
require_once \XF::getRootDirectory().'/src/addons/CleanTalk/CleantalkHelper.php';
require_once \XF::getRootDirectory().'/src/addons/CleanTalk/CleantalkRequest.php';
require_once \XF::getRootDirectory().'/src/addons/CleanTalk/CleantalkResponse.php';
use Cleantalk\Cleantalk;
use Cleantalk\CleantalkRequest;
use Cleantalk\CleantalkResponse;
use Cleantalk\Helper;
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
		catch (\GuzzleHttp\Exception\RequestException $e) { $this->app()->logException($e, false, 'Cleantalk error: '); }
		catch (\InvalidArgumentException $e) { $this->app()->logException($e, false, 'Cleantalk service error: '); }

		$this->logDecision($decision);
	}

	protected function isSpam(\XF\Entity\User $user, $message, $extraParams)
	{
        $js_on = 0;
        if (isset($_POST['ct_checkjs']) && $_POST['ct_checkjs'] == date("Y"))
            $js_on = 1; 
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
                'page_set_timestamp' => $page_set_timestamp
            )
        ); 
        $ct = new Cleantalk();
        $ct_request = new CleantalkRequest();
        $ct_request->auth_key = trim($this->app->options()->ct_apikey);
        $ct_request->sender_email = $user->email;
        $ct_request->sender_nickname = $user->username;
        $ct_request->message = $message;
        $ct_request->sender_ip = $ct->cleantalk_get_real_ip();
        $ct_request->agent = 'xenforo2-15';
        $ct_request->js_on = $js_on;
        $ct_request->submit_time = time() - $page_set_timestamp;
        $ct_request->sender_info = $sender_info;
        $ct->work_url = 'http://moderate.cleantalk.org';
        $ct->server_url = 'http://moderate.cleantalk.org';
        $ct_result = $ct->isAllowMessage($ct_request);
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
}