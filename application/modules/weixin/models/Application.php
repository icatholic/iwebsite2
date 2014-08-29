<?php

class Weixin_Model_Application extends iWebsite_Plugin_Mongo
{

    protected $name = 'iWeixin_application';

    protected $dbName = 'weixin';
    
    protected $secondary = true;

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
     * 获取应用的信息
     * @return array
     */
    public function getApplicationInfo()
    {
        $application = $this->findOne(array(
            'is_product' => true
        ));
        return $application;
    }

    /**
     * 获取有效的token信息
     *
     * @throws Exception
     * @return mixed array
     */
    public function getToken()
    {
        $token = $this->findOne(array(
            'is_product' => true
        ));
        
        if ($token == null) {
            return null;
        }
        
        if (isset($token['access_token_expire']) && ! empty($token['is_advanced'])) {
            if ($token['access_token_expire']->sec <= time()) {
                if (! empty($token['appid']) && ! empty($token['appid'])) {
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
                        return $rst['value'];
                    } else {
                        throw new Exception(json_encode($rst));
                    }
                }
            }
            
            // 缓存有效期不能超过token过期时间
            if ((time() + $this->_expire) > $token['access_token_expire']->sec) {
                $this->_expire = $token['access_token_expire']->sec - time();
            }
        }
        return $token;
    }

    /**
     * 发送通知消息给某人
     *
     * @param string $to
     *            openid
     * @param string $content
     *            内容
     */
    public function notify($to, $content)
    {
        $appConfig = $this->getToken();
        $weixin = new Weixin\Client();
        if (! empty($appConfig['access_token'])) {
            $weixin->setAccessToken($appConfig['access_token']);
            $weixin->getMsgManager()
                ->getCustomSender()
                ->sendText($to, $content);
            return true;
        }
        return false;
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
                $datas['Content'] = '联系';
                $datas['FromUserName'] = 'o8NOajuFB07kWd4eHbKhY24OXPFE';
                $datas['ToUserName'] = 'gh_127af9cfa796';
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
    
    public function getSignKey($openid, $secretKey, $timestamp = 0)
    {
        return sha1($openid . "|" . $secretKey . "|" . $timestamp);
    }
}