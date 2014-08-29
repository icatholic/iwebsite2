<?php

class Exchange_Model_Success extends iWebsite_Plugin_Mongo
{

    protected $name = 'iExchange_success';
    protected $dbName = 'exchange';
    
    public function addSuccess($strUserId,$nPrizeCode,$nNumber,$strRuleId,$arrayVirtual = array())
    {
    	$arrayLog = array();
    	$arrayLog['user_id'] = $strUserId;
    	$arrayLog['prize_code'] = $nPrizeCode;
    	$arrayLog['rule_id'] = $strRuleId;
    	$arrayLog['quantity'] = $nNumber;
    	$arrayLog['virtual'] = $arrayVirtual;
    	$arrayLog['is_valid'] = true;
    	$arrayLog = $this->insertRef($arrayLog);
    	return $arrayLog;
    }
    
    public function getPrizeList($strUserId)
    {
        $oExchangePrize = new Exchange_Model_Prize();
        $arrayPrize = $oExchangePrize->getPrize();
    	$arrayList = $this->findAll(array('user_id'=>$strUserId),array('_id'=>-1));
    	foreach ($arrayList as $key => $val)
    	{
    		$val['prize'] = $arrayPrize[$val['prize_code']];
    		$arrayList[$key] = $val;
    	}
    	return $arrayList;
    }
    
    public function recordVirtual($strSuccessId,$arrayVirtual,$bValid)
    {
    	$this->update(array('_id'=>$strSuccessId),array('$set'=>array('virtual'=>$arrayVirtual,'is_valid'=>$bValid)));
    }
    
    public function getExchangeCount(array $userIds, array $prizeCodes, $is_today = false)
    {
        $query = array();
        if ($is_today) { // 当天
            $today = date('Y-m-d');
            $start = new MongoDate(strtotime($today . ' 00:00:00'));
            $end = new MongoDate(strtotime($today . ' 23:59:59'));
            $query['__CREATE_TIME__'] = array(
                '$gte' => $start,
                '$lte' => $end
            );
        }
    
        if ($userIds) {
            $query['user_id'] = array(
                '$in' => $userIds
            );
        }
        if ($prizeCodes) {
            $query['prize_code'] = array(
                '$in' => $prizeCodes
            );
        }
        $num = $this->count($query);
        return $num;
    }
}