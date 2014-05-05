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
			//$info = $modelPaySalePlan->getInfoById($id);
			//print_r ( $info );
			
			$ProductId = "goo1";
			//$info = $modelPaySalePlan->getInfoByProductId($ProductId);
			//print_r ( $info );
			
			$list = $modelPaySalePlan->getList("");
			print_r ( $list );
			die ( 'ok' );
			
		} catch ( Exception $e ) {
		}
	}
}

