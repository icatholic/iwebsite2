<?php
class Lottery_IndexController extends iWebsite_Controller_Action
{
    public function init()
    {
    	$this->getHelper('viewRenderer')->setNoRender(true);
    }
	
    /**
     * 抽奖
     * 
     * */
    public function getAction()
    {
    	//http://iwebsite.umaman.com/Lottery/index/get?jsonpcallback=?&FromUserName=1233&source=1 or 2
    	try {
    		$source = intval($this->get('source', '2')); // 默认是微信
    		$FromUserName = trim($this->get('FromUserName'));//微信ID
    		
    		//获取抽奖参与人信息
    		$modelLotteryIdentity = new Lottery_Model_LotteryIdentity();
    		if(!empty($FromUserName)){//根据微信号生成一个抽奖的身份凭证
    			$lotteryIdentity = $modelLotteryIdentity->getIdentity("","","","","",$FromUserName);
    		}else{
    			$uniqid = "匿名".uniqid();//根据随机名生成一个抽奖的身份凭证
    			$lotteryIdentity = $modelLotteryIdentity->getIdentity($uniqid,"","","","","");
    		}
    		//抽奖处理
    		$modelLottery = new Lottery_Model_Lottery();
    		$lotteryInfo = $modelLottery->lottery($lotteryIdentity,$source);//PC or mobile
    		 
    		//将分享的属性设置为参与抽奖
    		$lottery_id = $lotteryInfo['lottery']['_id'];
    		$exchange_id = empty($lotteryInfo['exchange'])?'':$lotteryInfo['exchange']['_id'];

    		$lotteryResultInfo = array(
    				'identityInfo'=>$lotteryIdentity,//抽奖用户信息
    				'lotteryResult'=>empty($exchange_id)?0:1,//是否中奖
    				'is_processed'=>empty($lotteryInfo['is_processed'])?0:1,//是否处理
    				'prize'=>$lotteryInfo['exchange'],//奖品信息
    				'lottery'=>$lotteryInfo['lottery']);//参与抽奖的信息
    		 exit($this->response(true,"抽奖处理结束",$lotteryResultInfo));
    	}
    	catch (Exception $e) {
    		exit($this->response(false,$e->getMessage()));
    	}
    }

    
    /**
     * 记录中奖用户的信息
     *
     * */
    public function recordAction()
    {
    	//http://iwebsite.umaman.com/Lottery/index/record?jsonpcallback=?&exchangeId=123&name=guo&mobile=1233&address=232323
    	try {
    		$name     = trim($this->get('name'));
    		$mobile   = trim($this->get('mobile'));
    		$address  = trim($this->get('address'));
    		$exchangeId   = trim($this->get('exchangeId'));
    		
    		if(empty($mobile)) {
    			exit($this->response(false,'请填写手机号码或电话号码'));
    		}
    		if(!isValidMobile($mobile)) {
    			exit($this->response(false,'手机格式不正确'));
    		}
    		
    		if(empty($exchangeId)) {
    			exit($this->response(false,'中奖ID为空'));
    		}
    		$modelExchange = new Lottery_Model_Exchange();
    		$exchangeInfo = $modelExchange->getInfoById($exchangeId);
    		if(empty($exchangeInfo)) {
    			exit($this->response(false,'您今天未获得奖品'));
    		}
    		if(empty($address)) {
    		    if($exchangeInfo['prizeInfo']['is_real']){//如果中奖的奖品是实体奖
    			    exit($this->response(false,'请填写寄送地址'));
    		    }
    		}
    		if(empty($name)) {
    		    if($exchangeInfo['prize_code']){//如果中奖的奖品是实体奖
    			    exit($this->response(false,'请填写你的联系人姓名'));
    		    }
    		}
    		//更新中奖人信息
    		$modelExchange->updateExchangeInfo($exchangeId, $name, $mobile, $address);    		
			exit($this->response(true,"记录处理结束"));
    	}
    	catch(Exception $e) {
    		exit($this->response(false,$e->getMessage()));
    	}
    }
    
    /**
     * 获取我的中奖的信息
     *
     * */    
    public function myPrizeAction()
    {
    	//http://iwebsite.umaman.com/Lottery/index/my-prize?jsonpcallback=?&identity_id=232323
    	try {
	    	$identity_id = trim($this->get('identity_id'));
    		if(empty($identity_id)) {
    			exit($this->response(false,'抽奖用户ID为空'));
    		}
    		$modelLotteryIdentity = new Lottery_Model_LotteryIdentity();
    		$lotteryIdentity = $modelLotteryIdentity->findOne(array('_id'=>$identity_id));
    		if(empty($lotteryIdentity)) {
    			exit($this->response(false,'抽奖用户ID不正确'));
    		}
    		$skip  = intval($this->get('skip','0'));
    		$limit = intval($this->get('limit','1000'));
    		$modelExchange = new Lottery_Model_Exchange();
    		
    		$datas = $modelExchange->getPrizeList($lotteryIdentity, $skip, $limit,false,false);
	    	exit($this->response(true,'获取处理结束',$datas));
    	} catch (Exception $e) {
    		exit($this->response(false,$e->getMessage()));
    	}
    }
    /*
     * 
     * 获取中奖名单
     * 
     * */
    public function winnerAction()
    {
    	//http://iwebsite.umaman.com/Lottery/index/winner?jsonpcallback=?&skip=0&limit=10
    	try {
	    	$skip  = intval($this->get('skip','0'));
	    	$limit = intval($this->get('limit','10'));
	    	$modelExchange = new Lottery_Model_Exchange();
	    	$datas = $modelExchange->getPrizeList(null,$skip, $limit,true,true);
	    	exit($this->response(true,'获取处理结束',$datas));
    	} catch (Exception $e) {
    		exit($this->response(false,$e->getMessage()));
    	}
    }

    
    /**
     * 兑换码短信发送
     *
     * */
    public function sendAction ()
    {
    	try {
    		$mobile    = trim($this->get('mobile'));
    		$message = trim($this->get('message'));
    		$exchange_id    = trim($this->get('exchange_id'));
    		if(empty($mobile)) {
    			exit($this->response(false,'手机号码为空'));
    		}
    		if(!isValidMobile($mobile)) {
    			exit($this->response(false,'手机格式不正确'));
    		}
    		if(empty($message)) {
    			exit($this->response(false,'内容为空'));
    		}
    		if(empty($exchange_id)) {
    			exit($this->response(false,'exchange_id为空'));
    		}
    		
    		//中奖记录
    		$modelExchange = new Lottery_Model_Exchange();
    		$exchangeInfo = $modelExchange->getInfoById($exchange_id);
    		if(empty($exchangeInfo)){
    			exit($this->response(false,'对不起，您今天没有中奖！'));
    		}
    		//短信
    		$modelSmsLog = new Lottery_Model_SmsLog();
    		$user_id = $exchangeInfo['weibo_uid'];
    		$user_name =$exchangeInfo['weibo_screen_name'];
    		$modelSmsLog->sendSms($user_id,$user_name,$mobile,$message);    		 
    		exit($this->response(true,'短信发送处理结束',array()));
    		 
    	} catch (Exception $e) {
    		exit($this->response(false,$e->getMessage()));
    	}
    }
	
    /**
     * 中奖生效
     */
    public function doPrizeValidationAction()
    {
    	// http://iwebsite.umaman.com/lottery/index/do-prize-validation?jsonpcallback=?&exchangeId=1233
    	try {
    		$exchangeId = trim($this->get('exchangeId'));
    		if (empty($exchangeId)) {
    			exit($this->response(false, '中奖ID为空'));
    		}
    		$modelExchange = new Lottery_Model_Exchange();
    		$exchangeInfo = $modelExchange->getInfoById($exchangeId);
    		if (empty($exchangeInfo)) {
    			exit($this->response(false, '您今天未获得奖品'));
    		}
    		if (!empty($exchangeInfo['is_valid'])) {
    			exit($this->response(false, '中奖已经生效'));
    		}
    		// 中奖生效
    		$modelExchange->doPrizeValidation($exchangeId);
    		exit($this->response(true, "处理结束"));
    
    	} catch (Exception $e) {
    		exit($this->response(false, $e->getMessage()));
    	}
    }
    
    /**
     * 中奖的最高纪录
     */
    public function topAction()
    {
    	// http://iwebsite.umaman.com/lottery/index/top?jsonpcallback=?&hid=1233
    	try {
    		$hid = trim($this->get('hid')); // HID
    		if(empty($hid)) {
    			exit($this->response(false, '$hid为空'));
    		}
    		// 获取抽奖参与人信息
    		$modelLotteryIdentity = new Lottery_Model_LotteryIdentity();
    		//根据HID生成一个抽奖的身份凭证
    		$lotteryIdentity = $modelLotteryIdentity->getIdentity("", "", "", "", "", "", $hid);
    		//用途
    		$modelPrizeSource = new Lottery_Model_PrizeSource();
    		$prize_source = $modelPrizeSource->getCaiquan();
    
    		$modelExchange = new Lottery_Model_Exchange();
    		$info = $modelExchange->getTopInfo($lotteryIdentity,$prize_source);
    		exit($this->response(true, '获取处理结束', $info));
    
    	} catch (Exception $e) {
    		exit($this->response(false, $e->getMessage()));
    	}
    }
}

