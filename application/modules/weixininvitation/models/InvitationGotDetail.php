<?php

class Weixininvitation_Model_InvitationGotDetail extends iWebsite_Plugin_Mongo
{

    protected $name = 'iWeixinInvitation_InvitationGotDetail';

    protected $dbName = 'weixininvitation';

    /**
     * 根据ID获取信息
     *
     * @param string $id            
     * @return array
     */
    public function getInfoById($id)
    {
        $query = array(
            '_id' => myMongoId($id)
        );
        $info = $this->findOne($query);
        return $info;
    }

    /**
     * 根据FromUserName获取信息
     *
     * @param string $FromUserName            
     * @param number $activity            
     * @return array
     */
    public function getInfoByFromUserName($FromUserName, $activity = 0)
    {
        $query = array(
            'got_FromUserName' => $FromUserName,
            'activity' => $activity
        );
        $info = $this->findOne($query);
        return $info;
    }

    /**
     * 生成接受记录
     *
     * @param string $invitation_id            
     * @param string $owner_FromUserName            
     * @param string $got_FromUserName            
     * @param int $got_worth            
     * @param number $activity            
     * @param array $memo            
     * @return array
     */
    public function create($invitation_id, $owner_FromUserName, $got_FromUserName, $got_worth = 0, $activity = 0, array $memo = array())
    {
        $data = array();
        $data['activity'] = $activity; // 邀请活动
        $data['invitation_id'] = $invitation_id; // 邀请函ID
        $data['owner_FromUserName'] = $owner_FromUserName; // 发送邀请函的FromUserName
        $data['got_FromUserName'] = $got_FromUserName; // 领邀请函的FromUserName
        $data['got_time'] = new MongoDate(); // 获取时间
        $data['got_worth'] = $got_worth; // 获取价值
        $data['memo'] = $memo; // 备注
        $info = $this->insert($data);
        return $info;
    }

    /**
     * 是否已经领过或领取次数已达到
     *
     * @param string $invitation_id            
     * @param string $got_FromUserName            
     * @return boolean
     */
    public function isGot($invitation_id, $got_FromUserName, $receive_limit = 0)
    {
        if (empty($receive_limit)) {
            return false;
        }
        
        $query = array();
        $query['invitation_id'] = $invitation_id; // 邀请函ID
        $query['got_FromUserName'] = $got_FromUserName; // 领邀请函的FromUserName
        $num = $this->count($query);
        return ($num > ($receive_limit - 1));
    }

    /**
     * 分页读取某个用户的全部邀请函
     *
     * @param string $invitationId            
     * @param number $page            
     * @param number $limit            
     * @return array
     */
    public function getListByPage($invitationId, $page = 1, $limit = 10)
    {
        $sort = array(
            'got_time' => - 1
        );
        $query = array();
        $query['invitation_id'] = $invitationId;
        $list = $this->find($query, $sort, ($page - 1) * $limit, $limit);
        return $list;
    }

    /**
     * 分页读取某个用户邀请函的邀请记录列表
     *
     * @param string $FromUserName            
     * @param number $activity            
     * @param number $page            
     * @param number $limit            
     * @return array
     */
    public function getPageFriendListByFromUserName($FromUserName, $activity = 0, $page = 1, $limit = 10)
    {
        $sort = array(
            'got_time' => - 1
        );
        $query = array();
        $query['owner_FromUserName'] = $FromUserName;
        $query['activity'] = $activity;
        $list = $this->find($query, $sort, ($page - 1) * $limit, $limit);
        return $list;
    }

    /**
     * 分页读取某个用户帮朋友的列表
     *
     * @param string $FromUserName            
     * @param number $activity            
     * @param number $page            
     * @param number $limit            
     * @return array
     */
    public function getPageListByFromUserName($FromUserName, $activity = 0, $page = 1, $limit = 10)
    {
        $sort = array(
            'got_time' => - 1
        );
        $query = array();
        $query['got_FromUserName'] = $FromUserName;
        $query['activity'] = $activity;
        $list = $this->find($query, $sort, ($page - 1) * $limit, $limit);
        return $list;
    }
}