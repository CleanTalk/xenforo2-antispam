<?php

namespace Cleantalk\Custom\StorageHandler;

class StorageHandler implements \Cleantalk\Common\StorageHandler\StorageHandler
{
    public function getSetting($setting_name)
    {
        $setting_value = \Cleantalk\Custom\Funcs::getXF()->options()->$setting_name;

        return json_decode($setting_value, true);
    }

    public function deleteSetting($setting_name)
    {
        try {
            $delete_result = \Cleantalk\Custom\Funcs::getXF()->repository('XF:Option')->updateOption($setting_name, '');

            \Cleantalk\Custom\Funcs::getXF()->repository('XF:Option')->rebuildOptionCache();
        } catch ( \Throwable $e ) {
            // Container may be locked at this stage of the request (e.g. friendly-url/error pages).
            return false;
        }

        return $delete_result;
    }

    public function saveSetting($setting_name, $setting_value)
    {
        if ( is_array($setting_value) ) {
            $setting_value = json_encode($setting_value);
        }

        try {
            $saving_result = \Cleantalk\Custom\Funcs::getXF()->repository('XF:Option')->updateOption($setting_name, $setting_value);

            \Cleantalk\Custom\Funcs::getXF()->repository('XF:Option')->rebuildOptionCache();
        } catch ( \Throwable $e ) {
            // Container may be locked at this stage of the request (e.g. friendly-url/error pages).
            return false;
        }

        return $saving_result;
    }

    public static function getUpdatingFolder()
    {
        return \XF::getRootDirectory() . '/' . \Cleantalk\Custom\Funcs::getXF()->config('internalDataPath') . '/temp/cleantalk_fw_files/';
    }

    public static function getJsLocation()
    {
        // TODO: Implement getJsLocation() method.
    }
}
