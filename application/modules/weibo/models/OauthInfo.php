<?php

class Weibo_Model_OauthInfo extends iWebsite_Plugin_Mongo
{

    protected $name = 'iWeibo_oauthInfo';

    protected $dbName = 'weibo';

    private $_expire = 300;

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
     * 根据ID获取信息
     *
     * @param string $id            
     * @return array
     */
    public function getInfoById($id)
    {
        $query = array();
        $query["_id"] = myMongoId($id);
        $info = $this->findOne($query);
        return $info;
    }

    public function record($applicationId, $token)
    {
        $check = $this->findOne(array(
            'access_token' => $token['access_token'],
            'applicationId' => $applicationId
        ));
        if ($check == NULL) {
            $token['applicationId'] = $applicationId;
            $token['expireTime'] = new MongoDate(time() + $token['remind_in']);
            $expireTime = $token['expireTime'];
            $check = $this->insert($token);
        } else {
            $expireTime = new MongoDate(time() + $token['remind_in']);
            $this->update(array(
                '_id' => $check['_id']
            ), array(
                '$set' => array(
                    'expireTime' => $expireTime
                )
            ));
        }
        
        return myMongoId($check['_id']);
    }

    /**
     * 获取有效的token信息
     *
     * @throws Exception
     * @return mixed array
     */
    public function getToken($umaId)
    {
        $token = $this->getInfoById($umaId);
        
        if (empty($token)) {
            return null;
        }
        
        if (isset($token['expireTime'])) {
            if ($token['expireTime']->sec <= time()) { // 过期
                /*
                $modelApplication = new Weibo_Model_Application();
                $appConfig = $modelApplication->getInfoById($token['applicationId']);
                $modelAppKey = new Weibo_Model_AppKey();
                $appKey = $modelAppKey->getInfoById($appConfig['appKeyId']);
                
                if (! empty($appKey)) {
                    // 初始化新浪微博适配器
                    $oauth = new SaeTOAuthV2($appKey['akey'], $appKey['skey'], NULL);
                    $arrToken = $oauth->getAccessToken("token", array(
                        'refresh_token' => ""
                    ));
                    $cmd = array();
                    $cmd['query'] = array(
                        '_id' => $token['_id']
                    );
                    $cmd['update'] = array(
                        '$set' => array(
                            'access_token' => $arrToken['access_token'],
                            'expireTime' => new MongoDate(time() + $arrToken['remind_in'])
                        )
                    );
                    $rst = $this->findAndModify($cmd);
                    if ($rst['ok'] == 1) {
                        return $rst['value'];
                    } else {
                        throw new Exception(json_encode($rst));
                    }
                }
                */
            }
            
            // 缓存有效期不能超过token过期时间
            if ((time() + $this->_expire) > $token['expireTime']->sec) {
                $this->_expire = $token['expireTime']->sec - time();
            }
        }
        return $token;
    }
}