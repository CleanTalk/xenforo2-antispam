<?php

namespace CleanTalk\XF\Service\Post;
use CleanTalk\Cleantalk;
use CleanTalk\CleantalkRequest;
use XF\Entity\Post;

class Preparer extends XFCP_Preparer
{

	public function checkForSpam()
	{
		$post = $this->post;
		$thread = $this->post->Thread;

		/** @var \XF\Entity\User $user */
		$user = $post->User ?: $this->repository('XF:User')->getGuestUser($post->username);

		if ($post->isFirstPost())
		{
			$message = $thread->title . "\n" . $post->message;
			$contentType = 'thread';
		}
		else
		{
			$message = $post->message;
		 	$contentType = 'post';
		}

		$checker = $this->app->spam()->contentChecker();
		$checker->check($user, $message, [
			'permalink' => $this->app->router('public')->buildLink('canonical:threads', $thread),
			'content_type' => $contentType
		]);

		$decision = $checker->getFinalDecision();
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
        $session_values = $this->app['session.public']->get('cleantalk');
        $sender_info = json_encode($sender_info);   
        $ct = new Cleantalk();
        $ct_request = new CleantalkRequest();
        $ct_request->auth_key = trim($this->app->options()->ct_apikey);
        $ct_request->sender_email = $user->email;
        $ct_request->sender_nickname = $user->username;
        $ct_request->message = $post->Thread->title."\n".$post->message;
        $ct_request->sender_ip = $ct->cleantalk_get_real_ip();
        $ct_request->agent = 'xenforo2-11';
        $ct_request->js_on = $js_on;
        $ct_request->submit_time = time() - (int)$session_values['ct_page_start_time'];
        $ct_request->sender_info = $sender_info;
        $ct->work_url = 'http://moderate.cleantalk.org';
        $ct->server_url = 'http://moderate.cleantalk.org';
        $ct_result = $ct->isAllowMessage($ct_request);
        if ($ct_result->errno == 0 && $ct_result->allow == 0)
        {
 			$decision ='cleantalk_spam';  
 			$reason = $ct_result->comment;      	
        }
		switch ($decision)
		{
			case 'moderated':

				if ($post->isFirstPost())
				{
					$thread->discussion_state = 'moderated';
				}
				else
				{
					$post->message_state = 'moderated';
				}
				break;

			case 'denied':
				$checker->logSpamTrigger($post->isFirstPost() ? 'thread' : 'post', null);
				$post->error(\XF::phrase('your_content_cannot_be_submitted_try_later'));
				break;
			case 'cleantalk_spam':
				$post->error($reason);
				break;
		}
	}
}