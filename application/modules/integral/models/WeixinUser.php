<?php
class Integral_Model_WeixinUser extends iWebsite_Plugin_Mongo
{
    protected $name = 'weixin_user';
    protected $dbName = 'integral';
    
    public function getUserById($id)
    {
    	$userInfo = $this->findOne(array("_id"=>$id));
    	return $userInfo;
    }
    
    //是否已经注册过
    public function is_registed($FromUserName)
    {
    	$num = $this->count(array("FromUserName"=>$FromUserName));
    	return ($num>0);
    }
    
    //根据微信用户FromUserName获得用户信息
    public function getUserByFromUserName($FromUserName)
    {
    	$result = $this->findOne(array("FromUserName"=>$FromUserName));
    	return $result;
    }
    
    //登陆处理
    public function login($user,$source)
    {
    	$options = array(
    			"query"=>array("_id"=>$user['_id']),
    			"update"=>array(
    					'$set'=>array(
    							"lastip"=>getIp(),
    							"lasttime"=>date('Y-m-d H:i:s')),
    					'$inc'=>array('times'=>1)),
    			"new"=>true
    	);
    	$return_result = $this->findAndModify($options);
    	$userData = $return_result["value"];
    	return $userData;
    }
    
    public function registUser($FromUserName,$mobile="")
    {
    	$userData=array();    	
    	$userData['FromUserName']=$FromUserName;
    	$userData['mobile']=$mobile;
    	$userData['lastip']= getIp();
    	$userData['lasttime']=date('Y-m-d H:i:s');
    	$userData['times']=1;
    	$userData['is_subscribe']=0;//非关注
    	$userData=$this->insert($userData);    	
    	return $userData;
    }
    
    //处理微信用户登录
    public function handle($FromUserName,$mobile="")
    {
    	//用户数据登陆
    	$userinfo = $this->getUserByFromUserName($FromUserName);
    	if(empty($userinfo))
    	{
    		//注册微信用户
    		$this->registUser($FromUserName);
    	}else{
    		//登录处理
    		$this->login($userinfo, 1);
    	}
    }
	
    //绑定手机
    public function bindMobile($FromUserName,$mobile)
    {
    	$data =array('mobile'=>$mobile);
    	$this->update(array("FromUserName"=>$FromUserName), array('$set'=>$data));
    }
    
    //关注处理
    public function subscribe($FromUserName, $mobile="")
    {    	
    	//更新是否关注字段
    	$data =array('is_subscribe'=>1);//关注
    	$this->update(array("FromUserName"=>$FromUserName), array('$set'=>$data));
    	//获取积分数量
    	$modelIntegralRule = new Integral_Model_IntegralRule();
    	$ruleInfo = $modelIntegralRule->getRuleInfo("关注");
    	//获取积分凭证
    	$modelIntegralIdentity = new Integral_Model_IntegralIdentity();
    	$integralIdentity = $modelIntegralIdentity->getIdentity($mobile,$FromUserName);
    	//记录关注积分
    	$modelIntegralInfo = new Integral_Model_IntegralInfo();
    	$modelIntegralInfo->handle($integralIdentity, $ruleInfo);
    }
    
    //取消关注处理
    public function unsubscribe($FromUserName, $mobile="")
    {
    	//更新是否关注字段
    	$data =array('is_subscribe'=>0);//取消关注
    	$this->update(array("FromUserName"=>$FromUserName), array('$set'=>$data));
    	//获取积分数量
    	$modelIntegralRule = new Integral_Model_IntegralRule();
    	$ruleInfo = $modelIntegralRule->getRuleInfo("取消关注");
    	//获取积分凭证
    	$modelIntegralIdentity = new Integral_Model_IntegralIdentity();
    	$integralIdentity = $modelIntegralIdentity->getIdentity($mobile,$FromUserName);
    	//记录取消关注积分
    	$modelIntegralInfo = new Integral_Model_IntegralInfo();
    	$modelIntegralInfo->handle($integralIdentity, $ruleInfo);
    }
    //是否关注
    public function is_subscribed($FromUserName)
    {
    	$info = $this->getUserByFromUserName($FromUserName);
    	if(empty($info)){
    		return 0;//未关注
    	}else{
    		return $info['is_subscribe'];
    	}
    }
}