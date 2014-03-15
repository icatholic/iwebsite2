<?php

class Lottery_Model_Rule extends iWebsite_Plugin_Mongo
{

    protected $name = 'iLottery_rule';

    protected $dbName = 'lottery';

    private $_rules = null;

    private $_limit = null;

    private $_exchange = null;

    public function setExchangeModel(Lottery_Model_Exchange $exchange)
    {
        $this->_exchange = $exchange;
    }

    public function getExchangeModel()
    {
        if ($this->exchange == null) {
            $this->exchange = new Lottery_Model_Exchange();
        }
        return $this->exchange;
    }

    public function setLimitModel(Lottery_Model_Limit $limit)
    {
        $this->_limit = $limit;
    }

    public function getLimitModel()
    {
        if ($this->_limit == null) {
            $this->_limit = new Lottery_Model_Limit();
        }
        return $this->_limit;
    }

    /**
     * 获取指定活动的全部抽奖规则
     *
     * @param string $activity_id            
     */
    public function getRules($activity_id)
    {
        if ($this->_rules == null) {
            $now = new MongoDate();
            $this->_rules = $this->findAll(array(
                'activity_id' => $activity_id,
                'allow_start_time' => array(
                    '$lte' => $now
                ),
                'allow_end_time' => array(
                    '$gte' => $now
                )
            ));
        }
        return $this->doShuffle($this->_rules);
    }

    /**
     * 对于概率进行随机分组处理
     *
     * @param array $list            
     * @return array
     */
    private function doShuffle($list)
    {
        $groupList = array();
        // 按照allow_probability分组
        array_map(function ($row) use(&$groupList)
        {
            $groupList[$row['allow_probability']][] = $row;
        }, $list);
        
        // 按照概率从高到底的次序排序
        rsort($groupList, SORT_NUMERIC);
        
        // 按分组随机排序
        $resultList = array();
        foreach ($groupList as $key => $rows) {
            shuffle($rows);
            $resultList = array_merge($resultList, $rows);
        }
        return $resultList;
    }

    /**
     * 计算抽奖概率判断用户是否中奖
     */
    public function lottery($activity_id, $identity_id)
    {
        $rules = $this->getRules($activity_id);
        if (! empty($rules)) {
            foreach ($rules as $rule) {
                if (rand(0, 9999) < $rule['allow_probability'] && $rule['allow_number'] > 0) {
                    $allow = $this->getLimitModel()->checkLimit($activity_id, $identity_id, $rule['prize_id']);
                    if ($allow)
                        return $rule;
                }
            }
        }
        return false;
    }

    /**
     * 更新奖品的剩余数量
     *
     * @param array $rule            
     * @return bool false表示错误 true表示正确
     */
    public function updateRemain($rule)
    {
        $options = array();
        $options['query'] = array(
            '_id' => $rule['_id'],
            'prize_id' => $rule['prize_id'],
            'allow_number' => array(
                '$gt' => 0
            )
        );
        $options['update'] = array(
            '$inc' => array(
                'allow_number' => - 1
            )
        );
        $rst = $this->findAndModify($options);
        if ($rst['ok'] == 0) {
            throw new Exception("findAndModify执行错误，返回结果为:" . json_encode($rst));
        }
        if ($rst['value'] == null) {
            return false;
        }
        return true;
    }
}