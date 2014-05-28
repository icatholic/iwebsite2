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
     * @return array
     */
    public function getInfoByFromUserName($FromUserName)
    {
        $query = array(
            'got_FromUserName' => $FromUserName
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
     * @return array
     */
    public function create($invitation_id, $owner_FromUserName, $got_FromUserName)
    {
        $data = array();
        $data['invitation_id'] = $invitation_id; // 邀请函ID
        $data['owner_FromUserName'] = $owner_FromUserName; // 发送邀请函的FromUserName
        $data['got_FromUserName'] = $got_FromUserName; // 领邀请函的FromUserName
        $data['got_time'] = new MongoDate(); // 获取时间
        $info = $this->insert($data);
        return $info;
    }

    /**
     * 是否已经领过
     *
     * @param string $got_FromUserName            
     * @return boolean
     */
    public function isGot($got_FromUserName)
    {
        $query = array();
        $query['got_FromUserName'] = $got_FromUserName; // 领邀请函的FromUserName
        $num = $this->count($query);
        return ($num > 0);
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
}