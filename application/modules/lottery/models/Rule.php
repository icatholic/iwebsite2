<?php
class Lottery_Model_Rule extends iWebsite_Plugin_Mongo
{
    protected $name = 'rule';
    protected $dbName = 'lottery_sample';
    
    //处理抽奖
    public function handleLottery($lotteryIdentity)
    {
    	//处理是否执行
    	$is_processed = 0;//未执行
    	//用途
    	$modelPrizeSource = new Lottery_Model_PrizeSource();
    	$prize_source = $modelPrizeSource->getLottery();
    	//中奖次数限制表
    	$modelNumberLimit = new Lottery_Model_NumberLimit();
    	$today_lottery_limit = $modelNumberLimit->getLimit('today_lottery');
    	$prize_limit = $modelNumberLimit->getLimit('prize_limit');
    	//中奖记录
    	$modelExchange = new Lottery_Model_Exchange();
    	//奖品表
    	$modelPrize = new Lottery_Model_Prize();
    	
    	//是否已经参与过$prize_limit次了
    	if(!empty($prize_limit)){
	    	$is_prize_limit = $modelExchange->isPrized($lotteryIdentity,$prize_source,$prize_limit,array(1,2),false);
			if($is_prize_limit['isPrized']) {
				//已中过奖
				return array('lottery_result' => -5, 'prize' => null, 'is_processed'=>$is_processed);
			}
    	}
    	
    	//今天是否已经参与过$today_lottery_limit次了
    	if(!empty($today_lottery_limit)){
	    	$modelLottery = new Lottery_Model_Lottery();
	    	$todayIsLottery = $modelLottery->todayIsLottery($lotteryIdentity,$prize_source,$today_lottery_limit,array(3));//排除再玩一次
	    	if($todayIsLottery['todayIslottery']) {
	    		//今天已经参与过了
	    		return array('lottery_result' => -4, 'prize' => null, 'is_processed'=>$is_processed);
	    	}
    	}
    	//获取实物奖code列表
    	$prizes = $modelPrize->getRealPrizes();
    	//判断是否中过实物奖
    	$prizedInfo = $modelExchange->isPrized($lotteryIdentity,$prize_source,1,$prizes,false);
    	$isPrized = $prizedInfo['isPrized'];
    	
    	$now = date("Y-m-d H:i:s");
    	$query = array('allow_number'=>array('$gt'=>0),
    							'allow_start_time'=>array('$lt'=>$now),
    							'allow_end_time'=>array('$gt'=>$now),
    							'prize_source'=>$prize_source);//用途：抽奖
    	    	
    	$rst = $this->find($query,array('allow_probability'=>1),0,1000);
    	if($rst['total']>0) {
    		//按照allow_probability分组并随机排序在同一组内的奖品顺序
    		$rst['datas'] = $this->doShuffle($rst['datas']);
    		$prize_id = 0;
    		$prize_name = 0;
    		$is_processed = 1;//已处理
    		foreach($rst['datas'] as $row) {
    			$probability = rand(0,9999);    			
    			if($probability < $row['allow_probability']) {
    				$prize_id = $row['_id'];
    				$prize_name = $row['prize_name'];
    				//如果已经中了指定的奖品，那么就不能再次中这个奖
    				if($isPrized && in_array($row['prize_name'],$prizes))
    				{
    					$prize_id =0;
    					$prize_name= 0;
    					continue;
    				}
    				break;
    			}
    		}
    		
    		if($prize_id==0) {
    			//中奖概率之外
    			return array('lottery_result' => -1, 'prize' => null, 'is_processed'=>$is_processed);
    		}
    		    		
    		$options  = array();
    		$options['query']  = array('_id'=>$prize_id,'allow_number'=>array('$gt'=>0));
    		$options['update'] = array('$inc'=>array('allow_number'=>-1));
    		$rst = $this->findAndModify($options);
    		
    		if($rst['value']==null) {
    			//奖品发完了
    			return array('lottery_result' => -2, 'prize' => null, 'is_processed'=>$is_processed);
    		}
    		
    		//奖品信息
    		return array('lottery_result' => 0, 'prize' => $rst['value'], 'is_processed'=>$is_processed);
    		
    	}else {
    		//未设定中奖规则
    		return array('lottery_result' => -3, 'prize' => null, 'is_processed'=>$is_processed);
    	}
    }
	
    /*
     * 获取奖品剩余量
     */
    public function getPrizeRemain($prize_source)
    {
    	$now = date("Y-m-d H:i:s");
    	$query = array(
    			'allow_start_time'=>array('$lt'=>$now),
    			'allow_end_time'=>array('$gt'=>$now),
    			'prize_source'=>$prize_source);//用途
    	$list = $this->find($query,array('_id'=>-1),0,1);
    	
    	if(!empty($list['datas'])){
    		return $list['datas'][0]['allow_number'];
    	}else{
    		return 0;
    	}
    }
    
    private function doShuffle($list)
    { 
    	$groupList =array();
    	//按照allow_probability分组
    	array_map(function($row) use (&$groupList){
    		$groupList["key".$row['allow_probability']][] = $row;
    	},$list);
    	//按分组随机排序
    	$resultList = array();
    	foreach ($groupList as $key => $rows) {
    		srand((float)microtime()*1000000);
    		shuffle($rows);
    		$resultList = array_merge($resultList,$rows);
    	}
    	return $resultList;
    }
}