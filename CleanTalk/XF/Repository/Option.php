<?php
namespace CleanTalk\XF\Repository;

require_once \XF::getRootDirectory().'/src/addons/CleanTalk/CleantalkSFW.php';
require_once \XF::getRootDirectory().'/src/addons/CleanTalk/CleantalkHelper.php';

use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;
use CleanTalk\CleantalkSFW;
use CleanTalk\CleantalkHelper;

class Option extends \XF\Repository\Option
{
	public function updateOptions(array $values)
	{
		$options = parent::updateOptions($values);
		if ($values['ct_apikey'])
		{
			CleantalkHelper::api_method_send_empty_feedback($values['ct_apikey'], 'xenforo2-17');
			
			if ($values['ct_sfw'])
			{
				$sfw = new CleantalkSFW();
				$sfw->sfw_update($values['ct_apikey']);
				$sfw->send_logs($values['ct_apikey']);					
			}
		}
	}	
}