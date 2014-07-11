<?php

class Weibo_IndexController extends iWebsite_Controller_Action
{

    private $_user;

    private $_app;

    private $_config;

    private $_tracking;

    private $_model;

    private $_oauth;

    private $_token;

    public function init()
    {
        parent::init();
        $this->getHelper('viewRenderer')->setNoRender(true);
        
        $umaId = trim($this->get('umaId', '')); // UMAID
        if (empty($umaId)) {
            echo ($this->error(- 1, "UMA id不能为空"));
            return false;
        }
        
        $this->_config = Zend_Registry::get('config');
        $this->_tracking = new Weibo_Model_ScriptTracking();
        
        $this->_user = new Weibo_Model_User();
        $this->_model = new Weibo_Model_OauthInfo();
        $this->_app = new Weibo_Model_Application();
        $this->_key = new Weibo_Model_AppKey();
        
        // 获取设置
        $this->_appConfig = $this->_app->getConfig();
        // 初始化应用密钥
        $this->_appKey = $this->_key->getInfoById($this->_appConfig['appKeyId']);
        
        // 获取token
        $this->_token = $this->_model->getToken($umaId);
        $accessToken = (! empty($this->_token) && ! empty($this->_token['access_token'])) ? $this->_token['access_token'] : NULL;
        
        // 初始化新浪微博适配器
        $this->_oauth = new SaeTOAuthV2($this->_appKey['akey'], $this->_appKey['skey'], $accessToken);
    }

    /**
     * 发送POST类型的请求到微博API
     *
     * @param string $umaId
     *            UMA系统中的唯一授权表示 通过这个标示获取系统中的新浪微博access_token
     * @param string $url
     *            新浪微博API的请求地址 例如：statuses/user_timeline表示获取用户发布的微博
     * @param array $parameters
     *            新浪微博API请求地址对应的API参数
     * @param bool $multi
     *            默认为false。当POST参数中包含上传文件信息时，请将$multi参数设置为true
     *            
     *            例如：上传带图片的微博接口
     *            URL为statuses/upload
     *            参数为pic表示上传图片，路径为@http://www.filedomain.com/filepath/filename
     *            此时，请将$multi设置为true
     */
    public function postAction()
    {
        try {
            $url = trim($this->get('url', '')); // 新浪微博API的请求地址
            $parameters = $this->get('parameters'); // 新浪微博API请求地址对应的API参数
            $multi = trim($this->get('multi', 'false'));
            if (empty($url)) {
                echo ($this->error(- 2, "新浪微博API的请求地址不能为空"));
                return false;
            }
            if (empty($parameters)) {
                echo ($this->error(- 3, "新浪微博API请求地址对应的API参数不能为空"));
                return false;
            }
            if (strtolower($multi) == 'true') {
                $multi = true;
            } else {
                $multi = false;
            }
            
            // 调用微博接口
            $ret = $this->_oauth->post($url, $parameters, $multi);
            if (isset($ret['error'])) { // error
                echo ($this->error($ret['error_code'], $ret['error']));
                return false;
            }
            echo ($this->result("OK", $ret));
            return true;
        } catch (Exception $e) {
            echo ($this->error($e->getCode(), $e->getMessage()));
            return false;
        }
    }

    /**
     * 发送GET类型的请求到微博API
     *
     * @param string $url
     *            新浪微博API的请求地址 例如：statuses/user_timeline表示获取用户发布的微博
     * @param array $parameters
     *            新浪微博API请求地址对应的API参数
     */
    public function getAction()
    {
        try {
            $url = trim($this->get('url', '')); // 新浪微博API的请求地址
            $parameters = $this->get('parameters'); // 新浪微博API请求地址对应的API参数
            if (empty($url)) {
                echo ($this->error(- 2, "新浪微博API的请求地址不能为空"));
                return false;
            }
            if (empty($parameters)) {
                echo ($this->error(- 3, "新浪微博API请求地址对应的API参数不能为空"));
                return false;
            }
            // 调用微博接口
            $ret = $this->_oauth->get($url, $parameters);
            if (isset($ret['error'])) { // error
                echo ($this->error($ret['error_code'], $ret['error']));
                return false;
            }
            echo ($this->result("OK", $ret));
            return true;
        } catch (Exception $e) {
            echo ($this->error($e->getCode(), $e->getMessage()));
            return false;
        }
    }

    /**
     * 微博分享
     *
     * @param
     *            string content 分享内容 不能为空
     * @param
     *            string pic_url 分享图片 可空
     * @param
     *            string follow 自动关注的微博UID 可空
     * @param
     *            int friendNum @朋友数 可空
     * @param
     *            array friends @朋友 可空 如果有值，参数friendNum被忽略
     * @param
     *            string mobile 手机 可空
     */
    public function shareAction()
    {
        // http://xxxx/weibo/index/share?jsonpcallback=?&uid=1234&pic_url=&content=32323&follow=微博UID&friendNum=3&friends[]=guo
        try {
            $uid = trim($this->get('uid', '')); // 微博UID
            $screen_name = trim($this->get('screen_name', '')); // 微博昵称
            $content = trim($this->get('content')); // 分享内容
            $pic_url = trim($this->get('pic_url')); // 分享图片
            $follow = trim($this->get('follow', '')); // 自动关注的微博UID
            $friendNum = intval($this->get('friendNum', '0')); // @朋友数
            $friends = $this->get('friends'); // @朋友
            $mobile = $this->get('mobile'); // 手机
            
            if (empty($uid)) {
                echo ($this->error(- 2, "微博UID不能为空"));
                return false;
            }
            
            if (empty($content)) {
                echo ($this->error(- 3, "分享内容不能为空"));
                return false;
            }
            
            if (empty($pic_url)) {
                // echo ($this->error(- 4, "分享图片不能为空"));
                // return false;
            }
            
            if (! empty($mobile) && ! isValidMobile($mobile)) {
                echo ($this->error(- 5, "手机格式不正确"));
                return false;
            }
            
            // 特殊的业务逻辑代码开始
            // 特殊的业务逻辑代码结束
            
            // 微博分享
            $result = $this->share($uid, $content, $pic_url, $follow, $friendNum, $friends);
            echo ($this->result("微博分享成功", $result));
            return true;
        } catch (Exception $e) {
            echo ($this->error($e->getCode(), $e->getMessage()));
            return false;
        }
    }

    /**
     * 获取微博用户信息
     */
    public function getUserInfoAction()
    {
        // http://xxxx/weibo/index/get-user-info?jsonpcallback=?&uid=1234
        try {
            $uid = trim($this->get('uid', '')); // 微博UID
            if (empty($uid)) {
                echo ($this->error(- 2, "微博UID不能为空"));
                return false;
            }
            // 获取微博用户信息
            $result = $this->getUser($uid);
            echo ($this->result("获取微博用户信息成功", $result));
            return true;
        } catch (Exception $e) {
            echo ($this->error($e->getCode(), $e->getMessage()));
            return false;
        }
    }

    /**
     * 获取微博好友
     */
    public function getFriendListAction()
    {
        // http://xxxx/weibo/index/get-friend-list?jsonpcallback=?&uid=1234
        try {
            $uid = trim($this->get('uid', '')); // 微博UID
            $isBilateral = intval($this->get('isBilateral', '0')); // 是否互相关注
            if (empty($uid)) {
                echo ($this->error(- 2, "微博UID不能为空"));
                return false;
            }
            $result = $this->getFriendsByCondition($uid, $isBilateral);
            echo ($this->result("获取微博好友成功", $result));
            return true;
        } catch (Exception $e) {
            echo ($this->error($e->getCode(), $e->getMessage()));
            return false;
        }
    }
    
    // 自动关注处理
    private function follow($uid, $follow)
    {
        // 是否已关注
        $followed_by = false;
        $friendships = $this->_oauth->get('friendships/show', array(
            'source_id' => $uid,
            'target_id' => $follow
        )); // 关注的微博UID
        
        if (isset($friendships['target'])) {
            if (isset($friendships['target']['followed_by'])) {
                $followed_by = $friendships['target']['followed_by'];
            }
        }
        if (! $followed_by) {
            // 关注一个用户
            $parameter2 = array();
            $parameter2['uid'] = $follow; // 关注的微博UID
            $rst2 = $this->_oauth->post('friendships/create', $parameter2, false);
            if (isset($rst2['error'])) {
                throw new Exception($rst2['error']);
            }
        }
    }
    
    // 微博分享处理
    private function share($uid, $content, $pic_url, $follow = "", $friendNum = 0, $friendNames = array(), $isBilateral = false)
    {
        $source = 1; // 微博来源
        if (! empty($friendNum) || ! empty($friendNames)) { // 获取朋友列表
            if (! empty($friendNum)) {
                $friends = $this->getWeiboFriendsByRand($uid, $friendNum, $isBilateral);
            } else 
                if (! empty($friendNames)) {
                    $friends = implode("，", $friendNames);
                }
            $content .= $friends; // @好友
        }
        
        $parameters = array();
        $parameters['status'] = mb_substr($content, 0, 280, 'utf-8');
        $parameters['visible'] = 0;
        if (! empty($pic_url)) { // 有图片
            $parameters['pic'] = '@' . $pic_url;
        }
        if (! empty($pic_url)) { // 有图片
            $rst = $this->_oauth->post('statuses/upload', $parameters, true); // 上传图片并发布一条新微博
        } else {
            // 发布一条新微博
            $rst = $this->_oauth->post('statuses/update', $parameters);
        }
        if (isset($rst['error'])) {
            throw new Exception($rst['error'], $rst['error_code']);
        }
        
        // 自动关注
        if (! empty($follow)) { // 关注的微博UID
            $this->follow($uid, $follow);
        }
        
        // 特殊的业务逻辑代码开始
        // 特殊的业务逻辑代码结束
        
        return $rst;
    }
    
    // 获取微博用户信息
    private function getUser($uid)
    {
        $cacheKey = md5("weibo_user_" . $uid);
        $cache = Zend_Registry::get('cache');
        $userInfo = $cache->load($cacheKey);
        
        if (empty($userInfo)) {
            $userInfo = $this->_oauth->get('users/show', array(
                'uid' => $uid
            ));
            if (isset($userInfo['error'])) {
                throw new Exception($userInfo['error'], $userInfo['error_code']);
            } else {
                $cache->save($userInfo, $cacheKey); // 利用zend_cache对象缓存查询出来的结果
            }
        }
        return $userInfo;
    }
    
    // 获取微博好友列表
    private function getFriends($uid)
    {
        $cacheKey = md5("weibo_friendsList_" . $uid);
        $cache = Zend_Registry::get('cache');
        $friends = $cache->load($cacheKey);
        
        if (empty($friends)) {
            // 获取用户的关注列表
            $rst = $this->_oauth->get('friendships/friends', array(
                'uid' => $uid
            ));
            if (isset($rst['error'])) {
                throw new Exception($rst['error'], $rst['error_code']);
            }
            $friends = array();
            if (! empty($rst['users'])) {
                foreach ($rst['users'] as $user) {
                    $friends[] = $user;
                }
                $cache->save($friends, $cacheKey); // 利用zend_cache对象缓存查询出来的结果
            }
        }
        return $friends;
    }
    
    // 获取微博互相关注好友列表
    private function getBilateralFriends($uid)
    {
        $cacheKey = md5("weibo_bilateralfriendsList_" . $uid);
        $cache = Zend_Registry::get('cache');
        $friends = $cache->load($cacheKey);
        
        if (empty($friends)) {
            // 获取用户的关注列表
            $rst = $this->_oauth->get('friendships/friends/bilateral', array(
                'uid' => $uid,
                'count' => 200
            ));
            if (isset($rst['error'])) {
                throw new Exception($rst['error']);
            }
            $friends = array();
            if (! empty($rst['users'])) {
                foreach ($rst['users'] as $user) {
                    $friends[] = $user; // '@'.$user['screen_name'];
                }
                $cache->save($friends, $cacheKey); // 利用zend_cache对象缓存查询出来的结果
            }
        }
        return $friends;
    }
    
    // 获取随机@好友
    private function getWeiboFriendsByRand($uid, $friendNum, $isBilateral = false)
    {
        // 获取微博好友列表
        $friends = $this->getFriendsByCondition($uid, $isBilateral);
        $comma_separated = "";
        if (! empty($friends)) {
            srand((float) microtime() * 10000000);
            $rand_keys = array_rand($friends, $friendNum);
            $rand_friends = array();
            foreach ($rand_keys as $key) {
                $rand_friends[] = '@' . $friends[$key]['screen_name'];
            }
            $comma_separated = implode("，", $rand_friends);
        }
        return $comma_separated;
    }
    
    // 按条件获取粉丝
    private function getFriendsByCondition($uid, $isBilateral = false)
    {
        if (! $isBilateral) {
            $result = $this->getFriends($uid);
        } else {
            $result = $this->getBilateralFriends($uid);
        }
        return $result;
    }
}

