<?php

class Campaign_UserController extends iWebsite_Controller_Action
{

    private $_user;

    private $_point;
    
    private $_weixin_user;
    
    private $_config;

    public function init()
    {
        $this->_weixin_user = new Weixin_Model_User();
        $this->_user = new Campaign_Model_User_Info();
        $this->_point = new Campaign_Model_User_Point();
        $this->_config = $this->getConfig();
    }

    /**
     * 显示个人基本积分信息与积分明细
     */
    public function indexAction()
    {
        $openid = isset($_REQUEST['FromUserName']) ? trim($_REQUEST['FromUserName']) : '';
        $start = isset($_REQUEST['start']) ? intval($_REQUEST['start']) : 0;
        $limit = isset($_REQUEST['limit']) ? intval($_REQUEST['limit']) : 20;
        $info = $this->_user->getUserInfo($openid);
        
        $weixinUserInfo = $this->_weixin_user->getUserInfoById($openid);
//         if ($info == null) {
//             $this->view->assign('total', 0);
//             $this->view->assign('weixinUserInfo', $weixinUserInfo);
//         }
//         else {
            $pointRecords = $this->_point->getRecords($openid, $start, $limit);
            $total = $this->_point->getTotal($openid);
            $this->view->assign('total', $total);
            $this->view->assign('weixinUserInfo', $weixinUserInfo);
            $this->view->assign('pointRecords', $pointRecords);
//         }
        

        $config = $this->getConfig();
        $this->assign('rootPath', $config['global']['path']);
    }
    
    /**
     * 修改个人信息
     */
    public function infoAction() {
        
    }
}

