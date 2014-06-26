<?php

class Exchange_Model_Limit extends iWebsite_Plugin_Mongo
{

    protected $name = 'iExchange_limit';
    protected $dbName = 'exchange';
    
    //是否可以兑换 没有限制则默认可以
    public function checkLimit($nPrizeCode,$strUserId,$nNumber)
    {
    	$arrayLimit = $this->findAll(array(),array('_id'=>-1));
    	$arrayLimitOne = array();  //最近一次限制条件
    	foreach ($arrayLimit as $key => $val)
    	{
    		if(in_array($nPrizeCode, $val['prize']))
    		{
    		    $arrayLimitOne = $val;
    		    break;
    		}
    	}
    	
    	//限制数量为0时，则不限制
    	if(count($arrayLimitOne) && $arrayLimitOne['limit_quantity'] >= 0)
    	{
    	    if($arrayLimitOne['limit_quantity'] == 0)  //限制为0时，无法兑换
    	        return false;
    		$oSuccess = new Exchange_Model_Success();
    		$nCount = 0;
    		$arrayQuery = array('user_id'=>$strUserId,'prize_code'=>array('$in'=>$arrayLimitOne['prize']),'__CREATE_TIME__'=>array('$gte'=>new MongoDate($val['limit_begin']->sec),'$lt'=>new MongoDate($val['limit_end']->sec)));
    		$arrayCount = $oSuccess->findAll($arrayQuery);
    		foreach ($arrayCount as $key => $val)
    		{
    			$nCount+=$val['quantity'];
    		}
    		if($nCount+$nNumber > $arrayLimitOne['limit_quantity'])   //兑换数量>=限制时，无法兑换
    		    return false;
    	}
    	
    	return true;
    }
}