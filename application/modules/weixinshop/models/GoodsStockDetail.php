<?php

class Weixinshop_Model_GoodsStockDetail extends iWebsite_Plugin_Mongo
{

    protected $name = 'iWeixinPay_GoodsStockDetail';

    protected $dbName = 'weixinshop';

    /**
     * 记录商品库存明细
     *
     * @param string $out_trade_no            
     * @param string $gid            
     * @param int $stock_num            
     * @return array
     */
    public function handle($out_trade_no, $gid, $stock_num)
    {
        $data = array();
        $data['out_trade_no'] = $out_trade_no;
        $data['gid'] = $gid;
        $data['stock_time'] = new MongoDate();
        $data['stock_num'] = $stock_num;
        $info = $this->insert($data);
        return $info;
    }

    /**
     * 是否已存在
     *
     * @param string $out_trade_no            
     * @param string $gid            
     * @param boolean $is_today            
     * @return boolean
     */
    public function isExisted($out_trade_no, $gid, $is_today = false)
    {
        $query = array();
        if (! empty($out_trade_no)) {
            $query['out_trade_no'] = $out_trade_no;
        }
        if (! empty($gid)) { // 活动身份列表
            $query['gid'] = $gid;
        }
        if ($is_today) { // 当天
            $query['stock_time'] = array(
                '$gte' => date('Y-m-d') . ' 00:00:00',
                '$lte' => date('Y-m-d') . ' 23:59:59'
            );
        }
        $count = $this->count($query);
        return $count > 0;
    }
}