<?php
class Lottery_Model_Lottery extends iWebsite_Plugin_Mongo
{
    protected $name = 'lottery';
    protected $dbName = 'lottery_sample';
    
    //今天是否已参与抽奖$lottery_num次
    public function todayIsLottery($lotteryIdentity,$prize_source,$lottery_num=1,$exclude_lottery_results = array())
    {
    	$query =array();
    	$query['createTime'] =array('$gte'=>date('Y-m-d').' 00:00:00','$lte'=>date('Y-m-d').' 23:59:59');
    	$query['prize_source'] = $prize_source;//用途
    	$query['identity_id'] = $lotteryIdentity['_id'];
    	if(!empty($exclude_lottery_results))
    	{
    	    $query['lottery_result'] = array('$nin'=>$exclude_lottery_results);
    	}
    	$rst   =$this->count($query);
    	return array('todayIslottery'=>($rst>($lottery_num-1)),'todayLotteryNum'=>$rst) ;
    }
    
    //处理抽奖
    public function lottery($lotteryIdentity,$source=1,$is_passon = 0) 
    {
    	//规则表
		$modelRule = new Lottery_Model_Rule();
		//抽奖结果参数说明
		$modelLotteryResult = new Lottery_Model_LotteryResult();
		//中奖记录
		$modelExchange = new Lottery_Model_Exchange(); 
		//奖品表
		$modelPrize = new Lottery_Model_Prize();
		//用途
		$modelPrizeSource = new Lottery_Model_PrizeSource();
		$prize_source = $modelPrizeSource->getLottery();
		
		//抽奖记录信息
		$insert = array();
		$insert['identity_id'] = $lotteryIdentity['_id'];
		$insert['weibo_screen_name'] = $lotteryIdentity['weibo_screen_name'];
		$insert['weibo_uid'] = $lotteryIdentity['weibo_uid'];
		$insert['FromUserName'] = $lotteryIdentity['FromUserName'];
		$insert['source_code'] = $source;
		$insert['prize_source'] = $prize_source;//用途：抽奖
		
		//进行抽奖
		$lotteryResultInfo = $modelRule->handleLottery($lotteryIdentity);
		
		if(empty($lotteryResultInfo['prize']))//未中奖 -2，-1，-3 , -4, -5，3的情况
		{
			$insert['lottery_result'] = $lotteryResultInfo['lottery_result'];
			$lotteryInfo = $this->insert($insert);
			$lotteryInfo['lottery_result_msg'] = $modelLotteryResult->getLotteryResultMsg($insert['lottery_result']);
			return array('lottery'=>$lotteryInfo,'exchange'=>null,'is_processed'=>$lotteryResultInfo['is_processed']);
		}else {//中奖
			//取出奖品的信息
			$p = $modelPrize->findOne(array('prize_code'=>$lotteryResultInfo['prize']['prize_name']));
			
			//记录中奖信息
			$exchangeInfo = $modelExchange->handle($p,$lotteryIdentity,$prize_source,$source,$is_passon);
				
			$insert['lottery_result']   = $p['prize_code'];
			$lotteryInfo = $this->insert($insert);
			$lotteryInfo['lottery_result_msg'] = $modelLotteryResult->getLotteryResultMsg($insert['lottery_result']);
			return array('lottery'=>$lotteryInfo,'exchange'=>$exchangeInfo,'is_processed'=>$lotteryResultInfo['is_processed']);
		}
    }
    
    //处理秒杀
    public function seckill($lotteryIdentity,$source=1,$is_passon = 0) 
    {
    	//规则表
		$modelRule = new Lottery_Model_Rule();
		//抽奖结果参数说明
		$modelLotteryResult = new Lottery_Model_LotteryResult();
		//中奖记录
		$modelExchange = new Lottery_Model_Exchange();
		//奖品表
		$modelPrize = new Lottery_Model_Prize();
		//用途
		$modelPrizeSource = new Lottery_Model_PrizeSource();
		$prize_source = $modelPrizeSource->getSeckill();

		//抽奖记录信息
		$insert = array();
		$insert['identity_id'] = $lotteryIdentity['_id'];
		$insert['weibo_screen_name'] = $lotteryIdentity['weibo_screen_name'];
		$insert['weibo_uid'] = $lotteryIdentity['weibo_uid'];
		$insert['FromUserName'] = $lotteryIdentity['FromUserName'];
		$insert['source_code'] = $source;
		$insert['prize_source'] = $prize_source;//用途：秒杀

		//进行秒杀
		$lotteryResultInfo = $modelRule->handleSeckill($lotteryIdentity);
		
		if(empty($lotteryResultInfo['prize']))//未中奖 -2，-1，-3的情况
		{
			$insert['lottery_result'] = $lotteryResultInfo['lottery_result'];
			$lotteryInfo = $this->insert($insert);
			$lotteryInfo['lottery_result_msg'] = $modelLotteryResult->getLotteryResultMsg($insert['lottery_result']);
			return array('lottery'=>$lotteryInfo,'exchange'=>null,'is_processed'=>$lotteryResultInfo['is_processed']);
		}else {//中奖
			//取出奖品的信息
			$p = $modelPrize->findOne(array('prize_code'=>$lotteryResultInfo['prize']['prize_name']));
			
			//记录中奖信息
			$exchangeInfo = $modelExchange->handle($p,$lotteryIdentity,$prize_source,$source,$is_passon);
			
			$insert['lottery_result']   = $p['prize_code'];
			$lotteryInfo = $this->insert($insert);
			$lotteryInfo['lottery_result_msg'] = $modelLotteryResult->getLotteryResultMsg($insert['lottery_result']);
			return array('lottery'=>$lotteryInfo,'exchange'=>$exchangeInfo,'is_processed'=>$lotteryResultInfo['is_processed']);
		}
    }
    
    //处理兑奖
    public function exchange($lotteryIdentity,$source=1,$is_passon = 0) 
    {
    	//规则表
		$modelRule = new Lottery_Model_Rule();
		//抽奖结果参数说明
		$modelLotteryResult = new Lottery_Model_LotteryResult();
		//中奖记录
		$modelExchange = new Lottery_Model_Exchange();
		//奖品表
		$modelPrize = new Lottery_Model_Prize();
		//用途
		$modelPrizeSource = new Lottery_Model_PrizeSource();
		$prize_source = $modelPrizeSource->getExchange();
		
		//抽奖记录信息
		$insert = array();
		$insert['identity_id'] = $lotteryIdentity['_id'];
		$insert['weibo_screen_name'] = $lotteryIdentity['weibo_screen_name'];
		$insert['weibo_uid'] = $lotteryIdentity['weibo_uid'];
		$insert['FromUserName'] = $lotteryIdentity['FromUserName'];
		$insert['source_code'] = $source;
		$insert['prize_source'] = $prize_source;//用途：兑奖
		    
		//进行兑奖
		$lotteryResultInfo = $modelRule->handleExchange($lotteryIdentity);
		
		if(empty($lotteryResultInfo['prize']))//未中奖 -2，-1，-3的情况
		{
			$insert['lottery_result'] = $lotteryResultInfo['lottery_result'];
			$lotteryInfo = $this->insert($insert);
			$lotteryInfo['lottery_result_msg'] = $modelLotteryResult->getLotteryResultMsg($insert['lottery_result']);
			return array('lottery'=>$lotteryInfo,'exchange'=>null,'is_processed'=>$lotteryResultInfo['is_processed']);
		}else {//中奖
			//取出奖品的信息
			$p = $modelPrize->findOne(array('prize_code'=>$lotteryResultInfo['prize']['prize_name']));
			
			//记录中奖信息
			$exchangeInfo = $modelExchange->handle($p,$lotteryIdentity,$prize_source,$source,$is_passon);
				 
			$insert['lottery_result']   = $p['prize_code'];
			$lotteryInfo = $this->insert($insert);
			$lotteryInfo['lottery_result_msg'] = $modelLotteryResult->getLotteryResultMsg($insert['lottery_result']);
			return array('lottery'=>$lotteryInfo,'exchange'=>$exchangeInfo,'is_processed'=>$lotteryResultInfo['is_processed']);
		}
    }
}