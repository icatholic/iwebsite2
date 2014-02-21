<?php

class Weixin_Model_Application extends iWebsite_Plugin_Mongo
{

    protected $name = 'iWeixin_application';

    protected $dbName = 'weixin';

    private $_params = array();

    /**
     * 获取字段列表
     *
     * @return array
     */
    public function getFields()
    {
        $fields = array();
        $schema = $this->getSchema();
        if (empty($schema)) {
            throw new Exception("该集合未定义文档结构");
        }
        
        return array_map(function ($row)
        {
            return $row['field'];
        }, $schema);
    }

    public function getToken()
    {
        return $this->findOne(array(
            'is_product' => true
        ));
    }
}