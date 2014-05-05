<?php
class Weixinshop_Model_Consignee extends iWebsite_Plugin_Mongo
{
	protected $name = 'iWeixinshop_Consignee';
	protected $dbName = 'fg0034';
		
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
	 * 根据微信ID获取最后一条信息
	 */
	public function getLastInfoByOpenid($openid)
	{
		$query = array('openid'=>$openid);
		$sort = array('_id' =>-1);
		$list = $this->find($query,$sort,0,1);
		if(!empty($list['datas'])){
			return $list['datas'][0];
		}else{
			return array();
		}
	}
	
	/**
	 * 记录收货人信息
	 * 
	 * @param string $province
	 * @param string $city
	 * @param string $area
	 * @param string $name
	 * @param string $address
	 * @param string $tel
	 * @param string $zipcode
	 * @param string $openid
	 * @param string $orderid
	 */
	public function log($province,$city,$area,$name,$address,$tel,$zipcode,$openid,$orderid)
	{
		$data = array();
		$data['province'] = $province;
		$data['city'] = $city;
		$data['area'] = $area;
		$data['name'] = $name;
		$data['address'] = $address;
		$data['tel'] = $tel;
		$data['zipcode'] = $zipcode;
		$data['openid'] = $openid;
		$data['orderid'] = $orderid;
		$this->insert($data);
		
	}
}