<?php

class Exchange_Model_Rule extends iWebsite_Plugin_Mongo
{

    protected $name = 'iExchange_rules';
    protected $dbName = 'exchange';
    
    private $_arrayPrize = array();
    
    /**
     * 根据ID获取信息
     *
     * @param string $id
     * @return array
     */
    public function getInfoById($id)
    {
        $query = array(
            '_id' => myMongoId($id)
        );
        $info = $this->findOne($query);
        return $info;
    }
    
    //开始兑换
    public function exchange($strUserId,$strRuleId,$nNumber = 1,$nDefaultScore = 0)
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
            if($arrayRule['exchange_begin']->sec >= $nNow || $arrayRule['exchange_end']->sec < $nNow)
            {
                throw new ErrorException('兑换未开始',601);
                exit;
            }
            if($arrayRule['quantity'] < $nNumber)
            {
                throw new ErrorException('奖品数量不足',602);
                exit;
            }
        
            /*虚拟发卡密的奖品，每次只能兑换1个 Start*/
            $oPrize = new Exchange_Model_Prize();
            $this->_arrayPrize = $oPrize->getPrizeByCode($arrayRule['prize_code']);
            if($nNumber>1)  
            {
                //如果是虚拟的，需要发券码的，每次仅能兑换1张
                if(!$this->_arrayPrize['is_real'] && $this->_arrayPrize['is_send_code'])
                {
                    throw new ErrorException('每次仅能兑换1份',606);
                }
            }
            /*虚拟发卡密的奖品，每次只能兑换1个 End*/
            
            if(isset($arrayRule['score']) && $arrayRule['score'])   //需要积分
            {
                if($nDefaultScore == 0)
                {
                   //验证积分是否满足
                	if(class_exists('Score_Model_User'))
                	{
                		$oUserScore = new Score_Model_User($strUserId,$arrayRule['score_source_code']);
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
                else 
                {
                    if($nDefaultScore<$arrayRule['score']*$nNumber)
                    {
                    	throw new ErrorException('积分不足',603);
                    	exit;
                    }
                }
            }
            
            $oLimit = new Exchange_Model_Limit();
            if(!$oLimit->checkLimit($arrayRule['prize_code'], $strUserId,$nNumber))
            {
                throw new ErrorException('兑换数量限制!',605);
                exit;
            }
            
            //扣除数量
            $arrayExchangeResult = $this->doExchange($strRuleId, $nNumber);
            
            //添加兑换记录
            $oSuccess = new Exchange_Model_Success();
            $arraySuccess = $oSuccess->addSuccess($strUserId, $arrayRule['prize_code'],$nNumber, $strRuleId);
            
            //发虚拟券
            $arrayCode = array();
            if(!$this->_arrayPrize['is_real'] && $this->_arrayPrize['is_send_code'])
            {
            	$oCode = new Exchange_Model_Code();
            	$arrayCode = $oCode->getCode($this->_arrayPrize['prize_code']);
            	if(!$arrayCode)
            	{
            	    $oSuccess->recordVirtual($arraySuccess['_id'], array(),false);
            		throw new ErrorException('虚拟券不足!',607);
            	}
            	else
            	{
            	    $oSuccess->recordVirtual($arraySuccess['_id'], $arrayCode,true);
            	}
            }            
            
            //扣除积分
            if(isset($arrayRule['score']) && $arrayRule['score'])   //需要积分 扣除积分
            {
                if($oUserScore)
            	   $oUserScore->reduceScore($arrayRule['score']*$nNumber, '兑换商品'.$arraySuccess['prize_code']);
            }
            return $oLog->addLog($strUserId, $arrayRule['prize_code'],$nNumber,$strRuleId,0,'兑换成功',$this->_arrayPrize);
            
        }catch (Exception $e)
        {
            return $oLog->addLog($strUserId, $arrayRule['prize_code'],$nNumber, $arrayRule['_id']->__toString(),$e->getCode(),$e->getMessage(),$this->_arrayPrize);
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
        return $arrayResult;
    }
    
    //获得可兑换奖品
    public function exchangeNow($nDate = 0,$nScore = 0)
    {
        if(!$nDate)
        {
            $nDate = time();
        }
        $oExchangePrize = new Exchange_Model_Prize();
        $arrayPrize = $oExchangePrize->getPrize();
        
        $arrayQuery = array();
        $arrayQuery['exchange_begin'] = array('$lte'=>new MongoDate($nDate));
        $arrayQuery['exchange_end'] = array('$gt'=>new MongoDate($nDate));
        if($nScore)
            $arrayQuery['score'] = array('$gte'=>$nScore);
        $arrayList = $this->findAll($arrayQuery);
        foreach ($arrayList as $key => $val)
        {
        	$val['prize'] = $arrayPrize[$val['prize_code']];
        	$arrayList[$key] = $val;
        }
        return $arrayList;
    }
}