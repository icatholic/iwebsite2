<?php

class Admin_Model_Sku extends iWebsite_Plugin_Mongo
{

    protected $name = 'sku';

    protected $dbName = 'fg0034';

    /**
     * 根据ID获取信息
     */
    public function getInfoById($id)
    {
        $query = array(
            '_id' => $id
        );
        $info = $this->findOne($query);
        return $info;
    }

    /**
     * 根据商品号获取信息
     */
    public function getListByGid($skuList, $gid)
    {
        $skuIdList = explode("-", $gid);
        $list = array();
        foreach ($skuIdList as $skuNo) {
            if (key_exists($skuNo, $skuList)) {
                $list[$skuNo] = $skuList[$skuNo];
            }
        }
        return $list;
    }

    /**
     * 获取所有信息
     */
    public function getAllList()
    {
        $cache = Zend_Registry::get('cache');
        $cacheKey = md5('laiyifeng_skulist');
        // if (($skuList = $cache->load ( $cacheKey )) === false) {
        $query = array();
        $list = $this->findAll($query);
        $skuList = array();
        if (! empty($list['datas'])) {
            foreach ($list['datas'] as $sku) {
                $skuList[$sku['sku_no']] = $sku;
            }
        }
        // $cache->save ( $skuList, $cacheKey );
        // }
        return $skuList;
    }
}