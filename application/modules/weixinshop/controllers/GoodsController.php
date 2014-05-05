<?php
/**
 * 微信商城--商品
 * @author 郭永荣
 *
 */
class Weixinshop_GoodsController extends iWebsite_Controller_Action {
	public function init() {
		parent::init ();
		$this->getHelper ( 'viewRenderer' )->setNoRender ( false );
	}
	
	/**
	 * 显示商品详细页面
	 */
	public function indexAction() {
		try {
			$goodsId = trim ( $this->get ( 'goodsId', '' ) ); // 商品ID
			// 根据分类ID获取商品列表
			$modelGoods = new Weixinshop_Model_Goods ();
			$info = $modelGoods->getInfoByGid($goodsId);
			$this->assign ( "goods", $info );
			
		} catch ( Exception $e ) {
			exit ( $this->response ( false, $e->getMessage () ) );
		}
	}
	
	
	/**
	 * 显示商品内购页面
	 */
	public function innerListAction() {
		try {
			// 根据分类ID获取商品列表
			$modelGoods = new Weixinshop_Model_Goods ();
			$goodsList = $modelGoods->getList(1);//内购
			$this->assign ( "goodsList", $info );
				
		} catch ( Exception $e ) {
			exit ( $this->response ( false, $e->getMessage () ) );
		}
	}
	
	/**
	 * 显示订单页面
	 */
	public function orderAction() {
		try {
			$test4weixinpay = intval ( $this->get ( 'test4weixinpay', '0' ) );
			
			$OpenId = $this->getRequest ()->getCookie ( "FromUserName", '' );
			if (empty ( $test4weixinpay ) && empty ( $OpenId )) {
				throw new Exception ( "微信号为空" );
			}
			
			$ProductId = trim ( $this->get ( 'ProductId', '' ) ); // 商品号
			if (empty ( $ProductId )) {
				throw new Exception ( "商品号为空" );
			}
			
			$modelGoods = new Weixinshop_Model_Goods ();
			$goodsInfo = $modelGoods->getInfoByGid ( $ProductId );
			if (empty ( $goodsInfo )) {
				throw new Exception ( "商品号无效" );
			}
			$this->assign ( "goods", $goodsInfo );
			
			// 获取发货人信息
			$modelConsignee = new Weixinshop_Model_Consignee ();
			$consigneeInfo = $modelConsignee->getLastInfoByOpenid ( $OpenId );
			$this->assign ( "consigneeInfo", $consigneeInfo );
		} catch ( Exception $e ) {
			$this->errorLog->log ( $e );
			exit ( $this->response ( false, $e->getMessage () ) );
		}
	}
	
	/**
	 * 结果页面
	 */
	public function resultAction() {
		try {
			// 检查是否有订单的信息
			if (empty ( $_SESSION ['orderInfo'] )) {
				throw new Exception ( "订单不存在" );
			}
			$orderInfo = $_SESSION ['orderInfo'];
			$_SESSION ['orderInfo'] = null;
			
			$OpenId = $this->getRequest ()->getCookie ( "FromUserName", '' );
			if (empty ( $OpenId )) {
				throw new Exception ( "微信号为空" );
			}
			
			$modelGoods = new Weixinshop_Model_Goods ();
			$goodsInfo = $modelGoods->getInfoByGid ( $orderInfo ['ProductId'] );
			if (empty ( $goodsInfo )) {
				throw new Exception ( "商品号无效" );
			}
			$this->assign ( "goods", $goodsInfo );
			
			// 查询订单的状态
			$out_trade_no = $orderInfo ['out_trade_no'];
			if (strlen ( $out_trade_no ) < 1) {
				throw new Exception ( '商户订单号out_trade_no为空' );
			}
			
			$modelOrder = new Weixinshop_Model_Order ();
			$orderInfo = $modelOrder->getInfoByOutTradeNo ( $out_trade_no );
			if (empty ( $orderInfo )) {
				throw new Exception ( "订单号out_trade_no无效:{$out_trade_no}" );
			}
			
			// 是否支付成功
			$isOk = false; // $modelOrder->isOK ( $orderInfo ['trade_state'],
			               // $orderInfo ['trade_mode'] );
			if (! $isOk) {
				// // 调用订单查询 orderquery
				// $iWeixinPay = $modelOrder->getWeixinPayInstance ();
				// $ret = $iWeixinPay->orderquery ( $out_trade_no, time () );
				// $order_info = $ret ['order_info'];
				// // 订单状态的改变
				// $newOrderInfo = $modelOrder->changeStatus ( $orderInfo,
				// $order_info, 'orderquery' );
				
				// 获取财富通的订单结果
				$order_info = $modelOrder->queryTenpayInfo ( $orderInfo ['out_trade_no'] );
				// 订单状态的改变
				$newOrderInfo = $modelOrder->changeStatus ( $orderInfo, $order_info, 'orderquery4tenpay' );
				
				// 是否支付成功
				$isOk = $modelOrder->isOK ( $newOrderInfo ['trade_state'], $newOrderInfo ['trade_mode'] );
				
				if ($isOk) { // 发送客服消息
					$config = Zend_Registry::get ( 'config' );
					$token = $config ['iWeixin'] ['token'];
					$project_id = $config ['iWeixin'] ['project_id'];
					$weixin = new iWeixin2 ( $project_id, $token );
					$total_fee = number_format ( $newOrderInfo ['total_fee'] / 100, 2 );
					$content = "你支付了{$total_fee}元人民币,成功购买商品号为{$newOrderInfo['ProductId']} {$newOrderInfo['body']}的商品";
					$weixin->getWeixinMsgManager ()->getWeixinCustomMsgSender ()->setToUser ( $OpenId );
					$weixin->getWeixinMsgManager ()->getWeixinCustomMsgSender ()->sendText ( $content );
				}
			}
		} catch ( Exception $e ) {
			$this->errorLog->log ( $e );
			$isOk = false;
		}
		$this->assign ( "isOK", empty ( $isOk ) ? 0 : 1 );
	}
	
	/**
	 * 显示发货地址页面
	 */
	public function addressAction() {
		try {
			// $OpenId = $this->getRequest ()->getCookie ( "FromUserName", '' );
			// if (empty ( $OpenId )) {
			// throw new Exception ( "微信号为空" );
			// }
			
			$ProductId = trim ( $this->get ( 'ProductId', '' ) ); // 商品号
			if (empty ( $ProductId )) {
				throw new Exception ( "商品号为空" );
			}
			
			$modelGoods = new Weixinshop_Model_Goods ();
			$goodsInfo = $modelGoods->getInfoByGid ( $ProductId );
			if (empty ( $goodsInfo )) {
				throw new Exception ( "商品号无效" );
			}
			$this->assign ( "goods", $goodsInfo );
			
			// 微信授权
			$url = "http://wx.laiyifen.com/lyfwx_pay/address.php?showwxpaytitle=1&test4weixinpay=1&ProductId={$ProductId}&authorizeAction=weixin";
			$this->assign ( "url", $url );
			
			$authorizeAction = trim ( $this->get ( 'authorizeAction', '' ) ); // 操作
			if ($authorizeAction == 'weixin') {
				// url参数
				$umaId = trim ( $this->getRequest ()->getParam ( 'umaId', '' ) );
				$config = Zend_Registry::get ( 'config' );
				$token = $config ['iWeixin'] ['token'];
				$project_id = $config ['iWeixin'] ['project_id'];
				$weixin = new iWeixinSns ( $project_id, $token );
				$token = $weixin->get ( $umaId, 'uma/sns/get_token', array () );
				$accessToken = $token ['access_token'];
				$code = $token ['code'];
				$state = "editaddress";
				$url = $url . "&state={$state}&code={$code}";
				
				$appid = $config ['iWeixin'] ['pay'] ['appId'];
				$this->assign ( "appId", $appid );
				
				$timeStamp = time ();
				$this->assign ( "timeStamp", $timeStamp );
				
				$nonceStr = md5 ( time () . mt_rand ( 0, 1000 ) );
				$this->assign ( "nonceStr", $nonceStr );
				
				$addrSign = $this->getAddrSign ( $appid, $timeStamp, $nonceStr, $accessToken, $url );
				$this->assign ( "addrSign", $addrSign );
			} else {
				if (IsWeixinBrowser ()) { // 如果不是微信浏览器的时候
				                       // 微信授权
					$callbackUrl = urlencode ( $url );
					header ( "Location: http://131224fg0402.umaman.com/weixin/campaign/authorize?scope=snsapi_base&state={$state}&callbackUrl={$callbackUrl}" );
					exit ( 0 );
				}
			}
		} catch ( Exception $e ) {
			$this->errorLog->log ( $e );
			exit ( $this->response ( false, $e->getMessage () ) );
		}
	}
	private function getAddrSign($appid, $timeStamp, $nonceStr, $accessToken, $url) {
		$parameters = array ();
		$parameters ["appid"] = $appid;
		$parameters ["accesstoken"] = $accessToken;
		$parameters ["noncestr"] = $nonceStr;
		$parameters ["url"] = $url;
		$parameters ["timestamp"] = $timeStamp;
		
		$reqPar = "";
		ksort ( $parameters );
		foreach ( $parameters as $k => $v ) {
			$reqPar .= $k . "=" . $v . "&";
		}
		$reqPar = substr ( $reqPar, 0, strlen ( $reqPar ) - 1 );
		$addrSign = sha1 ( $reqPar );
		return $addrSign;
	}
	public function __destruct() {
	}
}

