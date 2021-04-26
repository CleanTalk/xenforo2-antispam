<?php
namespace CleanTalk\XF\Repository;

require_once \XF::getRootDirectory().'/src/addons/CleanTalk/lib/autoload.php';

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
				CleantalkFuncs::apbct_sfw_update($values['ct_apikey']);
				CleantalkFuncs::apbct_sfw_send_logs($values['ct_apikey']);		
			}
		}
	}	
}