<?php
class Integral_Model_MemberInfo extends iWebsite_Plugin_Mongo
{
    protected $name = 'member_info';
    protected $dbName = 'integral';
    
    //是否已经注册过
    public function is_registed($mobile)
    {
    	$num = $this->count(array("mobile"=>$mobile));
    	return ($num>0);
    }
    
    //会员信息
    public function getMemberInfo($mobile="",$FromUserName="",$id="")
    {
    	if(!empty($mobile)){
    		$result = $this->getMemberByMobile($mobile);
    	}else if(!empty($FromUserName)){
    		$result = $this->getMemberByFromUserName($FromUserName);
    	}else if(!empty($id)){
    		$result = $this->getMemberById($id);
    	}
    	return $result;
    }
    
    //根据ID获得会员信息
    public function getMemberById($id)
    {
    	$result = $this->findOne(array("_id"=>$id));
    	return $result;
    }
    //根据mobile获得会员信息
    public function getMemberByMobile($mobile)
    {
    	$result = $this->findOne(array("mobile"=>$mobile));
    	return $result;
    }    
    //根据FromUsername获得会员信息
    public function getMemberByFromUserName($FromUserName)
    {
    	$result = $this->findOne(array("FromUserName"=>$FromUserName));
    	return $result;
    }
    
    //是否是会员
    public function is_member($FromUserName)
    {
    	$num = $this->count(array("FromUserName"=>$FromUserName));
    	return ($num>0);
    }
    
    //登陆处理
    public function login($member,$source)
    {
    	$options = array(
    			"query"=>array("_id"=>$member['_id']),
    			"update"=>array(
    					'$set'=>array(
    							"lastip"=>getIp(),
    							"lasttime"=>date('Y-m-d H:i:s')),
    					'$inc'=>array('times'=>1)),
    			"new"=>true
    	);
    	$return_result = $this->findAndModify($options);
    	$memberData = $return_result["value"];
    	return $memberData;
    }
    
    public function registMember($mobile,$name,$referee_mobile="",$FromUserName="")
    {
    	$memberData=array();
    	$memberData['name']=$name;
    	$memberData['mobile']=$mobile;
    	$memberData['FromUserName']=$FromUserName;
    	$memberData['referee_mobile']=$referee_mobile;//推荐人号码
    	$memberData['lastip']= getIp();
    	$memberData['lasttime']=date('Y-m-d H:i:s');
    	$memberData['times']=1;
    	$memberData=$this->insert($memberData);
    	//获取积分数量
    	$modelIntegralRule = new Integral_Model_IntegralRule();
    	$ruleInfo = $modelIntegralRule->getRuleInfo("会员注册");
    	//获取积分凭证
    	$modelIntegralIdentity = new Integral_Model_IntegralIdentity();
    	$integralIdentity = $modelIntegralIdentity->getIdentity($mobile,$FromUserName);
    	//记录会员注册积分
    	$modelIntegralInfo = new Integral_Model_IntegralInfo();
    	$modelIntegralInfo->handle($integralIdentity, $ruleInfo);
    	//处理推荐人的积分
    	$this->handleRefereeIntegral($referee_mobile,$mobile);
    	return $memberData;
    }
    
    //处理会员登录
    public function handle($mobile,$name,$referee_mobile="",$FromUserName="")
    {
    	//会员数据登陆
    	$memberInfo = $this->getMemberByMobile($mobile);
    	if(empty($memberInfo))
    	{
    		//注册会员
    		$this->registMember($mobile,$name,$referee_mobile,$FromUserName);
    	}else{
    		//登录处理
    		$this->login($memberInfo, 1);
    	}
    }
	
    //绑定微信ID
    public function bindFromUserName($mobile,$FromUserName)
    {
    	$data =array('FromUserName'=>$FromUserName);
    	$this->update(array("mobile"=>$mobile), array('$set'=>$data));
    }
    
    //处理推荐人的积分
    public function handleRefereeIntegral($referee_mobile,$fromMobile)
    {
    	if(!empty($referee_mobile)){
    		//获取积分凭证
    		$modelIntegralIdentity = new Integral_Model_IntegralIdentity();
    		$integralIdentity = $modelIntegralIdentity->getIdentity($referee_mobile,'');
    		//处理推荐
    		$modelRefereeInfo = new Integral_Model_RefereeInfo();
    		$modelRefereeInfo->handle($integralIdentity, $fromMobile);
    	}
    	
    }
}