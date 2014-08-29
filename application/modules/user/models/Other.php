<?php

class User_Model_Other extends User_Model_Member
{
//     protected $name = 'iUser_Weibo';
//     protected $dbName = 'user';
    
    public function __construct()
    {
    	$this->name = 'iUser_Other';
    	$this->dbName = 'user';
    	parent::__construct();
    }
    
    public function add($arrayInfo,$uniqueValue='')
    {
        if(!isset($arrayInfo['other_id']) || trim($arrayInfo['other_id']) == '')
        {
        	 throw new Exception('other_id 为空！',1000);
    	       return false;
        }
        try {
            $arrayData = $this->getData($arrayInfo);
            $arrayTmp = $this->findOne(array('other_id'=>$arrayInfo['other_id']));
            if($arrayTmp == null)   //新数据
            {
            	$arrayData = $this->insertRef($arrayData);
            }
            else 
            {
                $arrayData = $arrayTmp;
            }
            if($uniqueValue)    //需要绑定,返回用户ID
            {
                $oBind = new User_Model_Bind();
                return $oBind->bind('iUser_Other', $uniqueValue, $arrayData);
            }
        }catch (Exception $e){
        	throw new Exception($e->getMessage(),$e->getCode());
    		return false;
        }
    }
}