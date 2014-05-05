<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=gbk">
	<title>对账单下载后台调用示例</title>
</head>
<body>

<?php


require ("classes/CheckRequestHandler.class.php");
require ("classes/client/TenpayHttpClient.class.php");

/* 商户号 */
$spid = "1900000109";

/* 密钥 */
$key = "8934e7d15453e97507ef794cf7b0519d";



/* 创建支付请求对象 */
$reqHandler = new CheckRequestHandler();
//通信对象
$httpClient = new TenpayHttpClient();


//-----------------------------
//设置请求参数
//-----------------------------
$reqHandler->init();
$reqHandler->setKey($key);

//-----------------------------
//设置支付参数
//-----------------------------
$reqHandler->setParameter("spid", $spid);
$reqHandler->setParameter("trans_time", "2011-10-12");
$reqHandler->setParameter("stamp", time());		
$reqHandler->setParameter("cft_signtype", "0");				
$reqHandler->setParameter("mchtype", "0");	


//-----------------------------
//设置通信参数
//-----------------------------

//设置请求内容
$httpClient->setReqContent($reqHandler->getRequestURL());

//后台调用
if($httpClient->call()) {
	
	echo "OK<br>";

	
} else {
	//后台调用通信失败
	echo "call err:" . $httpClient->getErrInfo() . "<br>";
	//有可能因为网络原因，请求已经处理，但未收到应答。
}

echo "内容:<br>\r\n" . $httpClient->getResContent() . "<br>";
//调试信息,建议把请求、应答内容、debug信息，通信返回码写入日志，方便定位问题

echo "<br>------------------------------------------------------<br>";
echo "http res:" . $httpClient->getResponseCode() . "," . $httpClient->getErrInfo() . "<br>";
echo "req:" . htmlentities($reqHandler->getRequestURL(), ENT_NOQUOTES, "GB2312") . "<br><br>";
echo "reqdebug:" . $reqHandler->getDebugInfo() . "<br><br>" ;



?>


</body>
</html>
