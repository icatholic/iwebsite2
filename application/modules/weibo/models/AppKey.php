<?php

class Weibo_Model_AppKey extends iWebsite_Plugin_Mongo
{

    protected $name = 'iWeibo_appKey';

    protected $dbName = 'weibo';

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