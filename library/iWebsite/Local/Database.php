<?php
/**
 * iDatabase服务
 * @author yangming
 * 
 */
class iWebsite_Local_Database
{

    /**
     * 操作集合的MongoCollection实例
     *
     * @var MongoCollection
     */
    private $_model = null;

    /**
     * 连接MongoDB的配置文件类
     *
     * @var Config
     */
    private $_config = null;

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
        if (!empty($fields)) {
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
        if (!empty($fields)) {
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
        if (!empty($keys)) {
            $keys = $this->toArray($keys);
        } else {
            $keys = trim($keys);
        }
        
        if (!empty($options)) {
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