<?php

class Exchange_Model_Success extends iWebsite_Plugin_Mongo
{

    protected $name = 'iExchange_success';
    protected $dbName = 'exchange';
    
    public function addSuccess($strUserId,$nPrizeCode,$nNumber,$strRuleId)
    {
    	$arrayLog = array();
    	$arrayLog['user_id'] = $strUserId;
    	$arrayLog['prize_code'] = $nPrizeCode;
    	$arrayLog['rule_id'] = $strRuleId;
    	$arrayLog['quantity'] = $nNumber;
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
}