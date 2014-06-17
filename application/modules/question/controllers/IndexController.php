<?php
class Question_IndexController extends iWebsite_Controller_Action
{
	private $_question;	//题库
	private $_naire;	//问卷
	
	private $naireCode;	//问卷编号
	private $userId;	//用户唯一标识
	
    public function init()
    {
    	$this->getHelper('viewRenderer')->setNoRender(true);
    	$this->_naire = new Question_Model_Naire();
    	
    	$this->naireCode = $this->get('code','');
    	$this->userId = $this->get('user_id','');
    	
    	if($this->naireCode == '' || $this->userId == '')
    	{
    		exit('ERR NULL');
    	}
    }
    
    public function indexAction()
    {
    	echo 'index';exit;
    }
    
    
    //获取题目
    public function getAction()
    {
       $this->_helper->viewRenderer->setNoRender(false);
       try {
            $arrayQuestion = $this->_naire->getQuestion($this->naireCode,$this->userId);
            $this->view->question = $arrayQuestion;
            $this->view->naire = $this->naireCode;
            $this->view->user = $this->userId;
       }catch (Exception $e)
       {
       	echo $e->getMessage();exit;
       }
            
    }
    
    //用户答题
    public function answerAction()
    {
        $oQuestion = new Question_Model_Question();
        $arrayAnser = $this->get('answer',array());
        foreach ($arrayAnser as $key => $val)
        {
        	if(is_array($val))
        	{
        		$arrayAnser[$key] = implode('', $val);
        	}
        }
        $randId = $this->get('randId','');
        
        $result = $oQuestion->answer($arrayAnser,$this->userId,$this->naireCode,$randId);
        var_dump($result);exit;
    	
    }
    
    public function initqAction()
    {
    	
    }
}

