<?php

class Campaign_Model_User_Invite extends iWebsite_Plugin_Mongo
{

    protected $name = 'user_invite';

    protected $dbName = 'default';

    /**
     * 记录邀请人和被邀请人信息
     * 
     * @param string $openid            
     * @param string $invitedOpenid            
     */
    public function record($openid, $invitedOpenid)
    {
        $info = $this->findOne(array(
            'openid' => $openid,
            'invited_openid' => $invitedOpenid
        ));
        if ($info == null) {
            // 检测用户是否已经关注了，如果已经关注的用户，不作为被邀请用户
            $modelWeixinUser = new Weixin_Model_User();
            $userInfo = $modelWeixinUser->findOne(array(
                'openid' => $invitedOpenid
            ));
            if (empty($userInfo['subscribe'])) {
                return $this->insert(array(
                    'openid' => $openid,
                    'invited_openid' => $invitedOpenid,
                    'is_subscribed' => false
                ));
            }
        } else {
            return $info;
        }
    }
}