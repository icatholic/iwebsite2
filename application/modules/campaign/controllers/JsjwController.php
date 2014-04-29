<?php

class Campaign_JsjwController extends iWebsite_Controller_Action
{

    private $_user;

    private $_point;

    private $_weixin_user;

    private $_config;

    public function init()
    {
        $this->_user = new Weixin_Model_User();
    }

    public function indexAction()
    {
        $FromUserName = isset($_GET['FromUserName']) ? trim($_GET['FromUserName']) : '';
        
        // 检测是否为关注用户
        if ($this->_user->checkOpenId($FromUserName)) {
            $rst = doGet("http://kotexcrm.umaman.com/lottery/index/get?FromUserName={$FromUserName}&activity_id=532058de489619f50d7eb1b7");
            $this->assign('rst', $rst);
            $config = $this->getConfig();
            $this->assign('rootPath', $config['global']['path']);
        } else {
            $url = 'http://mp.weixin.qq.com/s?__biz=MjM5MjUxMTQ3Mg==&mid=200170766&idx=1&sn=d9cb3b910bc3d438c8d21d51a804b172#rd&ADUIN=6047329&ADSESSION=1394763272&ADTAG=CLIENT.QQ.5281_.0&ADPUBNO=26292';
            header("location:{$url}");
            exit();
        }
    }
}

