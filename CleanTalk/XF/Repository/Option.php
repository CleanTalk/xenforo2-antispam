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
		parent::updateOptions($values);
		$plugin_version = $this->app()->addOnManager()->getById('CleanTalk')->getJsonVersion();

        if ($this->checkAccessKey($values['ct_apikey']))
        {
            CleantalkAPI::method__send_empty_feedback($values['ct_apikey'], 'xenforo2-' . $plugin_version['version_id']);

            if (isset($values['ct_sfw']) && intval($values['ct_sfw']) == 1)
            {
                CleantalkFuncs::apbct_sfw_update($values['ct_apikey']);
                CleantalkFuncs::apbct_sfw_send_logs($values['ct_apikey']);
            }
        }
    }

    /**
     * Check the key and set an error on options if is incorrect.
     * @param $ct_apikey
     * @return bool
     */
    private function checkAccessKey($ct_apikey){
        $key_error = '';
        if (isset($ct_apikey) && $ct_apikey != '')
        {
            $site_url = $_SERVER['HTTP_HOST'];
            //take a notice_paid_till result
            $npt_result = CleantalkAPI::method__notice_paid_till($ct_apikey,$site_url);
            if ( !$npt_result ){
                $key_error = 'Cannot validate the access key. Check if cURL support is enabled.';
            }
            if ( !empty($npt_result['error_message']) ){
                //error message if provided
                $key_error = $npt_result['error_message'];
            } elseif (isset($npt_result['valid']) && $npt_result['valid'] == '0'){
                //valid flag
                $key_error = 'Access key is invalid.';
            }
        } else {
            // empty key
            $key_error = 'Access key is not set.';
        }

        parent::updateOption('ct_apikey_error', $key_error);

        return empty($key_error) ? true : false;
    }
}