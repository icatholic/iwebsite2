<?php

/**
 * 微博--测试
 * @author 郭永荣
 *
 */
class Weibo_TestController extends iWebsite_Controller_Action
{

    public function init()
    {
        parent::init();
        $this->getHelper('viewRenderer')->setNoRender(true);
    }

    public function indexAction()
    {
        $this->getHelper('viewRenderer')->setNoRender(false);
        
        $module = $this->getRequest()->getModuleName();
        $controller = $this->getRequest()->getControllerName();
        $action = $this->getRequest()->getActionName();
        $config = Zend_Registry::get("config");
        $this->view->assign('config', $config);
        $this->view->assign('module', $module);
        $this->view->assign('controller', $controller);
        $this->view->assign('action', $action);
        
        $appid = "53e84ca9499619f45d8b4593";
        $this->view->assign('appid', $appid);
        
        $uid = $this->get('uid');
        $signkey = $this->get('signkey');
        $timestamp = $this->get('timestamp');
        if (! empty($uid)) {
            $app = new Weibo_Model_Application();
            $appConfig = $app->getInfoById($appid);
            if ($signkey != sha1($uid . "|" . $appConfig['secretKey'] . "|" . $timestamp )) {
                die('UID is wrong');
            }
        }
    }

    public function applicationAction()
    {
        try {
            $app = new Weibo_Model_Application();
            // 获取设置
            $appConfig = $app->getConfig();
            print_r($appConfig);
            die('aaaaaaaaaa');
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }
}

