<?php
class Integral_Model_IntegralIdentity extends iWebsite_Plugin_Mongo
{
    protected $name = 'integral_identity';
    protected $dbName = 'integral';
    
    //处理
    public function getIdentity($mobile="",$FromUserName="")
    {
    	//获取会员的信息
    	$modelMeberInfo = new Integral_Model_MemberInfo();
    	$memberInfo = $modelMeberInfo->getMemberInfo($mobile,$FromUserName);
    	
    	$query = array();
    	if($memberInfo){//会员的信息存在的时候
    		if(!empty($memberInfo['mobile'])){
    			$mobile = $memberInfo['mobile'];
    			$query['$or'][] = array('mobile' => $mobile);
    		}
    		if(!empty($memberInfo['FromUserName'])){
    			$FromUserName = $memberInfo['FromUserName'];
    			$query['$or'][] = array('FromUserName' => $FromUserName);
    		}
    	}else{//会员的信息不存在的时候
	    	//以下条件可以根据具体业务来决定
	    	if(!empty($mobile)){
	    		$query['mobile'] =$mobile;
	    	}else if(!empty($FromUserName)){
	    		$query['FromUserName'] =$FromUserName;
	    	}
    	}
    	if(empty($query)){
    		throw new Exception("查询条件不能为空");
    	}
    	$info = $this->findOne($query);
    	
    	if(empty($info)){//如果不存在
	    	//记录信息
	    	$datas = array();
	    	$datas['mobile'] = $mobile;
	    	$datas['FromUserName'] = $FromUserName;
	    	$info = $this->insert($datas);
    	}else{//如果存在，更新
    		$query=array('_id'=>$info['_id']);
    		$datas = array();
    		if(!empty($mobile) && $info['mobile'] != $mobile){
    			$datas['mobile'] = $mobile;
    		}
    		if(!empty($FromUserName) && $info['FromUserName'] != $FromUserName){
    			$datas['FromUserName'] = $FromUserName;
    		}
    		if(!empty($datas)){
    			$this->update($query,array('$set'=>$datas));
    		}
    	}
    	return $info;
    }

}