<?php

class Weixininvitation_Model_Invitation extends iWebsite_Plugin_Mongo
{

    protected $name = 'iWeixinInvitation_Invitation';

    protected $dbName = 'weixininvitation';

    private $isExclusive = true;

    /**
     * 设置排他
     *
     * @param unknown $isExclusive            
     */
    public function setIsExclusive($isExclusive)
    {
        $this->isExclusive = $isExclusive;
    }

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
     * 根据邀请内容ID获取信息
     *
     * @param string $FromUserName            
     * @param number $activity            
     * @param array $otherCondition            
     * @return array
     */
    public function getInfoByFromUserName($FromUserName, $activity = 0, array $otherCondition = array())
    {
        $query = array(
            'FromUserName' => $FromUserName,
            'activity' => $activity
        );
        if (! empty($otherCondition)) {
            $query = array_merge($query, $otherCondition);
        }
        $info = $this->findOne($query);
        return $info;
    }

    /**
     * 根据邀请内容ID获取最新信息
     *
     * @param string $FromUserName            
     * @param number $activity            
     * @param array $otherCondition            
     * @return array
     */
    public function getLatestInfoByFromUserName($FromUserName, $activity = 0, array $otherCondition = array())
    {
        $query = array(
            'FromUserName' => $FromUserName,
            'activity' => $activity
        );
        if (! empty($otherCondition)) {
            $query = array_merge($query, $otherCondition);
        }
        $sort = array(
            'send_time' => - 1
        );
        $list = $this->find($query, $sort, 0, 1);
        if (! empty($list['datas'])) {
            return $list['datas'][0];
        } else {
            return null;
        }
    }

    /**
     * 生成邀请函
     *
     * @param string $FromUserName            
     * @param string $url            
     * @param string $nickname            
     * @param string $desc            
     * @param number $worth            
     * @param number $invited_total            
     * @param number $personal_receive_num            
     * @param boolean $is_need_subscribed            
     * @param string $subscibe_hint_url            
     * @param number $activity            
     * @param array $memo            
     * @return array
     */
    public function create($FromUserName, $url, $nickname, $desc, $worth = 0, $invited_total = 0, $personal_receive_num = 0, $is_need_subscribed = false, $subscibe_hint_url = "", $activity = 0, array $memo = array())
    {
        $data = array();
        $data['activity'] = $activity; // 邀请活动
        $data['FromUserName'] = $FromUserName; // 微信ID
        $data['url'] = $url; // 邀请函URL
        $data['nickname'] = $nickname; // 邀请函昵称
        $data['desc'] = $desc; // 邀请函详细
        $data['worth'] = $worth; // 价值
        $data['invited_num'] = 0; // 接受邀请次数
        $data['invited_total'] = $invited_total; // 接受邀请总次数，如果为0，不限制
        $data['send_time'] = new MongoDate(); // 发送时间
        $data['is_need_subscribed'] = $is_need_subscribed; // 是否需要微信关注
        $data['subscibe_hint_url'] = $subscibe_hint_url; // 微信关注提示页面链接
        $data['personal_receive_num'] = $personal_receive_num; // 个人领取次数，如果为0，不限制
        $data['lock'] = false; // 未锁定
        $data['expire'] = new MongoDate(); // 过期时间
        $data['memo'] = $memo; // 备注
        $info = $this->insert($data);
        return $info;
    }

    /**
     * 根据FromUserName生成或获取邀请函
     *
     * @param string $FromUserName            
     * @param string $url            
     * @param string $nickname            
     * @param string $desc            
     * @param number $worth            
     * @param number $invited_total            
     * @param number $personal_receive_num            
     * @param boolean $is_need_subscribed            
     * @param string $subscibe_hint_url            
     * @param number $activity            
     * @param array $memo            
     * @return array
     */
    public function getOrCreateByFromUserName($FromUserName, $url, $nickname, $desc, $worth = 0, $invited_total = 0, $personal_receive_num = 0, $is_need_subscribed = false, $subscibe_hint_url = "", $activity = 0, array $memo = array())
    {
        $info = $this->getInfoByFromUserName($FromUserName, $activity);
        if (empty($info)) {
            $info = $this->create($FromUserName, $url, $nickname, $desc, $worth, $invited_total, $personal_receive_num, $is_need_subscribed, $subscibe_hint_url, $activity, $memo);
        }
        return $info;
    }

    /**
     * 发送邀请次数
     *
     * @param string $FromUserName            
     * @param number $activity            
     * @return int
     */
    public function getSentCount($FromUserName, $activity = 0)
    {
        $count = $this->count(array(
            'FromUserName' => $FromUserName,
            'activity' => $activity
        ));
        return $count;
    }

    /**
     * 加锁
     *
     * @param string $invitationId            
     * @param boolean $isExclusive            
     * @throws Exception
     * @return boolean
     */
    public function lock($invitationId)
    {
        if (! $this->isExclusive) { // 非排他
            return false;
        }
        // 锁定之前，先清除过期锁
        $this->expire($invitationId);
        
        // 查找当前用户的锁
        $lock = $this->findOne(array(
            '_id' => myMongoId($invitationId)
        ));
        if ($lock == null) {
            throw new Exception("未初始化锁");
        } else {
            $query = array(
                '_id' => $lock['_id'],
                'lock' => false
            );
        }
        
        $options = array();
        $options['query'] = $query;
        $options['update'] = array(
            '$set' => array(
                'lock' => true,
                'expire' => new MongoDate(time() + 300)
            )
        );
        $options['new'] = false; // 返回更新之前的值
        
        $rst = $this->findAndModify($options);
        if (empty($rst['ok'])) {
            throw new Exception("findandmodify失败");
        }
        
        if (empty($rst['value'])) {
            // 已经被锁定
            return true;
        } else {
            // 未被加锁，但是现在已经被锁定
            return false;
        }
    }

    /**
     * 解锁
     *
     * @param string $invitationId            
     */
    public function unlock($invitationId)
    {
        if (! $this->isExclusive) { // 非排他
            return;
        }
        return $this->update(array(
            '_id' => myMongoId($invitationId)
        ), array(
            '$set' => array(
                'lock' => false,
                'expire' => new MongoDate()
            )
        ));
    }

    /**
     * 自动清除过期的锁
     *
     * @param string $invitationId            
     */
    public function expire($invitationId)
    {
        return $this->update(array(
            '_id' => myMongoId($invitationId),
            'expire' => array(
                '$lte' => new MongoDate()
            )
        ), array(
            '$set' => array(
                'lock' => false
            )
        ));
    }

    /**
     * 增加接受邀请次数
     *
     * @param string $invitationId            
     * @param int $minusWorth
     *            减少的价值
     * @throws Exception
     * @return boolean
     */
    public function incInvitedNum($invitationId, $minusWorth = 0)
    {
        $info = $this->getInfoById($invitationId);
        if (empty($info)) {
            throw new Exception("邀请函记录不存在");
        }
        $query = array(
            '_id' => $info['_id']
        );
        
        if ($this->isExclusive) { // 排他
            $query['lock'] = true;
        }
        
        if (! empty($info['invited_total'])) {
            $query['invited_num'] = array(
                '$lt' => $info['invited_total']
            );
        }
        
        $options = array();
        $options['query'] = $query;
        $options['update'] = array(
            '$inc' => array(
                'invited_num' => 1,
                'worth' => $minusWorth
            )
        );
        $options['new'] = true; // 返回更新之后的值
        
        $rst = $this->findAndModify($options);
        if (empty($rst['ok'])) {
            throw new Exception("findandmodify失败");
        }
        
        if (! empty($rst['value'])) {
            return $rst['value'];
        } else {
            throw new Exception("接受邀请次数增加失败");
        }
    }

    /**
     * 分页读取某个用户的全部邀请函
     *
     * @param string $FromUserName            
     * @param number $activity            
     * @param number $page            
     * @param number $limit            
     * @return array
     */
    public function getListByPage($FromUserName, $activity = 0, $page = 1, $limit = 10)
    {
        $sort = array(
            'send_time' => - 1
        );
        $query = array();
        $query['FromUserName'] = $FromUserName;
        $query['activity'] = $activity;
        $list = $this->find($query, $sort, ($page - 1) * $limit, $limit);
        return $list;
    }

    /**
     * 是否同一个人领了
     *
     * @param array $info            
     * @param string $FromUserName            
     * @return boolean
     */
    public function isSame($info, $FromUserName)
    {
        $isSame = ($info['FromUserName'] == $FromUserName) ? true : false;
        return $isSame;
    }

    /**
     * 是否已经领完了
     *
     * @param array $info            
     * @throws Exception
     * @return boolean
     */
    public function isOver($info)
    {
        $isOver = (! empty($info['invited_total']) && $info['invited_num'] >= $info['invited_total']) ? true : false;
        return $isOver;
    }

    /**
     * 获取总价值和总邀请次数
     *
     * @param string $FromUserName            
     * @param number $activity            
     * @return number
     */
    public function getTotalByFromUserName($FromUserName, $activity = 0)
    {
        /**
         * [
         * { $match: { status: "A" } },
         * { $group: { _id: "$cust_id", total: { $sum: "$amount" } } },
         * { $sort: { total: -1 } }
         * ]
         */
        $rst = $this->aggregate(array(
            array(
                '$match' => array(
                    'FromUserName' => $FromUserName,
                    'activity' => $activity
                )
            ),
            array(
                '$group' => array(
                    '_id' => '$FromUserName',
                    'totalWorth' => array(
                        '$sum' => '$worth'
                    ),
                    'totalInvitedNum' => array(
                        '$sum' => '$invited_num'
                    )
                )
            )
        ));
        
        if (! empty($rst['result'])) {
            return $rst['result'][0];
        } else {
            return 0;
        }
    }
}