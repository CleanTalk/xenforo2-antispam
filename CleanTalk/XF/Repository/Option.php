<?php
namespace CleanTalk\XF\Repository;

require_once \XF::getRootDirectory().'/src/addons/CleanTalk/lib/autoload.php';

use CleanTalk\Antispam\Cleantalk;
use CleanTalk\Antispam\CleantalkRequest;
use Cleantalk\ApbctXF2\Funcs as CleantalkFuncs;
use Cleantalk\Common\Api\Api;

class Option extends \XF\Repository\Option
{
	public function updateOptions(array $values)
	{
		parent::updateOptions($values);
		$plugin_version = $this->app()->addOnManager()->getById('CleanTalk')->getJsonVersion();
        $ct_access_key = isset($values['ct_apikey']) ? $values['ct_apikey'] : '';
        if ( $this->checkAccessKey($ct_access_key) )
        {
            $this->sendEmptyFeedback($ct_access_key, 'xenforo2-' . $plugin_version['version_id']);

            if (isset($values['ct_sfw']) && intval($values['ct_sfw']) == 1)
            {
                CleantalkFuncs::sfwUpdate($ct_access_key);
                CleantalkFuncs::sfwSendLogs($ct_access_key);
            }
        }
    }

    /**
     * Check the key and set an error on options if is incorrect.
     * @param $ct_access_key
     * @return bool
     */
    private function checkAccessKey($ct_access_key){
        $key_error = '';
        if ( !empty($ct_access_key) )
        {
            $site_url = $_SERVER['HTTP_HOST'];
            //take a notice_paid_till result
            $npt_result = Api::methodNoticePaidTill($ct_access_key,$site_url);
            if ( !$npt_result ){
                $key_error = 'Cannot validate the access key. Check if cURL support is enabled.';
            }
            if ( !empty($npt_result['error_message']) ){
                //error message if provided
                $key_error = $npt_result['error_message'];
            } elseif (isset($npt_result['valid']) && $npt_result['valid'] == '0'){
                //valid flag
                $key_error = 'Access key is invalid.';
            } elseif (isset($npt_result['moderate']) && $npt_result['moderate'] == '0'){
                $key_error = 'Access key is inactive. Check your account status.';
            }
        } else {
            // empty key
            $key_error = 'Access key is not set.';
        }

        parent::updateOption('ct_apikey_error', $key_error);

        return empty($key_error) ? true : false;
    }

    private function sendEmptyFeedback($ct_access_key, $string)
    {
        $ct_request = new CleantalkRequest(array(
            // General
            'auth_key' => $ct_access_key,
            // Additional
            'feedback' => 0 . ':' . $string,
        ));

        $ct = new Cleantalk();
        $ct->server_url = $ct->work_url = CleantalkFuncs::getXF()->options()->ct_server_url;
        $res = $ct->sendFeedback($ct_request);
    }
}
