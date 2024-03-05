<?php

namespace Cleantalk\Custom\Cron;

class Cron extends \Cleantalk\Common\Cron\Cron
{
    public function getDefaultTasks()
    {
        return [
            'sfw_update' => [
                'handler'   => APBCT_CRON_HANDLER__SFW_UPDATE,
                'next_call' => time() + 60,
                'period'    => 86400,
                'params'    => [],
            ],
            'sfw_send_logs' => [
                'handler'   => APBCT_CRON_HANDLER__SFW_LOGS,
                'next_call' => time() + 3600,
                'period'    => 3600,
                'params'    => [],
            ]
        ];
    }
}
