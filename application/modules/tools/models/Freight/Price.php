<?php

class Tools_Model_Freight_Price extends iWebsite_Plugin_Mongo
{

    protected $name = 'iFreight_price';

    protected $dbName = 'iFreight';

    /**
     *
     * @param number $code            
     * @param int $number            
     */
    /**
     * 
     * @param string $campany 物流公司
     * @param string $warehouse 仓库编号
     * @param int $area 地理位置信息
     * @param int $number 购买数量
     */
    public function getPrice($campany, $warehouse, $area, $number)
    {
        
    }
}