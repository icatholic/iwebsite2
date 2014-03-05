<?php

class Weixin_Model_Source extends iWebsite_Plugin_Mongo
{

    protected $name = 'iWeixin_source';

    protected $dbName = 'weixin';

    private $_params = array();

    /**
     * 获取字段列表
     * 
     * @return array
     */
    public function getFields()
    {
        return array_keys($this->getSchema());
    }
    
    /**
     * 获取信息接收信息
     *
     * @return array
     */
    public function revieve()
    {
        $postStr = file_get_contents('php://input');
        $datas = (array) simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        return $datas;
    }
}