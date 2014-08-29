<?php

class User_Model_Bind extends iWebsite_Plugin_Mongo
{
    protected $name = 'iUser_Bind';
    protected $dbName = 'user';
    
    //更具微博获取用户ID
    public function getIdByWeibo($weibo_id)
    {
    	$arrayUser = $this->findOne(array('info.weibo_id'=>$weibo_id));
    	if(isset($arrayUser['user_id']))
    	    return $arrayUser['user_id'];
    	else
    	    return false;
    }
    
    //根据微信获取用户ID
    public function getIdByWeixin($FromUserName)
    {
    	$arrayUser = $this->findOne(array('info.openid'=>$FromUserName));
    	if(isset($arrayUser['user_id']))
    		return $arrayUser['user_id'];
    	else
    		return false;
    }
    
    //取用户ID
    public function getIdByOther($other_id)
    {
    	$arrayUser = $this->findOne(array('info.other_id'=>$other_id));
    	if(isset($arrayUser['user_id']))
    		return $arrayUser['user_id'];
    	else
    		return false;
    }
    
    public function bind($type,$uniqueValue,$arrayData)
    {
        $arrayTmp = $this->findOne(array('bind_id'=>$arrayData['_id']->__toString()));
        if($arrayTmp == null) //未绑定过
        {
            $oUser = new User_Model_User();
            $arrayUser = $oUser->findOne(array('unique'=>$uniqueValue));
            if(!isset($arrayUser)) //无用户信息
            {
            	$arrayUser = array('unique'=>$uniqueValue);
            	$arrayUser = $oUser->insertRef($arrayUser);    //新建用户
            	$this->insert(array('user_id'=>$arrayUser['_id']->__toString(),'bind_id'=>$arrayData['_id']->__toString(),'type'=>$type,'info'=>$arrayData));
            }
            else
            {
//             	$arrayBind = $this->findOne(array('bind_id'=>$arrayData['_id']->__toString(),'type'=>$type));
                $arrayBind = $this->findOne(array('user_id'=>$arrayUser['_id']->__toString(),'type'=>$type));   //同一账号类型，只能绑定一个用户
            	if(!$arrayBind)
            	{
            		$this->insert(array('user_id'=>$arrayUser['_id']->__toString(),'bind_id'=>$arrayData['_id']->__toString(),'type'=>$type,'info'=>$arrayData));
            		return $arrayUser['_id']->__toString();
            	}
            	else //已绑定过
            	{
            		throw new Exception($uniqueValue.'已被绑定',1001);
                    return false;
            	}
                
            }
            return $arrayUser;
        }
        else
        {
            $oUser = new User_Model_User();
            $arrayUser = $oUser->findOne(array('_id'=>$arrayTmp['user_id']));
            if($arrayUser['unique'] == $uniqueValue)
                return $arrayTmp;
            
            throw new Exception('该账号已被绑定',1001);
        	return false;
        }
    }
}