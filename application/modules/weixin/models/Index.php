<?php

class Weixin_Model_Index extends iWebsite_Plugin_Mongo
{

    protected $name = 'iWeixin_source';

    protected $dbName = 'weixin';

    private $_params = array();

    /**
     * 获取字段列表
     * 
     * @return array
     */
    public function getFields()
    {
        return array_keys($this->getSchema());
    }
}