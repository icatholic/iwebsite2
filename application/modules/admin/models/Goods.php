<?php

class Admin_Model_Goods extends iWebsite_Plugin_Mongo
{

    protected $name = 'iWeixinPay_Goods';

    protected $dbName = 'weixinshop';

    /**
     * 根据ID获取信息
     * 
     * @param string $id            
     * @return array
     */
    public function getInfoById($id)
    {
        $query = array(
            '_id' => myMongoId($id)
        );
        $info = $this->findOne($query);
        return $info;
    }

    /**
     * 根据商品号获取信息
     * 
     * @param string $gid            
     * @return array
     */
    public function getInfoByGid($gid)
    {
        $query = array(
            'gid' => $gid
        );
        $info = $this->findOne($query);
        return $info;
    }
}