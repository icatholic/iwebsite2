<?php

class Campaign_CouponController extends iWebsite_Controller_Action
{

    private $_user;

    private $_point;

    private $_weixin_user;

    private $_config;

    private $_exchange;

    private $_activity;

    private $_lock;

    public function init()
    {
        $this->getHelper('viewRenderer')->setNoRender(true);
        $this->_weixin_user = new Weixin_Model_User();
        $this->_user = new Campaign_Model_User_Info();
        $this->_point = new Campaign_Model_User_Point();
        $this->_exchange = new Lottery_Model_Exchange();
        $this->_activity = new Lottery_Model_Activity();
        $this->_lock = new Lottery_Model_Lock();
        $this->_config = $this->getConfig();
    }

    /**
     * 标准优惠券
     */
    public function indexAction()
    {
        $FromUserName = isset($_GET['FromUserName']) ? trim($_GET['FromUserName']) : '';
        if ($this->_weixin_user->checkOpenId($FromUserName)) {
            $activity_id = '5355f0424996197f2c8b457d';
            
            if (rand(0, 10) == 1) {
                $this->_lock->expireRelease($activity_id, 60);
            }
            $this->_lock->lock($activity_id, $FromUserName);
            $rst = doGet("http://kotexcrm.umaman.com/lottery/index/get?FromUserName={$FromUserName}&activity_id={$activity_id}");
            $this->_lock->release($activity_id, $FromUserName);
            
            echo $rst;
            // 如果成功记录优惠券信息
            $rst = json_decode($rst, true);
            if (! empty($rst['success']) && ! empty($rst['result'])) {
                $identity_id = $rst['result']['identity_id'];
                $exchange_id = $rst['result']['_id'];
                
                $exchangeInfo = $this->_exchange->checkExchangeBy($identity_id, $exchange_id);
                $exchange_id = $exchangeInfo['_id']->__toString();
                $openid = $exchangeInfo['identity_info']['weixin_openid'];
                $activityInfo = $this->_activity->getActivityInfo($exchangeInfo['activity_id']);
                $activity_name = $activityInfo['name'];
                $coupon_name = $exchangeInfo['prize_info']['prize_name'];
                $code = $exchangeInfo['prize_code']['code'];
                $pwd = $exchangeInfo['prize_code']['pwd'];
                $start_time = $exchangeInfo['prize_code']['start_time'];
                $end_time = $exchangeInfo['prize_code']['end_time'];
                $couponModel = new Campaign_Model_User_Coupon();
                $couponModel->record($openid, $activity_name, $coupon_name, $code, $pwd, $start_time, $end_time, $exchange_id);
            }
            
            return true;
        }
    }
}