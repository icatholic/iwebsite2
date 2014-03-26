<?php

abstract class iWebsite_Plugin_Mongo
{

    protected $name = null;

    protected $dbName = 'default';

    private $_db;

    /**
     * 建立默认的数据库连接
     */
    public function __construct()
    {
        try {
            if (Zend_Registry::isRegistered('db')) {
                $db = Zend_Registry::get('db');
                if (count($db) == 0)
                    exit('Please set db config');
                
                if (isset($db[$this->dbName])) {
                    $this->_db = clone $db[$this->dbName];
                } else {
                    $db = array_values($db);
                    $this->_db = clone $db[0];
                }
            } else {
                exit('Zend_Registry::isRegistered(\'db\') is undefined');
            }
            
            $this->_db->setCollection($this->name);
        } catch (Exception $e) {
            var_dump($e);
        }
    }

    /**
     * 获取当前的数据库连接
     *
     * @return mixed
     */
    public function getDB()
    {
        return $this->_db;
    }

    public function getSchema()
    {
        return $this->_db->getSchema();
    }

    public function insertRef(&$datas)
    {
        return $this->_db->insert($datas);
    }

    public function save(&$datas)
    {
        return $this->_db->save($datas);
    }

    /**
     * 是否开启调试模式
     *
     * @param bool $bool            
     */
    public function setDebug($bool)
    {
        $bool = is_bool($bool) ? $bool : false;
        $this->_db->setDebug($bool);
    }

    /**
     * 过载处理
     *
     * @param string $funcname            
     * @param array $arguments            
     * @return mixed
     */
    public function __call($funcname, $arguments)
    {
        if (! is_array($arguments)) {
            $arguments = array();
        }
        return call_user_func_array(array(
            $this->_db,
            $funcname
        ), $arguments);
    }
}