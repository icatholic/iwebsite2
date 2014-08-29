<?php

class Lottery_Model_Lock extends iWebsite_Plugin_Mongo
{

    protected $name = 'iLottery_lock';

    protected $dbName = 'lottery';

    private $_lockInfo = null;

    private $_activity_id;

    private $_uniqueId;

    private $_isLocked = false;

    /**
     * 获取锁定信息,在活动使用锁之前，请在锁集合中创建该活动的锁
     *
     * @param string $activity_id            
     */
    private function getLockByActivity($activity_id)
    {
        if ($this->_lockInfo == null) {
            $this->_lockInfo = $this->findOne(array(
                'activity_id' => $activity_id
            ));
            if ($this->_lockInfo == null) {
                $this->_lockInfo = $this->insert(array(
                    'activity_id' => $activity_id,
                    'unique_array' => array(),
                    'log' => array()
                ));
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
        $this->_activity_id = $activity_id;
        $this->_uniqueId = $uniqueId;
        
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
                    '$slice' => - 1000
                )
            )
        );
        $options['upsert'] = false;
        $options['new'] = false;
        $rst = $this->findAndModify($options);
        if (! empty($rst['value'])) {
            if (in_array($uniqueId, $rst['value']['unique_array'], true)) {
                // 已经被锁定
                $this->_isLocked = true;
                return true;
            }
        } else {
            fb($rst, 'LOG');
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
    private function release($activity_id = null, $uniqueId = null)
    {
        if (empty($activity_id))
            $activity_id = $this->_activity_id;
        
        if (empty($uniqueId))
            $uniqueId = $this->_uniqueId;
        
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
    private function expireRelease($activity_id = null, $expire = 300)
    {
        if (empty($activity_id))
            $activity_id = $this->_activity_id;
        
        $expire = intval($expire);
        $lockInfo = $this->findOne(array(
            'activity_id' => $activity_id
        ));
        
        $expired = time() - $expire;
        $expiredUniqueIds = array();
        
        $arr = $lockInfo['log'];
        if (! empty($arr)) {
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

    /**
     * 自动在析构函数中解锁和清理过期的锁
     */
    public function __destruct()
    {
        //只有上锁的人有资格开锁，而有上锁资格的人看到的一定是没锁的门；面对已经加锁的门，别人没资格打开噢~
        if (!$this->_isLocked)
            $this->release();
        $this->expireRelease();
    }
}