<?php

class iDatabase
{

    /**
     * soap服务的调用地址
     *
     * @var string
     */
    //private $_wsdl = 'http://cloud.umaman.com/service/database/index?wsdl';
    private $_wsdl = 'http://localhost/service/database/index?wsdl';

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
    //private $_namespace = 'http://cloud.umaman.com/service/database/index';
    private $_namespace = 'http://localhost/service/database/index';

    /**
     * 传统采用SOAP上传文件，如果文件过大，解析xml会出现问题
     * 故采用http协议本身的multi part的方式通过form表单进行提交
     * 设定接受提交文件的地址
     *
     * @var string
     */
    private $_uploadUrl = 'http://cloud.umaman.com/service/database/upload';

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
     * 启用缓存对象
     *
     * @var string
     */
    private $_cache = null;

    /**
     * 开启直接连接mongodb的模式
     *
     * @var string
     */
    private $_local = true;

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
     * 开启直连MongoDB模式
     *
     * @param string $local            
     */
    public function setLocal($local = false)
    {
        $this->_local = is_bool($local) ? $local : false;
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
            $this->_client = new SoapClient($wsdl, $options);
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
        if (! $this->_local) {
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
        } else {
            $this->_client = new iWebsite_Local_Database();
            $this->_client->authenticate($this->_project_id, $this->_rand, $this->sign());
            $this->_client->setCollection($this->_collection_alias);
            return $this->_client;
        }
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
            return $this->result($this->_client->count($this->serialize($query)));
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
            return $this->result($this->_client->distinct($key, $this->serialize($query)));
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
            return $this->result($this->_client->find($this->serialize($query), $this->serialize($sort), $skip, $limit, $this->serialize($fields)));
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
            return $this->result($this->_client->findOne($this->serialize($query)));
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
            return $this->result($this->_client->findAll($this->serialize($query), $this->serialize($sort), $this->serialize($fields)));
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
            return $this->result($this->_client->findAndModify($this->serialize($options)));
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
            return $this->result($this->_client->remove($this->serialize($query)));
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
            $datas = $this->result($this->_client->insert($this->serialize($datas)));
            return $datas;
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
    public function insertRef(array &$datas)
    {
        try {
            $datas = $this->result($this->_client->insert($this->serialize($datas)));
            return $datas;
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
            return $this->result($this->_client->batchInsert($this->serialize($datas)));
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
     * @param array $options            
     * @return array boolean
     */
    public function update(array $criteria, array $object, array $options = array())
    {
        try {
            return $this->result($this->_client->update($this->serialize($criteria), $this->serialize($object), $this->serialize($options)));
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
            $result = $this->result($this->_client->save($this->serialize($datas)));
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
            return $this->result($this->_client->aggregate($this->serialize($ops1), $this->serialize($ops2), $this->serialize($ops3)));
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
            return $this->result($this->_client->ensureIndex($this->serialize($keys), $this->serialize($options)));
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
            return $this->result($this->_client->deleteIndex($this->serialize($keys)));
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
     * 通过$_FILES方式上传文件到集群
     * 当前方法可以上传形式如$_FILES[$fileFieldName]的文件
     */
    public function uploadFile($fileFieldName)
    {
        if ($_FILES[$fileFieldName]['error'] === UPLOAD_ERR_OK) {
            $client = new Zend_Http_Client();
            $client->setUri($this->_uploadUrl);
            $client->setEncType(Zend_Http_Client::ENC_FORMDATA);
            $client->setParameterGet(array(
                'project_id' => $this->_project_id
            ));
            $client->setFileUpload($_FILES[$fileFieldName]['name'], $fileFieldName, file_get_contents($_FILES[$fileFieldName]['tmp_name']));
            $response = $client->request('POST');
            if ($response->isSuccessful()) {
                return json_decode($response->getBody(), true);
            } else {
                throw new Exception("请求未成功");
            }
        } else {
            throw new Exception("文件上传未成功，请检查文件是否超过服务器额定限制或者网络是否正常");
        }
    }

    /**
     * 上传指定文件名和内容的文件
     *
     * @param string $fileName            
     * @param bytes $fileBytes            
     * @throws Exception
     * @return mixed
     */
    public function uploadBytes($fileName, $fileBytes)
    {
        $client = new Zend_Http_Client();
        $client->setUri($this->_uploadUrl);
        $client->setEncType(Zend_Http_Client::ENC_FORMDATA);
        $client->setParameterGet(array(
            'project_id' => $this->_project_id
        ));
        $client->setFileUpload($fileName, 'file', $fileBytes);
        $response = $client->request('POST');
        if ($response->isSuccessful()) {
            return json_decode($response->getBody(), true);
        } else {
            throw new Exception("请求未成功");
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
            return $this->result($this->_client->pipe($this->serialize($ops), $last));
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
     * 序列化 
     * @param array $arr            
     * @return mixed
     */
    public function serialize($arr)
    {
        if (! $this->_local) {
            return serialize($arr);
        } else {
            return $arr;
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
        
        return array_key_exists('result', $unserialize) ? $unserialize['result'] : array(
            'err' => $unserialize
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
     * 析构函数
     */
    public function __destruct()
    {
        if ($this->_debug) {
            var_dump($this->_error, $this->_client->__getLastRequestHeaders(), $this->_client->__getLastRequest(), $this->_client->__getLastResponseHeaders(), $this->_client->__getLastResponse());
        }
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