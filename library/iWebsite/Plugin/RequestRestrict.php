<?php
class iWebsite_Plugin_RequestRestrict extends Zend_Controller_Plugin_Abstract {
	public function preDispatch(Zend_Controller_Request_Abstract $request) {		
		
		// 判断是否为ajax请求
		if ($request->isXmlHttpRequest ()) {
						
			$module = strtolower ( $request->getModuleName () );
			$controller = strtolower ( $request->getControllerName () );
			$action = strtolower ( $request->getActionName () );
			$ip = getIp();
			//$params = $request->getParams ();
						
			$httpHost = $request->getHttpHost();
			$param = parse_url($request->getRequestUri());
			$requestUri = $param['path'];
			$key = md5 ( "httphost_{$httpHost}_requestUri_{$requestUri}_ip_{$ip}" );
			$isRestricted = isRequestRestricted ( $key );
			if ($isRestricted) {
				exit ( $this->response ( false, "你的请求过于频繁，亲" ) );
			}
		}
	}
	
	protected function response($stat, $msg = '', $result = '') {
		$jsonpcallback = trim ( $this->getRequest ()->getParam ( 'jsonpcallback', '' ) );
		return jsonpcallback($jsonpcallback, $stat, $msg, $result);
	}
}