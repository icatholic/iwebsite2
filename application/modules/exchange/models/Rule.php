<?php

class Exchange_Model_Rule extends iWebsite_Plugin_Mongo
{

    protected $name = 'iExchange_rules';
    protected $dbName = 'exchange';
    
    //开始兑换
    public function exchange($strUserId,$strRuleId,$nNumber = 1)
    {
        $nNumber = intval($nNumber);
        $oLog = new Exchange_Model_Log();
        $nNow = time();
        $arrayRule = $this->findOne(array('_id'=>$strRuleId));
        if(!$arrayRule)
        {
            throw new ErrorException('异常数据',600);
            exit;
        }
        try {
            if($arrayRule['exchange_begin']->sec > $nNow || $arrayRule['exchange_end']->sec <= $nNow)
            {
                throw new ErrorException('兑换未开始',601);
                exit;
            }
            if($arrayRule['quantity'] < $nNumber)
            {
                throw new ErrorException('奖品数量不足',602);
                exit;
            }
        
        
            if(isset($arrayRule['score']) && $arrayRule['score'])   //需要积分
            {
            	if(class_exists('Score_Model_User'))
            	{
            		$oUserScore = new Score_Model_User($strUserId);
            		if($oUserScore->getScore()<$arrayRule['score']*$nNumber)
            		{
            		    throw new ErrorException('积分不足',603);
            		    exit;
            		}
            	}
            	else 
            	{
            	    throw new ErrorException('请安装积分组件!',604);
            	    exit;
            	}
            }
            
            $oLimit = new Exchange_Model_Limit();
            if(!$oLimit->checkLimit($arrayRule['prize_code'], $strUserId,$nNumber))
            {
                throw new ErrorException('兑换数量限制!',605);
                exit;
            }
            $this->doExchange($strRuleId, $nNumber);
            $oSuccess = new Exchange_Model_Success();
            $arraySuccess = $oSuccess->addSuccess($strUserId, $arrayRule['prize_code'],$nNumber, $strRuleId);
            if(isset($arrayRule['score']) && $arrayRule['score'])   //需要积分 扣除积分
            {
            	$oUserScore->reduceScore($arrayRule['score']*$nNumber, '兑换商品'.$arraySuccess['prize_code']);
            }
            return $oLog->addLog($strUserId, $arrayRule['prize_code'],$nNumber,$strRuleId,0,'兑换成功');
            
        }catch (Exception $e)
        {
            return $oLog->addLog($strUserId, $arrayRule['prize_code'],$nNumber, $arrayRule['_id']->__toString(),$e->getCode(),$e->getMessage());
        }
    }
    
    public function getRuleByPrizeCode($prize_id,$nNow = 0,$nNumber)
    {
    	if($nNow == 0)
    	    $nNow = time();
    	
    	$arrayRule = $this->findOne(array('prize_id'=>$prize_id,'exchange_begin'=>array('$lte'=>new MongoDate($nNow)),'exchange_end'=>array('$gt'=>new MongoDate($nNow)),'quantity'=>array('$gte'=>$nNumber)));
    	if($arrayRule)
    	    return $arrayRule;
    	else 
    	{
    	  	throw new ErrorException('奖品已兑换完',601);
    	  	exit;
    	}
    }
    
    //减少规则数量
    private function doExchange($strRuleId,$nNumber)
    {
        $arrayOption = array();
        $arrayOption['query'] = array('_id'=>$strRuleId,'quantity'=>array('$gte'=>$nNumber));
        $arrayOption['update'] = array('$inc'=>array('quantity'=>-$nNumber,'exchange_quantity'=>$nNumber));
        $arrayResult = $this->findAndModify($arrayOption);
        if ($arrayResult['value'] == null) {
        	throw new ErrorException('奖品数量不足',602);
            return false;
        }
        return true;
    }
    
    //获得可兑换奖品
    public function exchangeNow($nScore)
    {
        $oExchangePrize = new Exchange_Model_Prize();
        $arrayPrize = $oExchangePrize->getPrize();
        
        $arrayList = $this->findAll(array());
        foreach ($arrayList as $key => $val)
        {
        	$val['prize'] = $arrayPrize[$val['prize_code']];
        	$arrayList[$key] = $val;
        }
        return $arrayList;
    }
}