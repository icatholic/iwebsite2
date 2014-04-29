<?php

class Lottery_Model_Lock extends iWebsite_Plugin_Mongo
{

    protected $name = 'iLottery_lock';

    protected $dbName = 'lottery';

    private $_lockInfo = null;

    /**
     * 获取锁定信息,在活动使用锁之前，请在锁集合中创建该活动的锁
     *
     * @param string $activity_id            
     */
    public function getLockByActivity($activity_id)
    {
        if ($this->_lockInfo == null) {
            $this->_lockInfo = $this->findOne(array(
                'activity_id' => $activity_id
            ));
            if ($this->_lockInfo == null) {
                throw new Exception("请设定活动编号");
            }
        }
        return $this->_lockInfo;
    }

    /**
     * 给活动的参与人员添加锁，同一个用户发起并发请求，并发请求只允许一条执行完成
     *
     * @param string $activity_id            
     * @param string $uniqueId            
     * @throws Exception
     */
    public function lock($activity_id, $uniqueId)
    {
        $uniqueId = strval($uniqueId);
        $lockInfo = $this->getLockByActivity($activity_id);
        if ($lockInfo == null) {
            throw new Exception("活动不存在");
        }
        
        
        $options = array();
        $options['query'] = array(
            '_id' => $lockInfo['_id']
        );
        $options['update'] = array(
            '$addToSet' => array(
                'unique_array' => $uniqueId
            ),
            '$push' => array(
                'log' => array(
                    '$each' => array(
                        array(
                            'unique' => $uniqueId,
                            'time' => time()
                        )
                    ),
                    '$sort' => array(
                        'time' => 1
                    ),
                    '$slice' => -1000
                )
            )
        );
        $options['upsert'] = false;
        $options['new'] = false;
        $rst = $this->findAndModify($options);
        if (! empty($rst['value'])) {
            if (in_array($uniqueId, $rst['value']['unique_array'],true)) {
                // 已经被锁定
                return true;
            }
        } else {
            fb($rst,'LOG');
        }
        // 尚未被锁定，但是目前已经锁定
        return false;
    }

    /**
     * 释放锁
     *
     * @param string $activity_id            
     * @param string $uniqueId            
     * @throws Exception
     */
    public function release($activity_id, $uniqueId)
    {
        $uniqueId = strval($uniqueId);
        $lockInfo = $this->getLockByActivity($activity_id);
        if ($lockInfo == null) {
            throw new Exception("活动不存在");
        }
        
        return $this->update(array(
            '_id' => $lockInfo['_id']
        ), array(
            '$pull' => array(
                "unique_array" => $uniqueId
            )
        ));
    }

    /**
     * 释放过期的锁
     *
     * @param string $activity_id            
     * @param int $expire            
     */
    public function expireRelease($activity_id, $expire = 300)
    {
        $expire = intval($expire);
        $lockInfo = $this->findOne(array(
            'activity_id' => $activity_id
        ));
        
        $expired = time() - $expire;
        $expiredUniqueIds = array();
        
        $arr = $lockInfo['log'];
        if (!empty($arr)) {
            foreach ($arr as $one) {
                if ($one['time'] < $expired) {
                    $expiredUniqueIds[] = $one['unique'];
                }
            }
        }
        
        if (! empty($expiredUniqueIds)) {
            $this->update(array(
                '_id' => $lockInfo['_id']
            ), array(
                '$pull' => array(
                    "unique_array" => array(
                        '$each' => $expiredUniqueIds
                    )
                )
            ));
        }
        
        return true;
    }
}