<?php

namespace Cleantalk\Custom\StorageHandler;

class StorageHandler implements \Cleantalk\Common\StorageHandler\StorageHandler
{
    public function getSetting($setting_name)
    {
        $setting_value =  \Cleantalk\ApbctXF2\Funcs::getXF()->options()->$setting_name;
        if ( $setting_value === '' ) {
            return null;
        }
        return $setting_value;
    }

    public function deleteSetting($setting_name)
    {
        \Cleantalk\ApbctXF2\Funcs::getXF()->repository('XF:Option')->updateOption($setting_name, '');
    }

    public function saveSetting($setting_name, $setting_value)
    {
        if ( is_array($setting_value) ) {
            $setting_value = json_encode($setting_value);
        }
        \Cleantalk\ApbctXF2\Funcs::getXF()->repository('XF:Option')->updateOption($setting_name, $setting_value);
    }

    public static function getUpdatingFolder()
    {
        return \Cleantalk\ApbctXF2\Funcs::getXF()->config('internalDataPath') . '/temp';
    }

    public static function getJsLocation()
    {
        // TODO: Implement getJsLocation() method.
    }
}
