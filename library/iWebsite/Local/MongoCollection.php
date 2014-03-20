<?php
/**
 * 扩展和限定基础类库操作
 * @author yangming
 * 
 * 使用说明：
 * 
 * 对于mongocollection的操作进行了规范，危险方法的drop remove均采用了伪删除的实现。
 * 删除操作时，remove实际上是添加了保留属性__REMOVED__设置为true
 * 添加操作时，额外添加了保留属性__CREATE_TIME__(创建时间) 和 __MODIFY_TIME__(修改时间) __REMOVED__：false
 * 更新操作时，将自动更新__MODIFY_TIME__
 * 查询操作时,count/find/findOne/findAndModify操作 ，查询条件将自动添加__REMOVED__:false参数，编码时，无需手动添加
 * 
 * 注意事项：
 * 
 * 1. findAndModify内部的操作update时，请手动添加__MODIFY_TIME__ __CREATE_TIME__ 原因详见mongodb的upsert操作说明，我想看完你就理解了
 * 2. group、aggregate操作因为涉及到里面诸多pipe细节，考虑到代码的可读性、简洁以及易用性，所以请手动处理__MODIFY_TIME__ __CREATE_TIME__ __REMOVED__ 三个保留参数
 * 3. 同理，对于db->command操作内部，诸如mapreduce等操作时，如涉及到数据修改，请注意以上三个参数的变更与保留，以免引起不必要的问题。
 * 
 */
defined('DEFAULT_DATABASE') || define('DEFAULT_DATABASE', 'ICCv1');
defined('DEFAULT_CLUSTER') || define('DEFAULT_CLUSTER', 'default');

defined('DB_ADMIN') || define('DB_ADMIN', 'admin');
defined('DB_BACKUP') || define('DB_BACKUP', 'backup');
defined('DB_MAPREDUCE') || define('DB_MAPREDUCE', 'mapreduce');
defined('DB_LOGS') || define('DB_LOGS', 'logs');
defined('GRIDFS_PREFIX') || define('GRIDFS_PREFIX', 'icc');

defined('MONGOS_DEFAULT_01') || define('MONGOS_DEFAULT_01', '10.0.0.30:57017');
defined('MONGOS_DEFAULT_02') || define('MONGOS_DEFAULT_02', '10.0.0.31:57017');
defined('MONGOS_DEFAULT_03') || define('MONGOS_DEFAULT_03', '10.0.0.32:57017');

defined('MONGOS_LOCAL') || define('MONGOS_LOCAL', '127.0.0.1:27017');

class iWebsite_Local_MongoCollection extends \MongoCollection
{

    /**
     * 连接的集合名称
     *
     * @var string
     */
    private $_collection = '';

    /**
     * 连接的数据库名称，默认为系统默认数据库
     *
     * @var string
     */
    private $_database = DEFAULT_DATABASE;

    /**
     * 连接集群的名称，模拟人生为系统默认集合
     *
     * @var string
     */
    private $_cluster = DEFAULT_CLUSTER;

    /**
     * 集合操作参数
     *
     * @var array
     */
    private $_collectionOptions = NULL;

    /**
     * 当前数据库连接实例
     *
     * @var object
     */
    private $_db;

    /**
     * 管理数据连接实例
     *
     * @var object
     */
    private $_admin;

    /**
     * 备份数据库连接实例
     *
     * @var object
     */
    private $_backup;

    /**
     * mapreduce保存数据的数据库连接实例
     *
     * @var object
     */
    private $_mapreduce;

    /**
     * 相关数据库配置参数的数组
     *
     * @var array
     */
    private $_config;

    /**
     * 相关数据库配置参数的Config实例
     *
     * @var Config
     */
    private $_configInstance;

    /**
     * GridFS连接实例
     *
     * @var MongoGridFS
     */
    private $_fs;

    /**
     * 查询操作列表
     *
     * @var array
     */
    private $_queryHaystack = array(
        '$and',
        '$or',
        '$nor',
        '$not',
        '$where'
    );

    /**
     * 更新操作列表
     *
     * @var array
     */
    private $_updateHaystack = array(
        '$set',
        '$inc',
        '$unset',
        '$rename',
        '$setOnInsert',
        '$addToSet',
        '$pop',
        '$pullAll',
        '$pull',
        '$pushAll',
        '$push',
        '$each',
        '$slice',
        '$sort',
        '$bit',
        '$isolated'
    );

    /**
     * 是否开启追加参数__REMOVED__:true
     *
     * @var boolean
     */
    private $_noAppendQuery = false;

    /**
     * 超时时间
     *
     * @var int
     */
    const timeout = 6000000;

    /**
     * 强制同步写入操作
     *
     * @var boolean
     */
    const fsync = false;

    /**
     * 是否开启更新不存在插入数据
     *
     * @var boolean
     */
    const upsert = false;

    /**
     * 允许更改多项
     *
     * @var boolean
     */
    const multiple = true;

    /**
     * 仅此一项
     *
     * @var boolean
     */
    const justOne = false;

    /**
     * 开启调试模式
     *
     * @var boolean
     */
    const debug = false;

    /**
     * 构造函数
     *
     * @param Config $config            
     * @param string $collection            
     * @param string $database            
     * @param string $cluster            
     * @param string $collectionOptions            
     * @throws \Exception
     */
    public function __construct($collection = null, $database = DEFAULT_DATABASE, $cluster = DEFAULT_CLUSTER, $collectionOptions = null)
    {
        if (! class_exists("MongoClient")) {
            throw new \Exception('请安装MongoClient');
        }
        
        if ($collection === null) {
            throw new \Exception('$collection集合为空');
        }
        
        $this->_collection = $collection;
        $this->_database = $database;
        $this->_cluster = $cluster;
        $this->_collectionOptions = $collectionOptions;
        $this->_configInstance = $this->_config = $config;
        
        $options = array();
        $options['connectTimeoutMS'] = 60000;
        $options['socketTimeoutMS'] = 60000;
        $options['w'] = 1;
        $options['wTimeout'] = 60000;
        
        if (APPLICATION_ENV == 'production') {
            $mongos = array(
                MONGOS_DEFAULT_01,
                MONGOS_DEFAULT_02,
                MONGOS_DEFAULT_03
            );
            
            shuffle($mongos);
            $dnsString = 'mongodb://' . join(',', $mongos);
        } else {
            $dnsString = 'mongodb://127.0.0.1:27017';
        }
        $connect = new \MongoClient($dnsString, $options);
        $this->_db = $connect->selectDB(DEFAULT_DATABASE);
        $this->_admin = $connect->selectDB(DB_ADMIN);
        $this->_backup = $connect->selectDB(DB_BACKUP);
        $this->_mapreduce = $connect->selectDB(DB_MAPREDUCE);
        $this->_fs = new \MongoGridFS($this->_db, GRIDFS_PREFIX);
        
        // 默认执行几个操作
        // 第一个操作，判断集合是否创建，如果没有创建，则进行分片处理（目前采用_ID作为片键）
        if (APPLICATION_ENV === 'production') {
            $this->shardingCollection();
        }
        parent::__construct($this->_db, $this->_collection);
        
        /**
         * 设定读取优先级
         * MongoClient::RP_PRIMARY 只读取主db
         * MongoClient::RP_PRIMARY_PREFERRED 读取主db优先
         * MongoClient::RP_SECONDARY 只读从db优先
         * MongoClient::RP_SECONDARY_PREFERRED 读取从db优先
         */
        $this->db->setReadPreference(\MongoClient::RP_PRIMARY_PREFERRED);
    }

    /**
     * 是否开启追加模式
     *
     * @param boolean $boolean            
     */
    public function setNoAppendQuery($boolean)
    {
        $this->_noAppendQuery = is_bool($boolean) ? $boolean : false;
    }

    /**
     * 检测是简单查询还是复杂查询，涉及复杂查询采用$and方式进行处理，简单模式采用连接方式进行处理
     *
     * @param array $query            
     * @throws \Exception
     */
    private function appendQuery(array $query = null)
    {
        if (! is_array($query)) {
            $query = array();
        }
        if ($this->_noAppendQuery) {
            return $query;
        }
        
        $keys = array_keys($query);
        $intersect = array_intersect($keys, $this->_queryHaystack);
        if (! empty($intersect)) {
            $query = array(
                '$and' => array(
                    array(
                        '__REMOVED__' => false
                    ),
                    $query
                )
            );
        } else {
            $query['__REMOVED__'] = false;
        }
        return $query;
    }

    /**
     * 对于新建集合进行自动分片
     *
     * @return boolean
     */
    private function shardingCollection()
    {
        $defaultCollectionOptions = array(
            'capped' => false, // 是否开启固定集合
            'size' => pow(1024, 3), // 如果简单开启capped=>true,单个集合的最大尺寸为2G
            'max' => pow(10, 8), // 如果简单开启capped=>true,单个集合的最大条数为1亿条数据
            'autoIndexId' => true
        );
        
        if ($this->_collectionOptions !== NULL) {
            $this->_collectionOptions = array_merge($defaultCollectionOptions, $this->_collectionOptions);
        }
        
        $this->_db->createCollection($this->_collection, $this->_collectionOptions);
        $rst = $this->_admin->command(array(
            'shardCollection' => $this->_database . '.' . $this->_collection,
            'key' => array(
                '_id' => 1
            )
        ));
        if ($rst['ok'] == 1) {
            return true;
        }
        return false;
    }

    /**
     * 处理检索条件
     *
     * @param string $text            
     */
    private function search($text)
    {
        return new \MongoRegex('/' . preg_replace("/[\s\r\t\n]/", '.*', $text) . '/i');
    }

    /**
     * aggregate框架指令达成
     *
     * @return mixed
     */
    public function aggregate($pipeline, $op = NULL, $op1 = NULL)
    {
        if (! $this->_noAppendQuery) {
            array_unshift($pipeline, array(
                '$match' => array(
                    '__REMOVED__' => false
                )
            ));
        }
        
        return parent::aggregate($pipeline);
    }

    /**
     * 批量插入数据
     *
     * @see MongoCollection::batchInsert()
     */
    public function batchInsert(array $documents, array $options = NULL)
    {
        array_walk($documents, function (&$row, $key)
        {
            $row['__CREATE_TIME__'] = $row['__MODIFY_TIME__'] = new \MongoDate();
            $row['__REMOVED__'] = false;
        });
        return parent::batchInsert($documents, $options);
    }

    /**
     * 统计符合条件的数量
     *
     * @see MongoCollection::count()
     */
    public function count($query = NULL, $limit = NULL, $skip = NULL)
    {
        $query = $this->appendQuery($query);
        return parent::count($query, $limit, $skip);
    }

    /**
     * 根据指定字段
     *
     * @param string $key            
     * @param array $query            
     */
    public function distinct($key, $query = null)
    {
        $query = $this->appendQuery($query);
        return parent::distinct($key, $query);
    }

    /**
     * 直接禁止drop操作,注意备份表中只包含当前集合中的有效数据，__REMOVED__为true的不在此列
     *
     * @see MongoCollection::drop()
     */
    function drop()
    {
        // 做法1：抛出异常禁止Drop操作
        // throw new \Exception('ICC deny execute "drop()" collection operation');
        // 做法2：复制整个集合的数据到新的集合中，用于备份，备份数据不做片键，不做索引以便节约空间，仅出于安全考虑，原有_id使用保留字__OLD_ID__进行保留
        $targetCollection = 'bak_' . date('YmdHis') . '_' . $this->_collection;
        $target = new self($this->_backup, $targetCollection);
        // 变更为重命名某个集合或者复制某个集合的操作作为替代。
        $cursor = $this->find(array());
        while ($cursor->hasNext()) {
            $row = $cursor->getNext();
            $row['__OLD_ID__'] = $row['_id'];
            unset($row['_id']);
            $target->insert($row);
        }
        return parent::drop();
    }

    /**
     * 物理删除数据集合
     */
    public function physicalDrop()
    {
        return parent::drop();
    }

    /**
     * ICC系统默认采用后台创建的方式，建立索引
     *
     * @see MongoCollection::ensureIndex()
     */
    public function ensureIndex($key_keys, array $options = NULL)
    {
        $default = array();
        $default['background'] = true;
        // $default['expireAfterSeconds'] = 3600; // 请充分了解后开启此参数，慎用
        $options = ($options === NULL) ? $default : array_merge($default, $options);
        return parent::ensureIndex($key_keys, $options);
    }

    /**
     * 查询符合条件的项目，自动排除__REMOVED__:true的结果集
     *
     * @see MongoCollection::find()
     */
    public function find($query = NULL, $fields = NULL)
    {
        $fields = empty($fields) ? array() : $fields;
        return parent::find($this->appendQuery($query), $fields);
    }

    /**
     * 查询符合条件的一条数据
     *
     * @see MongoCollection::findOne()
     */
    public function findOne($query = NULL, $fields = NULL)
    {
        $fields = empty($fields) ? array() : $fields;
        return parent::findOne($this->appendQuery($query), $fields);
    }

    /**
     * 获取符合条件的全部数据
     *
     * @param array $query            
     * @param array $sort            
     * @param int $skip            
     * @param int $limit            
     * @param array $fields            
     * @return array
     */
    public function findAll($query, $sort = array('$natural'=>1), $skip = 0, $limit = 0, $fields = array())
    {
        $fields = empty($fields) ? array() : $fields;
        $cursor = $this->find($query, $fields);
        if (! $cursor instanceof \MongoCursor)
            throw new \Exception('$query error:' . json_encode($query));
        
        if (! empty($sort))
            $cursor->sort($sort);
        if (! empty($skip))
            $cursor->skip($skip);
        
        if ($limit > 0) {
            $cursor->limit($limit);
        }
        
        if ($cursor->count() == 0)
            return array();
        
        return iterator_to_array($cursor, false);
    }

    /**
     * findAndModify操作
     * 特别注意：__REMOVED__ __MODIFY_TIME__ __CREATE_TIME__ 3个系统保留变量在update参数中的使用
     *
     * @param array $query            
     * @param array $update            
     * @param array $fields            
     * @param array $options            
     * @return array
     */
    public function findAndModify(array $query, array $update = NULL, array $fields = NULL, array $options = NULL)
    {
        $query = $this->appendQuery($query);
        return parent::findAndModify($query, $update, $fields, $options);
    }

    /**
     * 增加findAndModify方法
     * 特别注意：__REMOVED__ __MODIFY_TIME__ __CREATE_TIME__ 3个系统保留变量在update参数中的使用
     *
     * @param array $option            
     * @param string $collection            
     * @return mixed array|null
     */
    public function findAndModifyByCommand($option, $collection = NULL)
    {
        $cmd = array();
        $targetCollection = $collection === NULL ? $this->_collection : $collection;
        $cmd['findandmodify'] = $targetCollection;
        if (isset($option['query']))
            $cmd['query'] = $this->appendQuery($option['query']);
        if (isset($option['sort']))
            $cmd['sort'] = $option['sort'];
        if (isset($option['remove']))
            $cmd['remove'] = is_bool($option['remove']) ? $option['remove'] : false;
        if (isset($option['update']))
            $cmd['update'] = $option['update'];
        if (isset($option['new']))
            $cmd['new'] = is_bool($option['new']) ? $option['new'] : false;
        if (isset($option['fields']))
            $cmd['fields'] = $option['fields'];
        if (isset($option['upsert']))
            $cmd['upsert'] = is_bool($option['upsert']) ? $option['upsert'] : false;
        
        return $this->_db->command($cmd);
    }

    /**
     * 插入特定的数据,并保持insert第一个参数$a在没有_id的时候添加_id属性
     *
     * @param array $object            
     * @param array $options            
     */
    public function insertRef(&$a, array $options = NULL)
    {
        if (empty($a))
            throw new \Exception('$object is NULL');
        
        $default = array(
            'fsync' => self::fsync,
            'timeout' => self::timeout
        );
        $options = ($options === NULL) ? $default : array_merge($default, $options);
        
        array_unset_recursive($a, array(
            '__CREATE_TIME__',
            '__MODIFY_TIME__',
            '__REMOVED__'
        ));
        
        if (! isset($a['__CREATE_TIME__'])) {
            $a['__CREATE_TIME__'] = new \MongoDate();
        }
        
        if (! isset($a['__MODIFY_TIME__'])) {
            $a['__MODIFY_TIME__'] = new \MongoDate();
        }
        
        if (! isset($a['__REMOVED__'])) {
            $a['__REMOVED__'] = false;
        }
        
        $b = $a;
        $res = parent::insert($b, $options);
        $a = $b;
        return $res;
    }

    /**
     * 插入特定的数据，注意此方法無法針對$a添加_id属性，详见参数丢失原因的文档说明
     * 解决这个问题，请使用上面的方法insertRef
     * 注意因为参数检查的原因，无法直接覆盖insert方法并采用引用，如下原因
     * <b>Strict Standards</b>: Declaration of My\Common\MongoCollection::insert() should be compatible with MongoCollection::insert($array_of_fields_OR_object, array $options = NULL)
     *
     * @param array $object            
     * @param array $options            
     */
    public function insert($a, array $options = NULL)
    {
        if (empty($a))
            throw new \Exception('$object is NULL');
        
        $default = array(
            'fsync' => self::fsync,
            'timeout' => self::timeout
        );
        $options = ($options === NULL) ? $default : array_merge($default, $options);
        
        array_unset_recursive($a, array(
            '__CREATE_TIME__',
            '__MODIFY_TIME__',
            '__REMOVED__'
        ));
        
        if (! isset($a['__CREATE_TIME__'])) {
            $a['__CREATE_TIME__'] = new \MongoDate();
        }
        
        if (! isset($a['__MODIFY_TIME__'])) {
            $a['__MODIFY_TIME__'] = new \MongoDate();
        }
        
        if (! isset($a['__REMOVED__'])) {
            $a['__REMOVED__'] = false;
        }
        
        return parent::insert($a, $options);
    }

    /**
     * 通过findAndModify的方式，插入数据。
     * 这样可以使用$a['a.b']的方式插入结构为{a:{b:xxx}}的数据,这是insert所不能办到的
     * 采用update也可以实现类似的效果，区别在于findAndModify可以返回插入之后的新数据，更接近insert的原始行为
     *
     * @param array $a            
     * @return array
     */
    public function insertByFindAndModify($a)
    {
        if (empty($a))
            throw new \Exception('$a is NULL');
        
        array_unset_recursive($a, array(
            '__CREATE_TIME__',
            '__MODIFY_TIME__',
            '__REMOVED__'
        ));
        
        if (! isset($a['__CREATE_TIME__'])) {
            $a['__CREATE_TIME__'] = new \MongoDate();
        }
        
        if (! isset($a['__MODIFY_TIME__'])) {
            $a['__MODIFY_TIME__'] = new \MongoDate();
        }
        
        if (! isset($a['__REMOVED__'])) {
            $a['__REMOVED__'] = false;
        }
        
        $query = array(
            '_id' => new \MongoId()
        );
        $a = array(
            '$set' => $a
        );
        $fields = null;
        $options = array(
            'new' => true,
            'upsert' => true
        );
        
        return parent::findAndModify($query, $a, $fields, $options);
    }

    /**
     * 删除指定范围的数据
     *
     * @param array $criteria            
     * @param array $options            
     */
    public function remove($criteria = NULL, array $options = NULL)
    {
        if ($criteria === NULL)
            throw new \Exception('$criteria is NULL');
        
        $default = array(
            'justOne' => self::justOne,
            'fsync' => self::fsync,
            'timeout' => self::timeout
        );
        
        $options = ($options === NULL) ? $default : array_merge($default, $options);
        
        // 方案一 真实删除
        // return parent::remove($criteria, $options);
        // 方案二 伪删除
        
        if (! $options['justOne']) {
            $options['multiple'] = true;
        }
        
        $criteria = $this->appendQuery($criteria);
        return parent::update($criteria, array(
            '$set' => array(
                '__REMOVED__' => true
            )
        ), $options);
    }

    /**
     * 物理删除指定范围的数据
     *
     * @param array $criteria            
     * @param array $options            
     */
    public function physicalRemove($criteria = NULL, array $options = NULL)
    {
        if ($criteria === NULL)
            throw new \Exception('$criteria is NULL');
        
        $default = array(
            'justOne' => self::justOne,
            'fsync' => self::fsync,
            'timeout' => self::timeout
        );
        
        $options = ($options === NULL) ? $default : array_merge($default, $options);
        return parent::remove($criteria, $options);
    }

    /**
     * 更新指定范围的数据
     *
     * @param array $criteria            
     * @param array $object            
     * @param array $options            
     */
    public function update($criteria, $object, array $options = NULL)
    {
        if (! is_array($criteria))
            throw new \Exception('$criteria is array');
        
        if (empty($object))
            throw new \Exception('$object is empty');
        
        $keys = array_keys($object);
        foreach ($keys as $key) {
            $key = strtolower($key);
            if (! in_array($key, $this->_updateHaystack, true)) {
                throw new \Exception('$key must contain ' . join(',', $this->_updateHaystack));
            }
        }
        $default = array(
            'upsert' => self::upsert,
            'multiple' => self::multiple,
            'fsync' => self::fsync,
            'timeout' => self::timeout
        );
        
        $options = ($options === NULL) ? $default : array_merge($default, $options);
        
        $criteria = $this->appendQuery($criteria);
        array_unset_recursive($object, array(
            '_id',
            '__CREATE_TIME__',
            '__MODIFY_TIME__',
            '__REMOVED__'
        ));
        
        if (parent::count($criteria) == 0) {
            if (isset($options['upsert']) && $options['upsert']) {
                parent::update($criteria, array(
                    '$set' => array(
                        '__CREATE_TIME__' => new \MongoDate(),
                        '__MODIFY_TIME__' => new \MongoDate(),
                        '__REMOVED__' => false
                    )
                ), $options);
            }
        } else {
            parent::update($criteria, array(
                '$set' => array(
                    '__MODIFY_TIME__' => new \MongoDate()
                )
            ), $options);
        }
        
        return parent::update($criteria, $object, $options);
    }

    /**
     * 保存并保持引用修改状态
     *
     * @param array $a            
     * @param array $options            
     * @return mixed
     */
    public function save($a, array $options = NULL)
    {
        if (! isset($a['__CREATE_TIME__'])) {
            $a['__CREATE_TIME__'] = new \MongoDate();
        }
        $a['__REMOVED__'] = false;
        $a['__MODIFY_TIME__'] = new \MongoDate();
        if ($options == null) {
            $options = array(
                'w' => 1
            );
        }
        return parent::save($a, $options);
    }

    /**
     * 保存并保持引用修改状态
     *
     * @param array $a            
     * @param array $options            
     * @return mixed
     */
    public function saveRef(&$a, array $options = NULL)
    {
        if (! isset($a['__CREATE_TIME__'])) {
            $a['__CREATE_TIME__'] = new \MongoDate();
        }
        $a['__REMOVED__'] = false;
        $a['__MODIFY_TIME__'] = new \MongoDate();
        if ($options == null) {
            $options = array(
                'w' => 1
            );
        }
        
        $b = $a;
        $res = parent::save($b, $options);
        $a = $b;
        return $res;
    }

    /**
     * 执行DB的command操作
     *
     * @param array $command            
     * @return array
     */
    public function command($command)
    {
        return $this->db->command($command);
    }

    /**
     * 执行map reduce操作,为了防止数据量过大，导致无法完成mapreduce,统一采用集合的方式，取代内存方式
     * 内存方式，不允许执行过程的数据量量超过物理内存的10%，故无法进行大数量分析工作。
     *
     * @param array $command            
     */
    public function mapReduce($out = null, $map, $reduce, $query = array(), $finalize = null, $method = 'replace', $scope = null, $sort = array('$natural'=>1), $limit = null)
    {
        if ($out == null) {
            $out = md5(serialize(func_get_args()));
        }
        try {
            // map reduce执行锁管理开始
            $locks = new self($this->_configInstance, 'locks', DB_MAPREDUCE, $this->_cluster);
            $locks->setReadPreference(\MongoClient::RP_PRIMARY_PREFERRED);
            
            $checkLock = function ($out) use($locks)
            {
                $check = $locks->findOne(array(
                    'out' => $out
                ));
                if ($check == null) {
                    $locks->insert(array(
                        'out' => $out,
                        'isRunning' => true,
                        'expire' => new \MongoDate(time() + 300)
                    ));
                    return false;
                } else {
                    if (isset($check['isRunning']) && $check['isRunning']) {
                        return true;
                    }
                    if ($check['isRunning'] && isset($check['expire']) && $check['expire'] instanceof \MongoDate) {
                        if ($check['expire']->sec > time()) {
                            return true;
                        } else {
                            $releaseLock($out);
                            return false;
                        }
                    }
                    $locks->update(array(
                        'out' => $out
                    ), array(
                        '$set' => array(
                            'isRunning' => true,
                            'expire' => new \MongoDate(time() + 300)
                        )
                    ));
                    return false;
                }
            };
            
            $releaseLock = function ($out, $rst = null) use($locks)
            {
                return $locks->update(array(
                    'out' => $out
                ), array(
                    '$set' => array(
                        'isRunning' => false,
                        'rst' => is_string($rst) ? $rst : json_encode($rst)
                    )
                ));
            };
            
            $failure = function ($code, $msg)
            {
                if (is_array($msg)) {
                    $msg = json_encode($msg);
                }
                return array(
                    'ok' => 0,
                    'code' => $code,
                    'msg' => $msg
                );
            };
            // map reduce执行锁管理结束
            
            if (! $checkLock($out)) {
                $command = array();
                $command['mapreduce'] = $this->_collection;
                $command['map'] = ($map instanceof \MongoCode) ? $map : new \MongoCode($map);
                $command['reduce'] = ($reduce instanceof \MongoCode) ? $reduce : new \MongoCode($reduce);
                $command['query'] = $this->appendQuery($query);
                
                if (! empty($finalize))
                    $command['finalize'] = ($finalize instanceof \MongoCode) ? $finalize : new \MongoCode($finalize);
                if (! empty($sort))
                    $command['sort'] = $sort;
                if (! empty($limit))
                    $command['limit'] = $limit;
                if (! empty($scope))
                    $command['scope'] = $scope;
                $command['verbose'] = true;
                
                if (! in_array($method, array(
                    'replace',
                    'merge',
                    'reduce'
                ), true)) {
                    $method = 'replace';
                }
                
                $command['out'] = array(
                    $method => $out,
                    'db' => DB_MAPREDUCE,
                    'sharded' => false,
                    'nonAtomic' => in_array($method, array(
                        'merge',
                        'reduce'
                    ), true) ? true : false
                );
                $rst = $this->command($command);
                $releaseLock($out, $rst);
                
                if ($rst['ok'] == 1) {
                    if ($rst['counts']['emit'] > 0 && $rst['counts']['output'] > 0) {
                        $outMongoCollection = new self($this->_configInstance, $out, DB_MAPREDUCE, $this->_cluster);
                        $outMongoCollection->setNoAppendQuery(true);
                        return $outMongoCollection;
                    }
                    return $failure(500, $rst['counts']);
                } else {
                    return $failure(501, $rst);
                }
            } else {
                return $failure(502, '程序正在执行中，请勿频繁尝试');
            }
        } catch (\Exception $e) {
            $releaseLock($out, exceptionMsg($e));
            return $failure(503, exceptionMsg($e));
        }
    }

    /**
     * 云存储文件
     *
     * @param string $fieldName
     *            上传表单字段的名称
     *            
     */
    public function storeToGridFS($fieldName, $metadata = array())
    {
        if (! is_array($metadata))
            $metadata = array();
        
        if (! isset($_FILES[$fieldName]))
            throw new \Exception('$_FILES[$fieldName]无效');
        
        $metadata = array_merge($metadata, $_FILES[$fieldName]);
        $finfo = new \finfo(FILEINFO_MIME);
        $mime = $finfo->file($_FILES[$fieldName]['tmp_name']);
        if ($mime !== false)
            $metadata['mime'] = $mime;
        
        $id = $this->_fs->storeUpload($fieldName, $metadata);
        $gridfsFile = $this->_fs->get($id);
        if (! ($gridfsFile instanceof \MongoGridFSFile)) {
            throw new \Exception('$gridfsFile is not instanceof MongoGridFSFile');
        }
        return $gridfsFile->file;
    }

    /**
     * 存储二进制内容
     *
     * @param bytes $bytes            
     * @param string $filename            
     * @param array $metadata            
     */
    public function storeBytesToGridFS($bytes, $filename = '', $metadata = array())
    {
        if (! is_array($metadata))
            $metadata = array();
        
        if (! empty($filename))
            $metadata['filename'] = $filename;
        
        $finfo = new \finfo(FILEINFO_MIME);
        $mime = $finfo->buffer($bytes);
        if ($mime !== false)
            $metadata['mime'] = $mime;
        
        $id = $this->_fs->storeBytes($bytes, $metadata);
        $gridfsFile = $this->_fs->get($id);
        return $gridfsFile->file;
    }

    /**
     * 获取指定ID的GridFSFile对象
     *
     * @param string $id            
     * @return \MongoGridFSFile object
     */
    public function getGridFsFileById($id)
    {
        if (! $id instanceof \MongoId) {
            $id = new \MongoId($id);
        }
        return $this->_fs->get($id);
    }

    /**
     * 根据ID获取文件的信息
     *
     * @param string $id            
     * @return array 文件信息数组
     */
    public function getInfoFromGridFS($id)
    {
        if (! $id instanceof \MongoId) {
            $id = new \MongoId($id);
        }
        $gridfsFile = $this->_fs->get($id);
        return $gridfsFile->file;
    }

    /**
     * 根据ID获取文件内容，二进制
     *
     * @param string $id            
     * @return bytes
     */
    public function getFileFromGridFS($id)
    {
        if (! $id instanceof \MongoId) {
            $id = new \MongoId($id);
        }
        $gridfsFile = $this->_fs->get($id);
        return $gridfsFile->getBytes();
    }

    /**
     * 删除陈旧的文件
     *
     * @param mixed $id
     *            \MongoID or String
     * @return bool true or false
     */
    public function removeFileFromGridFS($id)
    {
        if (! $id instanceof \MongoId) {
            $id = new \MongoId($id);
        }
        return $this->_fs->remove($id);
    }

    /**
     * 在析构函数中调用debug方法
     */
    public function __destruct()
    {
        $this->debug();
    }
}