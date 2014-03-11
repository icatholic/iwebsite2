<?php

class Lottery_Model_ResultMsg extends iWebsite_Plugin_Mongo
{

    protected $name = 'iLottery_result_msg';

    protected $dbName = 'lottery';

    private $_result = null;

    /**
     * 获取全部提示信息
     */
    public function getResults()
    {
        if ($this->_result == null) {
            $results = $this->findAll(array());
            if (! empty($results)) {
                foreach ($results as $row) {
                    $this->_result[$row['value']] = $row['msg'];
                }
            }
        }
        return $this->_result;
    }
    
    /**
     * 根据结果范围数据
     * @param int $value
     * @return string
     */
    public function getResultBy($value) {
        $results = $this->getResults();
        if(isset($results[$value])) {
            return $results[$value];
        }
        else {
            return '未知的结果类型';
        }
    }
}