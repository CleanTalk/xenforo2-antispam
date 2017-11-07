<?php

namespace CleanTalk\XF\Pub\Controller;

use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\AbstractReply;

abstract class AbstractController extends \XF\Mvc\Controller
{
	protected function preDispatchType($action, ParameterBag $params)
	{
		$this->checkTfaRedirect();

		$this->assertCorrectVersion($action);
		$this->assertIpNotBanned();
		$this->assertNotBanned();
		$this->assertNotRejected($action);
		$this->assertNotDisabled($action);
		$this->assertCanonicalBaseUrl($action);
		$this->assertViewingPermissions($action);
		$this->assertBoardActive($action);
		$this->assertTfaRequirement($action);

		if ($this->isDiscouraged())
		{
			$this->discourage($action);
		}

		$this->preDispatchController($action, $params);
	}

	protected function preDispatchController($action, ParameterBag $params)
	{
	}

	protected function postDispatchType($action, ParameterBag $params, AbstractReply &$reply)
	{
		$this->postDispatchController($action, $params, $reply);

		$this->updateSessionActivity($action, $params, $reply);
	}

	protected function postDispatchController($action, ParameterBag $params, AbstractReply &$reply)
	{
	}

	protected function updateSessionActivity($action, ParameterBag $params, AbstractReply &$reply)
	{
		if ($this->request->getServer('HTTP_X_MOZ') == 'prefetch')
		{
			// never update the session activity for this; the user didn't see it
			return;
		}

		if ($this->canUpdateSessionActivity($action, $params, $reply, $viewState))
		{
			$session = $this->session();
			$session->set('cleantalk', [
			'ct_page_start_time' => \XF::$time
		]);
			$controller = $this->app->extension()->resolveExtendedClassToRoot($this);

			/** @var \XF\Repository\SessionActivity $activityRepo */
			$activityRepo = $this->repository('XF:SessionActivity');
			$activityRepo->updateSessionActivity(
				\XF::visitor()->user_id, $this->request->getIp(),
				$controller, $action, $params->params(), $viewState,
				isset($session['robot']) ? $session['robot'] : ''
			);
		}
	}

	protected function canUpdateSessionActivity($action, ParameterBag $params, AbstractReply &$reply, &$viewState)
	{
		// don't update session activity for an AJAX request
		if ($this->request->isXhr())
		{
			return false;
		}

		$viewState = 'error';

		switch (get_class($reply))
		{
			case 'XF\Mvc\Reply\Redirect':
			case 'XF\Mvc\Reply\Reroute':
				return false; // don't update anything, assume the next page will do it

			case 'XF\Mvc\Reply\Message':
			case 'XF\Mvc\Reply\View':
				$viewState = 'valid';
				break;
		}

		if ($reply->getResponseCode() >= 400)
		{
			$viewState = 'error';
		}

		return true;
	}

	public function checkTfaRedirect()
	{
		$session = $this->session();
		if ($session->tfaLoginRedirect)
		{
			unset($session->tfaLoginRedirect);

			if (\XF::visitor()->user_id || !$session->tfaLoginUserId)
			{
				return;
			}

			throw $this->exception($this->redirect($this->buildLink('login/two-step', null, [
				'_xfRedirect' => $this->request->getFullRequestUri(),
				'remember' => 1
			])));
		}
	}

	public function assertRegistrationRequired()
	{
		if (!\XF::visitor()->user_id)
		{
			throw $this->exception(
				$this->plugin('XF:Error')->actionRegistrationRequired()
			);
		}
	}

	public function assertIpNotBanned()
	{
		$bannedIps = $this->app()->container('bannedIps');
		$result = $this->getRequestIpConstraintCached($bannedIps, 'isIpBanned');
		if (is_array($result))
		{
			/** @var \XF\Repository\Banning $repo */
			$repo = $this->repository('XF:Banning');

			$matched = $repo->findIpMatchesByRange($result[0], $result[1])
				->where('match_type', 'banned');

			foreach ($matched->fetch() AS $match)
			{
				$match->fastUpdate('last_triggered_date', time());
			}
		}
		if ($result)
		{
			throw $this->exception(
				$this->plugin('XF:Error')->actionBannedIp()
			);
		}
	}

	public function assertNotBanned()
	{
		if (\XF::visitor()->is_banned)
		{
			throw $this->exception(
				$this->plugin('XF:Error')->actionBanned()
			);
		}
	}

	public function assertNotRejected($action)
	{
		if (\XF::visitor()->user_state == 'rejected')
		{
			throw $this->exception(
				$this->plugin('XF:Error')->actionRejected()
			);
		}
	}

	public function assertNotDisabled($action)
	{
		if (\XF::visitor()->user_state == 'disabled')
		{
			throw $this->exception(
				$this->plugin('XF:Error')->actionDisabled()
			);
		}
	}

	public function assertCanonicalBaseUrl($action)
	{
		if ($this->responseType != 'html')
		{
			return;
		}

		if (!$this->request->isGet() && !$this->request->isHead())
		{
			return;
		}

		$options = $this->options();
		if (!$options->boardUrlCanonical)
		{
			return;
		}

		$request = $this->request;
		$boardUrl = rtrim($options->boardUrl, '/');
		$fullBasePath = rtrim($request->getFullBasePath(), '/');

		if ($fullBasePath == $options->boardUrl)
		{
			// the URL is already canonical
			return;
		}

		$requestUri = $request->getFullRequestUri();

		if (strpos($requestUri, $fullBasePath) === 0)
		{
			$extendedPath = ltrim(substr($requestUri, strlen($fullBasePath)), '/');
			$newUrl = $boardUrl . '/' . $extendedPath;
			throw $this->exception($this->redirectPermanently($newUrl));
		}
	}

	public function assertViewingPermissions($action)
	{
		if (!\XF::visitor()->hasPermission('general', 'view'))
		{
			throw $this->exception($this->noPermission());
		}
	}

	public function assertBoardActive($action)
	{
		$options = $this->options();
		if (!$options->boardActive && !\XF::visitor()->is_admin)
		{
			throw $this->exception($this->message(new \XF\PreEscaped($options->boardInactiveMessage), 503));
		}
	}

	public function assertTfaRequirement($action)
	{
		$visitor = \XF::visitor();
		if ($visitor->user_id
			&& empty($visitor->Option->use_tfa)
			&& \XF::config('enableTfa')
			&& $visitor->hasPermission('general', 'requireTfa')
		)
		{
			throw $this->exception($this->message(\XF::phrase('you_must_enable_two_step_to_continue', [
				'link' => $this->buildLink('account/two-step')
			])));
		}
	}

	public function hasContentPendingApproval()
	{
		$pendingUntil = $this->session()->hasContentPendingUntil;
		return ($pendingUntil && $pendingUntil < \XF::$time);
	}

	protected function isDiscouraged()
	{
		$visitor = \XF::visitor();
		if ($visitor->user_id && $visitor->Option->is_discouraged)
		{
			return true;
		}
		else
		{
			$discouragedIps = $this->app()->container('discouragedIps');
			$result = $this->getRequestIpConstraintCached($discouragedIps, 'isIpDiscouraged');
			if (is_array($result))
			{
				/** @var \XF\Repository\Banning $repo */
				$repo = $this->repository('XF:Banning');

				$matched = $repo->findIpMatchesByRange($result[0], $result[1])
					->where('match_type', 'discouraged');

				foreach ($matched->fetch() AS $match)
				{
					$match->fastUpdate('last_triggered_date', time());
				}
			}
			return (bool)$result;
		}
	}

	protected $discourageChecked;

	/**
	 * Discourage the current visitor from remaining on the board by making theirs a bad experience.
	 *
	 * @param string $action
	 */
	protected function discourage($action)
	{
		if ($this->discourageChecked === true)
		{
			return;
		}
		$this->discourageChecked = true;

		$options = $this->app()->options();

		// random loading delay
		if ($options->discourageDelay['max'])
		{
			usleep(mt_rand($options->discourageDelay['min'], $options->discourageDelay['max']) * 1000000);
		}

		// random page redirect
		if ($options->discourageRedirectChance && mt_rand(0, 100) < $options->discourageRedirectChance)
		{
			header('Location: ' . ($options->discourageRedirectUrl ? $options->discourageRedirectUrl : $options->boardUrl));
			die();
		}

		// random blank page
		if ($options->discourageBlankChance && mt_rand(0, 100) < $options->discourageBlankChance)
		{
			die();
		}

		// randomly disable search
		if ($options->discourageSearchChance && mt_rand(0, 100) < $options->discourageSearchChance)
		{
			$options->enableSearch = false;
		}

		// increase flood check time
		if ($options->discourageFloodMultiplier > 1)
		{
			$options->floodCheckLength = $options->floodCheckLength * $options->discourageFloodMultiplier;
		}
	}

	/**
	 * Checks whether a IP constraint is matched. The value is cached in the session.
	 * The IP data should be an array with 2 keys:
	 *  - version: unique identifier of cache revision
	 *  - data: array of IPs to check (grouped by first byte)
	 *
	 * If the version differs, the cache value is ignored.
	 *
	 * @param array $ipData
	 * @param string $sessionKey Key to write to/read from in the session
	 *
	 * @return bool True if matched, false otherwise
	 */
	protected function getRequestIpConstraintCached(array $ipData, $sessionKey)
	{
		if (!$ipData || empty($ipData['data']))
		{
			return false;
		}

		$result = null;

		$session = $this->app()->session();
		$sessionValue = $session->{$sessionKey};
		if ($sessionValue
			&& isset($sessionValue['version'])
			&& isset($ipData['version'])
			&& $sessionValue['version'] == $ipData['version']
		)
		{
			$result = $sessionValue['result'];
		}

		if ($result === null)
		{
			$result = $this->ipMatch($this->getClientIps(), $ipData['data']);

			if ($session)
			{
				$session->{$sessionKey} = [
					'result' => $result,
					'version' => isset($ipData['version']) ? $ipData['version'] : 1
				];
			}

			return $result;
		}

		return $result;
	}

	public function assertNotFlooding($action, $floodingLimit = null)
	{
		$visitor = \XF::visitor();
		if ($visitor->hasPermission('general', 'bypassFloodCheck'))
		{
			return;
		}

		/** @var \XF\Service\FloodCheck $floodChecker */
		$floodChecker = $this->service('XF:FloodCheck');
		$timeRemaining = $floodChecker->checkFlooding($action, $visitor->user_id, $floodingLimit);
		if ($timeRemaining)
		{
			throw $this->exception($this->responseFlooding($timeRemaining));
		}
	}

	public function responseFlooding($floodSeconds)
	{
		return $this->error(\XF::phrase('must_wait_x_seconds_before_performing_this_action', ['count' => $floodSeconds]));
	}

	/**
	 * @return \XF\Repository\Ip
	 */
	protected function getIpRepo()
	{
		return $this->repository('XF:Ip');
	}

	protected static function getActivityDetailsForContent(
		array $activities, $phrase, $pluckParam, \Closure $dataLoader, $fallbackPhrase = null
	)
	{
		$ids = [];

		foreach ($activities AS $activity)
		{
			/** @var \XF\Entity\SessionActivity $activity */
			$id = $activity->pluckParam($pluckParam);
			if ($id)
			{
				$ids[$id] = $id;
			}
		}

		if ($ids)
		{
			$data = $dataLoader($ids);

		}
		else
		{
			$data = [];
		}

		$output = [];

		foreach ($activities AS $key => $activity)
		{
			/** @var \XF\Entity\SessionActivity $activity */
			$id = $activity->pluckParam($pluckParam);

			$content = $id && isset($data[$id]) ? $data[$id] : null;
			if ($content)
			{
				$output[$key] = [
					'description' => $phrase,
					'title' => $content['title'],
					'url' => $content['url'],
				];
			}
			else if ($id)
			{
				$output[$key] = $phrase;
			}
			else
			{
				$output[$key] = $fallbackPhrase ?: $phrase;
			}
		}

		return $output;
	}

	/**
	 * @param \XF\Entity\SessionActivity[] $activities
	 */
	public static function getActivityDetails(array $activities)
	{
		return false;
	}
}