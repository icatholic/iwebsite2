<?php
/**
 * iDatabase服务
 * @author yangming
 * 
 */
defined('IDATABASE_INDEXES') || define('IDATABASE_INDEXES', 'idatabase_indexes');
defined('IDATABASE_COLLECTIONS') || define('IDATABASE_COLLECTIONS', 'idatabase_collections');
defined('IDATABASE_STRUCTURES') || define('IDATABASE_STRUCTURES', 'idatabase_structures');
defined('IDATABASE_PROJECTS') || define('IDATABASE_PROJECTS', 'idatabase_projects');
defined('IDATABASE_PLUGINS') || define('IDATABASE_PLUGINS', 'idatabase_plugins');
defined('IDATABASE_PLUGINS_COLLECTIONS') || define('IDATABASE_PLUGINS_COLLECTIONS', 'idatabase_plugins_collections');
defined('IDATABASE_PLUGINS_STRUCTURES') || define('IDATABASE_PLUGINS_STRUCTURES', 'idatabase_plugins_structures');
defined('IDATABASE_PROJECT_PLUGINS') || define('IDATABASE_PROJECT_PLUGINS', 'idatabase_project_plugins');
defined('IDATABASE_VIEWS') || define('IDATABASE_VIEWS', 'idatabase_views');
defined('IDATABASE_STATISTIC') || define('IDATABASE_STATISTIC', 'idatabase_statistic');
defined('IDATABASE_PROMISSION') || define('IDATABASE_PROMISSION', 'idatabase_promission');
defined('IDATABASE_KEYS') || define('IDATABASE_KEYS', 'idatabase_keys');
defined('IDATABASE_COLLECTION_ORDERBY') || define('IDATABASE_COLLECTION_ORDERBY', 'idatabase_collection_orderby');
defined('IDATABASE_MAPPING') || define('IDATABASE_MAPPING', 'idatabase_mapping');
defined('IDATABASE_LOCK') || define('IDATABASE_LOCK', 'idatabase_lock');
defined('IDATABASE_QUICK') || define('IDATABASE_QUICK', 'idatabase_quick');
defined('IDATABASE_DASHBOARD') || define('IDATABASE_DASHBOARD', 'idatabase_dashboard');
defined('IDATABASE_FILES') || define('IDATABASE_FILES', 'idatabase_files');

class iWebsite_Local_Database
{

    /**
     * 操作集合的iWebsite_Local_MongoCollection实例
     *
     * @var iWebsite_Local_MongoCollection
     */
    private $_model = null;

    /**
     * 连接MongoDB的配置文件类
     *
     * @var Config
     */
    private $_config = null;

    /**
     * 获取访问密钥的MongoCollection实例
     *
     * @var MongoCollection
     */
    private $_key = null;

    /**
     * 获取物理集合的映射关系
     *
     * @var MongoCollection
     */
    private $_mapping = null;

    /**
     * 获取idb集合信息
     *
     * @var MongoCollection
     */
    private $_collection = null;

    /**
     * 获取集合的文档结构定义信息
     *
     * @var MongoCollection
     */
    private $_structure = null;

    /**
     * 被操作集合所属的项目编号
     *
     * @var string
     */
    private $_project_id = null;

    /**
     * 被操作集合的集合编号
     *
     * @var array
     */
    private $_collection_id = null;

    /**
     * 文档结构数组，字段名做键 结构详情数组做值
     *
     * @var array
     */
    private $_schema = array();

    /**
     * 字段与类型对应关系数组
     *
     * @var array
     */
    private $_fieldAndType = array();

    /**
     * 上传文件字段列表
     *
     * @var array
     */
    private $_fileField = array();

    /**
     * 初始化
     *
     * @param Config $config            
     */
    public function __construct()
    {
        $this->_config = $config;
        $this->_key = new iWebsite_Local_MongoCollection(IDATABASE_KEYS);
    }

    /**
     * 身份认证，请在SOAP HEADER部分请求该函数进行身份校验
     * 签名算法:md5($project_id.$rand.$sign) 请转化为长度为32位的16进制字符串
     *
     * @param string $project_id            
     * @param string $rand            
     * @param string $sign            
     * @param string $key_id            
     * @throws \SoapFault
     * @return boolean
     */
    public function authenticate($project_id, $rand, $sign, $key_id = null)
    {
        if (strlen($rand) < 8) {
            throw new \SoapFault(411, '随机字符串长度过短，为了安全起见至少8位');
        }
        $this->_project_id = $project_id;
        $key_id = ! empty($key_id) ? $key_id : null;
        $keyInfo = $this->getKeysInfo($project_id, $key_id);
        if (md5($project_id . $rand . $keyInfo['key']) !== strtolower($sign)) {
            throw new \SoapFault(401, '身份认证校验失败');
        }
        return true;
    }

    /**
     * 获取密钥信息
     *
     * @param string $project_id            
     * @param string $key_id            
     * @throws \SoapFault
     * @return array
     */
    private function getKeysInfo($project_id, $key_id)
    {
        $query = array();
        $query['project_id'] = $project_id;
        if ($key_id !== null) {
            $query['_id'] = myMongoId($key_id);
        } else {
            $query['default'] = true;
        }
        $query['expire'] = array(
            '$gte' => new \MongoDate()
        );
        $query['active'] = true;
        $rst = $this->_key->findOne($query, array(
            'key' => true
        ));
        if ($rst === null) {
            throw new \SoapFault(404, '授权密钥无效');
        }
        return $rst;
    }

    /**
     * 设定集合名称，请在SOAP HEADER部分进行设定
     *
     * @param string $collectionAlias            
     * @throws \SoapFault
     * @return bool
     */
    public function setCollection($collectionAlias)
    {
        $this->_collection = new iWebsite_Local_MongoCollection(IDATABASE_COLLECTIONS);
        $this->_mapping = new iWebsite_Local_MongoCollection(IDATABASE_MAPPING);
        
        $collectionInfo = $this->_collection->findOne(array(
            'project_id' => $this->_project_id,
            'alias' => $collectionAlias
        ));
        if ($collectionInfo === null) {
            throw new \SoapFault(404, '访问集合不存在');
        }
        
        $this->_collection_id = myMongoId($collectionInfo['_id']);
        $mapping = $this->_mapping->findOne(array(
            'project_id' => $this->_project_id,
            'collection_id' => $this->_collection_id,
            'active' => true
        ));
        if ($mapping === null) {
            $this->_model = new iWebsite_Local_MongoCollection(iCollectionName($this->_collection_id));
        } else {
            $this->_model = new iWebsite_Local_MongoCollection($mapping['collection'], $mapping['database'], $mapping['cluster']);
        }
        
        $this->getSchema();
        return true;
    }

    /**
     * 获取当前集合的文档结构
     *
     * @throws \SoapFault
     * @return string
     */
    public function getSchema()
    {
        if ($this->_collection_id == null)
            throw new \SoapFault(500, '$_collection_id不存在');
        
        if ($this->_project_id == null)
            throw new \SoapFault(500, '$_project_id不存在');
        
        $this->_structure = new iWebsite_Local_MongoCollection(IDATABASE_STRUCTURES);
        $cursor = $this->_structure->find(array(
            'collection_id' => $this->_collection_id
        ));
        
        if ($cursor->count() == 0)
            throw new \SoapFault(500, '集合未定义文档结构');
        
        while ($cursor->hasNext()) {
            $row = $cursor->getNext();
            if (strpos($row['field'], '.') !== false) {
                $exp = explode('.', $row['field']);
                $subField = end($exp);
                $this->_schema[$subField] = $row;
                if ($row['type'] == 'filefield') {
                    $this->_fileField[$subField] = $row;
                }
            }
            $this->_schema[$row['field']] = $row;
            $this->_fieldAndType[$row['field']] = $row['type'];
            if ($row['type'] == 'filefield') {
                $this->_fileField[$row['field']] = $row;
            }
        }
        
        $cursor->rewind();
        return $this->result(iterator_to_array($cursor, false));
    }

    /**
     * 统计数量
     *
     * @param string $query            
     * @return string
     */
    public function count($query)
    {
        $query = $this->toArray($query);
        return $this->result($this->_model->count($query));
    }

    /**
     * 查询特定范围
     *
     * @param string $query            
     * @param string $sort            
     * @param int $skip            
     * @param int $limit            
     * @param string $fields            
     * @return string
     */
    public function find($query, $sort, $skip, $limit, $fields)
    {
        $query = $this->toArray($query);
        $sort = $this->toArray($sort);
        $skip = intval($skip);
        $skip = $skip > 0 ? $skip : 0;
        $limit = intval($limit);
        $limit = $limit < 0 ? 10 : $limit;
        $limit = $limit > 1000 ? 1000 : $limit;
        if (! empty($fields)) {
            $fields = $this->toArray($fields);
        } else {
            $fields = array();
        }
        
        $cursor = $this->_model->find($query, $fields);
        $total = $cursor->count();
        if (! empty($sort))
            $cursor->sort($sort);
        if ($skip > 0)
            $cursor->skip($skip);
        $cursor->limit($limit);
        
        $rst = array(
            'datas' => iterator_to_array($cursor, false),
            'total' => $total
        );
        return $this->result($rst);
    }

    /**
     * 查询某一个项
     *
     * @param string $query            
     * @param string $fields            
     * @return string
     */
    public function findOne($query, $fields)
    {
        $query = $this->toArray($query);
        if (! empty($fields)) {
            $fields = $this->toArray($fields);
        } else {
            $fields = array();
        }
        $rst = $this->_model->findOne($query, $fields);
        return $this->result($rst);
    }

    /**
     * 查询全部数据
     *
     * @param string $query            
     * @param string $sort            
     * @param string $fields            
     * @return string
     */
    public function findAll($query, $sort, $fields)
    {
        $query = $this->toArray($query);
        $sort = $this->toArray($sort);
        if (empty($sort)) {
            $sort = array(
                '_id' => - 1
            );
        }
        $fields = $this->toArray($fields);
        $rst = $this->_model->findAll($query, $sort, 0, 0, $fields);
        
        return $this->result($rst);
    }

    /**
     * 某一列唯一的数据
     *
     * @param string $key            
     * @param string $query            
     * @return string
     */
    public function distinct($key, $query)
    {
        $key = is_string($key) ? trim($key) : '';
        $query = $this->toArray($query);
        $rst = $this->_model->distinct($key, $query);
        return $this->result($rst);
    }

    /**
     * 保存数据，$datas中如果包含_id属性，那么将更新_id的数据，否则创建新的数据
     *
     * @param string $datas            
     * @throws \SoapFault
     * @return string
     */
    public function save($datas)
    {
        try {
            $datas = $this->toArray($datas);
            $rst = $this->_model->saveRef($datas);
            return $this->result(array(
                'datas' => $datas,
                'rst' => $rst
            ));
        } catch (\SoapFault $e) {
            return $this->result(exceptionMsg($e));
        }
    }

    /**
     * 插入数据
     *
     * @param string $datas            
     * @throws \SoapFault
     * @return string
     */
    public function insert($datas)
    {
        $datas = $this->toArray($datas);
        $rst = $this->_model->insertByFindAndModify($datas);
        return $this->result($rst);
    }

    /**
     * 批量插入
     *
     * @param string $a            
     * @throws \SoapFault
     * @return string
     */
    public function batchInsert($a)
    {
        $a = $this->toArray($a);
        $rst = $this->_model->batchInsert($a);
        return $this->result($rst);
    }

    /**
     * 更新操作
     *
     * @param string $criteria            
     * @param string $object            
     * @param string $options            
     * @throws \SoapFault
     * @return string
     */
    public function update($criteria, $object, $options)
    {
        $criteria = $this->toArray($criteria);
        $object = $this->toArray($object);
        $options = $this->toArray($options);
        $rst = $this->_model->update($criteria, $object, $options);
        return $this->result($rst);
    }

    /**
     * 删除操作
     *
     * @param string $criteria            
     * @throws \SoapFault
     * @return string
     */
    public function remove($criteria)
    {
        $criteria = $this->toArray($criteria);
        $rst = $this->_model->remove($criteria);
        return $this->result($rst);
    }

    /**
     * 清空整个集合
     *
     * @return string
     */
    public function drop()
    {
        $rst = $this->_model->drop();
        return $this->result($rst);
    }

    /**
     * 创建索引
     *
     * @param string $keys            
     * @param string $options            
     * @return string
     */
    public function ensureIndex($keys, $options)
    {
        if (! empty($keys)) {
            $keys = $this->toArray($keys);
        } else {
            $keys = trim($keys);
        }
        
        if (! empty($options)) {
            $options = $this->toArray($options);
        } else {
            $options = array(
                'background' => true
            );
        }
        $rst = $this->_model->ensureIndex($keys, $options);
        return $this->result($rst);
    }

    /**
     * 删除特定索引
     *
     * @param string $keys            
     * @return string
     */
    public function deleteIndex($keys)
    {
        $keys = $this->toArray($keys);
        $rst = $this->_model->deleteIndex($keys);
        return $this->result($rst);
    }

    /**
     * 删除全部索引
     *
     * @return string
     */
    public function deleteIndexes()
    {
        $rst = $this->_model->deleteIndexes();
        return $this->result($rst);
    }

    /**
     * findAndModify
     *
     * @param string $options            
     * @return string
     */
    public function findAndModify($options)
    {
        $options = $this->toArray($options);
        $rst = $this->_model->findAndModifyByCommand($options);
        return $this->result($rst);
    }

    /**
     * aggregate框架支持
     *
     * @param string $ops1            
     * @param string $ops2            
     * @param string $ops3            
     * @return string
     */
    public function aggregate($ops1, $ops2, $ops3)
    {
        $param_arr = array();
        $ops1 = $this->toArray($ops1);
        $ops2 = $this->toArray($ops2);
        $ops3 = $this->toArray($ops3);
        
        $param_arr[] = $ops1;
        if (! empty($ops2)) {
            $param_arr[] = $ops2;
        }
        if (! empty($ops3)) {
            $param_arr[] = $ops3;
        }
        
        $rst = call_user_func_array(array(
            $this->_model,
            'aggregate'
        ), $param_arr);
        return $this->result($rst);
    }

    /**
     * 存储小文件文件到集群（2M以内的文件）
     *
     * @param string $fileBytes            
     * @param string $fileName            
     * @return string
     */
    public function uploadFile($fileBytes, $fileName)
    {
        $fileBytes = base64_decode($fileBytes);
        $rst = $this->_model->storeBytesToGridFS($fileBytes, $fileName, array(
            'collection_id' => $this->_collection_id,
            'project_id' => $this->_project_id
        ));
        return $this->result($rst);
    }

    /**
     * 一次请求，执行一些列操作，降低网络传输导致的效率问题
     *
     * @param string $ops            
     * @param bool $last
     *            只返回最后一条处理的结果
     * @return string
     */
    public function pipe($ops, $last = true)
    {
        $rst = array();
        $ops = $this->toArray($ops);
        if (empty($ops)) {
            return $this->result($ops);
        }
        foreach ($ops as $cmd => $param_arr) {
            $execute = call_user_func_array(array(
                $this->_model,
                $cmd
            ), $param_arr);
            
            if ($last)
                $rst = $execute;
            else
                $rst[] = $execute;
        }
        return $this->result($rst);
    }

    /**
     * 规范返回数据的格式为数组
     *
     * @param mixed $rst            
     * @return array
     */
    private function result($rst)
    {
        if (! empty($this->_fileField) && is_array($rst)) {
            array_walk_recursive($rst, function (&$item, $key)
            {
                if (in_array($key, array_keys($this->_fileField), true) && $this->_fileField[$key]['type'] === 'filefield') {
                    $item = (empty($this->_fileField[$key]['cdnUrl']) ? DOMAIN : $this->_fileField[$key]['cdnUrl']) . '/file/' . $item;
                }
            });
        }
        return $rst;
    }

    /**
     * 将字符串转化为数组
     *
     * @param string $string            
     * @throws \SoapFault
     * @return array
     */
    private function toArray($string)
    {
        if ($rst !== false) {
            if (empty($rst)) {
                return array();
            }
            array_walk_recursive($rst, function (&$value, $key)
            {
                if ($key === '_id' && strlen($value) === 24) {
                    if (! ($value instanceof \MongoId))
                        $value = new \MongoId($value);
                }
            });
            return $rst;
        }
        throw new \SoapFault(500, '参数格式错误:无法进行有效的反序列化');
    }

    public function __destruct()
    {}
}