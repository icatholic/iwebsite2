<?php

class Exchange_Model_Result extends iWebsite_Plugin_Mongo
{

    protected $name = 'iExchange_result';
    protected $dbName = 'exchange';
    
    public function getInfo($code)
    {
    	$arrayResult = $this->findOne(array('result_code'=>$code));
    	if($arrayResult)
    	    return $arrayResult;
    	else
    	{
    	    $arrayResult = $this->findOne(array('result_code'=>605));
    	    return $arrayResult;
    	}
    }
}