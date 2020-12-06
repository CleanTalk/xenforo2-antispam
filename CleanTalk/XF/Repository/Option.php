<?php
namespace CleanTalk\XF\Repository;

require_once \XF::getRootDirectory().'/src/addons/CleanTalk/lib/Cleantalk/Common/API.php';
require_once \XF::getRootDirectory().'/src/addons/CleanTalk/lib/Cleantalk/ApbctXF2/Funcs.php';

use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;
use CleanTalk\Common\API as CleantalkAPI;
use CleanTalk\ApbctXF2\Funcs as CleantalkFuncs;

class Option extends XFCP_Option
{
	public function updateOptions(array $values)
	{
		$options = parent::updateOptions($values);

		$plugin_version = $this->app()->addOnManager()->getById('CleanTalk')->getJsonVersion();
		if (isset($values['ct_apikey']) && $values['ct_apikey'] != '')
		{
			CleantalkAPI::method__send_empty_feedback($values['ct_apikey'], 'xenforo2-' . $plugin_version['version_id']);
			
			if (isset($values['ct_sfw']) && intval($values['ct_sfw']) == 1)
			{
				$remote_calls_config = json_decode($this->app()->options()->ct_remote_calls,true);
				$remote_calls_config['sfw_update'] = 0;
				parent::updateOption('ct_remote_calls',json_encode($remote_calls_config));
				$funcs = new CleantalkFuncs($this->app());	
				$funcs->ctSFWUpdate($values['ct_apikey']);
				$funcs->ctSFWSendLogs($values['ct_apikey']);		
			}
		}
	}	
}