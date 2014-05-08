<?php

class Admin_Model_StockAlarm extends iWebsite_Plugin_Mongo
{

    protected $name = 'iWeixinPay_StockAlarm';

    protected $dbName = 'weixinshop';

    /**
     * 处理库存预警
     *
     * @param string $productId            
     * @param int $stock_day            
     */
    public function handle($productId, $stock_day)
    {
        $data = array();
        $data['productId'] = $productId; // 商品ID
        $data['stock_day'] = $stock_day; // 一天库存数
        $data['happen_time'] = new MongoDate(); // 统计时间
        return $this->insert($data);
    }
}