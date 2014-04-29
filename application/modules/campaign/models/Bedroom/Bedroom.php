<?php

class Campaign_Model_Bedroom_Bedroom extends iWebsite_Plugin_Mongo
{

    protected $name = 'bedroom';

    protected $dbName = 'default';

    private $_bedroomInfo = null;

    /**
     * 创建一个寝室
     *
     * @param string $openid            
     *
     */
    public function createBedroom($openid)
    {
        $check = $this->count(array(
            'openid' => $openid,
            'is_full' => false
        ));
        
        if ($check > 0) {
            return false;
        }
        
        $datas = array();
        $datas['openid'] = $openid;
        $datas['invitee'] = array(
            $openid
        );
        $datas['is_full'] = false;
        $this->insertRef($datas);
        return $datas['_id']->__toString();
    }

    /**
     * 获取某个寝室的信息
     *
     * @param string $bedroom_id            
     */
    public function getBedroomInfo($bedroom_id)
    {
        if ($this->_bedroomInfo == null) {
            $this->_bedroomInfo = $this->findOne(array(
                '_id' => myMongoId($bedroom_id)
            ));
        }
        return $this->_bedroomInfo;
    }

    /**
     * 获取我发起的寝室
     *
     * @param string $openid            
     */
    public function getMyBedroom($openid)
    {
        return $this->findAll(array(
            'openid' => $openid
        ), array(
            '__CREATE_TIME__' => - 1
        ));
    }

    /**
     * 获取某人的全部寝室
     *
     * @param string $openid            
     */
    public function getAllbedroom($openid)
    {
        return $this->findAll(array(
            'invitee' => $openid
        ), array(
            '__CREATE_TIME__' => - 1
        ));
    }

    /**
     * 获取我参加的活动
     * @param string $openid
     */
    public function getMyJoinBedroom($openid)
    {
        return $this->findAll(array(
            'invitee' => $openid,
            'openid' => array(
                '$ne' => $openid
            )
        ), array(
            '__CREATE_TIME__' => - 1
        ));
    }

    /**
     * 某人加入到某间寝室
     *
     * @param string $bedroom_id            
     * @param string $openid            
     */
    public function joinBedroom($bedroom_id, $openid)
    {
        $query = array(
            '_id' => myMongoId($bedroom_id)
        );
        $bedroomInfo = $this->findOne($query);
        if (! empty($bedroomInfo['is_full']))
            return false;
        
        $update = array(
            '$addToSet' => array(
                'invitee' => $openid
            )
        );
        if (count($bedroomInfo['invitee']) >= 3) {
            array_push($update, array(
                '$set' => array(
                    'is_full' => true
                )
            ));
        }
        $this->update($query, $update);
        return true;
    }

    /**
     * 获取寝室成员信息
     */
    public function getBedroomMembers()
    {
        $this->getBedroomInfo($bedroom_id);
        $invitee = $this->_bedroomInfo['invitee'];
        $openid = $this->_bedroomInfo['openid'];
        $invitee = array_slice($invitee, 0, 4);
        $invitee = array_filter($invitee, function ($var) use($openid)
        {
            if ($var === $openid)
                return false;
        });
        
        $members = array();
        if (! empty($invitee)) {
            foreach ($invitee as $one) {
                $members[] = $this->getWeixinUserInfo($one);
            }
        }
        return $members;
    }

    /**
     * 获取寝室的发起人
     *
     * @param string $bedroom_id            
     */
    public function getOwnerOfBedroom($bedroom_id)
    {
        $this->getBedroomInfo($bedroom_id);
        return $this->getWeixinUserInfo($this->_bedroomInfo['openid']);
    }

    /**
     * 获取微信用户信息
     *
     * @param string $openid            
     */
    public function getWeixinUserInfo($openid)
    {
        $modelWeixinUser = new Weixin_Model_User();
        return $modelWeixinUser->findOne(array(
            'openid' => $openid
        ));
    }
}