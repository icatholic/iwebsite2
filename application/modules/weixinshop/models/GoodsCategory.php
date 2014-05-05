<?php
class Weixinshop_Model_GoodsCategory extends iWebsite_Plugin_Mongo {
	protected $name = 'iWeixinPay_GoodsCategory';
	protected $dbName = 'weixinshop';
	
	/**
	 * 默认排序
	 */
	public function getDefaultSort() {
		$sort = array (
				'_id' => - 1 
		);
		return $sort;
	}
	
	/**
	 * 默认查询条件
	 */
	public function getQuery() {
		$query = array ();
		return $query;
	}
	
	/**
	 * 根据ID获取信息
	 *
	 * @param string $id        	
	 * @return array
	 */
	public function getInfoById($id) {
		$query = array (
				'_id' => myMongoId ( $id ) 
		);
		$info = $this->findOne ( $query );
		return $info;
	}
	
	/**
	 * 获取商品分类列表信息
	 *
	 * @param string $pid        	
	 * @return array
	 */
	public function getList($pid = "") {
		$query = $this->getQuery ();
		$query ['pid'] = $pid;
		$sort = $this->getDefaultSort ();
		$list = $this->findAll ( $query, $sort );
		return $list;
	}
}