<?php

/**
 * 中奖纪录表
 * @author Young
 *
 */
class Lottery_Model_Exchange extends iWebsite_Plugin_Mongo
{

    protected $name = 'iLottery_exchange';

    protected $dbName = 'lottery';

    private $_exchanges = null;

    /**
     * 检测当前信息是否存在
     */
    public function checkExchangeBy($indentity_id, $exchange_id)
    {
        $exchange_id = $exchange_id instanceof MongoId ? $exchange_id : myMongoId($exchange_id);
        return $this->findOne(array(
            '_id' => $exchange_id,
            'indentity_id' => $indentity_id
        ));
    }

    /**
     * 获取指定用户的全部中奖纪录
     *
     * @param string $identity_id            
     */
    public function getExchangeBy($identity_id)
    {
        if ($this->_exchanges == null) {
            $this->_exchanges = $this->findAll(array(
                'identity_id' => $identity_id,
                'is_valid' => true
            ));
        }
        return $this->_exchanges;
    }

    /**
     * 获取之前已经中奖，但是未被确认掉的奖品
     *
     * @param
     *            string
     */
    public function getExchangeInvalidById($identity_id)
    {
        return $this->_exchanges->findOne(array(
            'identity_id' => $identity_id,
            'is_valid' => false
        ));
    }

    /**
     * 在全部数据结果中过滤有效数据
     */
    public function filterExchangeByGroup($identity_id, MongoDate $startTime = null, MongoDate $endTime = null)
    {
        $rst = array();
        $exchanges = $this->getExchangeBy($identity_id);
        if (! empty($exchanges)) {
            $exchanges = array_filter($exchanges, function ($exchange) use($startTime, $endTime)
            {
                $startTime = $startTime == null ? new MongoDate(0) : $startTime;
                $endTime = $endTime == null ? new MongoDate() : $endTime;
                if ($exchange['__CREATE_TIME__'] >= $startTime && $exchange['__CREATE_TIME__'] <= $endTime) {
                    return true;
                }
                return false;
            });
            if (! empty($exchanges)) {
                foreach ($exchanges as $key => $exchange) {
                    if (! empty($exchange['prize_id'])) {
                        $rst[$exchange['prize_id']] += 1;
                    }
                }
                $rst['all'] = count($exchanges);
                return $rst;
            }
        }
        return $rst;
    }

    /**
     *
     * @param string $identity_id            
     * @param MongoDate $startTime            
     * @param MongoDate $endTime            
     * @return array boolean
     */
    public function getExchangeByGroup($identity_id, MongoDate $startTime = null, MongoDate $endTime = null)
    {
        $match = array(
            'identity_id' => $identity_id,
            'is_valid' => true
        );
        if ($startTime != null) {
            $match['__CREATE_TIME__']['$gt'] = $startTime;
        }
        if ($endTime != null) {
            $match['__CREATE_TIME__']['$lt'] = $endTime;
        }
        
        $ops = array(
            array(
                '$match' => $match
            ),
            array(
                '$group' => array(
                    '_id' => '$prize_id',
                    'total' => array(
                        '$sum' => 1
                    )
                )
            )
        );
        
        $rst = $this->aggregate($ops);
        if (! empty($rst['result'])) {
            return $rst['result'];
        }
        
        if ($rst['ok'] == 0) {
            throw new Exception("脚本执行失败，原因:" . json_encode($rst));
        }
        
        return false;
    }

    /**
     * 记录数据
     *
     * @param string $activity_id            
     * @param string $prize_id            
     * @param array $prizeInfo            
     * @param array $prizeCode            
     * @param string $identity_id            
     * @param array $identityInfo            
     * @param array $identityContact            
     * @param string $isValid            
     * @param string $source            
     */
    public function record($activity_id, $prize_id, $prizeInfo, $prizeCode, $identity_id, $identityInfo, $identityContact, $isValid, $source)
    {
        return $this->insert(array(
            'activity_id' => $activity_id,
            'prize_id' => $prize_id,
            'prize_info' => $prizeInfo,
            'prize_code' => $prizeCode,
            'identity_id' => $identity_id,
            'identity_info' => $identityInfo,
            'identity_contact' => $identityContact,
            'is_valid' => $isValid,
            'source' => $source
        ));
    }
    
    /**
     * 更新兑换信息
     * @param string $exchange_id
     * @param array $info
     */
    public function updateExchangeInfo($exchange_id,$info) {
        $exchange_id = $exchange_id instanceof MongoId ? $exchange_id : myMongoId($exchange_id);
        return $this->update(array(
            '_id' => $exchange_id
        ), array('$set'=>$info));
    }

    /**
     * 获取中奖记录信息
     *
     * @param string $_id            
     */
    public function getExchangeInfoBy($exchange_id)
    {
        $exchange_id = $exchange_id instanceof MongoId ? $exchange_id : myMongoId($exchange_id);
        return $this->findOne(array(
            '_id' => $exchange_id
        ));
    }

    
    /**
     * 析构函数
     */
    public function __destruct()
    {
        $this->_exchanges = null;
    }
}