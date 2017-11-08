<?php

namespace CleanTalk\XF\Service\User;
use CleanTalk\Cleantalk;
use CleanTalk\CleantalkRequest;

class Registration extends XFCP_Registration
{
	public function checkForSpam()
	{
		$user = $this->user;

		$userChecker = $this->app->spam()->userChecker();
		$userChecker->check($user);

		$decision = $userChecker->getFinalDecision();
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
       	$session_values = $this->app['session.public']->get('registration');
        $sender_info = json_encode($sender_info);   
        $ct = new Cleantalk();
        $ct_request = new CleantalkRequest();
        $ct_request->auth_key = trim($this->app->options()->ct_apikey);
        $ct_request->sender_email = $user->email;
        $ct_request->sender_nickname = $user->username;
        $ct_request->sender_ip = $ct->cleantalk_get_real_ip();
        $ct_request->agent = 'xenforo2-11';
        $ct_request->js_on = $js_on;
        $ct_request->submit_time = time() - (int)$session_values['time'];
        $ct_request->sender_info = $sender_info;
        $ct->work_url = 'http://moderate.cleantalk.org';
        $ct->server_url = 'http://moderate.cleantalk.org';
        $ct_result = $ct->isAllowUser($ct_request);
        if ($ct_result->errno == 0 && $ct_result->allow == 0)
        {
 			$decision ='cleantalk_spam';  
 			$reason = $ct_result->comment;      	
        }
		switch ($decision)
		{
			case 'denied':
				$user->rejectUser(\XF::phrase('spam_prevention_registration_rejected'));
				break;

			case 'moderated':
				$user->user_state = 'moderated';
				break;
			case 'cleantalk_spam':
				$user->error($reason);
				break;
		}
	}

}