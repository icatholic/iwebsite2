<?php

class Lottery_Model_Limit extends iWebsite_Plugin_Mongo
{

    protected $name = 'iLottery_limit';

    protected $dbName = 'lottery';

    private $_limits = null;

    private $_exchange = null;

    /**
     * 获取全部限定条件
     *
     * @param string $activity_id            
     */
    public function getLimits($activity_id)
    {
        if ($this->_limits == null) {
            $this->_limits = $this->findAll(array(
                'activity_id' => $activity_id
            ));
        }
        return $this->_limits;
    }

    /**
     *
     * @return Lottery_Model_Exchange
     */
    public function getExchangeModel()
    {
        if ($this->_exchange == null) {
            $this->_exchange = new Lottery_Model_Exchange();
        }
        return $this->_exchange;
    }

    public function setExchangeModel(Lottery_Model_Exchange $exchange)
    {
        $this->_exchange = $exchange;
    }

    /**
     * 检查指定操作指定规则的限制是否达到
     *
     * @param string $activity_id            
     * @param string $identity_id            
     * @param string $prize_id            
     */
    public function checkLimit($activity_id, $identity_id, $prize_id = 'all')
    {
        $modelExchange = $this->getExchangeModel();
        $limits = $this->getLimits($activity_id);
        if (! empty($limits)) {
            foreach ($limits as $limit) {
                $now = time();
                if ($limit['start_time']->sec < $now && $now < $limit['end_time']->sec) {
                    $exchanges = $modelExchange->filterExchangeByGroup($identity_id, $limit['start_time'], $limit['end_time']);
                    if (! empty($exchanges)) {
                        if (empty($limit['prize_id']) && $prize_id == 'all') {
                            if ($exchanges['all'] >= $limit['limit']) {
                                return false;
                            }
                        } else {
                            if (! empty($limit['prize_id']) && is_array($limit['prize_id']) && in_array($prize_id, $limit['prize_id'], true)) {
                                $exchangedTotalNumber = 0;
                                foreach ($exchanges as $k => $v) {
                                    if (in_array($k, $limit['prize_id'], true)) {
                                        $exchangedTotalNumber += $v;
                                    }
                                }
                                
                                if ($exchangedTotalNumber >= $limit['limit']) {
                                    return false;
                                }
                            } else {
                                if (is_string($limit['prize_id'])) {
                                    if (isset($exchanges[$limit['prize_id']]) && ! empty($limit['prize_id']) && $prize_id == $limit['prize_id']) {
                                        if ($exchanges[$limit['prize_id']] >= $limit['limit']) {
                                            return false;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return true;
    }
}