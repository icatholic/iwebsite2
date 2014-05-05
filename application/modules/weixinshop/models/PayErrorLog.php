<?php
class Weixinshop_Model_PayErrorLog extends iWebsite_Plugin_Mongo
{
	protected $name = 'iWeixinshop_PayErrorLog';
	protected $dbName = 'fg0034';
	
	/*
	 * 默认排序
	*/
	public function getDefaultSort()
	{
		$sort = array('_id'=>-1);
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
	 * 记录
	 * @param Exception $e
	 * @param string $error_message
	 * @param string $error_desc
	 * @return unknown
	 */
	public function log(Exception $e)
	{		
		$data =array();
		$data['error_code'] = $e->getCode();
		$data['error_message'] = $e->getMessage();
		$data['log_time'] = date('Y-m-d H:i:s');
		$result = $this->insert($data);
		
		return $result;
	}
}