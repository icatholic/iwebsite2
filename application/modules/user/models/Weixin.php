<?php

class User_Model_Weixin extends User_Model_Member
{
    
    public function __construct()
    {
    	$this->name = 'iUser_Weixin';
    	$this->dbName = 'user';
    	parent::__construct();
    }
    
    
    /*添加用户信息，并绑定
     * 参数：  $arrayInfo 用户信息
     *      $uniqueValue 绑定管理的值 未空则不绑定
     * 
     */
    public function add($arrayInfo,$uniqueValue='')
    {
    	if(!isset($arrayInfo['openid']) || trim($arrayInfo['openid']) == '')
    	{
//     	    return  array('error_code'=>101,'msg'=>'openid 为空！');
    	    throw new Exception('openid 为空！',1000);
    	    return false;
    	}
    		
    	try {
        	
        	$arrayTmp = $this->findOne(array('openid'=>$arrayInfo['openid']));
        	if($arrayTmp == null)   //新数据
        	{
        	    $arrayData = $this->getData($arrayInfo);
        		$arrayData = $this->insertRef($arrayData);
        	}
        	else
        	{
        		$arrayData = $arrayTmp;
        	}
        
        	if($uniqueValue)    //需要绑定,返回用户ID
        	{
        		$oBind = new User_Model_Bind();
        		return $oBind->bind('iUser_Weixin', $uniqueValue, $arrayData);
        	}
        	else 
        	{
        		return $arrayData;
        	}
    	}catch (Exception $e){
    		throw new Exception($e->getMessage(),$e->getCode());
    		return false;
    	}
    }
    
}