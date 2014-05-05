<?php
class Weixinshop_Model_Goods extends iWebsite_Plugin_Mongo {
	protected $name = 'iWeixinshop_Goods';
	protected $dbName = 'fg0034';
	/*
	 * 默认排序
	 */
	public function getDefaultSort() {
		$sort = array (
				'_id' => - 1 
		);
		return $sort;
	}
	/*
	 * 默认查询条件
	 */
	public function getQuery() {
		$query = array (
				"is_show" => 1 
		); // 显示
		return $query;
	}
	
	/**
	 * 根据ID获取信息
	 */
	public function getInfoById($id) {
		$query = array (
				'_id' => $id 
		);
		$info = $this->findOne ( $query );
		return $info;
	}
	
	/**
	 * 根据商品号获取信息
	 */
	public function getInfoByGid($gid) {
		$query = array (
				'gid' => $gid 
		);
		$info = $this->findOne ( $query );
		return $info;
	}
	
	/**
	 * 获取商品列表信息
	 * 
	 * @param int $is_purchase_inner 是否内购
	 * 	@param array $gids 商品ID列表
	 * @return array
	 */
	public function getList($is_purchase_inner = 0, array $gids = array()) {
		$query = $this->getQuery ();
		if (! empty ( $is_purchase_inner )) {
			$query ['is_purchase_inner'] = $is_purchase_inner;
		}
		if (! empty ( $gids )) {
			$query ['gid'] = array (
					'$in' => $gids 
			);
		}
		$sort = $this->getDefaultSort ();
		$list = $this->findAll ( $query, $sort );
		return $list;
	}
	
	/**
	 * 减少库存数量
	 * 
	 * @param string $out_trade_no        	
	 * @param string $gid        	
	 * @param int $gnum        	
	 */
	public function subStock($out_trade_no, $gid, $gnum) {
		if (! empty ( $gnum )) {
			// 判断是否已减少了库存数量
			$modelGoodsStockDetail = new Weixinshop_Model_GoodsStockDetail ();
			$isExisted = $modelGoodsStockDetail->isExisted ( $out_trade_no, $gid );
			
			if (! $isExisted) {
				$info = $this->getInfoByGid ( $gid );
				$data ['stock_num'] = 0 - $gnum;
				$options = array (
						"query" => array (
								"_id" => $info ['_id'],
								'stock_num' => array (
										'$gt' => 0 
								) 
						),
						"update" => array (
								'$inc' => $data 
						),
						"new" => true 
				);
				$this->findAndModify ( $options );
				
				// 记录明细追踪表
				$modelGoodsStockDetail->handle ( $out_trade_no, $gid, 0 - $gnum );
			}
		}
	}
	
	/**
	 * 是否有库存
	 * 
	 * @param string $gid        	
	 * @return boolean
	 */
	public function hasStock($gid, $gnum) {
		$info = $this->getInfoByGid ( $gid );
		if (! empty ( $info ) && ! empty ( $info ['stock_num'] )) {
			return ($info ['stock_num'] >= $gnum);
		} else {
			return false;
		}
	}
}