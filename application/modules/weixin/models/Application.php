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

    /**
     * 保持access token的有效性
     */
    public function updateAccessToken()
    {
        if (isset($this->_token['access_token_expire']) && ! empty($this->_token['is_advanced'])) {
            if ($this->_token['access_token_expire']->sec <= time()) {
                if (empty($this->_token['appid']) || empty($this->_token['appid'])) {
                    throw new Exception('应用编号和密钥未设定');
                }
                
                $objToken = new iWeixinAccessToken($this->_token['appid'], $this->_token['secret']);
                $arrToken = $objToken->get();
                $cmd = array();
                $cmd['query'] = array(
                    '_id' => $this->_token['_id']
                );
                $cmd['update'] = array(
                    '$set' => array(
                        'access_token' => $arrToken['access_token'],
                        'access_token_expire' => new MongoDate(time() + 7200)
                    )
                );
                $this->_app->findAndModify($cmd);
                return $arrToken['access_token'];
            }
        }
        return $this->_token['access_token'];
    }
}