<?php
class Weixinshop_Model_PayErrorLog extends iWebsite_Plugin_Mongo {
	protected $name = 'iWeixinPay_PayErrorLog';
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
	 * 记录
	 *
	 * @param Exception $e        	
	 * @return array
	 */
	public function log(Exception $e) {
		$data = array ();
		$data ['error_code'] = $e->getCode ();
		$data ['error_message'] = $e->getMessage ();
		$data ['log_time'] = date ( 'Y-m-d H:i:s' );
		$result = $this->insert ( $data );
		
		return $result;
	}
}