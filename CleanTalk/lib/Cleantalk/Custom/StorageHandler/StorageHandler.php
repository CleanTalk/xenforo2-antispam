<?php

namespace Cleantalk\Custom\StorageHandler;

class StorageHandler implements \Cleantalk\Common\StorageHandler\StorageHandler
{
    public function getSetting($setting_name)
    {
        // Read live state: the XenForo options cache is a request-local snapshot.
        $setting_value = \XF::db()->fetchOne(
            'SELECT option_value FROM xf_option WHERE option_id = ?',
            $setting_name
        );

        return $setting_value === false ? null : json_decode($setting_value, true);
    }

    public function deleteSetting($setting_name)
    {
        return $this->saveSetting($setting_name, '');
    }

    public function saveSetting($setting_name, $setting_value)
    {
        if ( is_array($setting_value) ) {
            $setting_value = json_encode($setting_value);
            if ( $setting_value === false ) {
                throw new \UnexpectedValueException('CleanTalk storage JSON encoding failed: ' . json_last_error_msg());
            }
        }

        // Runtime state must not schedule a rebuild of XenForo's global options cache.
        $updated = \XF::db()->update(
            'xf_option',
            array('option_value' => $setting_value),
            'option_id = ?',
            $setting_name
        );
        if ( !$updated && \XF::db()->fetchOne(
            'SELECT option_id FROM xf_option WHERE option_id = ?',
            $setting_name
        ) === false ) {
            throw new \InvalidArgumentException("Unknown option $setting_name");
        }

        return true;
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
