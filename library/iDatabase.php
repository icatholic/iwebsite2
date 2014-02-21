<?php

class iDatabase
{

    /**
     * soap服务的调用地址
     *
     * @var string
     */
    private $_wsdl = 'http://cloud.umaman.com/service/database/index?wsdl';

    /**
     * 是否每次加载WSDL 默认为false
     *
     * @var string
     */
    private $_refresh = false;

    /**
     * 身份认证的命名空间
     *
     * @var string
     */
    private $_namespace = 'http://cloud.umaman.com/service/database/index';

    /**
     * 身份认证中的授权方法名称
     *
     * @var string
     */
    private $_authenticate = 'authenticate';

    /**
     * 设定集合方法名称
     *
     * @var string
     */
    private $_set_collection = 'setCollection';

    /**
     * 项目编号
     *
     * @var string
     */
    private $_project_id;

    /**
     * 集合别名
     *
     * @var string
     */
    private $_collection_alias;

    /**
     * 密钥
     *
     * @var string
     */
    private $_password;

    /**
     * 随机字符串
     *
     * @var string
     */
    private $_rand;

    /**
     * 密钥编号，为空时候，使用默认密钥
     *
     * @var string
     */
    private $_key_id = '';

    /**
     * 调用客户端
     *
     * @var resource
     */
    private $_client;

    /**
     * 是否开启debug功能
     *
     * @var bool
     */
    private $_debug = false;

    /**
     * socket连接的最大超时时间
     *
     * @var int
     */
    private $_maxConnectionTime = 300;

    /**
     * 记录错误信息
     *
     * @var string
     */
    private $_error;

    /**
     *
     * @param string $project_id            
     * @param string $collectionAlias            
     * @param string $password            
     * @param string $key_id            
     */
    public function __construct($project_id, $password, $key_id = '')
    {
        $this->_project_id = $project_id;
        $this->_password = $password;
        $this->_rand = sha1(time());
        $this->_key_id = $key_id;
    }

    /**
     * 开启或者关闭debug模式
     *
     * @param bool $debug            
     */
    public function setDebug($debug = false)
    {
        $this->_debug = is_bool($debug) ? $debug : false;
    }

    /**
     * 建立soap链接
     *
     * @param string $wsdl            
     * @param bool $refresh            
     * @return resource boolean
     */
    private function callSoap($wsdl)
    {
        try {
            $options = array(
                'soap_version' => SOAP_1_2, // 必须是1.2版本的soap协议，支持soapheader
                'exceptions' => true,
                'trace' => true,
                'connection_timeout' => $this->_maxConnectionTime, // 避免网络延迟导致的链接丢失
                'keep_alive' => true,
                'compression' => true
            );
            
            ini_set('default_socket_timeout', $this->_maxConnectionTime);
            $this->_client = new MySoapClient($wsdl, $options);
            return $this->_client;
        } catch (SoapFault $e) {
            $this->soapFaultMsg($e);
            return false;
        }
    }

    /**
     * 进行调用授权身份认证处理
     *
     * @return resource
     */
    private function connect()
    {
        // 身份认证
        $auth = array();
        $auth['project_id'] = $this->_project_id;
        $auth['rand'] = $this->_rand;
        $auth['sign'] = $this->sign();
        $auth['key_id'] = $this->_key_id;
        $authenticate = new SoapHeader($this->_namespace, $this->_authenticate, new SoapVar($auth, SOAP_ENC_OBJECT), false);
        
        // 设定集合
        $alias = array();
        $alias['collectionAlias'] = $this->_collection_alias;
        $setCollection = new SoapHeader($this->_namespace, $this->_set_collection, new SoapVar($alias, SOAP_ENC_OBJECT), false);
        
        $this->_client = $this->callSoap($this->_wsdl);
        $this->_client->__setSoapHeaders(array(
            $authenticate,
            $setCollection
        ));
        return $this->_client;
    }

    /**
     * 设定被操作集合别名
     *
     * @param string $alias            
     */
    public function setCollection($alias)
    {
        $this->_collection_alias = $alias;
        $this->connect();
    }

    /**
     * 签名算法
     *
     * @return string
     */
    private function sign()
    {
        return md5($this->_project_id . $this->_rand . $this->_password);
    }

    /**
     * 执行count操作
     *
     * @param array $query            
     * @return array boolean
     */
    public function count($query)
    {
        try {
            return $this->result($this->_client->count(serialize($query)));
        } catch (SoapFault $e) {
            $this->soapFaultMsg($e);
            return false;
        }
    }

    /**
     * 查询某个表中的数据,并根据指定的key字段进行distinct唯一处理
     *
     * @param string $key            
     * @param array $query            
     * @return array boolean
     */
    public function distinct($key, array $query)
    {
        try {
            return $this->result($this->_client->distinct($key, serialize($query)));
        } catch (SoapFault $e) {
            $this->soapFaultMsg($e);
            return false;
        }
    }

    /**
     * 查询某个表中的数据
     *
     * @param array $query            
     * @param array $sort            
     * @param int $skip            
     * @param int $limit            
     * @param array $fields            
     * @return array boolean
     */
    public function find(array $query, array $sort = null, $skip = 0, $limit = 10, array $fields = array())
    {
        try {
            return $this->result($this->_client->find(serialize($query), serialize($sort), $skip, $limit, serialize($fields)));
        } catch (SoapFault $e) {
            $this->soapFaultMsg($e);
            return false;
        }
    }

    /**
     * 查询单条信息
     *
     * @param array $query            
     * @return array boolean
     */
    public function findOne(array $query)
    {
        try {
            return $this->result($this->_client->findOne(serialize($query)));
        } catch (SoapFault $e) {
            $this->soapFaultMsg($e);
            return false;
        }
    }

    /**
     * 查询全部信息
     *
     * @param array $query            
     * @param array $sort            
     * @param array $fields            
     * @return array
     */
    public function findAll(array $query, array $sort = array('_id'=>-1), array $fields = array())
    {
        try {
            return $this->result($this->_client->findAll(serialize($query), serialize($sort), serialize($fields)));
        } catch (SoapFault $e) {
            $this->soapFaultMsg($e);
            return false;
        }
    }

    /**
     * 执行findAndModify操作
     *
     * @param array $options            
     * @return array boolean
     */
    public function findAndModify(array $options)
    {
        try {
            return $this->result($this->_client->findAndModify(serialize($options)));
        } catch (SoapFault $e) {
            $this->soapFaultMsg($e);
            return false;
        }
    }

    /**
     * 执行remove操作
     *
     * @param array $query            
     * @return array boolean
     */
    public function remove(array $query)
    {
        try {
            return $this->result($this->_client->remove(serialize($query)));
        } catch (SoapFault $e) {
            $this->soapFaultMsg($e);
            return false;
        }
    }

    /**
     * 执行insert操作
     *
     * @param array $datas            
     * @return array boolean
     */
    public function insert(array $datas)
    {
        try {
            return $this->result($this->_client->insert(serialize($datas)));
        } catch (SoapFault $e) {
            $this->soapFaultMsg($e);
            return false;
        }
    }

    /**
     * 执行batchInsert操作
     *
     * @param array $datas            
     * @return array boolean
     */
    public function batchInsert(array $datas)
    {
        try {
            return $this->result($this->_client->batchInsert(serialize($datas)));
        } catch (SoapFault $e) {
            $this->soapFaultMsg($e);
            return false;
        }
    }

    /**
     * 执行更新操作
     *
     * @param array $criteria            
     * @param array $object            
     * @return array boolean
     */
    public function update(array $criteria, array $object)
    {
        try {
            return $this->result($this->_client->update(serialize($criteria), serialize($object)));
        } catch (SoapFault $e) {
            $this->soapFaultMsg($e);
            return false;
        }
    }

    /**
     * 保存操作，当包含_id时更新对应的_id所对应的文档，否则创建新的文档。
     *
     * @param array $datas            
     * @return array
     */
    public function save(&$datas)
    {
        try {
            $result = $this->result($this->_client->save(serialize($datas)));
            $datas = $result['datas'];
            return $result['rst'];
        } catch (SoapFault $e) {
            $this->soapFaultMsg($e);
            return false;
        }
    }

    /**
     * aggregate框架操作
     *
     * @param array $ops1            
     * @param array $ops2            
     * @param array $ops3            
     * @return array boolean
     */
    public function aggregate(array $ops1, array $ops2 = null, array $ops3 = null)
    {
        try {
            if (empty($ops2)) {
                $ops2 = array();
            }
            if (empty($ops3)) {
                $ops3 = array();
            }
            return $this->result($this->_client->aggregate(serialize($ops1), serialize($ops2), serialize($ops3)));
        } catch (SoapFault $e) {
            $this->soapFaultMsg($e);
            return false;
        }
    }

    /**
     * 创建索引
     *
     * @param mixed $keys            
     * @param array $options            
     * @return boolean
     */
    public function ensureIndex($keys, $options)
    {
        try {
            return $this->result($this->_client->ensureIndex(serialize($keys), serialize($options)));
        } catch (SoapFault $e) {
            $this->soapFaultMsg($e);
            return false;
        }
    }

    /**
     * 删除指定索引
     *
     * @param array $keys            
     * @return boolean
     */
    public function deleteIndex($keys)
    {
        try {
            return $this->result($this->_client->deleteIndex(serialize($keys)));
        } catch (SoapFault $e) {
            $this->soapFaultMsg($e);
            return false;
        }
    }

    /**
     * 删除全部索引
     *
     * @return boolean
     */
    public function deleteIndexes()
    {
        try {
            return $this->result($this->_client->deleteIndexes());
        } catch (SoapFault $e) {
            $this->soapFaultMsg($e);
            return false;
        }
    }

    /**
     * 上传文件到集群
     *
     * @param string $fileBytes
     *            base64编码后的文件内容
     * @param string $fileName
     *            文件名称
     */
    public function uploadFile($fileBytes, $fileName)
    {
        try {
            return $this->result($this->_client->uploadFile($fileBytes, $fileName));
        } catch (SoapFault $e) {
            $this->soapFaultMsg($e);
            return false;
        }
    }

    /**
     * 管道操作，一次请求完成多个操作
     *
     * @param array $ops            
     * @param string $last            
     * @return Ambigous <mixed, multitype:string , array>|boolean
     */
    public function pipe($ops, $last = true)
    {
        $last = is_bool($last) ? $last : true;
        try {
            return $this->result($this->_client->pipe(serialize($ops), $last));
        } catch (SoapFault $e) {
            $this->soapFaultMsg($e);
            return false;
        }
    }

    /**
     * 获取集合的数据结构设定
     * 
     * @return Ambigous <mixed, multitype:string , array>|boolean
     */
    public function getSchema()
    {
        try {
            return $this->result($this->_client->getSchema());
        } catch (SoapFault $e) {
            $this->soapFaultMsg($e);
            return false;
        }
    }

    /**
     * 输出结果，如此输出的原因，统一Soap服务端输出格式为数组
     *
     * @param string $rst            
     * @return mixed
     */
    private function result($rst)
    {
        $unserialize = @unserialize($rst);
        if ($unserialize === false) {
            var_dump($rst);
            throw new Exception("返回结果无法进行反序列化");
        }
        
        return isset($unserialize['result']) ? $unserialize['result'] : array(
            'err' => $rst
        );
    }

    /**
     * 将异常信息记录到$this->_error中
     *
     * @param object $e            
     * @return string
     */
    private function soapFaultMsg($e)
    {
        $this->_error = $e->getMessage() . $e->getFile() . $e->getLine() . $e->getTraceAsString();
    }

    /**
     * 保证异步操作全部完成
     */
    public function runAllAsync()
    {
        if (SoapClientSocketsRegistry::isRegistered('idbAsync')) {
            $asyncs = SoapClientSocketsRegistry::get('idbAsync');
            if (is_array($asyncs)) {
                foreach ($asyncs as $async) {
                    if ($async instanceof SoapClientAsync)
                        $async->wait();
                }
            }
            SoapClientSocketsRegistry::_unsetInstance();
        }
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        // if ($this->_debug && !empty($this->_error)) {
        if ($this->_debug) {
            var_dump($this->_error, $this->_client->__getLastRequestHeaders(), $this->_client->__getLastRequest(), $this->_client->__getLastResponseHeaders(), $this->_client->__getLastResponse());
        }
    }
}

/**
 * 扩展SOAP客户端增加异步处理模式
 */
class MySoapClient extends SoapClient
{

    public $asyncFunctionName = null;

    protected $_asynchronous = false;

    protected $_asyncResult = null;

    protected $_asyncAction = null;

    public function __construct($wsdl, $options)
    {
        parent::SoapClient($wsdl, $options);
    }

    public function __call($functionName, $arguments)
    {
        if ($this->_asyncResult == null) {
            $this->_asynchronous = false;
            $this->_asyncAction = null;
            
            if (preg_match('/Async$/', $functionName) == 1) {
                $this->_asynchronous = true;
                $functionName = str_replace('Async', '', $functionName);
                $this->asyncFunctionName = $functionName;
            }
        }
        
        try {
            $result = @parent::__call($functionName, $arguments);
        } catch (SoapFault $e) {
            throw new Exception(exceptionMsg($e));
        }
        
        if ($this->_asynchronous == true) {
            return $this->_asyncAction;
        }
        return $result;
    }

    public function __doRequest($request, $location, $action, $version, $one_way = false)
    {
        if ($this->_asyncResult != null) {
            $result = $this->_asyncResult;
            unset($this->_asyncResult);
            return $result;
        }
        
        if ($this->_asynchronous == false) {
            $result = parent::__doRequest($request, $location, $action, $version, $one_way);
            return $result;
        } else {
            $this->_asyncAction = new SoapClientAsync($this, $this->asyncFunctionName, $request, $location, $action);
            
            if (SoapClientSocketsRegistry::isRegistered('idbAsync'))
                $idbAsync = SoapClientSocketsRegistry::get('idbAsync');
            else
                $idbAsync = array();
            array_push($idbAsync, $this->_asyncAction);
            SoapClientSocketsRegistry::set('idbAsync', $idbAsync);
            
            return '';
        }
    }

    public function handleAsyncResult($functionName, $result)
    {
        $this->_asynchronous = false;
        $this->_asyncResult = $result;
        return $this->__call($functionName, array());
    }
}

class SoapClientAsync
{

    /**
     * 获取当前soapclient对象
     */
    protected $_soapClient;

    /**
     * 被叫方法名
     *
     * @var string
     */
    protected $_functionName;

    /**
     * 连接SOAP客户端的socket资源
     *
     * @var resource
     */
    protected $_socket;

    protected $_soapResult = '';

    public function __construct($soapClient, $functionName, $request, $location, $action)
    {
        preg_match('%^(http(?:s)?)://(.*?)(/.*?)$%', $location, $matches);
        
        $this->_soapClient = $soapClient;
        $this->_functionName = $functionName;
        
        $protocol = $matches[1];
        $host = $matches[2];
        $endpoint = $matches[3];
        
        $headers = array(
            'POST ' . $endpoint . ' HTTP/1.1',
            'Host: ' . $host,
            'User-Agent: PHP-SOAP/' . phpversion(),
            'Content-Type: text/xml; charset=utf-8',
            'SOAPAction: "' . $action . '"',
            'Content-Length: ' . strlen($request),
            'Connection: close'
        );
        
        if ($protocol == 'https') {
            $host = 'ssl://' . $host;
            $port = 443;
        } else {
            $port = 80;
        }
        
        $data = implode("\r\n", $headers) . "\r\n\r\n" . $request . "\r\n";
        $this->_socket = fsockopen($host, $port, $errorNumber, $errorMessage);
        
        if ($this->_socket === false) {
            $this->_socket = null;
            throw new Exception('Unable to make an asynchronous API call: ' . $errorNumber . ': ' . $errorMessage);
        }
        
        if (fwrite($this->_socket, $data) === false) {
            throw new Exception('Unable to write data to an asynchronous API call.');
        }
    }

    public function wait()
    {
        while (! feof($this->_socket)) {
            $this->_soapResult .= fread($this->_socket, 8192);
        }
        
        list ($headers, $data) = explode("\r\n\r\n", $this->_soapResult);
        return $this->rst($this->_soapClient->handleAsyncResult($this->_functionName, $data));
    }

    /**
     * 格式化返回结果
     *
     * @param string $rst            
     * @return array
     */
    private function rst($rst)
    {
        return isset($rst['result']) ? $rst['result'] : array(
            'unset result async'
        );
    }

    public function __destruct()
    {
        if ($this->_socket != null) {
            fclose($this->_socket);
        }
    }
}

/**
 * 使用静态方法保证变量的全局存储
 *
 * @author Young
 *        
 */
class SoapClientSocketsRegistry extends ArrayObject
{

    private static $_registryClassName = 'SoapClientSocketsRegistry';

    public static $_registry = null;

    public static function getInstance()
    {
        if (self::$_registry === null) {
            self::init();
        }
        
        return self::$_registry;
    }

    public static function setInstance(SoapClientSocketsRegistry $registry)
    {
        if (self::$_registry !== null) {
            throw new Exception('Registry is already initialized');
        }
        
        self::setClassName(get_class($registry));
        self::$_registry = $registry;
    }

    protected static function init()
    {
        self::setInstance(new self::$_registryClassName());
    }

    public static function isRegistered($index)
    {
        if (self::$_registry === null) {
            return false;
        }
        return self::$_registry->offsetExists($index);
    }

    public static function setClassName($registryClassName = 'SoapClientSocketsRegistry')
    {
        if (self::$_registry !== null) {
            throw new Exception('Registry is already initialized');
        }
        
        if (! is_string($registryClassName)) {
            throw new Exception("Argument is not a class name");
        }
        
        self::$_registryClassName = $registryClassName;
    }

    public static function _unsetInstance()
    {
        self::$_registry = null;
    }

    public static function get($index)
    {
        $instance = self::getInstance();
        
        if (! $instance->offsetExists($index)) {
            throw new Exception("No entry is registered for key '$index'");
        }
        
        return $instance->offsetGet($index);
    }

    public static function set($index, $value)
    {
        $instance = self::getInstance();
        $instance->offsetSet($index, $value);
    }

    public function __construct($array = array(), $flags = parent::ARRAY_AS_PROPS)
    {
        parent::__construct($array, $flags);
    }

    public function offsetExists($index)
    {
        return array_key_exists($index, $this);
    }
}

/**
 * iDatabase异常处理函数
 *
 * @author young
 *        
 */
class iDatabseException extends Exception
{
}