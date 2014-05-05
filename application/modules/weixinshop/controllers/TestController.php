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
	public function payFeedbackAction() {
		try {
			$modelPayFeedback = new Weixinshop_Model_PayFeedback ();
			
			$postStr = "<xml>
				<OpenId><![CDATA[111222]]></OpenId>
				<AppId><![CDATA[wwwwb4f85f3a797777]]></AppId>
				<TimeStamp> 1369743511</TimeStamp>
				<MsgType><![CDATA[request]]></MsgType>
				<FeedBackId><![CDATA[5883726847655944563]]></FeedBackId>
				<TransId><![CDATA[10123312412321435345]]></TransId>
				<Reason><![CDATA[商品质量有问题]]></Reason>
				<Solution><![CDATA[补发货给我]]></Solution>
				<ExtInfo><![CDATA[明天六点前联系我18610847266]]></ExtInfo>
	    		<PicInfo>
				 <item><PicUrl><![CDATA[http://mmbiz.qpic.cn/mmbiz/49ogibiahRNtOk37iaztwmdgFbyFS9FUrqfodiaUAmxr4hOP34C6R4nGgebMalKuY3H35riaZ5vtzJh25tp7vBUwWxw/0]]></PicUrl></item>
				 <item><PicUrl><![CDATA[http://mmbiz.qpic.cn/mmbiz/49ogibiahRNtOk37iaztwmdgFbyFS9FUrqfn3y72eHKRSAwVz1PyIcUSjBrDzXAibTiaAdrTGb4eBFbib9ibFaSeic3OIg/0]]></PicUrl></item>
				 <item><PicUrl><![CDATA[]]></PicUrl></item>
				 <item><PicUrl><![CDATA[]]></PicUrl></item>
				 <item><PicUrl><![CDATA[]]></PicUrl></item>
				 </PicInfo>
				<AppSignature><![CDATA[bc6f341a223be36d2e97fafd5dcc2ad3635a855d]]> </AppSignature>
				<SignMethod><![CDATA[sha1]]></SignMethod>
				</xml>";
			$postData = simplexml_load_string ( $postStr, 'SimpleXMLElement', LIBXML_NOCDATA );
			$postData=object2Array($postData);
			$picInfoList = array();
			foreach ($postData['PicInfo']['item'] as $picInfo) {
				if(!empty($picInfo['PicUrl'])){
					$picInfoList[] =$picInfo['PicUrl'];
				}
			}
			$postData['PicInfo'] = implode("\n", $picInfoList);
			$calc_appSignature ="calc_appSignature";
			
			$info = $modelPayFeedback ->handle($postData ['AppId'], $postData ['TimeStamp'], $postData ['OpenId'], $postData ['MsgType'], $postData ['FeedBackId'], $postData ['TransId'], $postData ['Reason'], $postData ['Solution'], $postData ['ExtInfo'], $postData ['PicInfo'], $postData ['AppSignature'], $postData ['SignMethod'], $postStr, $calc_appSignature);
			
			print_r ( $info );
			die ( 'ok' );
		} catch ( Exception $e ) {
		}
	}
	
}

