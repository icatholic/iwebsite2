<?php

/**
 * 微信商城--订单
 * @author 郭永荣
 *
 */
class Weixinshop_OrderController extends iWebsite_Controller_Action
{

    private $errorLog = null;

    private $modelOrder = null;

    public function init()
    {
        parent::init();
        $this->getHelper('viewRenderer')->setNoRender(false);
        $this->errorLog = new Weixinshop_Model_PayErrorLog();
        $this->modelOrder = new Weixinshop_Model_Order();
    }

    /**
     * 显示订单一览页面
     */
    public function listAction()
    {
        try {
            $FromUserName = $this->getRequest()->getCookie("openid");
            $page = intval($this->get('page', '1'));
            $limit = intval($this->get('limit', '5'));
            $orderList = $this->modelOrder->getList4PayFinished($FromUserName, $page, $limit);
            $this->assign("orderList", $orderList);
            $this->assign("is_more", $orderList['total'] > $page * $limit ? 1 : 0);
            $this->assign("OpenId", $FromUserName);
        } catch (Exception $e) {
            exit($this->error($e->getCode(), $e->getMessage()));
        }
    }

    /**
     * 根据分页获取订单接口
     */
    public function getListAction()
    {
        // http://140428fg0183/weixinshop/order/get-list?jsonpcallback=?&page=1&limit=2
        try {
            $this->getHelper('viewRenderer')->setNoRender(true);
            $FromUserName = $this->getRequest()->getCookie("openid");
            $page = intval($this->get('page', '1'));
            $limit = intval($this->get('limit', '5'));
            $orderList = $this->modelOrder->getList4PayFinished($FromUserName, $page, $limit);
            $this->assign("orderList", $orderList);
            $this->assign("is_more", $orderList['total'] > $page * $limit ? 1 : 0);
            $content = $this->view->render('order/partials/order_list.phtml');
            
            echo $this->result('获取成功', array(
                'content' => $content,
                'is_more' => $orderList['total'] > $page * $limit ? 1 : 0
            ));
            return true;
        } catch (Exception $e) {
            $this->errorLog->log($e);
            echo ($this->error($e->getCode(), $e->getMessage()));
            return false;
        }
    }

    /**
     * 显示订单详细页面
     */
    public function detailAction()
    {
        try {
            $out_trade_no = $this->get("out_trade_no");
            if (empty($out_trade_no)) {
                throw new Exception("订单号为空");
            }
            $orderInfo = $this->modelOrder->getInfoByOutTradeNo($out_trade_no);
            if (empty($orderInfo)) {
                throw new Exception("订单号无效");
            }
            $this->assign("orderInfo", $orderInfo);
        } catch (Exception $e) {
            exit($this->error($e->getCode(), $e->getMessage()));
        }
    }

    /**
     * 下单页面
     */
    public function payAction()
    {
        try {
            $FromUserName = trim($this->get('FromUserName', '')); // 微信号
            $orderId = trim($this->get('orderId', '')); // 订单号
                                                        
            // if (empty($orderId)) {
                                                        // throw new Exception("订单号为空");
                                                        // }
            
            $this->assign("orderId", $orderId);
            
            if (! empty($orderId)) {
                $orderInfo = $this->modelOrder->getInfoById($orderId);
                if (empty($orderInfo)) {
                    throw new Exception("订单不存在");
                }
                $isOK = $this->modelOrder->isOK($orderInfo['trade_state'], $orderInfo['trade_mode']);
                if ($isOK) {
                    throw new Exception("订单已支付");
                }
            } else {
                if (empty($_SESSION['checkout'])) {
                    throw new Exception("未购买商品");
                }
                // 构建一个假的订单
                $orderInfo['OpenId'] = $_SESSION['checkout']['OpenId'];
                $orderInfo['details'] = $_SESSION['checkout']['goods'];
                $orderInfo['transport_fee'] = 0;
                $orderInfo['Product_fee'] = $this->modelOrder->getProductFee($orderInfo['details']);
                $orderInfo['total_fee'] = $this->modelOrder->getTotalFee($orderInfo['transport_fee'], $orderInfo['Product_fee']);
            }
            // 或者是其他一些判断条件
            $this->assign("orderInfo", $orderInfo);
            
            // 获取发货人信息
            $modelConsignee = new Weixinshop_Model_Consignee();
            $consigneeInfo = $modelConsignee->getLastInfoByOpenid($orderInfo['OpenId']);
            $this->assign("consigneeInfo", $consigneeInfo);
            
            // 获取快递信息
            $modelFreightCampany = new Tools_Model_Freight_Campany();
            $freightCampanyList = $modelFreightCampany->getList();
            $this->assign("freightCampanyList", $freightCampanyList);
            
            // 获取省信息
            $modelFreightArea = new Tools_Model_Freight_Area();
            $provinceList = $modelFreightArea->getProvinces();
            $this->assign("provinceList", $provinceList);
            
            $authorizeAction = trim($this->get('authorizeAction', '')); // 操作
            $state = "editaddress";
            $url = "http://{$_SERVER["HTTP_HOST"]}/weixinshop/order/pay?showwxpaytitle=1&orderId={$orderId}&authorizeAction=weixin&FromUserName={$FromUserName}";
            
            $config = $this->getConfig();
            $appid = $config['iWeixin']['pay']['appId'];
            
            if ($authorizeAction == 'weixin') {
                $token = $_SESSION['iWeixin']['accessToken'];
                $accessToken = $token['access_token'];
                $this->assign("appId", $appid);
                
                $timeStamp = time();
                $this->assign("timeStamp", $timeStamp);
                
                $nonceStr = md5(time() . mt_rand(0, 1000));
                $this->assign("nonceStr", $nonceStr);
                
                $addrSign = $this->getAddrSign($appid, $timeStamp, $nonceStr, $accessToken, $url);
                $this->assign("addrSign", $addrSign);
            } else {
                if ($_SERVER["HTTP_HOST"] != "140428fg0183.umaman.com") {
                    $config = $this->getConfig();
                    $_COOKIE["openid"] = "o1UTVjoFmK9uo8mNvQrtj7rhlyBo";
                    setcookie('openid', "o1UTVjoFmK9uo8mNvQrtj7rhlyBo", time() + 365 * 24 * 3600, $config['global']['path']);
                } else {
                    unset($_SESSION['iWeixin']['accessToken']);
                    setcookie('openid', '', - 3600, '/');
                    // 微信授权
                    $callbackUrl = urlencode($url);
                    $url = "http://{$_SERVER["HTTP_HOST"]}/weixin/sns/index?redirect={$callbackUrl}&scope=snsapi_base";
                    header("location:{$url}");
                    exit();
                }
            }
        } catch (Exception $e) {
            $this->errorLog->log($e);
            exit($this->error($e->getCode(), $e->getMessage()));
        }
    }

    /**
     * 结果页面
     */
    public function resultAction()
    {
        try {
            // 检查是否有订单的信息
            if (empty($_SESSION['orderInfo'])) {
                throw new Exception("订单不存在");
            }
            $orderInfo = $_SESSION['orderInfo'];
            $_SESSION['orderInfo'] = null;
            
            $OpenId = $this->getRequest()->getCookie("openid", '');
            if (empty($OpenId)) {
                throw new Exception("微信号为空");
            }
            
            // 查询订单的状态
            $out_trade_no = $orderInfo['out_trade_no'];
            if (strlen($out_trade_no) < 1) {
                throw new Exception('商户订单号out_trade_no为空');
            }
            
            $orderInfo = $this->modelOrder->getInfoByOutTradeNo($out_trade_no);
            if (empty($orderInfo)) {
                throw new Exception("订单号out_trade_no无效:{$out_trade_no}");
            }
            
            // 是否支付成功
            $isOk = false; // $this->modelOrder->isOK ( $orderInfo ['trade_state'],
                           // $orderInfo ['trade_mode'] );
            if (! $isOk) {
                // // 调用订单查询 orderquery
                // $iWeixinPay = $this->modelOrder->getWeixinPayInstance ();
                // $ret = $iWeixinPay->orderquery ( $out_trade_no, time () );
                // $order_info = $ret ['order_info'];
                // // 订单状态的改变
                // $newOrderInfo = $this->modelOrder->changeStatus ( $orderInfo,
                // $order_info, 'orderquery' );
                
                // 获取财富通的订单结果
                $order_info = $this->modelOrder->queryTenpayInfo($orderInfo['out_trade_no']);
                // 订单状态的改变
                $newOrderInfo = $this->modelOrder->changeStatus($orderInfo, $order_info, 'orderquery4tenpay');
                
                // 是否支付成功
                $isOk = $this->modelOrder->isOK($newOrderInfo['trade_state'], $newOrderInfo['trade_mode']);
                
                if ($isOk) { // 发送客服消息
                    $total_fee = number_format($newOrderInfo['total_fee'] / 100, 2);
                    $content = "你支付了{$total_fee}元人民币,成功购买{$newOrderInfo['body']}的商品";
                    
                    $modelWeixinApplication = new Weixin_Model_Application();
                    $modelWeixinApplication->notify($OpenId, $content);
                }
            }
        } catch (Exception $e) {
            $this->errorLog->log($e);
            $isOk = false;
        }
        $this->assign("isOK", empty($isOk) ? 0 : 1);
    }

    private function getAddrSign($appid, $timeStamp, $nonceStr, $accessToken, $url)
    {
        $parameters = array();
        $parameters["appid"] = $appid;
        $parameters["accesstoken"] = $accessToken;
        $parameters["noncestr"] = $nonceStr;
        $parameters["url"] = $url;
        $parameters["timestamp"] = $timeStamp;
        
        $reqPar = "";
        ksort($parameters);
        foreach ($parameters as $k => $v) {
            $reqPar .= $k . "=" . $v . "&";
        }
        
        $reqPar = substr($reqPar, 0, strlen($reqPar) - 1);
        $addrSign = sha1($reqPar);
        return $addrSign;
    }

    /**
     * 确认收货并评价接口
     */
    public function confirmShippingAction()
    {
        // http://140428fg0183/weixinshop/order/confirm-shipping?jsonpcallback=?&out_trade_no=1&comment=xxx&advice=xxx
        try {
            $this->getHelper('viewRenderer')->setNoRender(true);
            $out_trade_no = trim($this->get('out_trade_no', ''));
            if (empty($out_trade_no)) {
                throw new Exception("订单号out_trade_no为空");
            }
            $comment = trim($this->get('comment', ''));
            if (empty($comment)) {
                throw new Exception("评价为空");
            }
            $advice = trim($this->get('advice', ''));
            
            $orderInfo = $this->modelOrder->getInfoByOutTradeNo($out_trade_no);
            if (empty($orderInfo)) {
                throw new Exception("订单号out_trade_no无效:{$out_trade_no}");
            }
            if ($orderInfo['uma_shipping_status'] == SS_RECEIVED) {
                throw new Exception("该订单已确认收货了，无法再次评价");
            }
            
            $this->modelOrder->confirmShipping($orderInfo, $comment, $advice);
            echo $this->result('成功');
            return true;
        } catch (Exception $e) {
            $this->errorLog->log($e);
            echo ($this->error($e->getCode(), $e->getMessage()));
            return false;
        }
    }

    public function __destruct()
    {}
}

