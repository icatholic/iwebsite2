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
        if (! empty($list)) {
            return $list;
        } else {
            return array();
        }
    }

    /**
     * 根据商户订单号获取信息
     *
     * @param string $out_trade_no            
     * @return array
     */
    public function getInfoByOutTradeNo($out_trade_no)
    {
        $query = array(
            'out_trade_no' => $out_trade_no
        );
        $info = $this->findOne($query);
        return $info;
    }

    /**
     * 根据商户订单号列表获取信息
     *
     * @param array $out_trade_no_list            
     * @return array
     */
    public function getInfoByOutTradeNos(array $out_trade_no_list)
    {
        $out_trade_no_list = array_map("trim", $out_trade_no_list);
        $query = array(
            'out_trade_no' => array(
                '$in' => $out_trade_no_list
            )
        );
        $ret = $this->findAll($query);
        $list = array();
        if (! empty($ret)) {
            foreach ($ret as $item) {
                $list['out_trade_no_' . $item['out_trade_no']] = $item;
            }
        }
        return $list;
    }

    /**
     * 根据财付通订单号获取信息
     *
     * @param string $transaction_id            
     * @return array
     */
    public function getInfoByTransactionId($transaction_id)
    {
        $query = array(
            'transaction_id' => $transaction_id
        );
        $info = $this->findOne($query);
        return $info;
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
            $filter['ship_status'] = intval($input->ship_status);
            $filter['refund_status'] = intval($input->refund_status);
            $filter['feedback_status'] = intval($input->feedback_status);
            
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
        $modelOrder = new Weixinshop_Model_Order();
        foreach ($list['datas'] as &$order) {
            $isOK = $modelOrder->isOK($order['trade_state'], $order['trade_mode']);
            if ($isOK) {
                // 获取财富通的订单结果并修改
                $order = $modelOrder->updateFromTenpay($order);
            }
        }
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
        if (empty($orderList))
            return array();
        return $orderList[count($orderList) - 1];
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
            "prev_id" => empty($prevOrder) ? '' : myMongoId($prevOrder['_id']),
            "next_id" => empty($nextOrder) ? '' : myMongoId($nextOrder['_id'])
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
            $where['uma_time_start']['$gte'] = new MongoDate(strtotime($filter['start_time']));
        }
        if (! empty($filter['end_time'])) {
            $where['uma_time_start']['$lte'] = new MongoDate(strtotime($filter['end_time']));
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
        
        if (! empty($filter['order_status'])) {
            if ($filter['order_status'] == 1) { // 取消
                $where['uma_order_status'] = OS_CANCELED;
            }
            if ($filter['order_status'] == 2) { // 无效
                $where['uma_order_status'] = OS_INVALID;
            }
            if ($filter['order_status'] == 3) { // 退货
                $where['uma_order_status'] = OS_RETURNED;
            }
        }
        
        if (! empty($filter['ship_status'])) {
            if ($filter['ship_status'] == 2) { // 已发货
                $where['uma_shipping_status'] = SS_SHIPPED;
            }
            if ($filter['ship_status'] == 1) { // 未发货
                $where['uma_shipping_status'] = SS_UNSHIPPED;
            }
        }
        
        if (! empty($filter['feedback_status'])) {
            if ($filter['feedback_status'] == 1) { // 未维权
                $where['uma_feedback_status'] = FDS_NONE;
            }
            if ($filter['feedback_status'] == 2) { // 维权中
                $where['uma_feedback_status'] = FDS_REQUEST;
            }
            if ($filter['feedback_status'] == 3) { // 等待用户确认
                $where['uma_feedback_status'] = FDS_WAIT;
            }
            if ($filter['feedback_status'] == 4) { // 维权已确认
                $where['uma_feedback_status'] = FDS_FINISHED;
            }
        }
        
        return $where;
    }

    /**
     * 已取消订单数量
     *
     * @return int
     */
    public function getCanceledCount($start, $end)
    {
        /* 已支付订单 */
        $where = array();
        $where['uma_order_status'] = OS_CANCELED;
        $where['uma_time_start'] = array(
            '$gte' => $start,
            '$lte' => $end
        );
        $num = $this->count($where);
        return $num;
    }

    /**
     * 已发货订单数量
     *
     * @return int
     */
    public function getShippedCount($start, $end)
    {
        /* 已支付订单 */
        $where = array();
        $where['uma_shipping_status'] = SS_SHIPPED;
        $where['uma_time_start'] = array(
            '$gte' => $start,
            '$lte' => $end
        );
        $num = $this->count($where);
        return $num;
    }

    /**
     * 已付款,未发货订单数量
     *
     * @return int
     */
    public function getPaidUnshipCount($start, $end)
    {
        /* 已支付订单 */
        $where = array();
        $where['trade_state'] = 0;
        $where['trade_mode'] = 1;
        $where['uma_shipping_status'] = SS_UNSHIPPED;
        $where['uma_time_start'] = array(
            '$gte' => $start,
            '$lte' => $end
        );
        $num = $this->count($where);
        return $num;
    }

    /**
     * 未支付订单数量
     *
     * @return int
     */
    public function getUnPayCount($start, $end)
    {
        /* 未支付订单 */
        $where = array();
        $where['trade_state'] = - 1;
        $where['trade_mode'] = 0;
        $where['uma_time_start'] = array(
            '$gte' => $start,
            '$lte' => $end
        );
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

    public function confirm($orderInfo, $action_note)
    {
        /* 标记订单为已确认 */
        $data['uma_order_status'] = OS_CONFIRMED;
        $this->update(array(
            '_id' => $orderInfo['_id']
        ), array(
            '$set' => $data
        ));
        
        /* 记录log */
        $orderInfo['uma_order_status'] = OS_CONFIRMED;
        $this->logOrderAction($orderInfo, $action_note);
    }

    public function pay($orderInfo, $action_note)
    {
        /* 记录log */
        $this->logOrderAction($orderInfo, $action_note);
    }

    public function unpay($orderInfo, $action_note, $refund_type, $refund_note)
    {
        /* 记录log */
        $this->logOrderAction($orderInfo, $action_note);
    }

    public function prepare($orderInfo, $action_note)
    {
        $data['uma_shipping_status'] = SS_PREPARING;
        $data['uma_shipping_time'] = new MongoDate();
        $this->update(array(
            '_id' => $orderInfo['_id']
        ), array(
            '$set' => $data
        ));
        
        /* 记录log */
        $orderInfo['uma_shipping_status'] = SS_PREPARING;
        $this->logOrderAction($orderInfo, $action_note);
    }

    public function ship($orderInfo, $action_note, $expressInfo, $shipping_no)
    {
        $data['uma_shipping_status'] = SS_SHIPPED;
        $data['uma_shipping_time'] = new MongoDate();
        
        $shipping_express_name = $expressInfo['name'];
        $data['uma_shipping_memo'] = array(
            'shipping_express' => myMongoId($expressInfo['_id']),
            'shipping_express_name' => $expressInfo['name'],
            'shipping_no' => $shipping_no
        );
        $this->update(array(
            '_id' => $orderInfo['_id']
        ), array(
            '$set' => $data
        ));
        
        /* 记录log */
        $orderInfo['uma_shipping_status'] = SS_SHIPPED;
        $this->logOrderAction($orderInfo, "[快递公司] {$shipping_express_name}\n[快递单号] {$shipping_no}\n{$action_note}");
    }

    public function unship($orderInfo, $action_note)
    {
        /* 标记订单为“未发货”，更新发货时间, 订单状态为“确认” */
        $data['uma_shipping_status'] = SS_UNSHIPPED;
        // $data['uma_shipping_time'] = 0;
        $this->update(array(
            '_id' => $orderInfo['_id']
        ), array(
            '$set' => $data
        ));
        
        /* 记录log */
        $orderInfo['uma_shipping_status'] = SS_UNSHIPPED;
        $this->logOrderAction($orderInfo, $action_note);
    }

    public function cancel($orderInfo, $action_note, $cancel_note, $refund_type, $refund_note)
    {
        /* 标记订单为“取消”，记录取消原因 */
        $data['uma_order_status'] = OS_CANCELED;
        $this->update(array(
            '_id' => $orderInfo['_id']
        ), array(
            '$set' => $data
        ));
        
        /* 记录log */
        $orderInfo['uma_order_status'] = OS_CANCELED;
        // $this->logOrderAction($orderInfo, "[取消原因] {$cancel_note}\n[退款方式] {$refund_type}\n[退款说明] {$refund_note}\n{$action_note}");
        $this->logOrderAction($orderInfo, "[取消原因] {$cancel_note}\n{$action_note}");
    }

    public function receive($orderInfo, $action_note)
    {
        /* 标记订单为“收货确认”，如果是货到付款，同时修改订单为已付款 */
        $data['uma_shipping_status'] = SS_RECEIVED;
        $this->update(array(
            '_id' => $orderInfo['_id']
        ), array(
            '$set' => $data
        ));
        /* 记录log */
        $orderInfo['uma_shipping_status'] = SS_RECEIVED;
        $this->logOrderAction($orderInfo, $action_note);
    }

    public function invalid($orderInfo, $action_note)
    {
        /* 标记订单为“无效”、“未付款” */
        $data['uma_order_status'] = OS_INVALID;
        $this->update(array(
            '_id' => $orderInfo['_id']
        ), array(
            '$set' => $data
        ));
        
        /* 记录log */
        $orderInfo['uma_order_status'] = OS_INVALID;
        $this->logOrderAction($orderInfo, $action_note);
    }

    public function split($orderInfo, $action_note, $suppliers_id, $send_number)
    {
        /* 记录log */
        $this->logOrderAction($orderInfo, $action_note);
    }

    public function deliveryship($orderInfo, $action_note, $invoice_no, $deliveryOrderRow, $delivery_stock_result)
    {
        /* 发货单发货记录log */
        $this->logOrderAction($orderInfo, $action_note, 1);
    }

    public function cancelDeliveryShip($orderInfo, $action_note, $invoice_no, $deliveryOrderRow, $delivery_stock_result)
    {
        /* 发货单取消发货记录log */
        $this->logOrderAction($orderInfo, $action_note, 1);
    }

    public function refund($orderInfo, $action_note, $refund_type, $refund_note)
    {
        
        /* 标记订单为“退货”、“未付款”、“未发货” */
        $data['uma_order_status'] = OS_RETURNED;
        $this->update(array(
            '_id' => $orderInfo['_id']
        ), array(
            '$set' => $data
        ));
        
        /* 记录log */
        $orderInfo['uma_order_status'] = OS_RETURNED;
        $this->logOrderAction($orderInfo, "[退款方式] {$refund_type}\n[退款说明] {$refund_note}\n{$action_note}");
    }

    public function afterService($orderInfo, $action_note)
    {
        /* 记录log */
        $this->logOrderAction($orderInfo, '[售后] ' . $action_note);
    }

    public function getOperableConfirm(array $orderInfo)
    {
        return false;
        
        /* 取得订单状态 */
        $os = $orderInfo['uma_order_status'];
        $confirm = in_array($os, array(
            OS_UNCONFIRMED,/* 状态：未确认 => 未付款、未发货 */
            OS_CANCELED,/* 状态：取消 */
            OS_INVALID,/* 状态：无效 */
            OS_RETURNED/* 状态：退货 */
        ));
        
        return $confirm;
    }

    public function getOperableInvalid(array $orderInfo)
    {
        return false;
        
        /* 取得订单状态、发货状态、付款状态 */
        $os = $orderInfo['uma_order_status'];
        $ss = $orderInfo['uma_shipping_status'];
        $ps = $this->getPayStatus($orderInfo['trade_state'], $orderInfo['trade_mode']);
        
        if (OS_UNCONFIRMED == $os)/* 状态：未确认 */
        {
            return true;
        }
        
        if (in_array($os, array(
            OS_CONFIRMED,
            OS_SPLITED,
            OS_SPLITING_PART
        )))/* 状态：已确认 */
        {
            if (PS_UNPAYED == $ps)/* 状态：未付款 */
            {
                if (in_array($ss, array(
                    SS_UNSHIPPED,
                    SS_PREPARING
                )))/* 状态：未发货（或配货中） */
                {
                    return true;
                }
            }
        }
        
        return false;
    }

    public function getOperableCancel(array $orderInfo)
    {
        return true;
        
        /* 取得订单状态、发货状态、付款状态 */
        $os = $orderInfo['uma_order_status'];
        $ss = $orderInfo['uma_shipping_status'];
        $ps = $this->getPayStatus($orderInfo['trade_state'], $orderInfo['trade_mode']);
        
        if (OS_UNCONFIRMED == $os) /* 状态：未确认 => 未付款、未发货 */
        {
            return true; // 取消
        }
        
        $cancel = false;
        if (in_array($os, array(
            OS_CONFIRMED,
            OS_SPLITED,
            OS_SPLITING_PART
        )))/* 状态：已确认 */
        {
            if (in_array($ss, array(
                SS_UNSHIPPED,
                SS_PREPARING
            )))/* 状态：未发货（或配货中） */
            {
                if (PS_UNPAYED == $ps) /* 状态：未付款 */
                {
                    $cancel = true; // 取消
                } else {
                    /* 状态：已付款和付款中 */
                    $cancel = true; // 取消
                }
            }
        }
        
        return $cancel;
    }

    public function getOperableSplit(array $orderInfo)
    {
        return false;
        
        /* 取得订单状态、发货状态、付款状态 */
        $os = $orderInfo['uma_order_status'];
        $ss = $orderInfo['uma_shipping_status'];
        $ps = $this->getPayStatus($orderInfo['trade_state'], $orderInfo['trade_mode']);
        
        $split = false;
        
        if (OS_UNCONFIRMED == $os)/* 状态：未确认 => 未付款、未发货 */
        {
            /* 货到付款 */
            $split = true; // 分单
        } elseif (in_array($os, array(
            OS_CONFIRMED,
            OS_SPLITED,
            OS_SPLITING_PART
        )))/* 状态：已确认 */
        {
            if (in_array($ss, array(
                SS_UNSHIPPED,
                SS_PREPARING
            )))/* 状态：已确认、未付款、未发货（或配货中） */
            {
                if (PS_UNPAYED == $ps)/* 状态：已确认、未付款 */
                {
                    /* 货到付款 */
                    $split = true; // 分单
                } else {
                    /* 状态：已确认、已付款和付款中 */
                    $split = true; // 分单
                }
            } elseif (in_array($ss, array(
                SS_SHIPPED_ING,
                SS_SHIPPED_PART
            )))/* 状态：已确认、未付款、发货中 */ 
            {
                // 部分分单
                $split = (OS_SPLITING_PART == $os); // 分单
            }
        }
        
        return $split;
    }

    public function getOperablePrepare(array $orderInfo)
    {
        return false;
        
        /* 取得订单状态、发货状态、付款状态 */
        $os = $orderInfo['uma_order_status'];
        $ss = $orderInfo['uma_shipping_status'];
        $ps = $this->getPayStatus($orderInfo['trade_state'], $orderInfo['trade_mode']);
        
        if (OS_UNCONFIRMED == $os)/* 状态：未确认 => 未付款、未发货 */
        {
            /* 货到付款 */
            return true; // 配货
        }
        
        if (in_array($os, array(
            OS_CONFIRMED,
            OS_SPLITED,
            OS_SPLITING_PART
        )))/* 状态：已确认 */
        {
            if (in_array($ss, array(
                SS_UNSHIPPED,
                SS_PREPARING
            )))/* 状态：已确认、未付款、未发货（或配货中） */
            {
                if (PS_UNPAYED == $ps)/* 状态：已确认、未付款 */
                {
                    /* 货到付款 */
                    return (SS_UNSHIPPED == $ss); // 配货
                } else {
                    /* 状态：已确认、已付款和付款中 */
                    return (SS_UNSHIPPED == $ss); // 配货
                }
            }
        }
        
        return false;
    }

    public function getOperablePay(array $orderInfo)
    {
        return false;
        
        /* 取得订单状态、发货状态、付款状态 */
        $os = $orderInfo['uma_order_status'];
        $ss = $orderInfo['uma_shipping_status'];
        $ps = $this->getPayStatus($orderInfo['trade_state'], $orderInfo['trade_mode']);
        
        if (OS_UNCONFIRMED == $os)/* 状态：未确认 => 未付款、未发货 */
        {
            /* 不是货到付款 */
            return true; // 付款
        }
        
        if (in_array($os, array(
            OS_CONFIRMED,
            OS_SPLITED,
            OS_SPLITING_PART
        )))/* 状态：已确认 */
        {
            if (PS_UNPAYED == $ps)/* 状态：已确认、未付款 */
            {
                if (in_array($ss, array(
                    SS_UNSHIPPED,
                    SS_PREPARING
                )))/* 状态：已确认、未付款、未发货（或配货中） */
                {
                    /* 不是货到付款 */
                    return true; // 付款
                } else {
                    /* 状态：已确认、未付款、已发货或已收货 => 货到付款 */
                    return true; // 付款
                }
            }
        }
        return false;
    }

    public function getOperableToDelivery(array $orderInfo)
    {
        return false;
        /* 取得订单状态、发货状态、付款状态 */
        $os = $orderInfo['uma_order_status'];
        $ss = $orderInfo['uma_shipping_status'];
        $to_delivery = false;
        if (in_array($os, array(
            OS_CONFIRMED,
            OS_SPLITED,
            OS_SPLITING_PART
        )))/* 状态：已确认 */
        {
            if (in_array($ss, array(
                SS_SHIPPED_ING,
                SS_SHIPPED_PART
            )))/* 状态：已确认、未付款、发货中 */
            {
                $to_delivery = true; // 去发货
            }
        }
        
        return $to_delivery;
    }

    public function getOperableReceive(array $orderInfo)
    {
        return false;
        
        /* 取得订单状态、发货状态、付款状态 */
        $os = $orderInfo['uma_order_status'];
        $ss = $orderInfo['uma_shipping_status'];
        
        if (in_array($os, array(
            OS_CONFIRMED,
            OS_SPLITED,
            OS_SPLITING_PART
        )))/* 状态：已确认 */
        {
            if (! in_array($ss, array(
                SS_SHIPPED_ING,
                SS_SHIPPED_PART
            )))/* 状态：已确认、已发货或已收货 */
            {
                return (SS_SHIPPED == $ss); // 收货确认
            }
        }
        return false;
    }

    public function getOperableShip(array $orderInfo)
    {
        $ss = $orderInfo['uma_shipping_status'];
        return (($ss != SS_SHIPPED) && ($orderInfo['trade_state']) == 0 && ($orderInfo['trade_mode'] == 1));
    }

    public function getOperableUnship(array $orderInfo)
    {
        $ss = $orderInfo['uma_shipping_status'];
        return ($ss == SS_SHIPPED);
        
        /* 取得订单状态、发货状态、付款状态 */
        $os = $orderInfo['uma_order_status'];
        $ss = $orderInfo['uma_shipping_status'];
        $ps = $this->getPayStatus($orderInfo['trade_state'], $orderInfo['trade_mode']);
        
        if (in_array($os, array(
            OS_CONFIRMED,
            OS_SPLITED,
            OS_SPLITING_PART
        )))/* 状态：已确认 */
        {
            if (! in_array($ss, array(
                SS_SHIPPED_ING,
                SS_SHIPPED_PART,
                SS_UNSHIPPED,
                SS_PREPARING
            )))/* 状态：已确认、已发货或已收货 */
            {
                if (PS_UNPAYED == $ps)/* 状态：未付款 */
                {
                    return true; // 设为未发货
                } else {
                    /* 状态：付款 */
                    return true; // 设为未发货
                }
            }
        }
        return false;
    }

    public function getOperableReturn(array $orderInfo)
    {
        return false;
        
        /* 取得订单状态、发货状态、付款状态 */
        $os = $orderInfo['uma_order_status'];
        $ss = $orderInfo['uma_shipping_status'];
        $ps = $this->getPayStatus($orderInfo['trade_state'], $orderInfo['trade_mode']);
        $return = false;
        if (in_array($os, array(
            OS_CONFIRMED,
            OS_SPLITED,
            OS_SPLITING_PART
        )))/* 状态：已确认 */
        {
            if (! in_array($ss, array(
                SS_SHIPPED_ING,
                SS_SHIPPED_PART,
                SS_UNSHIPPED,
                SS_PREPARING
            )))/* 状态：已发货或已收货 => 货到付款 */
            {
                if (PS_UNPAYED == $ps)/* 状态：未付款 */
                {
                    $return = true; // 退货（包括退款）
                } else {
                    /* 已付款和付款中 */
                    $return = true; // 退货（包括退款）
                }
            }
        }
        
        return $return;
    }

    public function getOperableUnpay(array $orderInfo)
    {
        return false;
        
        /* 取得订单状态、发货状态、付款状态 */
        $os = $orderInfo['uma_order_status'];
        $ss = $orderInfo['uma_shipping_status'];
        $ps = $this->getPayStatus($orderInfo['trade_state'], $orderInfo['trade_mode']);
        
        if (in_array($os, array(
            OS_CONFIRMED,
            OS_SPLITED,
            OS_SPLITING_PART
        )))/* 状态：已确认 */
        {
            if (PS_UNPAYED != $ps)/* 状态：已付款和付款中 */
            {
                if (in_array($ss, array(
                    SS_UNSHIPPED,
                    SS_PREPARING
                )))/* 状态：未发货（配货中） => 不是货到付款 */
                {
                    return true; // 设为未付款
                } else {
                    /* 状态：已发货或已收货 */
                    return true; // 设为未付款
                }
            }
        }
        return false;
    }

    public function getOperableRemove(array $orderInfo)
    {
        return false;
        
        /* 取得订单状态、发货状态、付款状态 */
        $os = $orderInfo['uma_order_status'];
        $remove = in_array($os, array(
            OS_CANCELED,/* 状态：取消 */
            OS_INVALID /* 状态：无效 */
        ));
        return $remove;
    }

    public function getOperableDrop(array $orderInfo)
    {
        return false;
    }

    public function getOperableAfterService(array $orderInfo)/* 售后 */
    {
        return true;
    }

    /**
     * 返回某个订单可执行的操作列表，包括权限判断
     *
     * @param array $orderInfo            
     * @return array 可执行的操作 confirm, pay, unpay, prepare, ship, unship, receive, cancel, invalid, return, drop
     *         格式 array('confirm' => true, 'pay' => true)
     */
    public function getOperableList(array $orderInfo)
    {
        $list = array(
            "confirm" => $this->getOperableConfirm($orderInfo),
            "pay" => $this->getOperablePay($orderInfo),
            "unpay" => $this->getOperableUnpay($orderInfo),
            "prepare" => $this->getOperablePrepare($orderInfo),
            "ship" => $this->getOperableShip($orderInfo),
            "unship" => $this->getOperableUnship($orderInfo),
            "receive" => $this->getOperableReceive($orderInfo),
            "cancel" => $this->getOperableCancel($orderInfo),
            "invalid" => $this->getOperableInvalid($orderInfo),
            "return" => $this->getOperableReturn($orderInfo),
            "drop" => $this->getOperableDrop($orderInfo),
            "split" => $this->getOperableSplit($orderInfo),
            "remove" => $this->getOperableRemove($orderInfo),
            "to_delivery" => $this->getOperableToDelivery($orderInfo),
            "after_service" => $this->getOperableAfterService($orderInfo)
        );
        
        return $list;
    }

    public function logOrderAction($orderInfo, $action_note, $action_place = 0)
    {
        $modelOrderAction = new Admin_Model_OrderAction();
        $order_id = myMongoId($orderInfo['_id']);
        $action_user = $_SESSION['admin_name'];
        $order_status = $orderInfo['uma_order_status'];
        $shipping_status = $orderInfo['uma_shipping_status'];
        $pay_status = $this->getPayStatus($orderInfo['trade_state'], $orderInfo['trade_mode']);
        $modelOrderAction->log($order_id, $action_user, $order_status, $shipping_status, $pay_status, $action_note, $action_place);
    }

    public function getPayStatus($trade_state, $trade_mode)
    {
        if (is_numeric($trade_state) && intval($trade_state) === 0 && is_numeric($trade_mode) && intval($trade_mode) === 1) {
            return PS_PAYED;
        } else {
            return PS_UNPAYED;
        }
    }

    /**
     * 判断支付完成
     *
     * @param int $trade_state            
     * @param int $trade_mode            
     * @return boolean
     */
    public function isOK($trade_state, $trade_mode)
    {
        $order = new Weixinshop_Model_Order();
        return $order->isOK($trade_state, $trade_mode);
    }
}