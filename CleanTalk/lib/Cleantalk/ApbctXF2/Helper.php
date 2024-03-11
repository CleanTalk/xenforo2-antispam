<?php

namespace CleanTalk\ApbctXF2;

class Helper extends \CleanTalk\Common\Helper {

    /**
     * Get fw stats from the storage.
     *
     * @return array
     * @example array( 'firewall_updating' => false, 'firewall_updating_id' => md5(), 'firewall_update_percent' => 0, 'firewall_updating_last_start' => 0 )
     * @important This method must be overloaded in the CMS-based Helper class.
     */
    public static function getFwStats()
    {
        return array('firewall_updating_id' =>  isset(\CleanTalk\ApbctXF2\Funcs::getXF()->options()->firewall_updating_id) ? \CleanTalk\ApbctXF2\Funcs::getXF()->options()->firewall_updating_id : null, 'firewall_updating_last_start' => isset(\CleanTalk\ApbctXF2\Funcs::getXF()->options()->firewall_updating_last_start) ? \CleanTalk\ApbctXF2\Funcs::getXF()->options()->firewall_updating_last_start : 0, 'firewall_update_percent' => isset(\CleanTalk\ApbctXF2\Funcs::getXF()->options()->firewall_update_percent) ? \CleanTalk\ApbctXF2\Funcs::getXF()->options()->firewall_update_percent : 0);
    }

    /**
     * Save fw stats on the storage.
     *
     * @param array $fw_stats
     * @return bool
     * @important This method must be overloaded in the CMS-based Helper class.
     */
    public static function setFwStats( $fw_stats )
    {
        if (isset(\CleanTalk\ApbctXF2\Funcs::getXF()->options()->firewall_updating_id)) {
            \CleanTalk\ApbctXF2\Funcs::getXF()->repository('XF:Option')->updateOption('firewall_updating_id', isset($fw_stats['firewall_updating_id']) ? $fw_stats['firewall_updating_id'] : null);
        }
        if (isset(\CleanTalk\ApbctXF2\Funcs::getXF()->options()->firewall_updating_last_start)) {
            \CleanTalk\ApbctXF2\Funcs::getXF()->repository('XF:Option')->updateOption('firewall_updating_last_start', isset($fw_stats['firewall_updating_last_start']) ? $fw_stats['firewall_updating_last_start'] : 0);
        }
        if (isset(\CleanTalk\ApbctXF2\Funcs::getXF()->options()->firewall_update_percent)) {
            \CleanTalk\ApbctXF2\Funcs::getXF()->repository('XF:Option')->updateOption('firewall_update_percent', isset($fw_stats['firewall_update_percent']) ? $fw_stats['firewall_update_percent'] : 0);
        }
    }

    /**
     * Implement here any actions after SFW updating finished.
     *
     * @return void
     */
    public static function SfwUpdate_DoFinisnAction()
    {
        \CleanTalk\ApbctXF2\Funcs::getXF()->repository('XF:Option')->updateOption('ct_sfw_last_check', time());
    }

    /**
     * Function convert from UTF8
     *
     * @param array|object|string $obj
     * @param string              $data_codepage
     *
     * @return mixed (array|object|string)
     */
    public static function fromUTF8($obj, $data_codepage = null)
    {
        // Array || object
        if (is_array($obj) || is_object($obj)) {
            foreach ($obj as $_key => &$val) {
                $val = self::fromUTF8($val, $data_codepage);
            }
            unset($val);
            //String
        } else {
            if ($data_codepage !== null && preg_match('//u', $obj)) {
                if ( function_exists('mb_convert_encoding') ) {
                    $obj = mb_convert_encoding($obj, $data_codepage, 'UTF-8');
                } elseif (version_compare(phpversion(), '8.3', '<')) {
                    $obj = @utf8_decode($obj);
                }
            }
        }

        return $obj;
    }
}
