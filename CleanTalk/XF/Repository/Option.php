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
		if (isset($values['ct_apikey']) && $values['ct_apikey'] != '')
		{
			CleantalkHelper::api_method_send_empty_feedback($values['ct_apikey'], 'xenforo2-18');
			
			if (isset($values['ct_sfw']) && intval($values['ct_sfw']) == 1)
			{
				$sfw = new CleantalkSFW();
				$sfw->sfw_update($values['ct_apikey']);
				$sfw->send_logs($values['ct_apikey']);	
				$this->repository('XF:Option')->updateOption('ct_sfw_last_check',time());
				$this->repository('XF:Option')->updateOption('ct_sfw_last_send_log',time());				
			}
		}
	}	
}