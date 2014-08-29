<?php

/**
 *  后台订单管理
 */
class Admin_OrderController extends iWebsite_Controller_Admin_Action
{

    private $modelOrder;

    private $modelOrderAction;

    private $readonly = true;

    public function init()
    {
        parent::init();
        $this->modelOrder = new Admin_Model_Order();
        $this->modelOrderAction = new Admin_Model_OrderAction();
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
            
            $operation = $this->get('export');
            
            if ("导出" == $operation) {
                $list = $this->modelOrder->getList($input);
                if (empty($list['data'])) {
                    $this->sysMsg("请选择一个有效地订单");
                }
                $this->export($list['data']);
                exit();
            } else {
                $this->getList($input);
                $this->view->assign('form_act_batch', $this->_helper->url("batch"));
            }
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
            $this->makeJsonResult($content, '', $filter);
        } catch (Exception $e) {
            $this->makeJsonError($e->getMessage());
        }
    }
    
    /* ------------------------------------------------------ */
    // -- 订单查询
    /* ------------------------------------------------------ */
    public function orderqueryAction()
    {
        try {
            // 下单时间
            $hour = date('H');
            $day = date('d');
            $month = date('m');
            $year = date('Y');
            
            if ($hour > 15) {
                $start_time = mktime(15, 0, 0, $month, $day - 1, $year);
                $end_time = mktime(14, 59, 59, $month, $day, $year);
            } else {
                $start_time = mktime(15, 0, 0, $month, $day - 2, $year);
                $end_time = mktime(14, 59, 59, $month, $day - 1, $year);
            }
            $this->view->assign('start_time', $start_time);
            $this->view->assign('end_time', $end_time);
            
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
            $input = $this->getInfoFilterInput();
            if ($input->isValid("order_id")) {
                $order = $this->modelOrder->getInfoById($input->order_id);
                $this->view->assign("order", $order);
            } else {
                $messageInfo = $this->_getValidationMessage($input);
                throw new Exception($messageInfo);
            }
            
            $this->view->assign("goods_list", $order['details']);
            
            $content = $this->view->render('order/partials/order_goods_info.phtml');
            $goods[] = array(
                'order_id' => $input->order_id,
                'str' => $content
            );
            $this->makeJsonResult($goods);
        } catch (Exception $e) {
            $this->makeJsonError($e->getMessage());
        }
    }
    
    /* ------------------------------------------------------ */
    // -- 订单详情页面
    /* ------------------------------------------------------ */
    public function infoAction()
    {
        try {
            
            $input = $this->getInfoFilterInput();
            if ($input->isValid('order_id') || $input->isValid('out_trade_no')) {
                /* 根据订单id或订单号查询订单信息 */
                $order = $this->modelOrder->getInfoById($input->order_id);
                /* 如果订单不存在，退出 */
                if (empty($order)) {
                    throw new Exception("订单不存在");
                }
            } else {
                $messageInfo = $this->_getValidationMessage($input);
                throw new Exception($messageInfo);
            }
            
            /* 取得上一个、下一个订单号 */
            $prev_next_order_id = $this->modelOrder->getPrevNextOrderId($order);
            $this->view->assign($prev_next_order_id);
            
            /* 参数赋值：订单 */
            $this->view->assign('order', $order);
            
            /* 取得订单商品及货品 */
            $this->view->assign('goods_list', $order['details']);
            
            // 获取所有的SKU
            $modelSku = new Admin_Model_Sku();
            $skuList = $modelSku->getAllList();
            $list = $modelSku->getListByCompositeSkuNo($skuList, empty($order['composite_sku_no']) ? "" : $order['composite_sku_no']);
            $this->view->assign('sku_list', $list);
            
            /* 取得能执行的操作列表 */
            $operable_list = $this->modelOrder->getOperableList($order);
            $this->view->assign('operable_list', $operable_list);
            
            /* 取得订单操作记录 */
            $action_list = $this->modelOrderAction->getListByOrderId($input->order_id);
            $this->view->assign('action_list', $action_list);
            
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
                
                $orderList = $this->modelOrder->getListByIds($id_list);
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
                // $this->view->assign('order_info', $sn_str);
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
                
                /* 新订单 */
                $arr['new_orders'] = $this->modelOrder->getNewOrdersCount();
                /* 新付款的订单 */
                $arr['new_paid'] = $this->modelOrder->getNewPaidCount();
            }
            
            $_SESSION['last_check'] = time();
            $this->makeJsonResult($arr);
        } catch (Exception $e) {
            $this->makeJsonError($e->getMessage());
        }
    }
    
    /* ------------------------------------------------------ */
    // -- 操作订单状态（载入页面）
    /* ------------------------------------------------------ */
    public function operateAction()
    {
        try {
            $input = $this->getFilterInput();
            if ($input->isValid("order_id") && $input->isValid("action_note") && $input->isValid("batch")) {
                // get exist
                $orderInfoRow = $this->modelOrder->getInfoById($input->order_id);
            } else {
                $messageInfo = $this->_getValidationMessage($input);
                throw new Exception($messageInfo);
            }
            
            $anonymous = false;
            $show_cancel_note = false;
            $show_refund = false;
            $show_invoice_no = false;
            $show_ship_memo = false;
            $batch = intval($input->batch); // 是否批处理
            $action_note = $input->action_note;
            $order_id = trim($input->order_id);
            $agency_id = intval($input->agency_id);
            
            $confirm = $this->getRequest()->getParam('confirm', '');
            $pay = $this->getRequest()->getParam('pay', '');
            $unpay = $this->getRequest()->getParam('unpay', '');
            $prepare = $this->getRequest()->getParam('prepare', '');
            $ship = $this->getRequest()->getParam('ship', '');
            $unship = $this->getRequest()->getParam('unship', '');
            $cancel = $this->getRequest()->getParam('cancel', '');
            $invalid = $this->getRequest()->getParam('invalid', '');
            $receive = $this->getRequest()->getParam('receive', '');
            $after_service = $this->getRequest()->getParam('after_service', '');
            $return = $this->getRequest()->getParam('return', '');
            $assign = $this->getRequest()->getParam('assign', '');
            $remove = $this->getRequest()->getParam('remove', '');
            $print = $this->getRequest()->getParam('print', '');
            $to_delivery = $this->getRequest()->getParam('to_delivery', '');
            
            if ($confirm) { /* 确认 */
                $require_note = true;
                $action = '确认';
                $operation = 'confirm';
            } elseif ($pay) { /* 付款 */
                $require_note = true;
                $action = '付款';
                $operation = 'pay';
            } elseif ($unpay) { /* 未付款 */
                $require_note = true;
                $show_refund = true;
                $anonymous = false;
                $action = '设为未付款';
                $operation = 'unpay';
            } elseif ($prepare) { /* 配货 */
                $require_note = false;
                $action = '配货';
                $operation = 'prepare';
            } elseif ($ship) { /* 分单 */
                $require_note = true;
                // $action = '生成发货单';
                // $operation = 'split';
                $action = '发货';
                $operation = 'ship';
                $show_ship_memo = true;
                /* 模板赋值 */
                // $this->view->assign('order', $orderInfoRow);
            } elseif ($unship) { /* 未发货 */
                $require_note = true;
                $action = '未发货';
                $operation = 'unship';
            } elseif ($receive) { /* 收货确认 */
                $require_note = true;
                $action = '已收货';
                $operation = 'receive';
            } elseif ($cancel) { /* 取消 */
                $require_note = true;
                $action = '取消';
                $operation = 'cancel';
                $show_cancel_note = true;
                $show_refund = false;
                $anonymous = false;
            } elseif ($invalid) { /* 无效 */
                $require_note = true;
                $action = '无效';
                $operation = 'invalid';
            } elseif ($after_service) { /* 售后 */
                $require_note = true;
                $action = '售后';
                $operation = 'after_service';
            } elseif ($return) { /* 退货 */
                $require_note = true;
                $show_refund = true;
                $anonymous = false;
                $action = '退货';
                $operation = 'return';
            } elseif ($assign) {/* 指派 */} elseif ($remove) {/* 订单删除 */} elseif ($print) {/* 批量打印订单 */} elseif ($to_delivery) {/* 去发货 */}
            
            /* 直接处理还是跳到详细页面 */
            if (($require_note && $action_note == '') || $show_invoice_no || $show_refund || $show_ship_memo) {
                /* 模板赋值 */
                $this->view->assign('require_note', $require_note); // 是否要求填写备注
                $this->view->assign('action_note', $action_note); // 备注
                $this->view->assign('show_ship_memo', $show_ship_memo); // 是否显示发货信息
                $this->view->assign('show_cancel_note', $show_cancel_note); // 是否显示取消原因
                $this->view->assign('show_invoice_no', $show_invoice_no); // 是否显示发货单号
                $this->view->assign('show_refund', $show_refund); // 是否显示退款
                $this->view->assign('anonymous', $anonymous); // 是否匿名
                $this->view->assign('order_id', $order_id); // 订单id
                $this->view->assign('batch', $batch); // 是否批处理
                $this->view->assign('operation', $operation); // 操作
                $this->view->assign('action', $action); // 操作
                $this->view->assign('form_act', $this->_helper->url("operatepost")); // 操作
            } else {
                
                if (! $batch) { /* 直接处理 */
                    $url = $this->_helper->url("operatepost");
                    $url .= "?order_id={$order_id}&operation={$operation}&action_note={$action_note}";
                    $this->_redirect($url);
                    exit();
                } else {}
            }
        } catch (Exception $e) {
            $this->makeJsonError($e->getMessage());
        }
    }
    
    /* ------------------------------------------------------ */
    // -- 操作订单状态（处理提交）
    /* ------------------------------------------------------ */
    public function operatepostAction()
    {
        try {
            $input = $this->getFilterInput();
            if ($input->isValid("order_id") && $input->isValid("action_note") && $input->isValid("operation")) {
                /* 检查能否操作 */
                $order_id = $input->order_id; // 订单ID
                $operation = $input->operation; // 订单操作
                $action_note = $input->action_note; /* 取得备注信息 */
                $cancel_note = trim($input->cancel_note);
                $refund_type = trim($input->refund);
                $refund_note = trim($input->refund_note);
                $ship_express = trim($input->ship_express);
                $ship_no = trim($input->ship_no);
            } else {
                $messageInfo = $this->_getValidationMessage($input);
                throw new Exception($messageInfo);
            }
            
            $orderInfo = $this->modelOrder->getInfoById($order_id);
            /* 初始化提示信息 */
            $msg = '';
            
            if ('confirm' == $operation) { /* 确认 */
                // $this->modelOrder->confirm($orderInfo, $action_note);/* 标记订单为已确认 */
            } elseif ('pay' == $operation) { /* 付款 */
                // $this->modelOrder->pay($orderInfo, $action_note);/* 标记订单为已确认、已付款，更新付款时间和已支付金额，如果是货到付款，同时修改订单为“收货确认” */
            } elseif ('unpay' == $operation) { /* 设为未付款 */
                // $this->modelOrder->unpay($orderInfo, $action_note, $refund_type, $refund_note);/* 标记订单为未付款，更新付款时间和已付款金额 */
            } elseif ('prepare' == $operation) { /* 配货 */
                // $this->modelOrder->prepare($orderInfo, $action_note);/* 标记订单为已确认，配货中 */
            } elseif ('split' == $operation) { /* 分单确认 */
                // $this->modelOrder->split($orderInfo, $action_note, $suppliers_id, $send_number);
            } elseif ('ship' == $operation) { /* 设为已发货 */
                $modelExpress = new Tools_Model_Freight_Express();
                $expressInfo = $modelExpress->getInfoById($ship_express);
                if (empty($expressInfo)) {
                    throw new Exception("你选择的快递公司不存在");
                }
                $this->modelOrder->ship($orderInfo, $action_note, $expressInfo, $ship_no); /* 标记订单为“已发货”，更新发货时间, 订单状态为“确认” */
            } elseif ('unship' == $operation) { /* 设为未发货 */
                $this->modelOrder->unship($orderInfo, $action_note); /* 标记订单为“未发货”，更新发货时间, 订单状态为“确认” */
            } elseif ('receive' == $operation) { /* 收货确认 */
                $this->modelOrder->receive($orderInfo, $action_note); /* 标记订单为“收货确认”，如果是货到付款，同时修改订单为已付款 */
            } elseif ('cancel' == $operation) { /* 取消 */
                $this->modelOrder->cancel($orderInfo, $action_note, $cancel_note, $refund_type, $refund_note); /* 标记订单为“取消”，记录取消原因 */
            } elseif ('invalid' == $operation) { /* 设为无效 */
                $this->modelOrder->invalid($orderInfo, $action_note); /* 标记订单为“无效”、“未付款” */
            } elseif ('return' == $operation) { /* 退货 */
                $this->modelOrder->refund($orderInfo, $action_note, $refund_type, $refund_note);
            } elseif ('after_service' == $operation) { /* 售后 */
                $this->modelOrder->afterService($orderInfo, $action_note);
            } else {
                die('invalid params');
            }
            
            /* 操作成功 */
            $url = $this->_helper->url("info") . "?order_id=" . $order_id;
            $links[] = array(
                'text' => '订单信息',
                'href' => $url
            );
            $this->sysMsg('操作成功' . $msg, 0, $links);
        } catch (Exception $e) {
            throw $e;
            // /* 操作失败 */
            // $links[] = array('text' => '订单信息', 'href' => 'order/info/order_id/' . $order_id);
            // $this->sysMsg('操作失败', 1, $links);
        }
    }
    
    /* ------------------------------------------------------ */
    // -- 批量上传
    /* ------------------------------------------------------ */
    public function uploadAction()
    {
        $op = $this->get('op', 'shipping');
        $this->assign("op", $op);
        $max_input_vars = ini_get("max_input_vars");
        // $this->assign("max", floor($max_input_vars / 9) - 1);
        $this->assign("max", 1000);
        $_SESSION['list'] = array();
    }
    
    /* ------------------------------------------------------ */
    // -- 批量上传：处理
    /* ------------------------------------------------------ */
    public function confirmAction()
    {
        try {
            $this->assign("readonly", $this->readonly);
            $op = $this->get('op', 'shipping');
            $this->assign("op", $op);
            $error = $this->get("error", "");
            if (! empty($error)) {
                return;
            }
            // resetTimeMemLimit();
            if ($_FILES['file']['error'] == UPLOAD_ERR_OK) {
                $fileName = $_FILES['file']['name'];
                $filePath = $_FILES['file']['tmp_name'];
                $importSheetName = trim("Sheet1");
                
                switch (strtolower(pathinfo($fileName, PATHINFO_EXTENSION))) {
                    case 'xls':
                        $inputFileType = 'Excel5';
                        break;
                    case 'xlsx':
                        $inputFileType = 'Excel2007';
                        break;
                    default:
                        throw new Exception('很抱歉，您上传的文件格式无法识别');
                }
                
                $objReader = PHPExcel_IOFactory::createReader($inputFileType);
                $objReader->setReadDataOnly(true);
                $objReader->setLoadSheetsOnly($importSheetName);
                
                $objPHPExcel = $objReader->load($filePath);
                if (! in_array($importSheetName, array_values($objPHPExcel->getSheetNames()))) {
                    throw new Exception('Sheet:"' . $importSheetName . '",不存在，请检查您导入的Excel表格');
                }
                $objPHPExcel->setActiveSheetIndexByName($importSheetName);
                $objActiveSheet = $objPHPExcel->getActiveSheet();
                $sheetData = $objActiveSheet->toArray(null, true, true, true);
                $objPHPExcel->disconnectWorksheets();
                
                unset($objReader, $objPHPExcel, $objActiveSheet);
                gc_collect_cycles(); // 回收内存
                
                if (empty($sheetData)) {
                    throw new Exception('请确认表格中包含数据');
                }
                
                for ($i = 0; $i < 3; $i ++) { // 前2行去除
                    array_shift($sheetData);
                }
                $sheetData = array_reverse($sheetData);
                array_shift($sheetData);
                $sheetData = array_reverse($sheetData);
                
                $list = array();
                foreach ($sheetData as $row) {
                    $tmp = array();
                    $tmp["out_trade_no"] = $row["C"];
                    $tmp["shipping_express_name"] = $row["F"];
                    $tmp["shipping_no"] = $row["N"];
                    $list[] = $tmp;
                }
                unset($sheetData);
                if ($this->readonly) {
                    $_SESSION['list'] = $list;
                }
                $this->assign("list", $list);
            } else {
                throw new Exception('上传文件失败');
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /* ------------------------------------------------------ */
    // -- 批量上传：发货
    /* ------------------------------------------------------ */
    public function shippingAction()
    {
        try {
            // 数据整理
            $list = array();
            if ($this->readonly) {
                $list = empty($_SESSION['list']) ? array() : $_SESSION['list'];
                $outTradeNoList = array();
                $shippingExpressNameList = array();
                $shippingNoList = array();
                foreach ($list as $item) {
                    $outTradeNoList[] = $item['out_trade_no'];
                    $shippingExpressNameList[] = $item['shipping_express_name'];
                    $shippingNoList[] = $item['shipping_no'];
                }
            } else {
                $checkList = $this->get('checked', array());
                $outTradeNoList = $this->get('out_trade_no', array());
                $shippingExpressNameList = $this->get('shipping_express_name', array());
                $shippingNoList = $this->get('shipping_no', array());
                
                if (! empty($checkList)) {
                    foreach ($checkList as $index) {
                        $data = array();
                        $data['out_trade_no'] = $outTradeNoList[$index];
                        $data['shipping_express_name'] = $shippingExpressNameList[$index];
                        $data['shipping_no'] = $shippingNoList[$index];
                        $list[] = $data;
                    }
                }
            }
            $this->assign("list", $list);
            // throw new Exception("发生错误");
            
            // 检查数据
            $processData = array();
            if (empty($outTradeNoList)) {
                throw new Exception("来源单号为空");
            }
            
            if (empty($shippingExpressNameList)) {
                throw new Exception("物流公司为空");
            }
            
            if (empty($shippingNoList)) {
                throw new Exception("物流单号为空");
            }
            
            $orderInfoList = $this->modelOrder->getInfoByOutTradeNos($outTradeNoList);
            
            $modelExpress = new Tools_Model_Freight_Express();
            $expressList = $modelExpress->getList('name');
            
            foreach ($list as $index => $data) {
                $line = $index + 1;
                
                if (empty($data['shipping_no'])) {
                    throw new Exception("第{$line}行的物流单号为空");
                }
                
                if (empty($data['shipping_express_name'])) {
                    throw new Exception("第{$line}行的物流公司为空");
                }
                
                if (empty($data['out_trade_no'])) {
                    throw new Exception("第{$line}行的来源单号为空");
                }
                
                if (! key_exists($data['shipping_express_name'], $expressList)) {
                    throw new Exception("第{$line}行的物流公司不存在");
                }
                $expressInfo = $expressList[$data['shipping_express_name']];
                
                $key = 'out_trade_no_' . $data['out_trade_no'];
                if (! key_exists($key, $orderInfoList)) {
                    throw new Exception("第{$line}行的来源单号不存在");
                }
                $orderInfo = $orderInfoList[$key];
                if ($orderInfo['uma_shipping_status'] == SS_SHIPPED) {
                    throw new Exception("第{$line}行的来源单号所对应的订单已发货了");
                }
                $isCan = $this->modelOrder->getOperableShip($orderInfo);
                if (! $isCan) {
                    throw new Exception("第{$line}行的来源单号所对应的订单未满足发货条件，无法发货");
                }
                
                $processData[] = array(
                    'orderInfo' => $orderInfo,
                    'expressInfo' => $expressInfo,
                    'shipping_no' => $data['shipping_no']
                );
            }
            
            // 发货处理
            $action_note = "批量上传,修改发货状态";
            foreach ($processData as $item) {
                $this->modelOrder->ship($item['orderInfo'], $action_note, $item['expressInfo'], $item['shipping_no']);
            }
            
            /* 显示提示信息，返回商品列表 */
            $url = $this->_helper->url("list");
            $link[] = array(
                'href' => $url,
                'text' => "订单列表"
            );
            $this->sysMsg('批量上传成功', 0, $link);
        } catch (Exception $e) {
            $this->assign("errors", $e->getMessage());
            return $this->_forward("confirm", null, null, array(
                'error' => 'happened',
                'op' => 'shipping'
            ));
            // throw $e;
        }
    }

    protected function getListFilterInput()
    {
        $options = array(
            'presence' => 'optional',
            'allowEmpty' => true
        );
        
        $filters = array(
            '*' => 'StringTrim',
            'ship_status' => 'Int',
            'pay_status' => 'Int',
            'order_status' => 'Int',
            'refund_status' => 'Int',
            'feedback_status' => 'Int',
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
                'default' => 0
            ),
            'ship_status' => array(
                'Int',
                'default' => 0
            ),
            'pay_status' => array(
                'Int',
                'default' => 2
            ),
            'refund_status' => array(
                'Int',
                'default' => 0
            ),
            'feedback_status' => array(
                'Int',
                'default' => 0
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
            'order_id' => array(),
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
            ),
            'ship_no' => array(
                'default' => ''
            ),
            'ship_express' => array(
                'default' => ''
            )
        );
        
        $data = $this->getRequest()->getParams();
        $input = new Zend_Filter_Input($filters, $validators, $data, $options);
        
        return $input;
    }

    private function export(array $orderList)
    {
        $extension = ".xls";
        $file1 = sys_get_temp_dir() . "/" . "order1{$extension}";
        $output = array(
            'by' => 'file',
            'filename' => $file1,
            'writerType' => "Excel5",
            'extension' => $extension
        );
        $this->getExportFile1($orderList, $output);
        
        $file2 = sys_get_temp_dir() . "/" . "order2{$extension}";
        $output = array(
            'by' => 'file',
            'filename' => $file2,
            'writerType' => "Excel5",
            'extension' => $extension
        );
        $this->getExportFile2($orderList, $output);
        
        $zip = new ZipArchive();
        $filename = "order_export_" . date('YmdHis', time()) . '.zip'; // 随机文件名
        $zipname = sys_get_temp_dir() . "/" . $filename;
        
        if (! file_exists($zipname)) {
            $zip->open($zipname, ZipArchive::OVERWRITE); // 创建一个空的zip文件
                                                         // $zip->addFile('/images/show.pic','show.pic');//将/images/show.pic文件添加进去
                                                         // $content=file_get_contents('http://127.0.0.1/data.php?r='$res);
                                                         // if ( $content !== false ) {
                                                         // $zip->addFromString('data.xml',$content);//使用字符串的方式动态添加
                                                         // }
                                                         // $zip->addFromString('test.txt', 'some text ...');
            $zip->addFile($file1, iconv("UTF-8", "GB2312", '订单文件' . $extension));
            $zip->addFile($file2, iconv("UTF-8", "GB2312", '订单商品文件' . $extension));
            
            $zip->close();
            ob_end_clean();
            // 打开文件
            $file = fopen($zipname, "r");
            // 返回的文件类型
            Header("Content-type: application/octet-stream");
            // 按照字节大小返回
            Header("Accept-Ranges: bytes");
            // 返回文件的大小
            Header("Accept-Length: " . filesize($zipname));
            // 这里对客户端的弹出对话框，对应的文件名
            Header("Content-Disposition: attachment; filename=" . $filename);
            // 修改之前，一次性将数据传输给客户端
            echo fread($file, filesize($zipname));
            // 修改之后，一次只传输1024个字节的数据给客户端
            // 向客户端回送数据
            $buffer = 1024; //
                            // 判断文件是否读完
            while (! feof($file)) {
                // 将文件读入内存
                $file_data = fread($file, $buffer);
                // 每次向客户端回送1024个字节的数据
                echo $file_data;
            }
            fclose($file);
            unlink($zipname); // 下载完成后要主动删除
            exit();
        }
    }

    /**
     * 第1个订单导出excel文件--订单文件
     *
     * @param array $orderList            
     * @param array $output            
     */
    private function getExportFile1(array $orderList, array $output = array('by'=>'download','filename'=>"",'writerType' => "Excel2007",'extension' => ".xlsx"))
    {
        $excel = array();
        $fields = array(
            '订单编号',
            '买家会员名',
            '买家支付宝账号',
            '买家应付货款',
            '买家应付邮费',
            '买家支付积分',
            '总金额',
            '返点积分',
            '买家实际支付金额',
            '买家实际支付积分',
            '订单状态',
            '买家留言',
            '收货人姓名',
            '收货地址',
            '运送方式',
            '联系电话',
            '联系手机',
            '订单创建时间',
            '订单付款时间',
            '宝贝标题',
            '宝贝种类',
            '物流单号',
            '物流公司',
            '订单备注',
            '宝贝总数量',
            '店铺Id',
            '店铺名称',
            '支付方式'
        );
        $excel['title'] = array_values($fields);
        $datas = array();
        
        foreach ($orderList as $order) {
            // 支付状态
            $isOk = $this->modelOrder->isOK($order['trade_state'], $order['trade_mode']);
            if (! $isOk) {
                continue;
            }
            $payStatus = $isOk ? "已支付" : "未支付";
            
            $consignee_province = empty($order['consignee_province']) ? "" : ($order['consignee_province'] . ".") ;
            $consignee_city = empty($order['consignee_city']) ? "" : ($order['consignee_city'] . ".") ;
            $consignee_area = empty($order['consignee_area']) ? "" : ($order['consignee_area'] . ".") ;
            $consignee_address = $order['consignee_address'];
            
            $datas[] = array(
                $order['out_trade_no'], // 订单编号
                $order['OpenId'], // 买家会员名
                "", // 买家支付宝账号
                $order['product_fee'] / 100, // 买家应付货款
                $order['transport_fee'] / 100, // 买家应付邮费
                "", // 买家支付积分
                $order['total_fee'] / 100, // 总金额
                "", // 返点积分
                $order['total_fee'] / 100, // 买家实际支付金额
                "", // 买家实际支付积分
                "买家已付款，等待卖家发货", // 订单状态
                "", // 买家留言
                $order['consignee_name'], // 收货人姓名
                $consignee_province . $consignee_city . $consignee_area . $consignee_address, // 收货地址
                "快递", // 运送方式
                "", // 联系电话
                $order['consignee_tel'], // 联系手机
                date("Y-m-d H:i:s", $order['uma_time_start']->sec), // 订单创建时间
                date("Y-m-d H:i:s", $order['uma_time_end']->sec), // 订单付款时间
                $order['body'], // 宝贝标题
                "", // 宝贝种类
                "", // 物流单号
                $this->getDeliveryInfo($order), // 物流公司
                "", // 订单备注
                $this->getBabyCount($order), // 宝贝总数量
                "", // 店铺Id
                "", // 店铺名称
                "微信支付" // 支付方式
                        );
        }
        $excel['result'] = $datas;
        arrayToExcel('订单文件', $excel, $output);
    }

    /**
     * 第2个订单导出excel文件--订单商品文件
     *
     * @param array $orderList            
     * @param array $output            
     */
    private function getExportFile2(array $orderList, array $output = array('by'=>'download','filename'=>"",'writerType' => "Excel2007",'extension' => ".xlsx"))
    {
        $excel = array();
        $fields = array(
            '订单编号',
            '标题',
            '价格',
            '购买数量',
            '外部系统编号',
            '商品属性',
            '套餐信息',
            '备注',
            '订单状态',
            '商家编码',
            '实际单价',
            '标准单价'
        );
        $excel['title'] = array_values($fields);
        
        // 获取所有的SKU
        $modelSku = new Admin_Model_Sku();
        $skuList = $modelSku->getAllList();
        
        $datas = array();
        // sku
        foreach ($orderList as $order) {
            // 支付状态
            $isOk = $this->modelOrder->isOK($order['trade_state'], $order['trade_mode']);
            if (! $isOk) {
                continue;
            }
            $list = $modelSku->getListByCompositeSkuNo($skuList, $order['composite_sku_no']);
            foreach ($list as $sku) {
                $datas[] = array(
                    $order['out_trade_no'], // 订单编号
                    "", // 标题
                    $sku['prize'] / 100, // 价格
                    $order['gnum'], // 购买数量
                    $sku['no'], // 外部系统编号
                    "", // 商品属性
                    "", // 套餐信息
                    "", // 备注
                    "买家已付款，等待卖家发货", // 订单状态
                    "", // 商家编码
                    $sku['prize'] / 100, // 实际单价
                    $sku['prize'] / 100 // 标准单价
                                );
            }
        }
        $excel['result'] = $datas;
        arrayToExcel('订单商品文件', $excel, $output);
    }

    /**
     * 获取宝贝数量
     *
     * @param array $order            
     */
    private function getBabyCount(array $order)
    {
        $count = 0;
        foreach ($order['details'] as $goods) {
            if (! empty($goods['composite_sku_no'])) {
                $skuList = explode(',', $goods['composite_sku_no']);
            } else {
                $skuList = array(
                    $goods['gid']
                );
            }
            $count += $goods['num'] * count($skuList);
        }
        return $count;
    }

    /**
     * 获取运送方式
     *
     * @param array $order            
     */
    private function getDeliveryInfo(array $order)
    {
        $name = array(
            'EMS快递' => 'EMS',
            // '普通快递' => '快递',
            '顺丰快递' => '顺丰'
        );
        if (key_exists($order['freight_campany'], $name)) {
            return $name[$order['freight_campany']];
        } else {
            return "";
            // throw new Exception("名为{$order['freight_campany']}的快递公司没有设置相应的运送方式");
        }
    }

    private function getList($input)
    {
        $list = $this->modelOrder->getList($input);
        
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

