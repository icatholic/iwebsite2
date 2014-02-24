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

    /**
     * 获取有效的token信息
     * @throws Exception
     * @return mixed|unknown
     */
    public function getToken()
    {
        $token = $this->findOne(array(
            'is_product' => true
        ));
        
        if($token==null) {
            return null;
        }
        
        if (isset($token['access_token_expire']) && ! empty($token['is_advanced'])) {
            if ($token['access_token_expire']->sec <= time()) {
                if (empty($token['appid']) || empty($token['appid'])) {
                    throw new Exception('应用编号和密钥未设定');
                }
                
                $objToken = new Weixin\Token\Server($token['appid'], $token['secret']);
                $arrToken = $objToken->getAccessToken();
                $cmd = array();
                $cmd['query'] = array(
                    '_id' => $token['_id']
                );
                $cmd['update'] = array(
                    '$set' => array(
                        'access_token' => $arrToken['access_token'],
                        'access_token_expire' => new MongoDate(time() + 7200)
                    )
                );
                $rst = $this->findAndModify($cmd);
                if($rst['ok']==1) {
                    return $rst['value'];
                }
                else {
                    throw new Exception(json_encode($rst));
                }
            }
        }
        return $token;
    }
}