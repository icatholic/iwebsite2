<?php

class Weixin_Model_Application extends iWebsite_Plugin_Mongo
{

    protected $name = 'iWeixin_application';

    protected $dbName = 'weixin';

    private $_params = array();

    private $_expire = 30;

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
     * 开启token的缓存信息
     *
     * @param number $cacheTime            
     */
    public function setTokenCache($expire = 300)
    {
        $this->_expire = (int) $expire;
    }

    /**
     * 获取有效的token信息
     *
     * @throws Exception
     * @return mixed array
     */
    public function getToken()
    {
        // $cacheKey = md5($_SERVER['HTTP_HOST'] . __METHOD__);
        // $cache = Zend_Registry::get('cache');
        // $token = $cache->load($cacheKey);
        // if (! $token) {
        $token = $this->findOne(array(
            'is_product' => true
        ));
        
        if ($token == null) {
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
                if ($rst['ok'] == 1) {
//                     $cache->save($rst['value'], $cacheKey, array(), $this->_expire);
                    return $rst['value'];
                } else {
                    throw new Exception(json_encode($rst));
                }
            }
            
            // 缓存有效期不能超过token过期时间
            if ((time() + $this->_expire) > $token['access_token_expire']->sec) {
                $this->_expire = $token['access_token_expire']->sec - time();
            }
            
            // $cache->save($token, $cacheKey, array(), $this->_expire);
        }
        // }
        return $token;
    }

    /**
     * 调试数据
     *
     * @param string $type            
     * @return array
     */
    public function debug($type)
    {
        $datas = array();
        switch ($type) {
            case 'subscribe':
                $datas['FromUserName'] = 'FromUserName';
                $datas['ToUserName'] = 'ToUserName';
                $datas['MsgType'] = 'event';
                $datas['Event'] = 'subscribe';
                $datas['EventKey'] = 'qrscene_1';
                break;
            case 'text':
                $datas['Content'] = '默认回复';
                $datas['FromUserName'] = 'FromUserName';
                $datas['ToUserName'] = 'ToUserName';
                $datas['MsgType'] = 'text';
                break;
            case 'reply':
                $datas['Content'] = '图片';
                $datas['FromUserName'] = 'FromUserName';
                $datas['ToUserName'] = 'ToUserName';
                $datas['MsgType'] = 'text';
                break;
            case 'image':
                $datas['FromUserName'] = 'FromUserName';
                $datas['ToUserName'] = 'ToUserName';
                $datas['MsgType'] = 'image';
                $datas['PicUrl'] = 'http://mmbiz.qpic.cn/mmbiz/DOiao54mZbb3K2hOsGN8dYQaAZIC8L46iaYictB2NNgJ1iav34rEX0bH6wnwpzanx0Dt9Zt0LZiaUsmM9EmgDESkXKw/0';
                $datas['MediaId'] = 'IozZY1RthEwbB3VsUux8kT0RNY14qXBZ-fYCdKS8u4CrExJe6ecRooSjiXc4n2uE';
                break;
            case 'event':
                $datas['FromUserName'] = 'FromUserName';
                $datas['ToUserName'] = 'ToUserName';
                $datas['MsgType'] = 'event';
                $datas['Event'] = 'CLICK';
                $datas['EventKey'] = 'Click测试文档';
                break;
        }
        return $datas;
    }
}