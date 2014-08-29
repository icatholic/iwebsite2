<?php

/**
 * 后台用户管理
 */
class Admin_UserController extends iWebsite_Controller_Admin_Action
{

    public function init()
    {
        parent::init();
    }
    
    /* ------------------------------------------------------ */
    // -- 退出登录
    /* ------------------------------------------------------ */
    public function logoutAction()
    {
        try {
            $modelUser = new Admin_Model_User();
            $modelUser->clearCookies();
            $url = $this->_helper->url("login");
            $this->_redirect($url);
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /* ------------------------------------------------------ */
    // -- 登陆界面
    /* ------------------------------------------------------ */
    public function loginAction()
    {
        try {
            $this->disableLayout();
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /* ------------------------------------------------------ */
    // -- 验证登陆信息
    /* ------------------------------------------------------ */
    public function signinAction()
    {
        try {
            $modelUser = new Admin_Model_User();
            $input = $this->getLoginFilterInput();
            
            if ($input->isValid()) {
                /* 检查密码是否正确 */
                $userInfo = $modelUser->checkLogin($input->username, $input->password);
            } else {
                $messageInfo = $this->_getValidationMessage($input);
                throw new Exception($messageInfo);
            }
            // 登陆处理
            $modelUser->login($userInfo);
            
            // 登录成功
            if (intval($input->remember)) {
                $modelUser->storeInCookies($userInfo);
            }
            
            $url = $this->_helper->url("index", "index");
            $this->_redirect($url);
        } catch (Exception $e) {
            throw $e;
        }
    }

    protected function getLoginFilterInput()
    {
        $options = array(
            'presence' => 'optional',
            'allowEmpty' => true
        );
        
        $filters = array(
            '*' => 'StringTrim'
        );
        $validators = array(
            'username' => array(
                'NotEmpty',
                'messages' => array(
                    0 => 'You must enter your username.'
                )
            ),
            'password' => array(
                'NotEmpty',
                'messages' => array(
                    0 => 'You must enter your password.'
                )
            ),
            'captcha' => array(),
            'remember' => array(
                'Int',
                'default' => 0
            )
        );
        
        $data = $this->getRequest()->getParams();
        $input = new Zend_Filter_Input($filters, $validators, $data, $options);
        
        return $input;
    }
}