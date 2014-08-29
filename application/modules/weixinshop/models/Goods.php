<?php

class Weixinshop_Model_Goods extends iWebsite_Plugin_Mongo
{

    protected $name = 'iWeixinPay_Goods';

    protected $dbName = 'weixinshop';

    /**
     * 默认排序
     */
    public function getDefaultSort()
    {
        //新品-精品-热门-购买数量-下单数量-设置顺序-时间
        $sort = array(
            'show_order' => - 1,
            'is_new' => - 1,
            'is_best' => - 1,
            'is_hot' => - 1,
            'purchase_num' => - 1,
            'order_num' => - 1,
            '_id' => - 1
        );
        return $sort;
    }

    /**
     * 默认查询条件
     */
    public function getQuery()
    {
        $query = array(
            "is_show" => true
        ); // 显示
        return $query;
    }

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
            'gid' => trim($gid)
        );
        $info = $this->findOne($query);
        return $info;
    }

    /**
     * 获取商品列表信息
     *
     * @param boolean $is_purchase_inner
     *            是否内购
     * @param array $gids
     *            商品ID列表
     * @return array
     */
    public function getList($is_purchase_inner = false, array $gids = array())
    {
        $query = $this->getQuery();
        if (! empty($is_purchase_inner)) {
            $query['is_purchase_inner'] = $is_purchase_inner;
        }
        if (! empty($gids)) {
            $gids = array_map("trim", $gids);
            $query['gid'] = array(
                '$in' => $gids
            );
        }
        $sort = $this->getDefaultSort();
        $list = $this->findAll($query, $sort);
        $datas = array();
        if (! empty($list)) {
            foreach ($list as $goods) {
                $datas[$goods['gid']] = $goods;
            }
        }
        return $datas;
    }

    /**
     * 根据类别获取商品列表信息
     *
     * @param array $gids
     *            商品ID列表
     * @param boolean $isShowBySale
     *            按货架时间
     * @param boolean $isShowByStock
     *            按库存
     * @return array
     */
    public function getListByCategory(array $categoryIds = array(), $isShowBySale = true, $isShowByStock = false)
    {
        $query = $this->getQuery();
        
        if (! empty($categoryIds)) {
            $categoryIds = array_map("trim", $categoryIds);
            $query['cat_id'] = array(
                '$in' => $categoryIds
            );
        }
        if ($isShowBySale) {
            $now = new MongoDate();
            $query['onsale_time'] = array(
                '$lte' => $now
            );
            $query['offsale_time'] = array(
                '$gte' => $now
            );
            $query['is_on_sale'] = true;
        }
        
        if ($isShowByStock) {
            $query['stock_num'] = array(
                '$gt' => 0
            );
        }
        
        $sort = $this->getDefaultSort();
        $list = $this->findAll($query, $sort);
        $datas = array();
        if (! empty($list)) {
            foreach ($list as $goods) {
                $datas[$goods['cat_id']][$goods['gid']] = $goods;
            }
        }
        return $datas;
    }

    /**
     * 减少库存数量
     *
     * @param string $out_trade_no            
     * @param string $gid            
     * @param int $gnum            
     */
    public function subStock($out_trade_no, $gid, $gnum)
    {
        if (! empty($gnum)) {
            // 判断是否已减少了库存数量
            $modelGoodsStockDetail = new Weixinshop_Model_GoodsStockDetail();
            $isExisted = $modelGoodsStockDetail->isExisted($out_trade_no, $gid);
            
            if (! $isExisted) {
                $info = $this->getInfoByGid($gid);
                $data['stock_num'] = 0 - $gnum;
                $data['order_num'] = 1; // 订单数
                $options = array(
                    "query" => array(
                        "_id" => $info['_id'],
                        'stock_num' => array(
                            '$gt' => 0
                        )
                    ),
                    "update" => array(
                        '$inc' => $data
                    ),
                    "new" => true
                );
                $rst = $this->findAndModify($options);
                if ($rst['ok'] == 0) {
                    throw new Exception("减少库存数量操作出错" . json_encode($rst));
                }
                if (empty($rst['value'])) {
                    throw new Exception("减少库存数量操作出错" . json_encode($rst));
                }
                // 记录明细追踪表
                $modelGoodsStockDetail->handle($out_trade_no, $gid, 0 - $gnum);
            }
        }
    }

    /**
     * 是否有库存
     *
     * @param string $gid            
     * @return boolean
     */
    public function hasStock($gid, $gnum)
    {
        $info = $this->getInfoByGid($gid);
        if (! empty($info) && ! empty($info['stock_num'])) {
            return ($info['stock_num'] >= $gnum);
        } else {
            return false;
        }
    }

    /**
     * 增加购买数量
     *
     * @param string $gid            
     * @param int $gnum            
     */
    public function incPurchaseNum($gid, $gnum)
    {
        if (! empty($gnum)) {
            $info = $this->getInfoByGid($gid);
            $data['purchase_num'] = $gnum; // 购买数
            $options = array(
                "query" => array(
                    "_id" => $info['_id']
                ),
                "update" => array(
                    '$inc' => $data
                ),
                "new" => true
            );
            $rst = $this->findAndModify($options);
            if ($rst['ok'] == 0) {
                throw new Exception("增加购买数量操作出错" . json_encode($rst));
            }
            if (empty($rst['value'])) {
                throw new Exception("增加购买数量操作出错" . json_encode($rst));
            }
        }
    }
}