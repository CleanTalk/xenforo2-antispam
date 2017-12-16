<?php

namespace CleanTalk\XF\Spam\Checker;
require_once $_SERVER['DOCUMENT_ROOT'].'/src/addons/CleanTalk/Cleantalk.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/src/addons/CleanTalk/CleantalkHelper.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/src/addons/CleanTalk/CleantalkRequest.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/src/addons/CleanTalk/CleantalkResponse.php';
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
		$refferrer = null;
        if (isset($_SERVER['HTTP_REFERER'])) {
            $refferrer = htmlspecialchars((string) $_SERVER['HTTP_REFERER']);
        }

        $user_agent = null;
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $user_agent = htmlspecialchars((string) $_SERVER['HTTP_USER_AGENT']);
        }
        $sender_info = array(
            'REFFERRER' => $refferrer,
            'post_url' => $refferrer,
            'USER_AGENT' => $user_agent
        );
        $js_on = 0;
        if (isset($_POST['ct_checkjs']) && $_POST['ct_checkjs'] == date("Y"))
            $js_on = 1; 
        $decision = null;
        $cookie_timestamp = (isset($_COOKIE['ct_timestamp']) ? $_COOKIE['ct_timestamp'] : 0);
        $sender_info = json_encode($sender_info);   
        $ct = new Cleantalk();
        $ct_request = new CleantalkRequest();
        $ct_request->auth_key = trim($this->app->options()->ct_apikey);
        $ct_request->sender_email = $user->email;
        $ct_request->sender_nickname = $user->username;
        $ct_request->message = $message;
        $ct_request->sender_ip = $ct->cleantalk_get_real_ip();
        $ct_request->agent = 'xenforo2-14';
        $ct_request->js_on = $js_on;
        $ct_request->submit_time = time() - $cookie_timestamp;
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