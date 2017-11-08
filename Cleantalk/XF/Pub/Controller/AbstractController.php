<?php

namespace CleanTalk\XF\Pub\Controller;

use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\AbstractReply;

abstract class AbstractController extends XFCP_AbstractController
{
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
}