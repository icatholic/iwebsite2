<?php
/**
 * 微信商城--测试
 * @author 郭永荣
 *
 */
class Weixinshop_TestController extends Zend_Controller_Action
{
    public function init()
    {
        $this->getHelper('viewRenderer')->setNoRender(true);
    }

    public function indexAction() {
        $obj = new iWeixinOauth('wx907e2f9c52a7df08','d2c7fd365cc9386bf23bb94d7857f29b','http://guotaiweixin.umaman.com/weixin/test/index','snsapi_userinfo');
        if(isset($_GET['code'])) {
            echo 'has code';
            $obj->getAccessToken();
            var_dump($obj->getUserInfo());
        }
        else {
            $obj->getCode();
            echo 'get code';
        }  
        echo 'finish';
    }
}

