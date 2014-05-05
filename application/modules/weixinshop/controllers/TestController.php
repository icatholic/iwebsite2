<?php
/**
 * 微信商城--测试
 * @author 郭永荣
 *
 */
class Weixinshop_TestController extends Zend_Controller_Action {
	public function init() {
		$this->getHelper ( 'viewRenderer' )->setNoRender ( true );
	}
	public function indexAction() {
		$obj = new iWeixinOauth ( 'wx907e2f9c52a7df08', 'd2c7fd365cc9386bf23bb94d7857f29b', 'http://guotaiweixin.umaman.com/weixin/test/index', 'snsapi_userinfo' );
		if (isset ( $_GET ['code'] )) {
			echo 'has code';
			$obj->getAccessToken ();
			var_dump ( $obj->getUserInfo () );
		} else {
			$obj->getCode ();
			echo 'get code';
		}
		echo 'finish';
	}
	public function goodsAction() {
		try {
			$modelGoods = new Weixinshop_Model_Goods ();
			$id = "5367056f4a961948378b4584";
			// $info = $modelGoods->getInfoById($id);
			// print_r($info);
			
			$gid = "goo1";
			// $info = $modelGoods->getInfoByGid($gid);
			// print_r($info);
			
			$gnum = 1;
			$is_purchase_inner = 1;
			$gids = array (
					$gid 
			);
			// $list = $modelGoods->getList($is_purchase_inner,$gids);
			// print_r($list);
			// die('aaaaaaaaaaa');
			
			// $hasStock= $modelGoods->hasStock($gid, $gnum);
			// die('hasStock result:'.$hasStock);
			
			$out_trade_no = uniqid ();
			$modelGoods->subStock ( $out_trade_no, $gid, $gnum );
			
			die ( 'aaaaaaaaaaa' );
		} catch ( Exception $e ) {
		}
	}
	public function goodsStockDetailAction() {
		try {
			$modelGoodsStockDetail = new Weixinshop_Model_GoodsStockDetail ();
			$out_trade_no = "536719fb20316"; // uniqid();
			$gid = "goo1";
			$is_today = false;
			$isExisted = $modelGoodsStockDetail->isExisted ( $out_trade_no, $gid, $is_today );
			die ( 'isExisted result:' . $isExisted );
			$stock_num = 1;
			// $info = $modelGoodsStockDetail->handle($out_trade_no, $gid,
			// $stock_num);
			// print_r($info);
			// die('ok');
		} catch ( Exception $e ) {
		}
	}
	public function goodsCategoryAction() {
		try {
			$modelGoodsCategory = new Weixinshop_Model_GoodsCategory ();
			$id = "5365e392499619d3698b4594";
			// $info = $modelGoodsCategory->getInfoById($id);
			// print_r($info);
			// die('OK');
			$pid = "5365e358499619c8688b45a2";
			$list = $modelGoodsCategory->getList ( $pid );
			print_r ( $list );
			die ( 'ok' );
		} catch ( Exception $e ) {
		}
	}
	public function consigneeAction() {
		try {
			$modelConsignee = new Weixinshop_Model_Consignee ();
			$province = "上海";
			$city = "上海";
			$area = "杨浦区";
			$name = "郭永荣";
			$address = "杨浦延吉路";
			$tel = "13564100096";
			$zipcode = "200092";
			$openid = uniqid ();
			$orderid = uniqid ();
			$info = $modelConsignee->log ( $province, $city, $area, $name, $address, $tel, $zipcode, $openid, $orderid );
			
			print_r ( $info );
			die ( 'ok' );
		} catch ( Exception $e ) {
		}
	}
	public function payErrorLogAction() {
		try {
			$modelPayErrorLog = new Weixinshop_Model_PayErrorLog ();
			$e = new ErrorException ( "错误1", 99 );
			$info = $modelPayErrorLog->log ( $e );
			
			print_r ( $info );
			die ( 'ok' );
		} catch ( Exception $e ) {
		}
	}
	public function paySalePlanAction() {
		try {
			$modelPaySalePlan = new Weixinshop_Model_PaySalePlan ();
			$id = "536720c8499619eb108b45a4";
			// $info = $modelPaySalePlan->getInfoById($id);
			// print_r ( $info );
			
			$ProductId = "goo1";
			// $info = $modelPaySalePlan->getInfoByProductId($ProductId);
			// print_r ( $info );
			
			$list = $modelPaySalePlan->getList ( "" );
			print_r ( $list );
			die ( 'ok' );
		} catch ( Exception $e ) {
		}
	}
	public function orderAction() {
		try {
			$modelOrder = new Weixinshop_Model_Order ();
			$OpenId = "guoyongrong1234567890";
			$ProductId = "goo1";
			$body = "说明";
			$gprize = 1;
			$gnum = 1;
			$notify_url = "http://wx.laiyifen.com/service/order/notify";
			$attach = "";
			$goods_tag = "";
			$transport_fee = 0;
			$composite_sku_no = "";
			$consignee_province = "";
			$consignee_city = "";
			$consignee_area = "";
			$consignee_name = "";
			$consignee_address = "";
			$consignee_tel = "";
			$consignee_zipcode = "";
			$fee_type = 1;
			$input_charset = "GBK";
			$bank_type = "WX";
			$signType = 'sha1';
			// $info = $modelOrder->createOrder($OpenId, $ProductId, $body,
			// $gprize, $gnum, $notify_url, $attach, $goods_tag, $transport_fee,
			// $composite_sku_no, $consignee_province, $consignee_city,
			// $consignee_area, $consignee_name, $consignee_address,
			// $consignee_tel, $consignee_zipcode, $fee_type, $input_charset,
			// $bank_type, $signType);
			// print_r ( $info );
			// die ( 'ok' );
			
			// $out_trade_no = "53672fba7f50eaf80a000001";
			// $info = $modelOrder->getInfoByOutTradeNo($out_trade_no);
			// print_r ( $info );
			// die ( 'ok' );
			
			// $list= $modelOrder->getList($OpenId);
			// print_r ( $list );
			// die ( 'ok' );
			
			// $list = $modelOrder->getList4PayFinished($OpenId);
			// print_r ( $list );
			// die ( 'ok' );
			
			$out_trade_no = "53672fba7f50eaf80a000001";
			$info = $modelOrder->getInfoByOutTradeNo ( $out_trade_no );
			$updateData = array (
					"input_charset" => "GBK",
					"trade_state" => "0",
					"trade_mode" => "1",
					"partner" => "1900000109",
					"bank_type" => "CMB_FP",
					"bank_billno" => "207029722724",
					"total_fee" => "1",
					"fee_type" => "1",
					"transaction_id" => "1900000109201307020305773741",
					"out_trade_no" => "2986872580246457300",
					"is_split" => "false",
					"is_refund" => "false",
					"attach" => "",
					"time_end" => "20130702175943",
					"transport_fee" => "0",
					"product_fee" => "1",
					"discount" => "0",
					"rmb_total_fee" => "" 
			);
			$status_change_by = "testOrderchanged";
			$info = $modelOrder->changeStatus ( $info, $updateData, $status_change_by );
			print_r ( $info );
			die ( 'ok' );
		} catch ( Exception $e ) {
		}
	}
}

