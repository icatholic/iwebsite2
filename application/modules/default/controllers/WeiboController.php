<?php
class Default_WeiboController extends Zend_Controller_Action
{
	public function indexAction()
	{
		$module = $this->getRequest()->getModuleName();
		$controller = $this->getRequest()->getControllerName();
		$action = $this->getRequest()->getActionName();
		$config = Zend_Registry::get("config");
		$this->view->assign('config' , $config);
		$this->view->assign('module' , $module);
		$this->view->assign('controller' , $controller);
		$this->view->assign('action', $action);
	}

}

 