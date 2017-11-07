<?php

namespace CleanTalk\XF\Service\Post;
use CleanTalk\Cleantalk;
use CleanTalk\CleantalkRequest;
use XF\Entity\Post;

class Preparer extends \XF\Service\AbstractService
{
	/**
	 * @var Post
	 */
	protected $post;

	protected $attachmentHash;

	protected $logIp = true;

	protected $quotedPosts = [];

	protected $mentionedUsers = [];

	public function __construct(\XF\App $app, Post $post)
	{
		parent::__construct($app);
		$this->post = $post;
	}

	public function getPost()
	{
		return $this->post;
	}

	public function logIp($logIp)
	{
		$this->logIp = $logIp;
	}

	public function getQuotedPosts()
	{
		return $this->quotedPosts;
	}

	public function getQuotedUserIds()
	{
		if (!$this->quotedPosts)
		{
			return [];
		}

		$postIds = array_keys($this->quotedPosts);
		$quotedUserIds = [];

		$db = $this->db();
		$postUserMap = $db->fetchPairs("
			SELECT post_id, user_id
			FROM xf_post
			WHERE post_id IN (" . $db->quote($postIds) .")
		");
		foreach ($postUserMap AS $postId => $userId)
		{
			if (!isset($this->quotedPosts[$postId]) || !$userId)
			{
				continue;
			}

			$quote = $this->quotedPosts[$postId];
			if (!isset($quote['member']) || $quote['member'] == $userId)
			{
				$quotedUserIds[] = $userId;
			}
		}

		return $quotedUserIds;
	}

	public function getMentionedUsers($limitPermissions = true)
	{
		if ($limitPermissions && $this->post)
		{
			/** @var \XF\Entity\User $user */
			$user = $this->post->User ?: $this->repository('XF:User')->getGuestUser();
			return $user->getAllowedUserMentions($this->mentionedUsers);
		}
		else
		{
			return $this->mentionedUsers;
		}
	}

	public function getMentionedUserIds($limitPermissions = true)
	{
		return array_keys($this->getMentionedUsers($limitPermissions));
	}

	public function setMessage($message, $format = true, $checkValidity = true)
	{
		$preparer = $this->getMessagePreparer($format);
		$this->post->message = $preparer->prepare($message, $checkValidity);
		$this->post->embed_metadata = $preparer->getEmbedMetadata();

		$this->quotedPosts = $preparer->getQuotesKeyed('post');
		$this->mentionedUsers = $preparer->getMentionedUsers();

		return $preparer->pushEntityErrorIfInvalid($this->post);
	}

	/**
	 * @param bool $format
	 *
	 * @return \XF\Service\Message\Preparer
	 */
	protected function getMessagePreparer($format = true)
	{
		/** @var \XF\Service\Message\Preparer $preparer */
		$preparer = $this->service('XF:Message\Preparer', 'post');
		if (!$format)
		{
			$preparer->disableAllFilters();
		}

		return $preparer;
	}

	public function setAttachmentHash($hash)
	{
		$this->attachmentHash = $hash;
	}

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
        $ct_request->agent = 'xenforo2-10';
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

	public function afterInsert()
	{
		if ($this->attachmentHash)
		{
			$this->associateAttachments($this->attachmentHash);
		}

		if ($this->logIp)
		{
			$ip = ($this->logIp === true ? $this->app->request()->getIp() : $this->logIp);
			$this->writeIpLog($ip);
		}

		$post = $this->post;
		$checker = $this->app->spam()->contentChecker();

		if ($post->isFirstPost())
		{
			$checker->logContentSpamCheck('thread', $post->thread_id);
			$checker->logSpamTrigger('thread', $post->thread_id);
		}
		else
		{
			$checker->logContentSpamCheck('post', $post->post_id);
			$checker->logSpamTrigger('post', $post->post_id);
		}
	}

	public function afterUpdate()
	{
		if ($this->attachmentHash)
		{
			$this->associateAttachments($this->attachmentHash);
		}

		$post = $this->post;
		$checker = $this->app->spam()->contentChecker();

		if ($post->isFirstPost())
		{
			$checker->logSpamTrigger('thread', $post->thread_id);
		}
		else
		{
			$checker->logSpamTrigger('post', $post->post_id);
		}
	}

	protected function associateAttachments($hash)
	{
		$post = $this->post;

		/** @var \XF\Service\Attachment\Preparer $inserter */
		$inserter = $this->service('XF:Attachment\Preparer');
		$associated = $inserter->associateAttachmentsWithContent($hash, 'post', $post->post_id);
		if ($associated)
		{
			$post->fastUpdate('attach_count', $post->attach_count + $associated);
		}
	}

	protected function writeIpLog($ip)
	{
		$post = $this->post;

		/** @var \XF\Repository\IP $ipRepo */
		$ipRepo = $this->repository('XF:Ip');
		$ipEnt = $ipRepo->logIp($post->user_id, $ip, 'post', $post->post_id);
		if ($ipEnt)
		{
			$post->fastUpdate('ip_id', $ipEnt->ip_id);
		}
	}
}