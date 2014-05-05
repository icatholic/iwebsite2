<?php
class Weixinshop_Model_GoodsStockDetail extends iWebsite_Plugin_Mongo
{
	protected $name = 'iWeixinshop_GoodsStockDetail';
	protected $dbName = 'fg0034';
	
	//处理
	public function handle($out_trade_no,$gid,$stock_num)
	{
		$data=array();
		$data['out_trade_no']=$out_trade_no;
		$data['gid']=$gid;
		$data['stock_time']=date('Y-m-d H:i:s');
		$data['stock_num']=$stock_num;
		$info=$this->insert($data);
		return $info;
	}
	
	//是否已存在
	public function isExisted($out_trade_no,$gid,$is_today=false)
	{
		$query=array();
		if(!empty($out_trade_no)){
			$query['out_trade_no'] = $out_trade_no;
		}
		if(!empty($gid)){//活动身份列表
			$query['gid'] = $gid;
		}
		if($is_today){//当天
			$query['stock_time'] = array('$gte'=>date('Y-m-d').' 00:00:00','$lte'=>date('Y-m-d').' 23:59:59');
		}
		$count = $this->count($query);
		return $count > 0;
	}
	
}