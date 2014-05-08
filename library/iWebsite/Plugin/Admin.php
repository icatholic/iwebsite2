<?php

class iWebsite_Plugin_Admin extends Zend_Controller_Plugin_Abstract
{

    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $module = strtolower($request->getModuleName());
        $actionName = strtolower($request->getActionName());
        
        $front = Zend_Controller_Front::getInstance();
        $plugin = $front->getPlugin("Zend_Controller_Plugin_ErrorHandler");
        $plugin->setErrorHandlerModule($module)
            ->setErrorHandlerController('error')
            ->setErrorHandlerAction('error');
        
        if ($module == 'admin') {
        }
        
        // 针对管理系统的的权限设定
        if ($module == 'admin') {
            $config = Zend_Registry::get('config');
            try {
                /* 验证管理员身份 */
        		/* session 不存在，检查cookie */
        		if (empty($_SESSION['admin_id']) && $actionName != 'login' && $actionName != 'signin' && $actionName != 'forgetpwd' && $actionName != 'resetpwd' && $actionName != 'captcha' && $actionName != 'checkorder') {
                    $this->validateAdminUserInfo();
                }
            } catch (Exception $e) {
                /* 清除cookie */
                $adminUser = new Admin_Model_User();
                $adminUser->clearCookies();
                $this->getResponse()->setRedirect($config['global']['path'] . "admin/user/login");
            }
        }
    }
    
    /* 验证管理员身份 */
    protected function validateAdminUserInfo()
    {
        /* cookie不存在 */
        if (empty($_COOKIE['ECSCP']['admin_id'])) {
            throw new Exception('未登陆');
        }
        
        // 验证cookie信息
        $adminUser = new Admin_Model_User();
        $user = $adminUser->getUserById($_COOKIE['ECSCP']['admin_id']);
        if (empty($user)) {
            throw new Exception('用户不存在');
        }
        
        // 检查密码是否正确
        if (md5($user['password']) == $_COOKIE['ECSCP']['admin_pass']) {
            $adminUser->login($user);
        } else {
            throw new Exception("密码不正确");
        }
    }

    public function __destruct()
    {}
}