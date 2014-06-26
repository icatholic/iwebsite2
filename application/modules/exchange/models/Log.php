<?php

class Exchange_Model_Log extends iWebsite_Plugin_Mongo
{

    protected $name = 'iExchange_log';
    protected $dbName = 'exchange';
    
    public function addLog($strUserId,$nPrizeCode,$nNumber,$strRuleId,$nResultCode,$strMsg)
    {
    	$arrayLog = array();
    	$arrayLog['user_id'] = $strUserId;
    	$arrayLog['prize_code'] = $nPrizeCode;
    	$arrayLog['rule_id'] = $strRuleId;
    	$arrayLog['result_code'] = $nResultCode;
    	$arrayLog['msg'] = $strMsg;
    	$arrayLog['quantity'] = $nNumber;
    	$arrayLog = $this->insertRef($arrayLog);
    	return $arrayLog;
    }
    
}