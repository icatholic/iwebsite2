<?php

class Admin_Model_Order extends iWebsite_Plugin_Mongo
{

    protected $name = 'iWeixinPay_Order';

    protected $dbName = 'weixinshop';

    /**
     * 默认排序
     *
     * @return array
     */
    public function getDefaultSort()
    {
        $sort = array(
            'out_trade_no' => - 1,
            '_id' => - 1
        );
        return $sort;
    }

    /**
     * 默认条件
     *
     * @return array
     */
    public function getQuery()
    {
        $condition = array();
        return $condition;
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
     * 根据IDs获取信息
     *
     * @param array $ids            
     * @return array
     */
    public function getListByIds($ids)
    {
        $query = array(
            '_id' => array(
                '$in' => myMongoId($ids)
            )
        );
        $list = $this->findAll($query);
        if (! empty($list['datas'])) {
            return $list['datas'];
        } else {
            return array();
        }
    }

    /**
     * 获取订单列表信息
     * 
     * @param Zend_Filter_Input $input            
     * @return array
     */
    public function getList(Zend_Filter_Input $input)
    {
        $pager = new Admin_Model_Pager();
        $result = $pager->get_filter();
        if ($result === false) {
            /* 过滤信息 */
            $filter['out_trade_no'] = trim($input->out_trade_no);
            $filter['OpenId'] = trim($input->OpenId);
            $filter['consignee_name'] = urldecode($input->consignee_name);
            $filter['consignee_address'] = trim($input->consignee_address);
            $filter['consignee_zipcode'] = trim($input->consignee_zipcode);
            $filter['consignee_tel'] = trim($input->consignee_tel);
            $filter['start_time'] = trim($input->start_time);
            $filter['end_time'] = trim($input->end_time);
            
            $filter['order_status'] = intval($input->order_status);
            $filter['pay_status'] = intval($input->pay_status);
            $filter['refund_status'] = intval($input->refund_status);
            
            $filter['sort_by'] = trim($input->sort_by);
            $filter['sort_order'] = trim($input->sort_order);
            /* 分页大小 */
            $filter = $pager->getPage($filter, $input);
        } else {
            $filter = $result['filter'];
        }
        
        // 根据Filter获取查询条件
        $where = $this->getConditionByFilter($filter);
        
        $sort = array();
        $sort[$filter['sort_by']] = ('DESC' == $filter['sort_order']) ? - 1 : 1;
        
        /* 记录总数 */
        $list = $this->find($where, $sort, $filter['start'], $filter['page_size']);
        $filter['record_count'] = $list['total'];
        
        /* page 总数 */
        $filter = $pager->setPage($filter, $input);
        
        $pager->set_filter($filter);
        
        return array(
            'data' => $list['datas'],
            'filter' => $filter,
            'page_count' => $filter['page_count'],
            'record_count' => $filter['record_count']
        );
    }

    /**
     * 合并两个数组
     * 
     * @param array $arr1            
     * @param array $arr2            
     * @return array
     */
    private function arrayMergeCondition($arr1, $arr2)
    {
        if (empty($arr1))
            return $arr2;
        if (empty($arr2))
            return $arr1;
        return array_merge($arr1, $arr2);
    }

    /**
     * 查询条件
     * 
     * @param array $order            
     * @param array $condition            
     * @param string $op            
     * @return array
     */
    public function getCondition($order, array $condition, $op = '$gt')
    {
        $sort = $this->getDefaultSort();
        $query = $condition;
        $orCondtion = array();
        $prevCondition = array();
        foreach ($sort as $key => $value) {
            $cond1 = array(
                $key => array(
                    $op => $order[$key]
                )
            );
            $cond1 = $this->arrayMergeCondition($cond1, $prevCondition);
            $prevCondition[$key] = $order[$key];
            $orCondtion[] = $cond1;
        }
        $query[] = array(
            '$or' => $orCondtion
        );
        
        return array(
            '$and' => $query
        );
    }

    /**
     * 获取前一条
     * 
     * @param array $order            
     * @param array $condition            
     * @return array
     */
    public function getPrevOrder($order, array $condition = array())
    {
        $query = $this->getCondition($order, $condition, '$gt');
        $orderList = $this->findAll($query, $this->getDefaultSort());
        if (empty($orderList['datas']))
            return array();
        return $orderList['datas'][count($orderList['datas']) - 1];
    }

    /**
     * 获取后一条
     * 
     * @param array $order            
     * @param array $condition            
     * @return array
     */
    public function getNextOrder($order, array $condition = array())
    {
        if (empty($condition))
            $condition = array();
        $query = $this->getCondition($order, $condition, '$lt');
        $orderList = $this->find($query, $this->getDefaultSort(), 0, 1);
        if (empty($orderList['datas']))
            return array();
        return $orderList['datas'][0];
    }

    /**
     * 取得上一个、下一个订单号
     * 
     * @param array $order            
     * @return array
     */
    public function getPrevNextOrderId($order)
    {
        $where = array();
        
        if (! empty($_COOKIE['ECSCP']['lastfilter'])) {
            $filter = unserialize(urldecode($_COOKIE['ECSCP']['lastfilter']));
            // 根据Filter获取查询条件
            // $where = $this->getConditionByFilter($filter);
        }
        
        // 获取上一个
        $prevOrder = $this->getPrevOrder($order, $where);
        
        // 获取下一个
        $nextOrder = $this->getNextOrder($order, $where);
        return array(
            "prev_id" => empty($prevOrder) ? '' : $prevOrder['_id'],
            "next_id" => empty($nextOrder) ? '' : $nextOrder['_id']
        );
    }

    /**
     * 根据画面条件获取订单查询条件
     * 
     * @param array $filter            
     * @return array
     */
    public function getConditionByFilter($filter)
    {
        $where = array();
        if (! empty($filter['out_trade_no'])) {
            $where['out_trade_no'] = $filter['out_trade_no'];
        }
        if (! empty($filter['OpenId'])) {
            $where['OpenId'] = $filter['OpenId'];
        }
        if (! empty($filter['consignee_name'])) {
            $where['consignee_name'] = $filter['consignee_name'];
        }
        if (! empty($filter['consignee_address'])) {
            $where['consignee_address'] = $filter['consignee_address'];
        }
        if (! empty($filter['consignee_zipcode'])) {
            $where['consignee_zipcode'] = $filter['consignee_zipcode'];
        }
        if (! empty($filter['consignee_tel'])) {
            $where['consignee_tel'] = $filter['consignee_tel'];
        }
        if (! empty($filter['start_time'])) {
            $where['uma_time_start']['$gte'] = $filter['start_time'];
        }
        if (! empty($filter['end_time'])) {
            $where['uma_time_start']['$lte'] = $filter['end_time'];
        }
        if (! empty($filter['pay_status'])) {
            if ($filter['pay_status'] == 2) { // 已付款
                $where['trade_state'] = 0;
                $where['trade_mode'] = 1;
            }
            
            if ($filter['pay_status'] == 1) { // 未付款
                $where['trade_state'] = - 1;
                $where['trade_mode'] = 0;
            }
        }
        
        if (! empty($filter['refund_status'])) {
            if ($filter['refund_status'] == 2) { // 已退款
                $where['is_refund'] = "true";
            }
            if ($filter['refund_status'] == 1) { // 未退款
                $where['is_refund'] = "false";
            }
        }
        return $where;
    }

    /**
     * 已付款订单数量
     * 
     * @return int
     */
    public function getPaidCount()
    {
        /* 已支付订单 */
        $where = array();
        $where['trade_state'] = 0;
        $where['trade_mode'] = 1;
        $num = $this->count($where);
        return $num;
    }

    /**
     * 未支付订单数量
     * 
     * @return int
     */
    public function getAwaitPayCount()
    {
        /* 未支付订单 */
        $where = array();
        $where['trade_state'] = - 1;
        $where['trade_mode'] = 0;
        $num = $this->count($where);
        return $num;
    }

    /**
     * 新订单数量
     * 
     * @return int
     */
    public function getNewOrdersCount()
    {
        /* 新订单 */
        $where = array();
        $where['uma_time_start']['$gte'] = date("Y-m-d H:i:s", $_SESSION["last_check"]);
        $new_orders = $this->count($where);
        return $new_orders;
    }

    /**
     * 新支付订单数量
     *
     * @return unknown
     */
    public function getNewPaidCount()
    {
        /* 新支付订单 */
        $where = array();
        $where['trade_state'] = 0;
        $where['trade_mode'] = 1;
        $where['uma_time_end']['$gte'] = date("Y-m-d H:i:s", $_SESSION["last_check"]);
        $new_orders = $this->count($where);
        return $new_orders;
    }
}