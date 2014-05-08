<?php

class Admin_Model_User extends iWebsite_Plugin_Mongo
{

    protected $name = 'iWeixinPay_AdminUser';

    protected $dbName = 'weixinshop';

    /**
     * 根据ID获得用户信息
     *
     * @param string $id            
     * @return array
     */
    public function getUserById($id)
    {
        $userInfo = $this->findOne(array(
            "_id" => myMongoId($id)
        ));
        return $userInfo;
    }

    /**
     * 是否已经注册过
     *
     * @param string $username            
     * @return boolean
     */
    public function isRegisted($username)
    {
        $num = $this->count(array(
            "username" => $username
        ));
        return ($num > 0);
    }

    /**
     * 根据用户username获得用户信息
     *
     * @param string $username            
     * @return array
     */
    public function getUserByUsername($username)
    {
        $result = $this->findOne(array(
            "username" => $username
        ));
        return $result;
    }

    /**
     * 登陆处理
     *
     * @param array $user            
     * @return array
     */
    public function login($user)
    {
        $_SESSION['admin_id'] = myMongoId($user['_id']);
        $_SESSION['admin_name'] = $user['username'];
        $_SESSION['last_check'] = ""; // 用于保存最后一次检查订单的时间
        
        $options = array(
            "query" => array(
                "_id" => $user['_id']
            ),
            "update" => array(
                '$set' => array(
                    "lastip" => getIp(),
                    "lasttime" => new MongoDate()
                ),
                '$inc' => array(
                    'times' => 1
                )
            ),
            "new" => true
        );
        $return_result = $this->findAndModify($options);
        $userData = $return_result["value"];
        
        return $userData;
    }

    /**
     * 注册处理
     *
     * @param string $username            
     * @param string $password            
     * @return array
     */
    public function registUser($username, $password)
    {
        $userData = array();
        $userData['username'] = $username;
        $userData['password'] = $password;
        $userData['lastip'] = getIp();
        $userData['lasttime'] = new MongoDate();
        $userData['times'] = 1;
        $userData = $this->insert($userData);
        return $userData;
    }

    /**
     * 处理用户登录和注册
     *
     * @param string $username            
     * @param string $password            
     * @return array
     */
    public function handle($username, $password)
    {
        // 用户数据登陆
        $userinfo = $this->getUserByUsername($username);
        if (empty($userinfo)) {
            // 注册用户
            return $this->registUser($username, $password);
        } else {
            // 登录处理
            return $this->login($userinfo);
        }
    }

    /**
     * 检查用户有效性
     * 
     * @param string $username            
     * @param string $password            
     * @throws Exception
     * @return array
     */
    public function checkLogin($username, $password)
    {
        /* 检查密码是否正确 */
        $query = array();
        $query['username'] = $username;
        $query['password'] = ($password);
        $userInfo = $this->findOne($query);
        if (empty($userInfo)) {
            throw new Exception("用户名或密码有误");
        }
        return $userInfo;
    }

    /**
     * 存入COOKIES
     *
     * @param array $userInfo            
     */
    public function storeInCookies($userInfo)
    {
        $time = time() + 3600 * 24 * 365;
        setcookie('ECSCP[admin_id]', myMongoId($userInfo['_id']), $time, "/admin/");
        setcookie('ECSCP[admin_pass]', md5($userInfo['password']), $time, "/admin/");
    }
    
    /**
     * 清空COOKIES
     */
    public function clearCookies()
    {
        /* 清除cookie */
        setcookie('ECSCP[admin_id]', '', time() - 3600, "/admin/");
        setcookie('ECSCP[admin_pass]', '', time() - 3600, "/admin/");
    }
}