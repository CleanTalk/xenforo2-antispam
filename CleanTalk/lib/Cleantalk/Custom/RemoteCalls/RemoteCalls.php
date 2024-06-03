<?php

namespace Cleantalk\Custom\RemoteCalls;

class RemoteCalls extends \Cleantalk\Common\RemoteCalls\RemoteCalls
{
    const OPTION_NAME = 'ct_remote_calls';

    /**
     * SFW update
     *
     * @return string
     */
    public function action__sfw_update() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return \CleanTalk\Custom\Funcs::sfwUpdate($this->api_key);
    }

    /**
     * SFW send logs
     *
     * @return string
     */
    public function action__sfw_send_logs() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return \CleanTalk\Custom\Funcs::sfwSendLogs($this->api_key);
    }

    /**
     * Get available remote calls from the storage.
     *
     * @return array
     */
    protected function getAvailableRcActions()
    {
        $remote_calls = json_decode(\CleanTalk\Custom\Funcs::getXF()->options()->ct_remote_calls, true);
        $default_rc = array(
            'sfw_update' => array('last_call' => 0, 'cooldown' => 0),
            'sfw_send_logs' => array('last_call' => 0, 'cooldown' => self::COOLDOWN)
        );
        if ( $remote_calls && !empty($remote_calls) ) {
            return empty(array_diff_key($remote_calls, $default_rc)) && is_array(
                $remote_calls['sfw_update']
            ) ? $remote_calls : $default_rc;
        }
        return $default_rc;
    }
}
