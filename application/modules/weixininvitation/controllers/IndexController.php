<?php

class Weixininvitation_IndexController extends iWebsite_Controller_Action
{

    private $activity = 0;

    public function init()
    {
        parent::init();
        $this->getHelper('viewRenderer')->setNoRender(false);
    }

    /**
     * 首页
     */
    public function indexAction()
    {
        // http://140324fg0120icc/weixininvitation/index/index?jsonpcallback=?&FromUserName=xxx
        try {
            $FromUserName = trim($this->get('FromUserName', ''));
            $this->assign('FromUserName', $FromUserName);
            
            // 获取发送过几次邀请函
            $modelInvitation = new Weixininvitation_Model_Invitation();
            $count = $modelInvitation->getSentCount($FromUserName, $this->activity);
            $this->assign('sendNum', $count);
            
            // 判断是否已领过
            $modelInvitationGotDetail = new Weixininvitation_Model_InvitationGotDetail();
            $info = $modelInvitationGotDetail->getInfoByFromUserName($FromUserName, $this->activity);
            $this->assign("info", $info);
            $this->assign('isGot', empty($info) ? 0 : 1);
        } catch (Exception $e) {
            var_dump(exceptionMsg($e));
        }
    }

    /**
     * 发邀请函接口
     *
     * @return boolean
     */
    public function sendAction()
    {
        // http://140324fg0120icc/weixininvitation/index/send?jsonpcallback=?&FromUserName=xx&nickname=xxx&desc=xxx
        try {
            $FromUserName = trim($this->get('FromUserName', ''));
            if (empty($FromUserName)) {
                echo ($this->error(- 1, "微信ID不能为空"));
                return false;
            }
            $nickname = trim($this->get('nickname', '')); // 邀请函昵称
            if (empty($nickname)) {
                echo ($this->error(- 2, "昵称不能为空"));
                return false;
            }
            $desc = trim($this->get('desc', '')); // 邀请函说明
            if (empty($desc)) {
                echo ($this->error(- 3, "邀请函说明不能为空"));
                return false;
            }
            $url = trim($this->get('url', '')); // 邀请函URL
            if (! empty($url)) {
                $url = urldecode($url);
            }
            
            $worth = intval($this->get('worth', rand(1, 10))); // 价值
            $invited_total = intval($this->get('invited_total', rand(1, 10))); // 接受邀请总次数
            $personal_receive_num = intval($this->get('personal_receive_num', '1')); // 个人领取次数
            
            $is_need_subscribed = intval($this->get('is_need_subscribed', '0')); // 是否需要微信关注
            $is_need_subscribed = empty($is_need_subscribed) ? false : true;
            
            $subscibe_hint_url = trim($this->get('subscibe_hint_url', '')); // 微信关注提示页面链接
            if (! empty($subscibe_hint_url)) {
                $subscibe_hint_url = urldecode($subscibe_hint_url);
            }
            
            // 获取发送过几次邀请函
            $modelInvitation = new Weixininvitation_Model_Invitation();
            $count = $modelInvitation->getSentCount($FromUserName, $this->activity);
            // 业务逻辑开始
            // 增加积分或者是抽奖什么的
            if ($count > 0) { // 不是第一次
            }
            // 业务逻辑结束
            
            // 生成邀请函
            $recordInfo = $modelInvitation->create($FromUserName, $url, $nickname, $desc, $worth, $invited_total, $personal_receive_num, $is_need_subscribed, $subscibe_hint_url, $this->activity);
            $recordInfo['invitationId'] = myMongoId($recordInfo['_id']);
            // 发送成功
            echo ($this->result("OK", $recordInfo));
            return true;
        } catch (Exception $e) {
            echo ($this->error($e->getCode(), $e->getMessage()));
            return false;
        }
    }

    /**
     * 我的邀请函
     */
    public function listAction()
    {
        // http://140324fg0120icc/weixininvitation/index/list?jsonpcallback=?&FromUserName=xxx
        try {
            $FromUserName = trim($this->get('FromUserName', ''));
            $this->assign('FromUserName', $FromUserName);
            // 获取邀请函列表
            $modelInvitation = new Weixininvitation_Model_Invitation();
            $list = $modelInvitation->getListByPage($FromUserName, $this->activity, 1, 100);
            $this->assign('list', $list['datas']);
        } catch (Exception $e) {
            var_dump(exceptionMsg($e));
        }
    }

    /**
     * 邀请函详细信息及领取情况信息页面
     *
     * @throws Exception
     */
    public function detailsAction()
    {
        // http://140324fg0120icc/weixininvitation/index/details?jsonpcallback=?&FromUserName=xx
        try {
            $invitationId = trim($this->get('invitationId', ''));
            if (empty($invitationId)) {
                throw new Exception("邀请函ID为空");
            }
            $modelInvitation = new Weixininvitation_Model_Invitation();
            $invitation = $modelInvitation->getInfoById($invitationId);
            $this->assign('invitation', $invitation);
            if (empty($invitation)) {
                throw new Exception("邀请函不存在");
            }
            // 获取领过的邀请函列表
            $modelInvitationGotDetail = new Weixininvitation_Model_InvitationGotDetail();
            $list = $modelInvitationGotDetail->getListByPage($invitationId, $this->activity, 1, $invitation['invited_total']);
            $this->assign('list', $list['datas']);
        } catch (Exception $e) {
            var_dump(exceptionMsg($e));
        }
    }

    /**
     * 从分享url点击进来，先经过微信授权之后，进入这个页面，获取邀请函
     *
     * @throws Exception
     */
    public function receiveAction()
    {
        // http://140324fg0120icc/weixininvitation/index/receive?jsonpcallback=?&FromUserName=xxx&invitationId=xxx
        try {
            $FromUserName = trim($this->get('FromUserName', '')); // FromUserName
            if (empty($FromUserName)) {
                throw new Exception("微信ID为空", - 1);
            }
            $invitationId = trim($this->get('invitationId', ''));
            if (empty($invitationId)) {
                throw new Exception("邀请函ID为空", - 2);
            }
            // 获取发送过几次邀请函
            $modelInvitation = new Weixininvitation_Model_Invitation();
            $invitation = $modelInvitation->getInfoById($invitationId);
            $this->assign('invitation', $invitation);
            if (empty($invitation)) {
                throw new Exception("邀请函不存在", - 3);
            }
            
            // 判断该邀请函是否被同一个人领了
            $isSame = $modelInvitation->isSame($invitation, $FromUserName);
            $this->assign('isSame', $isSame ? 1 : 0);
            if ($isSame) {
                throw new Exception("被同一个人领了", - 4);
            }
            
            // 判断该邀请函是否领完了
            $isOver = $modelInvitation->isOver($invitation);
            $this->assign('isOver', $isOver ? 1 : 0);
            if ($isOver) {
                if (! empty($invitation['url'])) {
                    $this->gotoUrl($invitation['url'], $FromUserName);
                } else {
                    throw new Exception("领完了", - 5);
                }
            }
            
            // 判断是否已领过
            $modelInvitationGotDetail = new Weixininvitation_Model_InvitationGotDetail();
            $isGot = $modelInvitationGotDetail->isGot($invitationId, $FromUserName, $invitation['personal_receive_num']);
            $this->assign('isGot', $isGot ? 1 : 0);
            if ($isGot) {
                if (! empty($invitation['url'])) {
                    $this->gotoUrl($invitation['url'], $FromUserName);
                } else {
                    throw new Exception("已领过或领取次数已到达", - 6);
                }
            }
            
            $modelInvitationWaitGetDetail = new Weixininvitation_Model_InvitationWaitGetDetail();
            
            if (! empty($invitation['is_need_subscribed'])) { // 如果需要微信关注的话
                $isSubscribed = $this->isSubscribed($FromUserName); // 判断是否关注
                if (empty($isSubscribed)) { // 未关注的话,跳转到某个提示关注的页面
                    if (empty($invitation['subscibe_hint_url'])) {
                        throw new Exception("没有设置关注页面的链接", - 7);
                    }
                    // 将信息记录到等待表中
                    $modelInvitationWaitGetDetail->wait($invitationId, $FromUserName, "需要关注");
                    
                    $url = $invitation['subscibe_hint_url'];
                    $this->_redirect($url);
                    exit();
                }
            }
            
            // 增加接受邀请函处理
            try {
                // 检查是否锁定，如果没有锁定加锁
                if ($modelInvitation->lock($invitationId)) {
                    // 前次操作尚未完成
                    throw new Exception('前次操作尚未完成', - 8);
                }
                
                // 业务逻辑开始
                // 增加积分或者是抽奖什么的
                // 业务逻辑结束
                // 为了防止在高并发的情况下获取的是旧的数据，所以再次获取一下
                $invitation = $modelInvitation->getInfoById($invitationId);
                $this->assign('invitation', $invitation);
                // 随机一个价值
                $got_worth = rand(0, $invitation['worth']);
                $this->assign('got_worth', $got_worth);
                
                $invitationGotInfo = $modelInvitationGotDetail->create($invitationId, $invitation['FromUserName'], $FromUserName, $got_worth, $this->activity);
                $this->assign('invitationGotInfo', $invitationGotInfo);
                
                // 将等待信息记录删除
                $modelInvitationWaitGetDetail->unwait($invitationId, $FromUserName);
                
                // 领取次数加一
                $modelInvitation->incInvitedNum($invitationId, - $got_worth);
                // unlock
                // 释放锁定
                $modelInvitation->unlock($invitationId);
            } catch (Exception $e) {
                throw $e;
            }
            
            if (! empty($invitation['url'])) {
                $this->gotoUrl($invitation['url'], $FromUserName);
            }
        } catch (Exception $e) {
            // var_dump(exceptionMsg($e));
        }
    }

    public function redirectAction()
    {
        exit('进入到这个');
    }

    /**
     * 判断是否已关注
     *
     * @param string $FromUserName            
     * @return boolean
     */
    private function isSubscribed($FromUserName)
    {
        /**
         * 先从缓存中查找有没有该$FromUserName所对应的记录，
         * 如果没有就调用微信用户接口获取微信用户信息,
         * 根据用户信息中的subscribe字段判断是否关注
         * 如果关注的话，将这条用户信息存入缓存中，缓存时间一天
         */
        $cache = Zend_Registry::get('cache');
        $cacheKey = md5("weixin_subscribe_{$FromUserName}");
        $is_subscribe = $cache->load($cacheKey);
        
        if (empty($is_subscribe)) {
            $modelApplication = new Weixin_Model_Application();
            $appConfig = $modelApplication->getToken();
            $weixin = new Weixin\Client();
            if (! empty($appConfig['access_token'])) {
                $weixin->setAccessToken($appConfig['access_token']);
            } else {
                throw new Exception("无法获取access_token");
            }
            $userInfo = $weixin->getUserManager()->getUserInfo($FromUserName);
            if (! isset($userInfo['errcode'])) {
                if (! empty($userInfo['subscribe'])) {
                    $time = 3600 * 1; // 1小时
                    $cache->save($userInfo['subscribe'], $cacheKey, array(), $time);
                    return true;
                } else {
                    return false;
                }
            } else {
                throw new Exception($userInfo['errmsg'], $userInfo['errcode']);
            }
        } else {
            return true;
        }
    }

    /**
     * 跳转地址
     *
     * @param array $invitation            
     * @param string $FromUserName            
     */
    private function gotoUrl($url, $FromUserName)
    {
        if (strpos($url, 'FromUserName') === false) {
            if (strpos($url, '?') === false)
                $url .= '?FromUserName=' . $FromUserName;
            else
                $url .= '&FromUserName=' . $FromUserName;
        }
        if (strpos(strtolower($url), 'http') === false) {
            $config = $this->getConfig();
            $path = $config['global']['path'];
            $scheme = $this->getRequest()->getScheme();
            $host = $this->getRequest()->getHttpHost();
            $url = trim($url, '/');
            $url = "{$scheme}://{$host}{$path}{$url}";
        }
        $this->_redirect($url);
        exit();
    }
}

