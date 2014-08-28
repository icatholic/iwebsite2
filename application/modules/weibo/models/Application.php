<?php

class Weibo_Model_Application extends iWebsite_Plugin_Mongo
{

    protected $name = 'iWeibo_application';

    protected $dbName = 'weibo';

    public function getConfig()
    {
        $config = $this->findOne(array());
        return $config;
    }
    
    /**
     * 根据ID获取信息
     *
     * @param string $id
     * @return array
     */
    public function getInfoById($id)
    {
        $query = array();
        $query["_id"] = myMongoId($id);
        $info = $this->findOne($query);
        return $info;
    }
}