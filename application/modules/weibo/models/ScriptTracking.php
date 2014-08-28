<?php

class Weibo_Model_ScriptTracking extends iWebsite_Plugin_Mongo
{

    protected $name = 'iWeibo_script_tracking';

    protected $dbName = 'weibo';

    /**
     * 记录执行时间
     *
     * @param string $type            
     * @param float $start_time            
     * @param float $end_time            
     * @param string $who            
     * @return
     *
     *
     */
    public function record($type, $start_time, $end_time, $who)
    {
        $datas = array(
            'who' => $who,
            'type' => $type,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'execute_time' => abs($end_time - $start_time)
        );
        
        return $this->insert($datas);
    }
}