<?php

class Campaign_InviteController extends iWebsite_Controller_Action
{

    private $_weixin;

    private $_weixin_user;

    public function init()
    {
        $this->_config = Zend_Registry::get('config');
        $this->_weixin_user = new Weixin_Model_User();
    }

    /**
     * 显示邀请活动页面
     */
    public function indexAction()
    {
        $openid = isset($_GET['FromUserName']) ? trim($_GET['FromUserName']) : '';
        $redirect = $this->_config['global']['path'] . 'campaign/invite/index?FromUserName=' . $openid;
        if (! $this->_weixin_user->checkOpenId($openid)) {
            $this->_forward('user-info', 'calendar', 'campaign', array(
                'redirect' => $redirect,
                'openid' => $openid
            ));
        }
        $this->view->assign('inviteUrl', 'http://' . $_SERVER['HTTP_HOST'] . $this->_config['global']['path'] . 'campaign/invite/friend?FromUserName=' . $openid);
        $this->view->assign('inviteImage', 'http://' . $_SERVER['HTTP_HOST'] . $this->_config['global']['path'] . 'html/img/logo.jpg');
        $config = $this->getConfig();
        $this->assign('rootPath', $config['global']['path']);
        $this->assign('openid', $openid);
    }

    /**
     * 发送给朋友的邀请链接
     * http://www.example.com/campaign/invite/friend?FromUserName=xxxxxxxxx
     */
    public function friendAction()
    {
        $this->getHelper('viewRenderer')->setNoRender(true);
        $FromUserName = isset($_GET['FromUserName']) ? trim($_GET['FromUserName']) : '';
        $redirect = $this->_config['global']['path'] . 'campaign/invite/success?FromUserName=' . $FromUserName;
        header("location:{$this->_config['global']['path']}weixin/sns/index?redirect={$redirect}&scope=snsapi_userinfo");
        exit();
    }

    /**
     * 授权成功之后的页面
     */
    public function successAction()
    {
        $this->getHelper('viewRenderer')->setNoRender(true);
        $openid = isset($_GET['FromUserName']) ? trim($_GET['FromUserName']) : '';
        $invitedOpenid = isset($_SESSION['iWeixin']['accessToken']['openid']) ? trim($_SESSION['iWeixin']['accessToken']['openid']) : '';
        // 记录邀请信息
        if (! empty($invitedOpenid) && ! empty($openid) && $openid != $invitedOpenid) {
            $model = new Campaign_Model_User_Invite();
            $model->record($openid, $invitedOpenid);
        }
        // 提示用户关注微信之后才能获取积分
        
        //直接跳转到指定的页面
        $url = 'http://mp.weixin.qq.com/s?__biz=MjM5MjUxMTQ3Mg==&mid=200144027&idx=1&sn=ce02c97dbb6b905679c692a34da6ecc8#rd';
        header("location:{$url}");
        exit();
        
        $config = $this->getConfig();
        $this->assign('rootPath', $config['global']['path']);
        $this->assign('version',$this->getVersion());
        $this->assign('openid',$openid);
        $this->assign('invitedOpenid',$invitedOpenid);
        $this->renderScript("calendar/care.phtml");
    }

}

