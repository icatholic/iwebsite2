<?php

class Exchange_Model_Prize extends iWebsite_Plugin_Mongo
{

    protected $name = 'iExchange_prize';
    protected $dbName = 'exchange';
    
    public function getIdByCode($prizeCode)
    {
    	$arrayPrize = $this->findOne(array('prize_code'=>$prizeCode));
    	if($arrayPrize)
    	    return $arrayPrize['_id']->__toString();
    	else
    	    return false;
    }
    
    public function getPrize()
    {
    	$arrayTmp = $this->findAll(array());
    	$arrayReturn = array();
    	foreach ($arrayTmp as $key => $val)
    	{
    		$arrayReturn[$val['prize_code']] = $val;
    	}
    	return $arrayReturn;
    }
}