<?php

/**
 * 微信商城--商品分类
 * @author 郭永荣
 *
 */
class Weixinshop_GoodsCategoryController extends iWebsite_Controller_Action
{

    public function init()
    {
        parent::init();
        $this->getHelper('viewRenderer')->setNoRender(false);
    }

    /**
     * 显示商品分类页面
     */
    public function indexAction()
    {
        try {
            $categoryId = trim($this->get('categoryId', '')); // 商品分类ID
                                                              
            // 获取商品分类信息
            $modelGoodsCategory = new Weixinshop_Model_GoodsCategory();
            $categoryInfo = $modelGoodsCategory->getInfoById($categoryId);
            $this->assign("category", $categoryInfo);
            
            // 根据分类ID获取商品列表
            $modelGoods = new Weixinshop_Model_Goods();
            $goodsList = $modelGoods->getListByCategory(array($categoryId));
            $this->assign("goodsList", $goodsList);
            
        } catch (Exception $e) {
            exit($this->error($e->getCode(), $e->getMessage()));
        }
    }

    public function __destruct()
    {}
}

