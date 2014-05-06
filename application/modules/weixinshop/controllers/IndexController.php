<?php

/**
 * 微信商城--首页
 * @author 郭永荣
 *
 */
class Weixinshop_IndexController extends iWebsite_Controller_Action
{

    public function init()
    {
        parent::init();
        $this->getHelper('viewRenderer')->setNoRender(false);
    }

    /**
     * 显示商城首页页面
     */
    public function indexAction()
    {
        try {
            // 获取商品分类列表信息
            $modelGoodsCategory = new Weixinshop_Model_GoodsCategory();
            $categorys = $modelGoodsCategory->getList("");
            $this->assign("categorys", $categorys);
        } catch (Exception $e) {
            exit($this->error($e->getCode(), $e->getMessage()));
        }
    }

    /**
     * 显示个人中心画面
     */
    public function myAction()
    {
        try {} catch (Exception $e) {
            exit($this->error($e->getCode(), $e->getMessage()));
        }
    }

    /**
     * 显示购物须知页面
     */
    public function helpAction()
    {
        try {} catch (Exception $e) {
            exit($this->error($e->getCode(), $e->getMessage()));
        }
    }

    public function __destruct()
    {}
}

