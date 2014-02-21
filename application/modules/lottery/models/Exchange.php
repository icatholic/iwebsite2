<?php
class Lottery_Model_Exchange extends iWebsite_Plugin_Mongo
{
    protected $name = 'exchange';
    protected $dbName = 'lottery_sample';
    
    //是否中过 $prize_num 次奖
    public function isPrized($lotteryIdentity,$prize_source=1,$prize_num=1,array $prizes=array(), $is_today=false)
    {
    	$query=array();
    	$query['is_valid'] = 1;//生效
    	$query['prize_source'] = $prize_source;//用途：抽奖
    	$query['identity_id'] = $lotteryIdentity['_id'];    	
    	if(!empty($prizes)){//奖品code列表
    		$query['prize_code'] = array('$in'=>$prizes);
    	}
    	if($is_today){//当天
    		$query['createTime'] = array('$gte'=>date('Y-m-d').' 00:00:00','$lte'=>date('Y-m-d').' 23:59:59');
    	}
    	$count = $this->count($query);
    	return array('isPrized'=>($count > ($prize_num-1)),'prizeNum'=>$count);
    }
    
    //获取奖品列表
    public function getPrizeList($lotteryIdentity=null,$skip=0, $limit=10,$needShuffle=false,$nameNeedhidden =false,$prize_source=0)
    {
    	$modelPrize = new Lottery_Model_Prize();
    	$prize_list = $modelPrize->getPrizeList();
    	
    	$sort  = array('_id'=>-1);
    	$query = array();
    	$query['is_valid'] = 1;//生效
    	if(!empty($prize_source)){
    		$query['prize_source'] = $prize_source;//用途：抽奖
    	}
    	if(!empty($lotteryIdentity)){
    		$query['identity_id'] = $lotteryIdentity['_id'];
    	}
    	    	
    	$rst = $this->find($query, $sort, $skip, $limit);
    	$datas = array();
    	$datas['total'] = $rst['total'];
    	$datas['datas'] = array();
    	if(!empty($rst['datas'])){
	    	foreach($rst['datas'] as $row) {
	    		$row['prize_name'] = $prize_list[$row['prize_code']];
	    		if($nameNeedhidden){
	    			//以下代码根据具体业务可能需要修改
	    			$row['weibo_screen_name'] = mb_substr($row['weibo_screen_name'],0,2,'utf-8').'***'.mb_substr($row['weibo_screen_name'],-2,2,'utf-8');
	    		}
	    		$datas['datas'][]  = $row;
	    	}
	    	if($needShuffle){//是否要打乱顺序
	    		shuffle($datas['datas']);
	    	}
    	}
    	return $datas;
    }
    //处理奖品
    public function handle($p,$lotteryIdentity,$prize_source=1,$source=1,$is_passon=0,$is_valid=1)
    {
    	//消耗一个奖品
    	$modelCode = new Lottery_Model_Code();
    	$result = $modelCode->handlePrize($p['prize_code'],$prize_source);
    	
    	//记录中奖信息
    	$datas = array();
    	$datas['prize_code']  = $p['prize_code'];
    	if($result) {
    		$datas['exchange_code'] = $result['code'];
    		$datas['exchange_pwd'] = $result['pwd'];
    	}else{
    		$datas['exchange_code'] = '';
    		$datas['exchange_pwd'] = '';
    	}
    	$datas['identity_id'] = $lotteryIdentity['_id'];
    	$datas['weibo_uid'] = $lotteryIdentity['weibo_uid'];
    	$datas['weibo_screen_name'] = $lotteryIdentity['weibo_screen_name'];
    	$datas['FromUserName'] = $lotteryIdentity['FromUserName'];
    	
    	$datas['exchange_name']     = $lotteryIdentity['name'];
    	$datas['exchange_mobile']   = $lotteryIdentity['mobile'];
    	$datas['exchange_address']  = $lotteryIdentity['address'];
    	
    	$datas['is_passon']  = $is_passon;//是否转送
    	$datas['is_valid']  = $is_valid;//是否生效
    	
    	$datas['source'] = $source;
    	$datas['prize_source'] = $prize_source;//用途
    	$exchangeInfo = $this->insert($datas);
    	$exchangeInfo['prize_info'] = $p;//奖品信息
    	return $exchangeInfo;
    }
    
    //根据ID获取中奖信息
    public function getInfoById($id)
    {
    	$info = $this->findOne(array('_id'=>$id));
    	if($info){
    		$modelPrize = new Lottery_Model_Prize();
    		$prizeInfo =$modelPrize->findOne(array('prize_code'=>$info['prize_code']));
    		if($prizeInfo){
    			$info['prizeInfo'] = $prizeInfo;
    		}else{
    			throw new Exception('奖品的信息有误');
    		}
    	}
    	return $info;
    }
    
    //记录中奖人的信息
    public function updateExchangeInfo($exchangeId,$name,$mobile,$address,$is_passon=0,$is_valid=1)
    {
    	$query =array('_id'=>$exchangeId);
    	$datas = array();
    	$datas['exchange_name']     = $name;
    	$datas['exchange_mobile']   = $mobile;
    	$datas['exchange_address']  = $address;
    	$datas['is_passon']  = $is_passon;
    	$datas['is_valid']  = $is_valid;//是否生效
    	$rst = $this->update($query,array('$set'=>$datas));
    	if(isset($rst['err'])) {
    		throw new Exception($rst['err']);
    	}
    	return $rst;
    }
    
    /*
     * 中奖生效
     */
    public function doPrizeValidation($exchangeId)
    {
    	$query =array('_id'=>$exchangeId);
    	$datas = array();
    	$datas['is_valid']  = 1;//生效
    	$rst = $this->update($query,array('$set'=>$datas));
    	if(isset($rst['err'])) {
    		throw new Exception($rst['err']);
    	}
    }
    
    /*
     * 获取中奖数量
    */
    public function getPrizedNum($lotteryIdentity=null,$prize_source=1,array $prizes=array(), $is_today=false)
    {
    	$query=array();
    	$query['is_valid'] = 1;//生效
    	$query['prize_source'] = $prize_source;//用途：抽奖
    	if($lotteryIdentity){
    		$query['identity_id'] = $lotteryIdentity['_id'];
    	}
    	if(!empty($prizes)){//奖品code列表
    		$query['prize_code'] = array('$in'=>$prizes);
    	}
    	if($is_today){//当天
    		$query['createTime'] = array('$gte'=>date('Y-m-d').' 00:00:00','$lte'=>date('Y-m-d').' 23:59:59');
    	}
    	$count = $this->count($query);
    	return $count;
    }
    
    /*
     * 获取最高纪录信息
    */
    public function getTopInfo($lotteryIdentity=null,$prize_source=0)
    {
    	$query=array();
    	$query['is_valid']=1;//生效
    	if(!empty($lotteryIdentity)){
    		$query['identity_id']=$lotteryIdentity['_id'];
    	}
    	if(!empty($prize_source)){
    		$query['prize_source']=$prize_source;
    	}
    	$rst = $this->find($query,array('prize_code'=>-1),0,1);
    	if(empty($rst['datas'])){
    		return null;
    	}else{
    		$info=$rst['datas'][0];
    		$modelPrize = new Lottery_Model_Prize();
    		$prizeInfo =$modelPrize->findOne(array('prize_code'=>$info['prize_code']));
    		if($prizeInfo){
    			$info['prizeInfo'] = $prizeInfo;
    		}else{
    			throw new Exception('奖品的信息有误');
    		}
    		return $info;
    	}
    }
}