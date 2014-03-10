<?php

class iWebsite_Plugin_Mongo_Local
{

    protected $name = null;

    protected $dbName = 'default';

    private $_db;
    
    private $_client;

    /**
     * 建立默认的数据库连接
     */
    public function __construct($alias)
    {
        
        $this->_client = new MongoCollection();
    }

}