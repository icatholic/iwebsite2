<?php
/**
 * 快钱测试
 * @author DMT-59
 * 
 */
class Default_BillController extends Zend_Controller_Action {
	/**
	 * 支付访问页面
	 */
	public function indexAction() {
		$projectId = '520b436e4996194d09910c67';//scrm
		//$password = '1q2w3e4r';
		//$projectId = '5204c6224896191a1e06a69d';//dev
		$password = '123456';
		$orderId = time (); // 订单号
		$o = new iPay ( $projectId, $orderId, $password );//*****重要说明：使用全请自行修改iPay文件中130-137行中相关的服务地址主机设置******
		$o->setDebug ( false ); // 打开调试模式，查看执行情况
		$orderName = '测试订单';
		$orderAmount = 0.01;
		$result = $o->bill99Pay ( $orderId, $orderName, $orderAmount, '商户名称'); 
		Zend_Debug::dump($result);
		$this->view->orderName = $orderName;
		$this->view->orderAmount = $orderAmount;
		$this->view->orderId = $orderId;
		$this->view->result = $result;
	}
	/**
	 * 支付访问页面  银行快捷支付
	 */
	public function indexBankAction() {
		$projectId = '520b43a14996193709ee7555';//scrm
		//$password = '1q2w3e4r';
		//$projectId = '5204c6224896191a1e06a69d';//dev
		$password = '123456';
		$orderId = time (); // 订单号
		$o = new iPay ( $projectId, $orderId, $password );//*****重要说明：使用全请自行修改iPay文件中130-137行中相关的服务地址主机设置******
		$o->setDebug ( false ); // 打开调试模式，查看执行情况
		$orderName = '测试订单';
		$orderAmount = 0.01;
		$bank = 'CMB';//银行代码 根据项目情况换，具体参见快钱银行代码
		$result = $o->bill99PayBank ( $orderId, $orderName, $orderAmount, '商户名称',$bank);
		Zend_Debug::dump($result);
		$this->view->orderName = $orderName;
		$this->view->orderAmount = $orderAmount;
		$this->view->orderId = $orderId;
		$this->view->result = $result;
	}
	/**
	 * 返回页面
	 */
	public function callbackAction() {
		$this->_helper->viewRenderer->setNoRender ( true );
		$orderId = $this->getParam ( 'orderId' );
		$params = $this->getAllParams ();
		//$projectId = '520b43a14996193709ee7555';//scrm
		//$password = '1q2w3e4r';
		$projectId = '520b436e4996194d09910c67';//dev
		$password = '123456';
		Zend_Debug::dump($params);
		$o = new iPay ( $projectId, $orderId, $password );//*****重要说明：使用全请自行修改iPay文件中130-137行中相关的服务地址主机设置******
		$result = $o->bill99CheckOrder ( $orderId ); //
		Zend_Debug::dump($result);
		if ($result ['status']) {
			// 成功逻辑
			echo '支付成功';
		} else {
			// 失败逻辑
			echo '支付失败';
		} // true说明成功
	}
	/**
	 * 支付访问页面
	 */
	public function index2Action() {
		$projectId = '520b43a14996193709ee7555';
		$password = '123456';
		$orderId = time (); // 订单号
		$o = new iPay ( $projectId, $orderId, $password );//*****重要说明：使用全请自行修改iPay文件中130-137行中相关的服务地址主机设置******
		$o->setDebug ( false ); // 打开调试模式，查看执行情况
		$orderName = '测试订单';
		$orderAmount = 0.01;
		$result = $o->bill99Pay ( $orderId, $orderName, $orderAmount, '商户名称' ); 
		Zend_Debug::dump($result);
		$this->view->orderName = $orderName;
		$this->view->orderAmount = $orderAmount;
		$this->view->orderId = $orderId;
		$this->view->result = $result;
	}
	/**
	 * 返回页面
	 */
	public function callback2Action() {
		$this->_helper->viewRenderer->setNoRender ( true );
		$orderId = $this->getParam ( 'orderId' );
		$params = $this->getAllParams ();
		$projectId = '520b43a14996193709ee7555';
		$password = '123456';
		Zend_Debug::dump($params);
		$o = new iPay ( $projectId, $orderId, $password );//*****重要说明：使用全请自行修改iPay文件中130-137行中相关的服务地址主机设置******
		$result = $o->bill99CheckOrder ( $orderId ); //
		Zend_Debug::dump($result);
		if ($result ['status']) {
			// 成功逻辑
			echo '支付成功';
		} else {
			// 失败逻辑
			echo '支付失败';
		} // true说明成功
	}
	/**
	 * 测试页面1
	 */
	public function testAction() {
		$this->_helper->viewRenderer->setNoRender ( true );
		
		//$orderId = '1375949986';
		$orderId = $this->getParam('orderId');
		$projectId = '51e61511479619ca5beea7bb';
		$password = '1q2w3e4r';
		$o = new iPay ( $projectId, $orderId, $password );//*****重要说明：使用全请自行修改iPay文件中130-137行中相关的服务地址主机设置******
		$result = $o->bill99Test ( $orderId ); // ?'支付成功':'支付失败';//true说明成功
		Zend_Debug::dump ( $result );
		//echo '<result>1</result> <redirecturl>http://27.115.13.122/iwebsite/bill/callback/</redirecturl>';
	}
	/**
	 * 测试页面2
	 */
	public function test2Action() {
		$this->_helper->viewRenderer->setNoRender ( true );
		ini_set ( "soap.wsdl_cache_enabled", "0" );
	
		$orderId = '1375949986';
		$orderId = $this->getParam('orderId');
		$projectId = '51e61511479619ca5beea7bb';
		$password = '1q2w3e4r';
		
		$o = new iPay ( $projectId, $orderId, $password );
		$result = $o->auth ( $projectId,$orderId ); // 身份认证
		var_dump ( $result );
	}
}

 