<?php

class Admin_ErrorController extends iWebsite_Controller_Admin_Action
{

    public function init()
    {
        parent::init();
        $this->disableLayout();
    }

    public function errorAction()
    {
        $errors = $this->_getParam('error_handler');
        $this->view->request = $errors->request;
        $links = array();
        if (count($links) == 0) {
            $links[0]['text'] = '返回上一页';
            $links[0]['href'] = 'javascript:history.go(-1)';
        }
        $this->view->ur_here = '系统信息';
        $this->view->msg_detail = $errors->exception->getMessage();
        $this->view->msg_type = 0;
        $this->view->links = $links;
        $this->view->default_url = $links[0]['href'];
        $this->view->auto_redirect = false;
        // Clear previous content
        $this->getResponse()->clearBody();
        $this->_helper->viewRenderer('message');
    }

    public function messageAction()
    {
        $request = $this->getRequest();
        $params = $request->getParams();
        $this->view->ur_here = $params['ur_here'];
        $this->view->msg_detail = $params['msg_detail'];
        $this->view->msg_type = $params['msg_type'];
        $this->view->links = $params['links'];
        $this->view->default_url = $params['default_url'];
        $this->view->auto_redirect = $params['auto_redirect'];
    }
}



