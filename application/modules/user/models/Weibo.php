<?php

class User_Model_Weibo extends User_Model_Member
{
//     protected $name = 'iUser_Weibo';
//     protected $dbName = 'user';
    
    public function __construct()
    {
    	$this->name = 'iUser_Weibo';
    	$this->dbName = 'user';
    	parent::__construct();
    }
    
    public function add($arrayInfo,$uniqueValue='')
    {
        if(!isset($arrayInfo['weibo_id']) || trim($arrayInfo['weibo_id']) == '')
        {
        	throw new Exception('weibo_id 为空！',1000);
    	    return false;
        }
        try {
            $arrayData = $this->getData($arrayInfo);
            $arrayTmp = $this->findOne(array('weibo_id'=>$arrayInfo['weibo_id']));
            if($arrayTmp == null)   //新数据
            {
            	$this->insertRef($arrayData);
            }
            else 
            {
                $arrayData = $arrayTmp;
            }
            
            if($uniqueValue)    //需要绑定,返回用户ID
            {
                $oBind = new User_Model_Bind();
                return $oBind->bind('iUser_Weibo', $uniqueValue, $arrayData);
            }
        }catch (Exception $e){
        	throw new Exception($e->getMessage(),$e->getCode());
    		return false;
        }
    }
}