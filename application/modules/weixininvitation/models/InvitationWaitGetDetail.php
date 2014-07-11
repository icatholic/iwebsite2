<?php

class Weixininvitation_Model_InvitationWaitGetDetail extends iWebsite_Plugin_Mongo
{

    protected $name = 'iWeixinInvitation_InvitationWaitGetDetail';

    protected $dbName = 'weixininvitation';

    /**
     * 根据ID等待信息
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
     * 根据FromUserName等待信息
     *
     * @param string $FromUserName            
     * @return array
     */
    public function getInfoByFromUserName($FromUserName)
    {
        $query = array(
            'wait_FromUserName' => $FromUserName
        );
        $info = $this->findOne($query);
        return $info;
    }

    /**
     * 生成等待记录
     *
     * @param string $invitation_id            
     * @param string $owner_FromUserName            
     * @param string $wait_FromUserName            
     * @param int $wait_worth            
     * @return array
     */
    public function wait($invitation_id, $wait_FromUserName, $wait_reason = "")
    {
        // 是否存在
        if ($this->isExisted($invitation_id, $wait_FromUserName)) {
            return;
        }
        
        $data = array();
        $data['invitation_id'] = $invitation_id; // 邀请函ID
        $data['wait_FromUserName'] = $wait_FromUserName; // 领邀请函的FromUserName
        $data['wait_time'] = new MongoDate(); // 等待时间
        $data['wait_reason'] = $wait_reason; // 等待原因
        $info = $this->insert($data);
        return $info;
    }

    /**
     * 是否存在
     *
     * @param string $invitation_id            
     * @param string $wait_FromUserName            
     * @return boolean
     */
    public function isExisted($invitation_id, $wait_FromUserName)
    {
        $query = array();
        $query['invitation_id'] = $invitation_id; // 领邀请函的FromUserName
        $query['wait_FromUserName'] = $wait_FromUserName; // 领邀请函的FromUserName
        $num = $this->count($query);
        return ($num > 0);
    }

    /**
     * 删除等待记录
     * @param string $invitation_id
     * @param string $wait_FromUserName
     */
    public function unwait($invitation_id, $wait_FromUserName)
    {
        $query = array();
        $query['invitation_id'] = $invitation_id; // 领邀请函的FromUserName
        $query['wait_FromUserName'] = $wait_FromUserName; // 领邀请函的FromUserName
        $this->remove($query);
    }
}