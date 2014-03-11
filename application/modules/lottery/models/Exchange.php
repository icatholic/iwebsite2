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
     * 获取指定用户的全部中奖纪录
     *
     * @param string $identity_id            
     */
    public function getExchangeBy($identity_id)
    {
        if ($this->_exchanges == null) {
            $this->_exchanges = $this->findAll(array(
                'identity_id' => $identity_id
            ));
        }
        return $this->_exchanges;
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
     * 记录错误信息
     * @param string $activity_id
     * @param string $prize_id
     * @param array $prize_info
     * @param string $identity_id
     * @param array $identity_info
     * @param string $is_valid
     * @param string $source
     */
    public function record($activity_id, $prize_id, $prize_info, $identity_id, $identity_info, $is_valid, $source)
    {
        return $this->insert(array(
            'activity_id' => $activity_id,
            'prize_id' => $prize_id,
            'prize_info' => $prize_info,
            'identity_id' => $identity_id,
            'identity_info' => $identity_info,
            'is_valid' => $is_valid,
            'source' => $source
        ));
    }

    public function __destruct()
    {
        $this->_exchanges = null;
    }
}