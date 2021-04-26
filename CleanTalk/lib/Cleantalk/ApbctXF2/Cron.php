<?php

namespace Cleantalk\ApbctXF2;

class Cron extends \Cleantalk\Common\Cron {

    public function saveTasks($tasks)
    {
        \CleanTalk\ApbctXF2::getXF()->repository('XF:Option')->updateOption($this->cron_option_name, json_encode(array('last_start' => time(), 'tasks' => $tasks)));
    }

    /**
     * Getting all tasks
     *
     * @return array
     */
    public function getTasks()
    {
        // TODO: Implement getTasks() method.
        $cron_option_name = $this->cron_option_name;
        $cron = json_decode(\CleanTalk\ApbctXF2::getXF()->options()->$cron_option_name,true);
        return (!empty($cron) && isset($cron['tasks'])) ? $cron['tasks'] : null;
    }

    /**
     * Save option with tasks
     *
     * @return int timestamp
     */
    public function getCronLastStart()
    {
        $cron_option_name = $this->cron_option_name;
        $cron = json_decode(\CleanTalk\ApbctXF2::getXF()->options()->$cron_option_name,true);
        return (!empty($cron) && isset($cron['last_start'])) ? $cron['last_start']: 0;
    }

    /**
     * Save timestamp of running Cron.
     *
     * @return bool
     */
    public function setCronLastStart()
    {
        \CleanTalk\ApbctXF2::getXF()->repository('XF:Option')->updateOption($this->cron_option_name, json_encode(array('last_start' => time(), 'tasks' => $this->getTasks())));
        return true;
    }
}