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
            $FromUserName = $this->getRequest()->getCookie("openid");
            if (empty($FromUserName)) {
                if ($_SERVER["HTTP_HOST"] != "140428fg0183.umaman.com") {
                    $config = $this->getConfig();
                    $_COOKIE["openid"] = "o1UTVjoFmK9uo8mNvQrtj7rhlyBo";
                    setcookie('openid', "o1UTVjoFmK9uo8mNvQrtj7rhlyBo", time() + 365 * 24 * 3600, $config['global']['path']);
                }else{
                    //微信授权
                    $callbackUrl = urlencode("http://{$_SERVER["HTTP_HOST"]}/weixinshop/index/index");
                    $url = "http://{$_SERVER["HTTP_HOST"]}/weixin/sns/index?redirect={$callbackUrl}&scope=snsapi_base";
                    header("location:{$url}");
                    exit();
                }
            }
            // 获取商品分类列表信息
            $modelGoodsCategory = new Weixinshop_Model_GoodsCategory();
            $categorys = $modelGoodsCategory->getList("");
            $this->assign("categorys", $categorys);
            
            // 根据分类ID获取商品列表
            $modelGoods = new Weixinshop_Model_Goods();
            $goodsList = $modelGoods->getListByCategory();
            $this->assign("products", $goodsList);
            
            // 获取Banner列表
            $modelBanner = new Weixinshop_Model_Banner();
            $bannerList = $modelBanner->getList();
            $this->assign("banners", $bannerList);
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

