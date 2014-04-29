<?php

class Campaign_BedroomController extends iWebsite_Controller_Action
{

    private $_user;

    private $_point;

    private $_weixin_user;

    private $_bedroom;

    private $_config;

    public function init()
    {
        $this->_weixin_user = new Weixin_Model_User();
        $this->_user = new Campaign_Model_User_Info();
        $this->_point = new Campaign_Model_User_Point();
        $this->_bedroom = new Campaign_Model_Bedroom_Bedroom();
        $this->_config = $this->getConfig();
    }

    /**
     * 寝室查看页面
     */
    public function indexAction()
    {
        $openid = isset($_GET['FromUserName']) ? trim($_GET['FromUserName']) : '';
        $bedroom_id = isset($_GET['bedroom_id']) ? trim($_GET['bedroom_id']) : '';
        $source = $this->get('source', null);
        
        if (! $this->_user->checkOpenId($openid)) {
            throw new Exception("请关注高洁丝");
        }
        
        // 检查是否存在主动发起的用户
        if (! empty($source) && $source === 'coupon') {
            if (! isset($_COOKIE['haveBeenGotCoupon'])) {
                setcookie('haveBeenGotCoupon', true, time() + 365 * 86400, '/');
                $this->assign('haveBeenGotCoupon', true);
            } else {
                $this->assign('haveBeenGotCoupon', false);
            }
        }
        
        // 检查是否存在主动发起的用户
        $myBedroom = $this->_bedroom->getMyBedroom($openid);
        if (empty($myBedroom)) {
            $rst = $this->_bedroom->getAllbedroom($openid);
        } else {
            $joinBedroom = $this->_bedroom->getMyJoinBedroom($openid);
            $rst = array_merge($myBedroom, $joinBedroom);
        }
        
        if (empty($rst)) {
            // 创建 一个寝室
            $this->assign('createBedroom', true);
        }
        
        $this->assign('bedrooms', $rst);
    }

    /**
     * 创建一间寝室
     */
    public function createAction()
    {
        $this->getHelper('viewRenderer')->setNoRender(true);
        $openid = isset($_GET['FromUserName']) ? trim($_GET['FromUserName']) : '';
        $rst = $this->_bedroom->createBedroom($openid);
        if ($rst == false) {
            echo $this->error(500, '创建失败，您此前创建的房间尚未邀请满室友');
            return false;
        }
        
        echo $this->result('创建成功', $rst);
    }

    /**
     * 入门优惠券
     */
    public function couponAction()
    {
        $FromUserName = isset($_GET['FromUserName']) ? trim($_GET['FromUserName']) : '';
        if (! $this->_user->checkOpenId($FromUserName)) {
            throw new Exception("请关注高洁丝");
        }
        $rst = doGet("http://kotexcrm.umaman.com/campaign/coupon/index?FromUserName={$FromUserName}");
        $rst = json_encode($rst);
        $this->assign('rst', $rst);
        $config = $this->getConfig();
        $this->assign('rootPath', $config['global']['path']);
        
        $this->_forward('index', null, null, array(
            'source' => 'coupon'
        ));
    }

    /**
     * 寝室关系页面
     */
    public function relationshipAction()
    {
        $FromUserName = isset($_GET['FromUserName']) ? trim($_GET['FromUserName']) : '';
        $bedroom_id = isset($_GET['bedroom_id']) ? trim($_GET['bedroom_id']) : '';
    }

    /**
     * 寝室活动邀请成功页面
     */
    public function successAction()
    {
        //被邀请人的openid
        $invitedOpenid = isset($_GET['FromUserName']) ? trim($_GET['FromUserName']) : '';
        $bedroom_id = isset($_GET['bedroom_id']) ? trim($_GET['bedroom_id']) : '';
        $from = isset($_GET['from']) ? trim($_GET['from']) : '';
        if (empty($bedroom_id)) {
            throw new Exception("寝室编号不能为空");
        }
        
        if (! empty($from)) {
            $this->_bedroom->joinBedroom($bedroom_id, $invitedOpenid);
        }
        
        $bedroomInfo = $this->_bedroom->getBedroomInfo($bedroom_id);
        $this->assign('bedroomInfo', $bedroomInfo);
    }

    /**
     * 寝室活动邀请链接
     */
    public function inviteAction()
    {
        $this->getHelper('viewRenderer')->setNoRender(true);
        $FromUserName = isset($_GET['FromUserName']) ? trim($_GET['FromUserName']) : '';
        $bedroom_id = isset($_GET['bedroom_id']) ? trim($_GET['bedroom_id']) : '';
        $redirect = $this->_config['global']['path'] . 'campaign/bedroom/success?' . http_build_query(array(
            'bedroom_id' => $bedroom_id,
            'from' => 'invite'
        ));
        header("location:{$this->_config['global']['path']}weixin/sns/index?redirect={$redirect}&scope=snsapi_userinfo");
        exit();
    }

    /**
     * 仅仅是分享，用于我参加的，但是是别人创建的寝室
     */
    public function shareAction()
    {
        $this->getHelper('viewRenderer')->setNoRender(true);
        $FromUserName = isset($_GET['FromUserName']) ? trim($_GET['FromUserName']) : '';
        $bedroom_id = isset($_GET['bedroom_id']) ? trim($_GET['bedroom_id']) : '';
        if (empty($bedroom_id)) {
            throw new Exception("寝室编号不能为空");
        }
        
        header("location:{$this->_config['global']['path']}campaign/bedroom/success?bedroom_id={$bedroom_id}&from=share");
        exit();
    }

    /**
     * 查看某个寝室的关系结果
     */
    public function seeAction()
    {
        $openid = isset($_GET['FromUserName']) ? trim($_GET['FromUserName']) : '';
        $bedroom_id = isset($_GET['bedroom_id']) ? trim($_GET['bedroom_id']) : '';
        
        $bedroomInfo = $this->_bedroom->getBedroomInfo($bedroom_id);
        $this->assign('bedroomInfo', $bedroomInfo);
    }
}