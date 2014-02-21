<?php
class Default_WeixinController extends Zend_Controller_Action
{
	public function indexAction()
	{
		$module = $this->getRequest()->getModuleName();
		$controller = $this->getRequest()->getControllerName();
		$action = $this->getRequest()->getActionName();
		$config = Zend_Registry::get("config");
		$this->view->assign('config' , $config);
		$this->view->assign('module' , $module);
		$this->view->assign('controller' , $controller);
		$this->view->assign('action', $action);
	}
	
	public function doTest1Action()
	{
		//http://file.api.weixin.qq.com/cgi-bin/media/get?access_token=AkYVMHSxG7xXzIOCnbPPZf2Bwcd9mCtXBGZmaQxjNldCgPSfprxP-Zz4m4klmc2Nh1t_Wmt05ZsbYrvTIvGhLgOl-vgS2xeKDN6LHMnD2PYlUJiFRyrpwmADHEQPPSMT86h6E7V61CMoiG0KEeqBKg&media_id=AoBRcco1W-H8t7tP82N3MysxLeexKP1l_VykGfI0CxLC_4mV-ZlyVEh0tne80FK2
		try {
			$this->getHelper('viewRenderer')->setNoRender(true);
			$access_token = 'AkYVMHSxG7xXzIOCnbPPZf2Bwcd9mCtXBGZmaQxjNldCgPSfprxP-Zz4m4klmc2Nh1t_Wmt05ZsbYrvTIvGhLgOl-vgS2xeKDN6LHMnD2PYlUJiFRyrpwmADHEQPPSMT86h6E7V61CMoiG0KEeqBKg';
			$params = array();
			$params['access_token'] = $access_token;
			$params['media_id'] = 'AoBRcco1W-H8t7tP82N3MysxLeexKP1l_VykGfI0CxLC_4mV-ZlyVEh0tne80FK2';
			$url = 'http://file.api.weixin.qq.com/cgi-bin/media/'.'get';
			$client = new Zend_Http_Client();
			$client->setUri($url);
			$client->setParameterGet($params);
			$client->setConfig(array('maxredirects'=>3,'timeout'=> 300));
			$response = $client->request('GET');
			if($response->isError())
				throw new Exception($url.', $response is error！');
			$content = $response->getBody();
			
			if(isJson($content)){
				$rst = json_decode($content,true);
			}else{
				$rst = array();
				$rst['content'] =$content;
				//获取文件名字
				$contentDisposition =$response->getHeader("Content-disposition");
				$pattern = '/filename="(.+)"/';//'filename="?(.+)"?'
				if(preg_match($pattern, $contentDisposition, $matches))
				{
					$rst['fileName'] =$matches[1];
				}
			}
			//返回说明
			if(isset($rst['errcode']))
			{
				// 错误情况下的返回JSON数据包示例如下（示例为无效媒体ID错误）：:
				// {"errcode":40007,"errmsg":"invalid media_id"}
				throw new Exception($rst['errmsg'],$rst['errcode']);
			}
			else
			{
				// 正确情况下的返回HTTP头如下：
				// HTTP/1.1 200 OK
				// Connection: close
				// Content-Type: image/jpeg
				// Content-disposition: attachment; filename="MEDIA_ID.jpg"
				// Date: Sun, 06 Jan 2013 10:20:18 GMT
				// Cache-Control: no-cache, must-revalidate
				// Content-Length: 339721
				// curl -G "http://file.api.weixin.qq.com/cgi-bin/media/get?access_token=ACCESS_TOKEN&media_id=MEDIA_ID"
				//return $rst;
			}
			var_dump($rst);
			exit;
		} catch (Exception $e) {
			exit($e->getMessage());
		}
		
	}
	
	public function doTest2Action()
	{
		$this->getHelper('viewRenderer')->setNoRender(true);
		$access_token = 'yMosRcxjN1iiALAm8Su4JfKu0HrdfWt2YSxoqlB8CzJzft19I6JyBWzNuD7EDxQMZqcuAhtRqBdUO7d2dUOJgMactVPQYgBgi6lyUD4_Y0bKGl82r96zTTBe7U_HiNVw4NIaarRyXdIspDLd3VCazg';
		$url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=yMosRcxjN1iiALAm8Su4JfKu0HrdfWt2YSxoqlB8CzJzft19I6JyBWzNuD7EDxQMZqcuAhtRqBdUO7d2dUOJgMactVPQYgBgi6lyUD4_Y0bKGl82r96zTTBe7U_HiNVw4NIaarRyXdIspDLd3VCazg';
		
		$msg =array();
		$msg['touser'] = 'oFEX-joe9BYUKqluMFux104CxRNE';
		$msg['msgtype'] = 'text';
		$msg['text']["content"] = '郭永荣dotest2';
		$json = json_encode($msg,JSON_UNESCAPED_UNICODE);
		
		$client = new Zend_Http_Client();
		$client->setUri($url);
		$client->setRawData($json);
		$client->setEncType(Zend_Http_Client::ENC_URLENCODED);
		$client->setConfig(array('maxredirects'=>3));
		$response = $client->request('POST');
		$message = $response->getBody();
		$message = preg_replace("/^\xEF\xBB\xBF/", '', $message);
		$message = preg_replace("/[\n\t\s\r]+/", '', $message);
		$retData= json_decode($message,true);
		var_dump($retData);
		exit;
	}
	
	public function doTest3Action()
	{
		$_POST['FromUserName'] = "oFEX-joe9BYUKqluMFux104CxRNE";
		$_weixin  = new iWeixin2('5209d431479619ff45e0f450','gtgt');
		$_weixin->getWeixinMsgManager()->getWeixinCustomMsgSender()->sendText('郭永荣dotest3');
		
		die($_weixin->getToUser());
	}
	
	public function doTest4Action()
	{
		$_POST['FromUserName'] = "oFEX-joe9BYUKqluMFux104CxRNE";
		$_weixin  = new iWeixin2('5209d431479619ff45e0f450','gtgt');
		$msg =array();
		$msg['touser'] = 'oFEX-joe9BYUKqluMFux104CxRNE';
		$msg['msgtype'] = 'text';
		$msg['text']["content"] = '郭永荣dotest4';
		//$msg = json_encode($msg,JSON_UNESCAPED_UNICODE);
		
		$_weixin->getWeixinMsgManager()->getWeixinCustomMsgSender()->send($msg);
	
		die($_weixin->getToUser());
	}
}

 