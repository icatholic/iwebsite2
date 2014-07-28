<?php
class Score_TestController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_helper->viewRenderer->setNoRender(true);
    }

    public function indexAction()
    {
        $this->_helper->viewRenderer->setNoRender(false);

        $modelScore= new Score_Model_User();
        $users = $modelScore->findAll(array());

        $modelRule = new Score_Model_Rule();
        $rules = $modelRule->findAll(array());

        $this->view->assign('users',$users);
        $this->view->assign('rules',$rules);
    }

    public function resultAction()
    {
        $this->_helper->viewRenderer->setNoRender(false);
        $mobile = trim($this->_request->get('mobile'));

        $modelScore= new Score_Model_User();
        $current_user = $modelScore->findOne(array('mobile'=>$mobile));

        $modelScoreDetail = new Score_Model_Detail();
        $results = $modelScoreDetail->findAll(array('mobile'=>$mobile,'rule_name'=>array('$exists'=>true)));

        $this->view->assign('current_user',$current_user);
        //var_dump($results);
        $this->view->assign('results',$results);
    }

}