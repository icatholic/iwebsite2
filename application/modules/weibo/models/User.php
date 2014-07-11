<?php

class Weibo_Model_User extends iWebsite_Plugin_Mongo
{

    protected $name = 'iWeibo_user';

    protected $dbName = 'weibo';

    /**
     * 获取用户信息
     *
     * @param string $uid            
     */
    public function getUserInfoByUid($uid)
    {
        return $this->findOne(array(
            'uid' => $uid
        ));
    }

    /**
     * 通过授权更新微信用户个人信息
     *
     * @param string $uid            
     * @param array $userInfo            
     */
    public function updateUserInfo($uid, $userInfo)
    {
        if (empty($userInfo['access_token'])) {
            $userInfo['access_token'] = isset($_SESSION['iWeibo']['accessToken']) ? $_SESSION['iWeibo']['accessToken'] : false;
        }
        return $this->update(array(
            'uid' => $uid
        ), array(
            '$set' => $userInfo
        ), array(
            'upsert' => true
        ));
    }
}