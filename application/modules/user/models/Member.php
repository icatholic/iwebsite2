<?php

abstract class User_Model_Member extends iWebsite_Plugin_Mongo
{
    protected function getStruct()
    {
    	$arraySchema = $this->getSchema();
    	$arrayStruct = array();
    	foreach ($arraySchema as $key => $val)
    	{
    		$arrayStruct[$val['field']] = $val;
    	}
    	return $arrayStruct;
    }
    
    //数据检验
    protected function getData($arrayInfo)
    {
        $arrayStruct = $this->getStruct();
        $arrayData = array();
        foreach ($arrayInfo as $key => $val)
        {
        	if(isset($arrayStruct[$key]))
        	{
        		switch ($arrayStruct[$key])
        		{
        			case 'numberfield':
        				$arrayInfo[$key] = intval($arrayInfo[$key]);
        				break;
        			case 'arrayfield':
        			case 'documentfield':
        				$arrayInfo[$key] = (array)$arrayInfo[$key];
        				break;
        		}
        		$arrayData[$key] = $arrayInfo[$key];
        	}
        }
        return $arrayData;
    }
}