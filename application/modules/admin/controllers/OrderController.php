<?php

/**
 *  订单管理
 */
class Admin_OrderController extends iWebsite_Controller_Admin_Action
{

    public function init()
    {
        parent::init();
    }
    
    /* ------------------------------------------------------ */
    // -- 订单列表
    /* ------------------------------------------------------ */
    public function listAction()
    {
        try {
            $input = $this->getListFilterInput();
            if (! $input->isValid()) {
                $messageInfo = $this->_getValidationMessage($input);
                throw new Exception($messageInfo);
            }
            $this->getList($input);
            $this->view->assign('form_act_batch', $this->_helper->url("batch"));
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /* ------------------------------------------------------ */
    // -- 排序、分页、查询
    /* ------------------------------------------------------ */
    public function queryAction()
    {
        try {
            $input = $this->getListFilterInput();
            if (! $input->isValid()) {
                $messageInfo = $this->_getValidationMessage($input);
                throw new Exception($messageInfo);
            }
            
            $filter = $this->getList($input);
            $content = $this->view->render('order/partials/order_list.phtml');
            $res = array(
                'error' => 0,
                'message' => '',
                'content' => $content
            );
            foreach ($filter as $key => $val) {
                $res[$key] = $val;
            }
        } catch (Exception $e) {
            $res = array(
                'error' => 1,
                'message' => $e->getMessage(),
                'content' => ""
            );
        }
        exit(json_encode($res));
    }
    
    /* ------------------------------------------------------ */
    // -- 订单查询
    /* ------------------------------------------------------ */
    public function orderqueryAction()
    {
        try {
            $this->view->assign('form_act', $this->_helper->url("list"));
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /* ------------------------------------------------------ */
    // -- 获取订单商品信息
    /* ------------------------------------------------------ */
    public function getgoodsinfoAction()
    {
        try {
            $this->_helper->viewRenderer->setNeverRender(true);
            $modelOrder = new Admin_Model_Order();
            $input = $this->getInfoFilterInput();
            if ($input->isValid("order_id")) {
                $order = $modelOrder->getInfoById($input->order_id);
                $this->view->assign("order", $order);
            } else {
                $messageInfo = $this->_getValidationMessage($input);
                throw new Exception($messageInfo);
            }
            $modelGoods = new Admin_Model_Goods();
            $goodsInfo = $modelGoods->getInfoByGid($order['ProductId']);
            $this->view->assign("goods_list", array(
                $goodsInfo
            ));
            
            $content = $this->view->render('order/partials/order_goods_info.phtml');
            $goods[] = array(
                'order_id' => $input->order_id,
                'str' => $content
            );
            
            $res = array(
                'error' => 0,
                'message' => '',
                'content' => $goods
            );
        } catch (Exception $e) {
            $res = array(
                'error' => 1,
                'message' => $e->getMessage(),
                'content' => ""
            );
        }
        exit(json_encode($res));
    }
    
    /* ------------------------------------------------------ */
    // -- 订单详情页面
    /* ------------------------------------------------------ */
    public function infoAction()
    {
        try {
            $modelOrder = new Admin_Model_Order();
            $input = $this->getInfoFilterInput();
            if ($input->isValid('order_id') || $input->isValid('out_trade_no')) {
                /* 根据订单id或订单号查询订单信息 */
                $order = $modelOrder->getInfoById($input->order_id);
                /* 如果订单不存在，退出 */
                if (empty($order)) {
                    throw new Exception("订单不存在");
                }
            } else {
                $messageInfo = $this->_getValidationMessage($input);
                throw new Exception($messageInfo);
            }
            
            /* 取得上一个、下一个订单号 */
            $prev_next_order_id = $modelOrder->getPrevNextOrderId($order);
            $this->view->assign($prev_next_order_id);
            
            /* 参数赋值：订单 */
            $this->view->assign('order', $order);
            /* 取得订单商品及货品 */
            $modelGoods = new Admin_Model_Goods();
            $goodsInfo = $modelGoods->getInfoByGid($order['ProductId']);
            $this->view->assign('goods_list', array(
                $goodsInfo
            ));
            
            /* 取得订单操作记录 */
            $this->view->assign('action_list', array());
            
            /* 是否打印订单，分别赋值 */
            if ($input->print) {
                $this->view->assign('order_list', array(
                    $order
                ));
                echo $this->view->render('order/partials/order_print.phtml');
                exit();
            }
            
            /* 是否下载，分别赋值 */
            if ($input->download) {
                $this->export(array(
                    $order
                ));
                exit();
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /* ------------------------------------------------------ */
    // -- 操作订单状态（处理批量提交）
    /* ------------------------------------------------------ */
    public function batchAction()
    {
        try {
            $input = $this->getBatchFilterInput();
            if (! $input->isValid()) {
                $messageInfo = $this->_getValidationMessage($input);
                throw new Exception($messageInfo);
            }
            
            $action_note = $input->action_note;
            $operation = $input->operation;
            
            if (empty($input->ids)) {
                $this->sysMsg("请选择一个订单");
            }
            
            /* 初始化处理的订单sn */
            $sn_list = array();
            $sn_not_list = array();
            $order_list = array();
            
            /* 就按ID删除 */
            $id_list = array_values($input->ids);
            
            /* 批量下载订单 */
            if ('download' == $operation) {
                $modelOrder = new Admin_Model_Order();
                $orderList = $modelOrder->getListByIds($id_list);
                if (empty($orderList)) {
                    $this->sysMsg("请选择一个有效地订单");
                }
                $this->export($orderList);
                exit();
            }
            
            if (empty($sn_not_list)) {
                $sn_list = empty($sn_list) ? '' : "'更新的订单：'" . join($sn_list, ',');
                $msg = $sn_list;
                $links[] = array(
                    'text' => '返回订单列表',
                    'href' => $this->_helper->url('list') . '/uselastfilter/1'
                );
                $this->sysMsg($msg, 0, $links);
            } else {
                /* 模板赋值 */
                $this->view->assign('operation', $operation);
                $this->view->assign('form_act_batch', $this->_helper->url("batch"));
                $this->view->assign('order_info', $sn_str);
                $this->view->assign('order_list', $sn_not_list);
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /* ------------------------------------------------------ */
    // -- 检查订单
    /* ------------------------------------------------------ */
    public function checkorderAction()
    {
        try {
            if (empty($_SESSION['last_check'])) {
                $arr = array(
                    'new_orders' => 0,
                    'new_paid' => 0
                );
            } else {
                $modelOrder = new Admin_Model_Order();
                /* 新订单 */
                $arr['new_orders'] = $modelOrder->getNewOrdersCount();
                /* 新付款的订单 */
                $arr['new_paid'] = $modelOrder->getNewPaidCount();
            }
            
            $_SESSION['last_check'] = time();
            $res = array(
                'error' => 0,
                'message' => '',
                'content' => ''
            );
            foreach ($arr as $key => $val) {
                $res[$key] = $val;
            }
        } catch (Exception $e) {
            $res = array(
                'error' => 1,
                'message' => $e->getMessage(),
                'content' => ''
            );
        }
        exit(json_encode($res));
    }

    protected function getListFilterInput()
    {
        $options = array(
            'presence' => 'optional',
            'allowEmpty' => true
        );
        
        $filters = array(
            '*' => 'StringTrim',
            'pay_status' => 'Int',
            'order_status' => 'Int',
            'refund_status' => 'Int',
            'page' => 'Int',
            'page_size' => 'Int'
        );
        
        $validators = array(
            'id' => array(),
            'out_trade_no' => array(),
            'OpenId' => array(),
            'consignee_name' => array(),
            'consignee_address' => array(),
            'consignee_zipcode' => array(),
            'consignee_tel' => array(),
            'start_time' => array(),
            'end_time' => array(),
            'order_status' => array(
                'Int',
                'default' => - 1
            ),
            'pay_status' => array(
                'Int',
                'default' => 2
            ),
            'refund_status' => array(
                'Int',
                'default' => 1
            ),
            'page' => array(
                'Int',
                'default' => 1
            ),
            'page_size' => array(
                'Int',
                'default' => 10
            ),
            
            'sort_by' => array(
                array(
                    'InArray',
                    array(
                        'haystack' => array(
                            'out_trade_no' => 'out_trade_no',
                            '_id' => '_id',
                            'consignee_name' => 'consignee_name',
                            'uma_time_start' => 'uma_time_start',
                            'total_fee' => 'total_fee'
                        ),
                        'strict' => true
                    )
                ),
                'default' => 'uma_time_start',
                'allowEmpty' => true
            ),
            'sort_order' => array(
                array(
                    'InArray',
                    array(
                        'haystack' => array(
                            'DESC' => 'DESC',
                            'ASC' => 'ASC'
                        ),
                        'strict' => true
                    )
                ),
                'default' => 'DESC',
                'allowEmpty' => true
            )
        );
        
        $data = $this->getRequest()->getParams();
        $input = new Zend_Filter_Input($filters, $validators, $data, $options);
        
        return $input;
    }

    protected function getInfoFilterInput()
    {
        $options = array(
            'presence' => 'optional',
            'allowEmpty' => true
        );
        
        $filters = array(
            '*' => 'StringTrim',
            'print' => 'Int',
            'download' => 'Int'
        );
        
        $validators = array(
            'order_id' => array(),
            'out_trade_no' => array(),
            'print' => array(
                'Int',
                'default' => 0
            ),
            'download' => array(
                'Int',
                'default' => 0
            )
        );
        
        $data = $this->getRequest()->getParams();
        $input = new Zend_Filter_Input($filters, $validators, $data, $options);
        
        return $input;
    }

    protected function getBatchFilterInput()
    {
        $options = array(
            'presence' => 'optional',
            'allowEmpty' => true
        );
        
        $filters = array(
            '*' => 'StringTrim'
        );
        
        $validators = array(
            'ids' => array(),
            'action_note' => array(),
            'operation' => array(
                array(
                    'InArray',
                    array(
                        'haystack' => array(
                            'confirm' => 'confirm',
                            'invalid' => 'invalid',
                            'cancel' => 'cancel',
                            'remove' => 'remove',
                            'print' => 'print',
                            'download' => 'download'
                        ),
                        'strict' => true
                    )
                ),
                'default' => 'remove',
                'allowEmpty' => true
            )
        );
        
        $data = $this->getRequest()->getParams();
        $input = new Zend_Filter_Input($filters, $validators, $data, $options);
        
        return $input;
    }

    protected function getFilterInput()
    {
        $actionName = $this->getRequest()->getActionName();
        $options = array(
            'presence' => 'optional',
            'allowEmpty' => true
        );
        
        $filters = array(
            '*' => 'StringTrim',
            'id' => 'Int',
            'order_id' => 'Int',
            'address_id' => 'Int',
            'shipping' => 'Int',
            'batch' => 'Int',
            'suppliers_id' => 'Int'
        );
        
        $validators = array(
            'id' => array(
                'Int',
                'default' => 0
            ),
            'order_id' => array(
                'Int',
                'default' => 0
            ),
            'address_id' => array(
                'Int',
                'default' => 0
            ),
            'step' => array(
                array(
                    'InArray',
                    array(
                        'haystack' => array(
                            'user',
                            'goods',
                            'consignee_name',
                            'shipping',
                            'payment',
                            'other',
                            'money',
                            'invoice'
                        ),
                        'strict' => true
                    )
                ),
                'default' => 'user',
                'allowEmpty' => true
            ),
            'step_act' => array(
                array(
                    'InArray',
                    array(
                        'haystack' => array(
                            'add',
                            'edit'
                        ),
                        'strict' => true
                    )
                ),
                'default' => 'add',
                'allowEmpty' => true
            ),
            'shipping' => array(
                'Int',
                'default' => 0
            ),
            'pay_note' => array(
                'default' => 'N/A'
            ),
            'invoice_no' => array(
                'default' => 'N/A'
            ),
            'action_note' => array(
                'default' => ''
            ),
            'batch' => array(
                'Int',
                'default' => 0
            ),
            'operation' => array(
                'default' => ''
            ),
            'cancel_note' => array(
                'default' => ''
            ),
            'refund' => array(
                'default' => ''
            ),
            'refund_note' => array(
                'default' => ''
            ),
            'suppliers_id' => array(
                'default' => ''
            ),
            'delivery' => array(
                'default' => ''
            ),
            'send_number' => array(
                'default' => ''
            ),
            'suppliers_id' => array(
                'Int',
                'default' => 0
            )
        );
        
        $data = $this->getRequest()->getParams();
        $input = new Zend_Filter_Input($filters, $validators, $data, $options);
        
        return $input;
    }

    private function export(array $orderList)
    {
        $excel = array();
        $fields = array(
            '*:订单号',
            '*:关联订单号',
            '*:支付方式',
            '*:下单时间',
            '*:配送方式',
            '*:配送费用',
            '*:订单状态',
            '*:付款状态',
            '*:发货状态',
            '*:已付金额',
            '*:异常处理状态',
            '*:确认状态',
            '*:订单来源',
            '*:来源店铺编号',
            '*:订单附言',
            '*:收货人姓名',
            '*:收货地址省份',
            '*:收货地址城市',
            '*:收货地址区/县',
            '*:收货详细地址',
            '*:收货人固定电话',
            '*:电子邮箱',
            '*:收货人移动电话',
            '*:邮编',
            '*:货到付款',
            '*:是否开发票',
            '*:发票抬头',
            '*:发票金额',
            '*:优惠方案',
            '*:订单优惠金额',
            '*:商品优惠金额',
            '*:折扣',
            '*:返点积分',
            '*:商品总额',
            '*:订单总额',
            '*:门店编号',
            '*:公司代码'
        );
        $excel['title'] = array_values($fields);
        $datas = array();
        $modelOrder = new Service_Model_Order();
        // 获取所有的SKU
        $modelSku = new Admin_Model_Sku();
        $skuList = $modelSku->getAllList();
        
        foreach ($orderList as $order) {
            // 支付状态
            $isOk = $modelOrder->isOK($order['trade_state'], $order['trade_mode']);
            if (! $isOk) {
                continue;
            }
            $payStatus = $isOk ? "已支付" : "未支付";
            $datas[] = array(
                $order['out_trade_no'], // 订单号
                $order['transaction_id'], // 关联订单号
                "微信支付", // 支付方式
                str_replace("-", "/", $order['uma_time_start']), // 下单时间
                "申通快递", // 配送方式
                "0", // 配送费用
                "active", // 订单状态
                1, // 付款状态
                0, // 发货状态
                $order['total_fee'] / 100, // 已付金额
                "false", // 异常处理状态
                "unconfirmed", // 确认状态
                "国泰微信", // 订单来源
                "guotai-weixinshop", // 来源店铺编号
                "", // 订单附言
                $order['consignee_name'], // 收货人姓名
                empty($order['consignee_province']) ? "无" : $order['consignee_province'], // 收货地址省份
                empty($order['consignee_city']) ? "无" : $order['consignee_city'], // 收货地址城市
                empty($order['consignee_area']) ? "无" : $order['consignee_area'], // 收货地址区/县
                $order['consignee_address'], // 收货详细地址
                "", // 收货人固定电话
                "", // 电子邮箱
                $order['consignee_tel'], // 收货人移动电话
                $order['consignee_zipcode'], // 邮编
                "否", // 货到付款
                "否", // 是否开发票
                "个人", // 发票抬头
                "0", // 发票金额
                "", // 优惠方案
                "0", // 订单优惠金额
                "0", // 商品优惠金额
                $order['discount'] / 100, // 折扣
                "0", // 返点积分
                $order['product_fee'] / 100, // 商品总额
                $order['total_fee'] / 100, // 订单总额
                "", // 门店编号
                "" // 公司代码
                        );
        }
        
        $datas[] = array(
            "*:订单号",
            "*:关联订单号",
            "*:子订单号",
            "*:商品货号",
            "*:商品名称",
            "*:商品规格",
            "*:购买数量",
            "*:商品单价",
            "*:小计",
            "*:成交金额",
            "*:优惠金额",
            "*:优惠方案",
            "",
            "",
            "",
            "",
            "",
            "",
            "",
            "",
            "",
            "",
            "",
            "",
            "",
            "",
            "",
            "",
            "",
            "",
            "",
            "",
            "",
            "",
            "",
            "",
            ""
        );
        
        // sku
        foreach ($orderList as $order) {
            // 支付状态
            $isOk = $modelOrder->isOK($order['trade_state'], $order['trade_mode']);
            if (! $isOk) {
                continue;
            }
            $list = $modelSku->getListByGid($skuList, $order['ProductId']);
            foreach ($list as $sku) {
                $datas[] = array(
                    $order['out_trade_no'],
                    $order['transaction_id'],
                    "",
                    $sku['sku_no'],
                    $sku['sku_name'],
                    $sku['spec'],
                    $order['gnum'],
                    $sku['price'] / 100,
                    $order['gnum'] * $sku['price'] / 100,
                    $order['gnum'] * $sku['price'] / 100,
                    $sku['discount'] / 100,
                    $sku['discount_desc'],
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    ""
                );
            }
        }
        $excel['result'] = $datas;
        arrayToCVS('export_order_' . date('YmdHis'), $excel);
    }

    private function getList($input)
    {
        $modelOrder = new Admin_Model_Order();
        $list = $modelOrder->getList($input);
        
        $this->view->assign('list', $list['data']);
        $this->view->assign('filter', $list['filter']);
        $this->view->assign('record_count', $list['record_count']);
        $this->view->assign('page_count', $list['page_count']);
        
        $sortFlag = $this->sortFlag($list['filter']);
        $this->view->assign($sortFlag['tag'], $sortFlag['img']);
        
        return array(
            'filter' => $list['filter'],
            'page_count' => $list['page_count']
        );
    }
}

