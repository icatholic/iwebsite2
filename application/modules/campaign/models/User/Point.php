<?php
use Weixin\Client;

class Campaign_Model_User_Point extends iWebsite_Plugin_Mongo
{

    protected $name = 'user_point';

    protected $dbName = 'default';

    const SUBSCRIBE_POINT = 500;

    const REASON_SUBSCRIBE = 1;

    const INVITE_POINT = 500;

    const REASON_INVITE = 2;

    const INPUT_CODE_POINTS = 100;

    const REASON_INPUT_CODE = 3;

    private $_weixin;

    public function setWeixinInstance(Client $weixin)
    {
        $this->_weixin = $weixin;
    }

    public function getTotal($openid)
    {
        $rst = $this->aggregate(array(
            array(
                '$group' => array(
                    '_id' => '$openid',
                    'total' => array(
                        '$sum' => '$points'
                    )
                )
            ),
            array(
                '$match' => array(
                    '_id' => $openid
                )
            )
        ));
        
        if (! empty($rst['result'])) {
            return $rst['result'][0]['total'];
        } else {
            return 0;
        }
    }

    /**
     * 增加用户 积分
     *
     * @param string $openid            
     * @param int $points            
     */
    public function inc($openid, $points)
    {
        $points = (int) $points;
        $modelUser = new Campaign_Model_User_Info();
        return $modelUser->update(array(
            'openid' => $openid
        ), array(
            '$inc' => array(
                'point' => $points
            )
        ));
    }

    /**
     * 检测用户的关注行为并加分
     *
     * @param string $openid            
     */
    public function subscribe($openid)
    {
        // $this->inc($openid, self::SUBSCRIBE_POINT);
        // return $this->update(array(
        // 'openid' => $openid,
        // 'reason' => self::REASON_SUBSCRIBE
        // ), array(
        // '$set' => array(
        // 'points' => self::SUBSCRIBE_POINT
        // )
        // ), array(
        // 'upsert' => true
        // ));
    }

    /**
     * 邀请积分
     *
     * @param string $openid            
     */
    public function invite($openid)
    {
        $this->inc($openid, self::INVITE_POINT);
        $this->insert(array(
            'openid' => $openid,
            'reason' => self::REASON_INVITE,
            'points' => self::INVITE_POINT
        ));
    }

    /**
     * 对于邀请好友加入微信活动的用户加分
     *
     * @param string $openid            
     */
    public function incInvitePoint($openid)
    {
        $modelInvite = new Campaign_Model_User_Invite();
        $check = $modelInvite->findOne(array(
            'invited_openid' => $openid,
            'is_subscribed' => false
        ));
        
        if ($check != null) {
            $this->invite($check['openid']);
            $modelInvite->update(array(
                '_id' => $check['_id']
            ), array(
                '$set' => array(
                    'is_subscribed' => true
                )
            ));
            $total = $this->getTotal($check['openid']);
            $content = "您的好友已经接受关注高洁丝官方微信的邀请！恭喜您获得500积分，目前累积积分为{$total}积分。";
            $this->sendMsg($check['openid'], $content);
        }
    }

    /**
     * 发送消息
     * @param string $content
     */
    public function sendMsg($to,$content)
    {
        $app = new Weixin_Model_Application();
        $appConfig = $app->getToken();
        $weixin = new Weixin\Client();
        if (! empty($appConfig['access_token'])) {
            $weixin->setAccessToken($appConfig['access_token']);
            $weixin->getMsgManager()
                ->getCustomSender()
                ->sendText($to, $content);
        }
        return true;
    }

    /**
     * 获取积分记录
     *
     * @param string $openid            
     * @param int $start            
     * @param int $limit            
     * @return array
     */
    public function getRecords($openid, $start = 0, $limit = 20)
    {
        $result = $this->find(array(
            'openid' => $openid
        ), array(
            '_id' => - 1
        ), $start, $limit);
        return $result['datas'];
    }

    /**
     * 计算用户本次需要增加的积分
     */
    public function calcInputCodePoints()
    {}
}