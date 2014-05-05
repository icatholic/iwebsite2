<?php
class Weixinshop_Model_PaySalePlan extends iWebsite_Plugin_Mongo
{
	protected $name = 'iWeixinshop_PaySalePlan';
	protected $dbName = 'fg0034';
	/*
	 * 默认排序
	*/
	public function getDefaultSort()
	{
		$sort = array('priority'=>-1,'onsale_time'=>1);
		return $sort;
	}
	
	/*
	 * 默认查询条件
	*/
	public function getQuery()
	{
		$query = array();
		return $query;
	}
	
	/**
	 * 根据ID获取信息
	 */
	public function getInfoById($id)
	{
		$query = array('_id'=>$id);
		$info = $this->findOne($query);
		return $info;
	}
	
	/**
	 * 根据商品号获取信息
	 */
	public function getInfoByProductId($ProductId)
	{
		$query = array('ProductId'=>$ProductId);
		$info = $this->findOne($query);
		return $info;
	}
	
	/**
	 * 获取上架商品列表信息
	 */
	public function getList($ProductId="")
	{
		$query = $this->getQuery();
		$now = date("Y-m-d H:i:s");
		if(!empty($ProductId)){
			$query['ProductId'] =$ProductId;
		}else{
			$query['onsale_time'] =array('$lt'=>$now);
			$query['offsale_time'] = array('$gt'=>$now);
			//$query['stock_num'] = array('$gt'=>0);
		}
		
		$sort = $this->getDefaultSort();
		$ret = $this->findAll($query,$sort);
		$total = 0;
		$datas = array();
		if(!empty($ret['datas'])){
			$modelGoods = new Weixinshop_Model_Goods();
			foreach ($ret['datas'] as $plan) {
				$goodsInfo = $modelGoods->getInfoByGid($ProductId);
				if(!empty($goodsInfo) && $goodsInfo['stock_num']>0){
					$total ++;
					$datas[] = $goodsInfo;
				}
			}
		}
		$list['total'] = $total;
		$list['datas'] = $datas;
		return $list;
	}
	
}