<?php
/**
 * 微信商城--购物车
 * @author 郭永荣
 *
 */
class Weixinshop_ShoppingcartController extends iWebsite_Controller_Action {
	public function init() {
		parent::init ();
		$this->getHelper ( 'viewRenderer' )->setNoRender ( false );
	}
	
	/**
	 * 显示购物车结算
	 */
	public function indexAction() {
		try {
			// 从COOKIE中获取内容
			$cart = self::getCookie ( 'cart' );
			$cart = $cart ? $cart : array ();
			if (! empty ( $cart )) {
				// 获取商品信息
				$modelGoods = new Weixinshop_Model_Goods ();
				$goodsIds = key ( $cart );
				$goodsList = $modelGoods->getList ( 0, $goodsIds );
				
				foreach ($cart as $goodsId => &$value) {
					if(array_key_exists($goodsId, $goodsList))//存在
					{
						$info = $goodsList[$goodsId];
						$value ['name'] = $info ['gname']; // 商品名
						$value ['prize'] = $info ['gprize']; // 商品单价
						$value ['gpic'] = $info ['gpic1']; // 商品图片
						$value ['amount'] = $info ['gprize']*$value ['num']; // 商品图片
						$value ['stock_num'] = $info ['stock_num']; // 商品库存;
					}else{
						unset($value);//删除
					}
				}
			}
			$this->assign ( 'cart', $cart );
		} catch ( Exception $e ) {
			exit ( $this->response ( false, $e->getMessage () ) );
		}
	}
	
	/**
	 * 显示结算页面
	 */
	public function settleAction() {
		try {				
			$OpenId = $this->getRequest ()->getCookie ( "FromUserName", '' );
			if (empty ( $OpenId ) ) {
				throw new Exception ( "微信号为空" );
			}
				
			$ProductIds = trim ( $this->get ( 'ProductIds', '' ) ); // 商品号
			if (empty ( $ProductIds )) {
				throw new Exception ( "商品号为空" );
			}
			
			$nums = trim ( $this->get ( 'nums', '' ) ); // 商品数量
			if (empty ( $nums )) {
				throw new Exception ( "商品数量为空" );
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
	 * 查看购物车
	 */
	public function viewAction() {
		try {
			// 从COOKIE中获取内容
			$cart = self::getCookie ( 'cart' );
			$cart = $cart ? $cart : array ();
			if (empty ( $cart )) {
				exit ( $this->response ( true, "购物车没有内容" ) );
			}
			exit ( $this->response ( true, "OK", $cart ) );
		} catch ( Exception $e ) {
			exit ( $this->response ( false, $e->getMessage () ) );
		}
	}
	
	/**
	 * 向购物车中增加商品
	 */
	public function addAction() {
		try {
			$goodsId = trim ( $this->get ( 'goodsId', '' ) ); // 商品ID
			$num = intval ( $this->get ( 'num' ) ); // 商品数量
			if (empty ( $goodsId )) {
				exit ( $this->response ( false, "商品ID为空" ) );
			}
			if (empty ( $num )) {
				exit ( $this->response ( false, "数量不能为空或0" ) );
			}
			if (($num) < 0) {
				exit ( $this->response ( false, "数量不正确" ) );
			}
			// 从COOKIE中获取内容
			$cart = self::getCookie ( 'cart' );
			$cart = $cart ? $cart : array ();
			if (key_exists ( $goodsId, $cart )) {
				$cart [$goodsId] ['num'] = intval ( $cart [$goodsId] ) + $num;
			} else {
				// 判断商品ID是否正确
				$modelGoods = new Weixinshop_Model_Goods ();
				$info = $modelGoods->getInfoById ( $goodsId );
				if (empty ( $info )) {
					exit ( $this->response ( false, "商品ID不存在" ) );
				}
				$cart [$goodsId] ['name'] = $info ['gname']; // 商品名
				$cart [$goodsId] ['prize'] = $info ['gprize']; // 商品单价
				$cart [$goodsId] ['num'] = $num;
			}
			// 最新的内容存入COOKIE中
			self::setCookie ( 'cart', $cart );
			exit ( $this->response ( true, "OK", $cart ) );
		} catch ( Exception $e ) {
			exit ( $this->response ( false, $e->getMessage () ) );
		}
	}
	
	/**
	 * 更改购物车内某商品的数量
	 */
	public function updateNumAction() {
		try {
			$goodsId = trim ( $this->get ( 'goodsId', '' ) ); // 商品ID
			$num = intval ( $this->get ( 'num' ) ); // 商品数量
			if (empty ( $goodsId )) {
				exit ( $this->response ( false, "商品ID为空" ) );
			}
			if (empty ( $num )) {
				exit ( $this->response ( false, "数量不能为空或0" ) );
			}
			if (($num) < 0) {
				exit ( $this->response ( false, "数量不正确" ) );
			}
			// 从COOKIE中获取内容
			$cart = self::getCookie ( 'cart' );
			$cart = $cart ? $cart : array ();
			if (key_exists ( $goodsId, $cart )) {
				$cart [$goodsId] ['num'] = $num;
			} else {
				exit ( $this->response ( false, "商品ID不存在" ) );
			}
			// 最新的内容存入COOKIE中
			self::setCookie ( 'cart', $cart );
			exit ( $this->response ( true, "OK", $cart ) );
		} catch ( Exception $e ) {
			exit ( $this->response ( false, $e->getMessage () ) );
		}
	}
	
	/**
	 * 删除购物车内某商品
	 */
	public function deleteAction() {
		try {
			$goodsId = trim ( $this->get ( 'goodsId', '' ) ); // 商品ID
			if (empty ( $goodsId )) {
				exit ( $this->response ( false, "商品ID为空" ) );
			}
			// 从COOKIE中获取内容
			$cart = self::getCookie ( 'cart' );
			$cart = $cart ? $cart : array ();
			if (key_exists ( $goodsId, $cart )) {
				unset ( $cart [$goodsId] );
			} else {
				exit ( $this->response ( false, "商品ID不存在" ) );
			}
			// 最新的内容存入COOKIE中
			self::setCookie ( 'cart', $cart );
			
			exit ( $this->response ( true, "OK", $cart ) );
		} catch ( Exception $e ) {
			exit ( $this->response ( false, $e->getMessage () ) );
		}
	}
	
	/**
	 * 清空购物车
	 */
	public function clearAction() {
		try {
			$cart = array ();
			// 最新的内容存入COOKIE中
			self::setCookie ( 'cart', $cart );
			exit ( $this->response ( true, "OK", $cart ) );
		} catch ( Exception $e ) {
			exit ( $this->response ( false, $e->getMessage () ) );
		}
	}
	
	/**
	 * 获取购物车的统计信息
	 */
	public function statisticsAction() {
		try {
			$info = array (
					'goods_count' => 0,
					'amount' => 0 
			);
			// 从COOKIE中获取内容
			$cart = self::getCookie ( 'cart' );
			$cart = $cart ? $cart : array ();
			
			if (! empty ( $cart )) {
				foreach ( $cart as $goodsId => $goodsInfo ) {
					$num = $goodsInfo ['num'];
					$prize = $goodsInfo ['prize'];
					$info ['goods_count'] ++;
					$info ['amount'] += $num * $prize;
				}
			}
			exit ( $this->response ( true, "OK", $info ) );
		} catch ( Exception $e ) {
			exit ( $this->response ( false, $e->getMessage () ) );
		}
	}

	public function __destruct() {
	}
}

