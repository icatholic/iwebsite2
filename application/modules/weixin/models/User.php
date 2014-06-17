<?php
use Weixin\Client;

class Weixin_Model_User extends iWebsite_Plugin_Mongo
{

    protected $name = 'iWeixin_user';

    protected $dbName = 'weixin';

    private $_weixin;

    public function setWeixinInstance(Client $weixin)
    {
        $this->_weixin = $weixin;
    }

    /**
     * 检测用户是否已经关注或者曾经关注过微信账号
     *
     * @param string $openid            
     * @return boolean
     */
    public function checkOpenId($openid)
    {
        $rst = $this->findOne(array(
            'openid' => $openid
        ));
        if ($rst == null) {
            return false;
        }
        return true;
    }

    /**
     * 获取用户信息
     *
     * @param string $openid            
     */
    public function getUserInfoById($openid)
    {
        return $this->findOne(array(
            'openid' => $openid
        ));
    }

    /**
     * 根据用户的互动行为，通过服务器端token获取该用户的个人信息
     * openid不存在或者随机100次执行一次更新用户信息
     */
    public function updateUserInfoByAction($openid)
    {
        $check = $this->findOne(array(
            'openid' => $openid
        ));
        
        $range = (rand(0, 100) === 1);
        if ($check == null || empty($check['subscribe']) || $range) {
            $userInfo = $this->_weixin->getUserManager()->getUserInfo($openid);
            if (! isset($userInfo['errcode'])) {
                $userInfo['subscribe'] = $userInfo['subscribe'] == 1 ? true : false;
                $userInfo['subscribe_time'] = new MongoDate($userInfo['subscribe_time']);
            } elseif(!$range) {
                $userInfo = array();
                $userInfo['subscribe'] = true;
                $userInfo['subscribe_time'] = new MongoDate();
            }
            
            return $this->update(array(
                'openid' => $openid
            ), array(
                '$set' => $userInfo
            ), array(
                'upsert' => true
            ));
        }
        return false;
    }

    /**
     * 通过活动授权更新微信用户个人信息
     *
     * @param string $openid            
     * @param array $userInfo            
     */
    public function updateUserInfoBySns($openid, $userInfo)
    {
        $userInfo['access_token'] = isset($_SESSION['iWeixin']['accessToken']) ? $_SESSION['iWeixin']['accessToken'] : false;
        return $this->update(array(
            'openid' => $openid
        ), array(
            '$set' => $userInfo
        ), array(
            'upsert' => true
        ));
    }
}