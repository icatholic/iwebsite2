<?php

class User_IndexController extends iWebsite_Controller_Action
{
    
    public function init()
    {
    }
    
    public function indexAction()
    {
        $oBind = new User_Model_Bind();
        $x = $oBind->getSchema();
        var_dump($x);
        exit;
    }
    
    
    /**
     * http://www.example.com/weixin/sns/index?redirect=回调地址&scope=[snsapi_userinfo(default)|snsapi_base]
     * 引导用户去往登录授权
     */
    public function demoAction()
    {
//         $FromUserName = $this->get('FromUserName','');
//         if($FromUserName == '')
//         {
//         	$url = 'http://140318dg0012.umaman.com/user/authorize/weixin?redirect=http://27.115.13.122/140528fg0226/user/index/demo';
//         	$this->redirect($url);
//         }
//         else 
//         {
//         	var_dump($_REQUEST);
//         }
//         $oBind = new User_Model_Bind();
//         $x = $oBind->getSchema();
//         var_dump($x);exit;
        $oUserWeixin = new Exchange_Model_Rule();
        var_dump($oUserWeixin->findAll(array()));exit;
        $arrayInfo = array('openid'=>'abc');
        echo $oUserWeixin->add($arrayInfo,'aaaaab');
        echo '<br>';
        $oUserWeibo = new User_Model_Weibo();
        $arrayInfo = array('weibo_id'=>'efg');
        echo  $oUserWeibo->add($arrayInfo,'aaaaa');
        exit;
    }
}