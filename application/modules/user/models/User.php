<?php

class User_Model_User extends iWebsite_Plugin_Mongo
{
    protected $name = 'iUser';
    protected $dbName = 'user';
    
    //更新用户信息
    public function saveUser($uniqueValue,$name='',$mobile='',$id_card='',$tel = '',$address = '',$zip = '',$email='',$custom='',$others = array())
    {
        $arrayData = array();
        if($name)
            $arrayData['name'] = $name;
        if($mobile)
        	$arrayData['mobile'] = $mobile;
        if($id_card)
        	$arrayData['id_card'] = $id_card;
        if($tel)
        	$arrayData['tel'] = $tel;
        if($address)
        	$arrayData['address'] = $address;
        if($zip)
        	$arrayData['zip'] = $zip;
        if($email)
        	$arrayData['email'] = $email;
        if($custom)
        	$arrayData['custom'] = $custom;
        if(count($others))
            $arrayData['others'] = $others;
    	$this->update(array('unique'=>$uniqueValue),array('$set'=>$arrayData));
    	
    	return $this->arrayReturn(true);
    }
    
    
    /*
     * 注册
     * 
     * */
    public function register($arrayData)
    {
    	if($arrayData['unique'] == '')
    	{
    		return $this->arrayReturn(false,103,'唯一识别码不能为空');
    	}
    	$nCount = $this->count(array('unique'=>$arrayData['unique']));
    	if($nCount)
    	{
    	    return $this->arrayReturn(false,104,'唯一识别码已存在');
    	}
    	if(isset($arrayData['password']))
    	    $arrayData['password'] = $this->encryption($arrayData['password']);
    	
    	$this->insertRef($arrayData);
    	return $this->arrayReturn(true);
    }
    
    /*
     * 登录
     * $username    用户名
     * $password    密码
     * $fieldUserName 用户名字段
     * */
    
    public function login($username,$password,$fieldUserName = 'username')
    {
        $arrayReturn = array('status'=>false,'error_code'=>0,'msg');
    	$arrayUser = $this->findOne(array($fieldUserName=>$username));
    	if($arrayUser)
    	{
    		if($arrayUser['password'] == $this->encryption($password))
    		{
    			return $this->arrayReturn(true);
    		}
    		else 
    		    return $this->arrayReturn(false,101,'密码错误！');
    	}
    	else
    	    return $this->arrayReturn(false,102,'用户名不存在！');
    }
    
    
    //可根据具体需求重新加密规则
    public function encryption($password)
    {
    	return md5($password);
    }
    
    public function arrayReturn($status = false,$nErrorCode = 0,$strMsg = '',$arrayResult = array())
    {
    	return array('status'=>$status,'error_code'=>$nErrorCode,'msg'=>$strMsg,'result'=>$arrayResult);
    }
}