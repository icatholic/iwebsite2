<?php
class Lottery_Model_LotteryIdentity extends iWebsite_Plugin_Mongo
{
    protected $name = 'lottery_identity';
    protected $dbName = 'lottery_sample';
    
    //处理
    public function getIdentity($name="",$mobile="",$address="",$weibo_uid="",$weibo_screen_name="",$FromUserName="")
    {
    	$query = array();
    	//以下条件可以根据具体业务来决定
    	//比如说如果抽奖是按照微信号来决定唯一性的话，那么就将最后一个IF语句提到最前
    	if(!empty($mobile)){
    		$query['mobile'] =$mobile;
    		$info = $this->findOne($query);
    	}else if(!empty($name)){
    		$query['name'] =$name;
    		$info = $this->findOne($query);
    	}else if(!empty($weibo_uid)){
    		$query['weibo_uid'] =$weibo_uid;
    		$info = $this->findOne($query);
    	}else if(!empty($FromUserName)){
    		$query['FromUserName'] =$FromUserName;
    		$info = $this->findOne($query);
    	}
    	
    	if(empty($info)){//如果不存在
	    	//记录信息
	    	$datas = array();
	    	$datas['name']  = $name;
	    	$datas['mobile'] = $mobile;
	    	$datas['address'] = $address;
	    	$datas['weibo_uid'] = $weibo_uid;
	    	$datas['weibo_screen_name'] = $weibo_screen_name;
	    	$datas['FromUserName'] = $FromUserName;
	    	$info = $this->insert($datas);
    	}
    	return $info;
    }

}