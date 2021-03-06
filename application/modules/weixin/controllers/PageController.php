<?php

class Weixin_PageController extends Zend_Controller_Action
{

    private $_weixin;

    private $_app;

    private $_appInfo;

    public function init()
    {
        try {
            $this->_app = new Weixin_Model_Application();
            $this->_appInfo = $this->_app->getApplicationInfo();
        } catch (Exception $e) {
            var_dump(exceptionMsg($e));
        }
    }

    public function indexAction()
    {
        try {
            $id = $this->_request->getParam('id', null);
            if ($id == null) {
                $this->getHelper('viewRenderer')->setNoRender(true);
            }
            $modelPage = new Weixin_Model_Page();
            $page = $modelPage->findOne(array(
                '_id' => new MongoId($id)
            ));
            
            if ($page != null) {
                $this->view->assign('title', isset($page['title']) ? $page['title'] : '');
                $this->view->assign('image', isset($page['picture']) ? $page['picture'] : '');
                $this->view->assign('date', date("Y-m-d", $page['__CREATE_TIME__']->sec));
                $this->view->assign('content', isset($page['content']) ? $page['content'] : '');
                $this->view->assign('weixin_id', isset($this->_appInfo['weixin_id']) ? $this->_appInfo['weixin_id'] : '');
                $this->view->assign('weixin_name', isset($this->_appInfo['weixin_name']) ? $this->_appInfo['weixin_name'] : '');
            }
        } catch (Exception $e) {
            var_dump(exceptionMsg($e));
        }
    }
}