<?php

class Lottery_Model_Source extends iWebsite_Plugin_Mongo
{

    protected $name = 'iLottery_source';

    protected $dbName = 'lottery';
    
    protected $secondary = true;

    /**
     * 获取全部来源类型
     */
    public function getSource()
    {
        $rst = $this->findAll(array());
        return array_map(function ($row)
        {
            return $row['value'];
        }, $rst);
    }
}