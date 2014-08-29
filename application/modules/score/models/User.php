<?php
class Score_Model_User  extends iWebsite_Plugin_Mongo
{
    protected $name = 'iScore_user';
    protected $dbName = 'score';
    protected $strUserId;
    protected $arrayUser;
    protected $nSourceCode;
    
    public function __construct($strUserId,$nSourceCode = 0)
    {
        parent::__construct();
    	$this->strUserId = $strUserId;
    	$this->nSourceCode = $nSourceCode;
    	$this->arrayUser = $this->findOne(array('user_id'=>$this->strUserId,'source_code'=>$nSourceCode));
    	
    	
    }
    
    //获得用户积分
    public function  getScore()
    {
    	if(isset($this->arrayUser['score']))
    	    return $this->arrayUser['score'];
    	else
    	    return 0;
    }
    
    //获取用户所有积分
    public function getAllScore()
    {
        $oSource = new Score_Model_Source();
        $arraySource = $oSource->getSource();
        $arrayReturn = array();
    	$arrayScore = $this->findAll(array('user_id'=>$this->strUserId));
    	foreach ($arrayScore as $key => $val)
    	{
    		$arrayReturn[$val['source_code']] = array('score'=>$val['score'],'source'=>$arraySource[$val['source_code']]);
    	}
    	return $arrayReturn;
    }
    //消费积分
    public function reduceScore($nStore,$strReason)
    {
        if($nStore>0)
        {
        	if($this->getScore() >= $nStore)
        	{
        		$this->update(array('user_id'=>$this->strUserId,'source_code'=>$this->nSourceCode),array('$inc'=>array('score'=>-$nStore,'score_use'=>$nStore)));
        		$oDetail = new Score_Model_Detail();
        		$oDetail->addOne($this->strUserId, -$nStore, $strReason);
        		return true;
        	}
        	else 
        	{
        		throw new Exception('积分不足',801);
        		return false;
        	}
        }
        else
        {
        	throw new Exception('积分错误',802);
        	return false;
        }
    }
    
    //添加积分
    public function addScore($nStore,$strReason)
    {
        if($nStore>0)
        {
            $this->addUser();
            $this->update(array('user_id'=>$this->strUserId,'source_code'=>$this->nSourceCode),array('$inc'=>array('score'=>$nStore,'score_total'=>$nStore)));
            $oDetail = new Score_Model_Detail();
            $oDetail->addOne($this->strUserId, $nStore, $strReason,$this->nSourceCode);
            $this->arrayUser['score'] = $this->arrayUser['score']+$nStore;
            $this->arrayUser['score_total'] = $this->arrayUser['score_total']+$nStore;
            
            return $this->arrayUser;
        }
        else
        {
        	throw new Exception('积分错误',802);
        	return false;
        }
    }
    
    //添加新用户
    public function addUser()
    {
    	if(!isset($this->arrayUser['user_id']) )
    	{
    		$arrayData = array();
    		$arrayData['user_id'] = $this->strUserId;
    		$arrayData['source_code'] = $this->nSourceCode;
    		$arrayData['score'] = 0;
    		$arrayData['score_total'] = 0;
    		$this->arrayUser = $this->insertRef($arrayData);
    	}
    }
    
}