<?php
class Default_TenpayController extends Zend_Controller_Action
{
	
	public function indexAction()
	{
		if(isset($_POST) && count($_POST))
		{
			$t = time();
			$oTen = new iPay('51d3df61479619647500021d',$t,'1q2w3e4r');
			$arrayParam = array();
			//必填参数
			$arrayParam['subject'] = $_POST['subject'];	//订单名称
			$arrayParam['body'] = $_POST['body'];	//订单名称
			$arrayParam['bank_type'] = $_POST['bank_type'];		//银行编号 0为财付通
			$arrayParam['out_trade_no'] = $_POST['out_trade_no'];	//订单编号
			$arrayParam['total_fee'] = $_POST['total_fee']*100;	//总金额(分)
			$arrayParam['spbill_create_ip'] = $_SERVER['REMOTE_ADDR'];	//购买者IP
			$arrayParam['attach'] = 'attach';
			$url = $oTen->tenPay($arrayParam);
			$this->redirect($url);
		}
	}
	
	public function callbackAction()
	{
		$project_id = '51d3df61479619647500021d';//iPay项目编号
		$password   = '1q2w3e4r';//密钥
		$out_trade_no = isset($_REQUEST['out_trade_no'])?$_REQUEST['out_trade_no']:'a';
		
		$oTen = new iPay($project_id,$out_trade_no,$password);
// 		if($oTen->verifyReturn()) {
			//执行相应的业务逻辑
			if($_REQUEST['pay'] == 'SUCCESS')
			{
				echo '订单：'.$out_trade_no.'付款成功<br>';
				var_dump($_REQUEST);
			}
			else
			{
				echo '付款失败<br>';
				var_dump($_REQUEST);
			}
		
		
// 		}
// 		else {
// 			//记录错误日志，方便调试
// 			echo 'fail';
// 			var_dump($_REQUEST);
// 		}
		exit;
	}
}

 