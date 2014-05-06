<?php

/**
 * 微信商城--商品
 * @author 郭永荣
 *
 */
class Weixinshop_GoodsController extends iWebsite_Controller_Action
{

    public function init()
    {
        parent::init();
        $this->getHelper('viewRenderer')->setNoRender(false);
    }

    /**
     * 显示商品详细页面
     */
    public function indexAction()
    {
        try {
            $goodsId = trim($this->get('goodsId', '')); // 商品ID
            
            // 根据分类ID获取商品列表
            $modelGoods = new Weixinshop_Model_Goods();
            $info = $modelGoods->getInfoByGid($goodsId);
            $this->assign("goods", $info);
        } catch (Exception $e) {
            exit($this->error($e->getCode(), $e->getMessage()));
        }
    }

    /**
     * 显示商品内购页面
     */
    public function innerListAction()
    {
        try {
            // 根据分类ID获取商品列表
            $modelGoods = new Weixinshop_Model_Goods();
            $goodsList = $modelGoods->getList(1); // 内购
            $this->assign("goodsList", $info);
        } catch (Exception $e) {
            exit($this->error($e->getCode(), $e->getMessage()));
        }
    }


    public function __destruct()
    {}
}

