<?php

class Weixin_PageController extends Zend_Controller_Action
{

    private $_weixin;

    private $_user;

    private $_app;

    private $_config;

    public function init()
    {
        $this->getHelper('viewRenderer')->setNoRender(true);
        $this->_config = Zend_Registry::get('config');
        $this->_user = new Weixin_Model_User();
        $this->_app = new Weixin_Model_Application();
        $this->_appConfig = $this->_app->getToken();
        $this->_weixin = new Weixin\Client();
        if (! empty($this->_appConfig['access_token'])) {
            $this->_weixin->setAccessToken($this->_appConfig['access_token']);
        }
    }

    public function indexAction()
    {
        $id = $this->_request->getParam('id', null);
        if ($id == null) {
            $this->getHelper('viewRenderer')->setNoRender(true);
        }
        
        $page = (new Weixin_Model_Page())->findOne(array(
            '_id' => new MongoId($id)
        ));
        
        if ($page != null) {
            $this->view->assign('title', isset($page['title']) ? $page['title'] : '');
            $this->view->assign('image', isset($page['picture']) ? $page['picture'] : '');
            $this->view->assign('date', date("Y-m-d", $page['__CREATE_TIME__']->sec));
            $this->view->assign('content', isset($page['content']) ? $page['content'] : '');
            $this->view->assign('weixin_id', isset($this->_appConfig['weixin_id']) ? $this->_appConfig['weixin_id'] : '');
            $this->view->assign('weixin_name', isset($this->_appConfig['weixin_name']) ? $this->_appConfig['weixin_name'] : '');
        }
    }
}