<?php

/**
 * 微信商城--订单
 * @author 郭永荣
 *
 */
class Weixinshop_OrderController extends iWebsite_Controller_Action
{

    private $errorLog = null;

    public function init()
    {
        parent::init();
        $this->getHelper('viewRenderer')->setNoRender(false);
    }

    /**
     * 显示订单一览页面
     */
    public function listAction()
    {
        try {
            $FromUserName = $this->getRequest()->getCookie("FromUserName");
            $modelOrder = new Weixinshop_Model_Order();
            $orderList = $modelOrder->getList4PayFinished($FromUserName, 1, 6);
            $this->assign("orderList", $orderList);
            $this->assign("OpenId", $FromUserName);
        } catch (Exception $e) {
            exit($this->response(false, $e->getMessage()));
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
            $modelOrder = new Weixinshop_Model_Order();
            $orderInfo = $modelOrder->getInfoByOutTradeNo($out_trade_no);
            if (empty($orderInfo)) {
                throw new Exception("订单号无效");
            }
            
            $modelGoods = new Weixinshop_Model_Goods();
            $goodsInfo = $modelGoods->getInfoByGid($orderInfo['ProductId']);
            if (empty($goodsInfo)) {
                throw new Exception("商品号无效");
            }
            
            $this->assign("orderInfo", $orderInfo);
            $this->assign("goods", $goodsInfo);
        } catch (Exception $e) {
            exit($this->response(false, $e->getMessage()));
        }
    }

    public function __destruct()
    {}
}

