<?php
/**
 *
 * 客户端调用UMA iDatabase服务的php版本SDK
 *
 * @version 1.0
 * @author Young
 * @change
 *
 */

class iColor
{
    /**
     * soap服务的调用地址
     * @var string
     */
    private $_wsdl      = 'http://scrm.umaman.com/soa/color/soap?wsdl';
    
    /**
     * 是否每次加载WSDL 默认为false
     * @var string
     */
    private $_refresh   = false;
    
    /**
     * 身份认证的命名空间
     * @var string
     */
    private $_namespace = 'http://scrm.umaman.com/soa/color/soap';
    
    /**
     * 身份认证中的授权方法名
     * @var string
     */
    private $_authenticate = 'authenticate';
    
    /**
     * 项目编号
     * @var string
     */
    private $_project_id;
    
    /**
     * 项目签名密码
     * @var string
     */
    private $_password;
    
    /**
     * 随机字符串
     * @var string
     */
    private $_rand;
    
    /**
     * 调用客户端
     * @var resource
     */
    private $_client = null;
    
    /**
     * 是否开启debug功能
     * @var bool
     */
    private $_debug = false;
    
    /**
     * 记录错误信息
     * @var string
     */
    private $_error = null;
    
    
    /**
     * 买的
     * @param string $project_id
     * @param string $password
     */
    public function __construct($project_id,$password) {
        $this->_client = null;
        $this->_project_id = $project_id;
        $this->_password   = $password;
        $this->_rand       = time();
        $this->connect();
    }
    
    /**
     * 开启或者关闭debug模式
     * @param bool $debug
     */
    public function setDebug($debug=false) {
        $this->_debug = is_bool($debug) ? $debug : false;
    }
    
    /**
     * 开启或者关闭soap客户端的wsdl缓存
     * @param bool $refresh
     */
    public function setRefresh($refresh=false) {
        $this->_refresh = is_bool($refresh) ? $refresh : false;
    }
    
    /**
     * 建立soap链接
     * @param string $wsdl
     * @param bool $refresh
     * @return resource|boolean
     */
    private function callSoap($wsdl,$refresh=false) {
        try {
            $options = array(
                    'soap_version'=>SOAP_1_2,//必须是1.2版本的soap协议，支持soapheader
                    'exceptions'=>true,
                    'trace'=>$this->_debug,
                    'connection_timeout'=>30, //避免网络延迟导致的链接丢失
                    'keep_alive'=>false,
                    'compression'=>true
            );
            if($refresh==true)
                $options['cache_wsdl'] = WSDL_CACHE_NONE;
            else
                $options['cache_wsdl'] = WSDL_CACHE_DISK;
            	
            $this->_client = new SoapClient($wsdl,$options);
            return $this->_client;
        }
        catch (Exception $e) {
            $this->exceptionMsg($e);
            throw new iColorException($this->_error);
        }
    }
    
    /**
     * 将异常信息记录到$this->_error中
     * @param object $e
     * @return null
     */
    private function exceptionMsg($e) {
        $this->_error = $e->getMessage().$e->getFile().$e->getLine().$e->getTraceAsString();
    }
    
    /**
     * 进行调用授权身份认证处理
     * @return resource
     */
    private function connect() {
        $auth = array();
        $auth['project_id'] = $this->_project_id;
        $auth['rand']       = $this->_rand;
        $auth['sign']       = $this->sign();
        $authenticate       = new SoapHeader($this->_namespace,$this->_authenticate,new SoapVar($auth, SOAP_ENC_OBJECT), false);
    
        $this->_client = $this->callSoap($this->_wsdl,$this->_refresh);
        $this->_client->__setSoapHeaders(array($authenticate));
        return $this->_client;
    }
    
    /**
     * 签名算法
     * @return string
     */
    private function sign() {
        return md5($this->_project_id.$this->_rand.$this->_password);
    }
    
    /**
     * 获取$url图片对应的颜色聚类结果 
     * @param string $url 图片的url
     * @param int $number 聚类的数量
     * @return array
     */
    public function getColors($url,$number) {
        return json_decode($this->_client->getColors($url,$number),true);
    }
    
    /**
     * 获取指定$url图片的主色调
     * @param string $url
     * @param bool $filter 是否开启k-means过滤杂色的功能
     * @return array
     */
    public function dominantColor($url,$filter=false) {
        return json_decode($this->_client->dominantColor($url,$filter),true);
    }
    
    /**
     * 计算图片的指纹phash
     * @param string $url
     * @return string
     */
    public function pHash($url) {
        return $this->_client->pHash($url);
    }
    
    /**
     * 计算图片的指纹phash
     * @param string $url
     * @return string
     */
    public function aHash($url) {
        return $this->_client->aHash($url);
    }
    
    /**
     * 计算图片的指纹phash
     * @param string $url
     * @return string
     */
    public function dHash($url) {
        return $this->_client->dHash($url);
    }
    
    /**
     * 计算两个图片之间的汉明距离，小于10被认为是相似 大于10表示不相似 数值越大差异越大
     * @param string $hashStrA
     * @param string $hashStrB
     * @return int
     */
    public function getDistance($hashStrA, $hashStrB) {
        $aL = strlen($hashStrA); 
        $bL = strlen($hashStrB);
        if ($aL !== $bL)
            return false;

        /*计算两个 hash 值的汉明距离*/
        $distance = 0;
        for($i=0; $i<$aL; $i++){
            if($hashStrA{$i} !== $hashStrB{$i}){ $distance++; }
        }

        return $distance;
    }
    
    /**
     * 计算两个点之间的欧式距离,用于多维的颜色比对
     * @param array $p1 array('r值','g值','b值');
     * @param array $p2 array('r值','g值','b值');
     * @return float 欧式距离
     */
    public function euclidean($p1,$p2) {
        $distance = 0;
        $len = count($p1);
        for($i=0;$i<$len;$i++) {
            if(isset($p1[$i]) && isset($p2[$i]))
                $distance += pow(($p1[$i] - $p2[$i]),2);
        }
        return abs(sqrt($distance));
    }
    
    /**
     * 析构函数
     */
    public function __destruct() {
        if($this->_debug) {
            var_dump($this->_error,
            $this->_client->__getLastRequestHeaders(),
            $this->_client->__getLastRequest(),
            $this->_client->__getLastResponseHeaders(),
            $this->_client->__getLastResponse(),
            $this->_client,
            $this->_client->__getFunctions(),
            $this->_wsdl);
        }
    }
}

class iColorException extends Exception
{
    
}