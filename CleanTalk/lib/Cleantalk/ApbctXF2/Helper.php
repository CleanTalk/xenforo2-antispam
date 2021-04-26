<?php

namespace Cleantalk\ApbctXF2;

class Helper extends \Cleantalk\Common\Helper {

    /**
     * Get fw stats from the storage.
     *
     * @return array
     * @example array( 'firewall_updating' => false, 'firewall_updating_id' => md5(), 'firewall_update_percent' => 0, 'firewall_updating_last_start' => 0 )
     * @important This method must be overloaded in the CMS-based Helper class.
     */
    public static function getFwStats()
    {
        return array('firewall_updating_id' => \CleanTalk\ApbctXF2::getXF()->options()->firewall_updating_id, 'firewall_updating_last_start' => \CleanTalk\ApbctXF2::getXF()->options()->firewall_updating_last_start, 'firewall_update_percent' => \CleanTalk\ApbctXF2::getXF()->options()->firewall_update_percent);
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
        \CleanTalk\ApbctXF2::getXF()->repository('XF:Option')->updateOption('firewall_updating_id', isset($fw_stats['firewall_updating_id']) ? $fw_stats['firewall_updating_id'] : null);
        \CleanTalk\ApbctXF2::getXF()->repository('XF:Option')->updateOption('firewall_updating_last_start', isset($fw_stats['firewall_updating_last_start']) ? $fw_stats['firewall_updating_last_start'] : 0);
        \CleanTalk\ApbctXF2::getXF()->repository('XF:Option')->updateOption('firewall_update_percent', isset($fw_stats['firewall_update_percent']) ? $fw_stats['firewall_update_percent'] : 0);
    }

    /**
     * Implement here any actions after SFW updating finished.
     *
     * @return void
     */
    public static function SfwUpdate_DoFinisnAction()
    {
        \CleanTalk\ApbctXF2::getXF()->repository('XF:Option')->updateOption('sfw_last_update', time());
    }
}