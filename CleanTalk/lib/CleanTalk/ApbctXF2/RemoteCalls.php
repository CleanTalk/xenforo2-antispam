<?php

namespace CleanTalk\ApbctXF2;

class RemoteCalls extends \CleanTalk\Common\RemoteCalls {
    /**
     * SFW update
     *
     * @return string
     */
    public function action__sfw_update()
    {
        return \CleanTalk\ApbctXF2\Funcs::apbct_sfw_update( $this->api_key );
    }

    /**
     * SFW send logs
     *
     * @return string
     */
    public function action__sfw_send_logs()
    {
        return \CleanTalk\ApbctXF2\Funcs::apbct_sfw_send_logs( $this->api_key );
    }

    public function action__sfw_update__write_base()
    {
        return \CleanTalk\ApbctXF2\Funcs::apbct_sfw_update( $this->api_key );
    }
    /**
     * Get available remote calls from the storage.
     *
     * @return array
     */
    protected function getAvailableRcActions()
    {
        $remote_calls = json_decode(\CleanTalk\ApbctXF2\Funcs::getXF()->options()->ct_remote_calls, true);
        $default_rc = array('close_renew_banner' => array('last_call' => 0, 'cooldown' => self::COOLDOWN), 'sfw_update' => array('last_call' => 0, 'cooldown' => self::COOLDOWN), 'sfw_send_logs' => array('last_call' => 0, 'cooldown' => self::COOLDOWN), 'sfw_update__write_base' => array('last_call' => 0, 'cooldown' => 0));
        if ($remote_calls && !empty($remote_calls)) {
            return empty(array_diff_key($remote_calls, $default_rc)) && is_array($remote_calls['sfw_update']) ? $remote_calls : $default_rc;
        }
        return $default_rc;
    }

    /**
     * Set last call timestamp and save it to the storage.
     *
     * @param array $action
     * @return void
     */
    protected function setLastCall( $actions )
    {
        $remote_calls = $this->getAvailableRcActions();
        if ( isset($remote_calls) ) {
            foreach ( $remote_calls as $call => &$stats ) {
                if ( isset($actions[$call]['last_call']) ) {
                    $stats['last_call'] = $actions[$call]['last_call'];
                }
            }
            unset($stats);
        }

        \CleanTalk\ApbctXF2\Funcs::getXF()->repository('XF:Option')->updateOption('ct_remote_calls', json_encode($remote_calls));
    }
}