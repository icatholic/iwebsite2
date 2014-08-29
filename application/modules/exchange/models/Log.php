<?php

class Exchange_Model_Log extends iWebsite_Plugin_Mongo
{

    protected $name = 'iExchange_log';
    protected $dbName = 'exchange';
    
    public function addLog($strUserId,$nPrizeCode,$nNumber,$strRuleId,$nResultCode,$strMsg,$arrayPrize = array())
    {
    	$arrayLog = array();
    	$arrayLog['user_id'] = $strUserId;
    	$arrayLog['prize_code'] = $nPrizeCode;
    	$arrayLog['rule_id'] = $strRuleId;
    	$arrayLog['result_code'] = $nResultCode;
    	$arrayLog['msg'] = $strMsg;
    	$arrayLog['quantity'] = $nNumber;
    	$arrayLog['prize_info'] = $arrayPrize;
    	$arrayLog = $this->insertRef($arrayLog);
    	return $arrayLog;
    }
    
    //获取个人所有成功日志
    public function getMySuccessLog($strUserId,$arraySort = array('_id'=>-1))
    {
    	$arrayLog = $this->findAll(array('user_id'=>$strUserId,'result_code'=>0),$arraySort);
    	return $arrayLog;
    }
    
    //获取个人所有日志
    public function getMyAllLog($strUserId,$arraySort = array('_id'=>-1))
    {
    	$arrayLog = $this->findAll(array('user_id'=>$strUserId),$arraySort);
    	return $arrayLog;
    }
    
    //获取所有成功日志
    public function getAllLog($arraySort = array('_id'=>-1))
    {
    	$arrayLog = $this->findAll(array(),$arraySort);
    	return $arrayLog;
    }
}