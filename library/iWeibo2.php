<?php
/**
 * 获取UMA中的微博数据
 * @author Young
 * @version 1.0
 * @date 2013-05-03
 * 
 * 使用方法：
 * 
 */

class iWeibo
{
	/**
	 * soap服务的调用地址
	 * @var string
	 */
	private $_wsdl      = 'http://scrm.umaman.com/soa/weibo/soap?wsdl';
	
	/**
	 * 项目编号
	 * @var string
	 */
	private $_project_id = '';
	
	/**
	 * 关键词数组列表
	 * @var array
	 */
	private $_keywords = array();
    
    /**
     * 调用客户端
     * @var resource
     */
    private $_client;
    

    /**
     * 是否刷新soap请求的wsdl缓存
     * @var bool
     */
    private $_refresh = false;
    
    /**
     *
     * @var bool 是否开启调试模式 默认为关闭
     */
    private $_debug = false;
    
    /**
     * 记录错误信息
     * @var string
     */
    private $_error;
    
    /**
     * 初始化需要传递项目编号，具体参数值请查阅UMA后台
     *
     * @param string $project_id 
     * @param array $keywords
     * 
     */
    public function __construct($project_id,$keywords=array()) {
        $this->_project_id = $project_id;
        $this->_keywords   = $keywords;
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
                    'trace'=>true,
                    'connection_timeout'=>60, //避免网络延迟导致的链接丢失
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
            throw new iWeiboException($this->_error);
        }
    }
    
    /**
     * 进行调用授权身份认证处理
     * @return resource
     */
    private function connect() {
        $this->_client = $this->callSoap($this->_wsdl,$this->_refresh);
        return $this->_client;
    }
    
    /**
     * 获取微博数据
     * @param array $condition
     * @param array $orderby
     * @param int $limit
     * 
     * 实例:
     * $condition = array(
     *     'tag'=>array(),  //微博标签筛选，数组型可以多个标签复合
     *     'original'=>'',   //是否只显示原创微博 1为只显示原创内容 空为显示全部
     *     'hasPic'=>'',     //是否微博必须包含图片  1包含 空表示全部
     *     'search'=>'',    //微博中必须包含指定词语
     *     'nickname'=>'',  //指定微博昵称
     *     'startTime'=>'', //截取指定时间段内的微博 开始时间 格式为date("Y-m-d H:i:s")
     *     'endTime'=>'',   //截取指定时间段内的微博 开始时间 格式为date("Y-m-d H:i:s")
     *     'debug'=>false
     * );
     * $orderby = array('order'=>'_id','by'=>1); //排列顺序，根据实际情况只采用倒序排列'commentTimes（评论次数）|createTime（创建时间）'
     * $limit = array('start'=>0,'limit'=>10);
     * 
     * @return boolean
     */
    public function getWeibos(array $condition, $orderby=null, $limit=null) {
        if($orderby==null) $orderby=array('order'=>'createTime','by'=>-1);
        if($limit==null) $limit=array('start'=>0,'limit'=>10);
        
        $condition['projectId'] = $this->_project_id;
        $condition['kwId'] = $this->_keywords;
        
        try {
            return $this->_client->getWeiboList($condition, $orderby, $limit);
        }
        catch (Exception $e) {
            $this->exceptionMsg($e);
            throw new iWeiboException($this->_error);
        }
    }
    
    /**
     * 获取微博的tags标签
     * @param string $weiboId _id
     * @return array
     */
    public function getWeiboTags($weiboId) {
        try {
            return $this->_client->getWeiboTags($this->_project_id, $weiboId);
        }
        catch (Exception $e) {
            $this->exceptionMsg($e);
            throw new iWeiboException($this->_error);
        }
    }
    
    /**
     * 获取关键词列表
     */
    public function getKeywords() {
        try {
            return $this->_client->getKeywords($this->_project_id);
        }
        catch (Exception $e) {
            $this->exceptionMsg($e);
            throw new iWeiboException($this->_error);
        }
    }
    
    /**
     * 获取标签列表
     */
    public function getTags() {
        try {
            return $this->_client->getTags($this->_project_id);
        }
        catch (Exception $e) {
            $this->exceptionMsg($e);
            throw new iWeiboException($this->_error);
        }
    }
    
    /**
     * 获取发送的微博列表
     * @param int $start
     * @param int $limit
     * @return mixed
     */
    public function getPostWeibo($start=0, $limit=10) {
        try {
            return $this->_client->getPostWeibo($this->_project_id,$start,$limit);
        }
        catch (Exception $e) {
            $this->exceptionMsg($e);
            throw new iWeiboException($this->_error);
        }
    }
    
    /**
     * 获取参与的微博会员的列表
     * @param int $start
     * @param int $limit
     * @return mixed
     * 
     */
    public function getJoinMember($start=0, $limit=10) {
        try {
            return $this->_client->getJoinMember($this->_project_id,$start,$limit);
        }
        catch (Exception $e) {
            $this->exceptionMsg($e);
            throw new iWeiboException($this->_error);
        }
    }
    
    /**
     * 获取活动参与的会员数量
     * @return int
     */
    public function getNumberOfMembers() {
        try {
            return $this->_client->getNumberOfMembers($this->_project_id);
        }
        catch (Exception $e) {
            $this->exceptionMsg($e);
            throw new iWeiboException($this->_error);
        } 
    }
    
    /**
     * 获取活动发表的微博数量
     * @return int
     */
    public function getNumbersOfPost() {
        try {
            return $this->_client->getNumbersOfPost($this->_project_id);
        }
        catch (Exception $e) {
            $this->exceptionMsg($e);
            throw new iWeiboException($this->_error);
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
     * 析构函数
     */
    public function __destruct() {
        if($this->_debug) {
            var_dump($this->_error,
            $this->_client->__getLastRequestHeaders(),
            $this->_client->__getLastRequest(),
            $this->_client->__getLastResponseHeaders(),
            $this->_client->__getLastResponse());
        }
    } 
    
}
/**
 * iDatabase异常处理函数
 * @author young
 *
 */
class iWeiboException extends Exception {

}