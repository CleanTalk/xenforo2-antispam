<?php

namespace Cleantalk\Custom\StorageHandler;

class StorageHandler implements \Cleantalk\Common\StorageHandler\StorageHandler
{
    public function getSetting($setting_name)
    {
        $setting_value =  \Cleantalk\ApbctXF2\Funcs::getXF()->options()->$setting_name;

        return json_decode($setting_value, true);
    }

    public function deleteSetting($setting_name)
    {
        return \Cleantalk\ApbctXF2\Funcs::getXF()->repository('XF:Option')->updateOption($setting_name, '');
    }

    public function saveSetting($setting_name, $setting_value)
    {
        if ( is_array($setting_value) ) {
            $setting_value = json_encode($setting_value);
        }
        $saving_result = \Cleantalk\ApbctXF2\Funcs::getXF()->repository('XF:Option')->updateOption($setting_name, $setting_value);
        \Cleantalk\ApbctXF2\Funcs::getXF()->repository('XF:Option')->rebuildOptionCache();

        return $saving_result;
    }

    public static function getUpdatingFolder()
    {
        return \XF::getRootDirectory() . '/' . \Cleantalk\ApbctXF2\Funcs::getXF()->config('internalDataPath') . '/temp/cleantalk_fw_files/';
    }

    public static function getJsLocation()
    {
        // TODO: Implement getJsLocation() method.
    }
}
