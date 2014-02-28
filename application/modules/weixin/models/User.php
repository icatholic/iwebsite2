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
     * 根据用户的互动行为，通过服务器端token获取该用户的个人信息
     * openid不存在或者随机10次执行一次更新用户信息
     */
    public function updateUserInfoByAction($openid)
    {
        if ($this->count(array(
            'openid' => $openid
        )) == 0 || rand(0, 10) == 5) {
            $userInfo = $this->_weixin->getUserManager()->getUserInfo($openid);
            if (! isset($userInfo['errcode'])) {
                $userInfo['subscribe'] = $userInfo['subscribe'] == 1 ? true : false;
                $userInfo['subscribe_time'] = new MongoDate($userInfo['subscribe_time']);
                return $this->update(array(
                    'openid' => $openid
                ), array(
                    '$set' => $userInfo
                ), array(
                    'upsert' => true
                ));
            }
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
        return $this->_user->update(array(
            'openid' => $openid
        ), array(
            '$set' => $userInfo
        ), array(
            'upsert' => true
        ));
    }
}