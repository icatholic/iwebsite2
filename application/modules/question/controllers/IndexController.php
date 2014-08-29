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
    	
    }
    
    public function indexAction()
    {
        $this->getHelper('viewRenderer')->setNoRender(false);
        try {
            $oUserBind = new User_Model_Bind();
        	$FromUserName = $this->get('FromUserName','');
        	$mobile = $this->get('mobile','');
        	if($mobile == '')
        	{
            	$this->userId = $oUserBind->getIdByWeixin($FromUserName);
            	$unique = '';      //唯一码
            	if($this->userId)  //如果已绑定，获取当前唯一码
            	{
            	    $oUserInfo = new User_Model_User();
            	    $arrayUserInfo = $oUserInfo->findOne(array('_id'=>$this->userId));
            	    $unique = $arrayUserInfo['unique'];
            	}
        	}
        	else
        	{
        		$this->userId = $oUserBind->getIdByWeixin($FromUserName);
        		if(!$this->userId)
        		{
        		    $oCompUser = new User_Model_Weixin();
        		    $arrayWeixin = array('openid'=>$FromUserName);
        		    $arrayData = $oCompUser->add($arrayWeixin,$mobile);
        			$this->userId = $arrayData['_id'];
        			
        		}
        		$oScore = new Score_Model_User($this->userId);
        		$oScore->addScore(1, '登录');
        		
//                 $arrayQuestion = $this->_naire->getQuestion($this->naireCode,$this->userId);
                
        		$this->redirect('/question/index/get/?user_id='.$this->userId.'&code='.$this->get('code',''));
        		exit;
        	}
    	}catch (Exception $e)
    	{
    		echo $e->getMessage();exit;
    	}
    	
    	$this->view->FromUserName = $FromUserName;
    	$this->view->unique = $unique;
    }
    
    public function index2Action()
    {
        $this->getHelper('viewRenderer')->setNoRender(false);
        $mobile = $this->get('mobile','');
        if(trim($mobile) != '')
        {
            try {
                $other = $this->get('other','');
            	$arrayOther = array('other_id'=>$other);
            	$oCompUser = new User_Model_Other();
            	$oUserBind = new User_Model_Bind();
            	$this->userId = $oUserBind->getIdByOther($other);
            	if(!$this->userId)
            		$this->userId = $oCompUser->add(array('other_id'=>$other),$mobile);
            	$oScore = new Score_Model_User($this->userId);
            	$oScore->addScore(1, '登录');
            	$this->redirect('/question/index/get/?user_id='.$this->userId.'&code='.$this->get('code',''));
            	exit;
            }catch (Exception $e)
            {
            	echo $e->getMessage();exit;
            }
        }
    }
    
    //获取题目
    public function getAction()
    {
       $this->_helper->viewRenderer->setNoRender(false);
       
       $this->userId = $this->get('user_id','');
       $this->naireCode = $this->get('code','');
       
//        $this->userId = $this->get('user_id','');
       try {
            $oScore = new Score_Model_User($this->userId,1);
            $arrayQuestion = $this->_naire->getQuestion($this->naireCode,$this->userId);
            
            $this->view->score = $oScore->getScore();
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
        $userId = $this->get('user_id','');
        $naireCode = $this->get('code','');
        $result = $oQuestion->answer($arrayAnser,$userId,$naireCode,$randId);
        
        $oScoreUser = new Score_Model_User($userId,1);
        $arrayScoreUser = $oScoreUser->addScore($result['score'], '问卷答题');
        echo '您的当前积分为：'.$arrayScoreUser['score'].'<br>';
        echo '<a href="/exchange/?user_id='.$userId.'">兑换奖品</a><br>';
        echo '<a href="/question/index/get?user_id='.$userId.'&code='.$naireCode.'">继续答题</a><br>';
        exit;
    	
    }
    
    public function initqAction()
    {
    	
    }
}

