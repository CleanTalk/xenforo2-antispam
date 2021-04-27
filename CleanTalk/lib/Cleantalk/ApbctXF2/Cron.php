<?php

namespace CleanTalk\ApbctXF2;

class Cron extends \CleanTalk\Common\Cron {

    public function saveTasks($tasks)
    {
        $cron_option_name = $this->cron_option_name;
        if (isset(\CleanTalk\ApbctXF2\Funcs::getXF()->options()->$cron_option_name)) {
           \CleanTalk\ApbctXF2\Funcs::getXF()->repository('XF:Option')->updateOption($cron_option_name, json_encode(array('last_start' => time(), 'tasks' => $tasks))); 
        }
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
        if (isset(\CleanTalk\ApbctXF2\Funcs::getXF()->options()->$cron_option_name)) {
            $cron = json_decode(\CleanTalk\ApbctXF2\Funcs::getXF()->options()->$cron_option_name,true);
            return (!empty($cron) && isset($cron['tasks'])) ? $cron['tasks'] : null;
        }
        return null;
    }

    /**
     * Save option with tasks
     *
     * @return int timestamp
     */
    public function getCronLastStart()
    {
        $cron_option_name = $this->cron_option_name;
        if (isset(\CleanTalk\ApbctXF2\Funcs::getXF()->options()->$cron_option_name)) {
            $cron = json_decode(\CleanTalk\ApbctXF2\Funcs::getXF()->options()->$cron_option_name,true);
            return (!empty($cron) && isset($cron['last_start'])) ? $cron['last_start']: 0;            
        }
        return 0;
    }

    /**
     * Save timestamp of running Cron.
     *
     * @return bool
     */
    public function setCronLastStart()
    {
        $cron_option_name = $this->cron_option_name;
        if (isset(\CleanTalk\ApbctXF2\Funcs::getXF()->options()->$cron_option_name)) {
            \CleanTalk\ApbctXF2\Funcs::getXF()->repository('XF:Option')->updateOption($cron_option_name, json_encode(array('last_start' => time(), 'tasks' => $this->getTasks())));
            return true;            
        }
        return false;
    }
}