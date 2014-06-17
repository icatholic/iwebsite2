<?php
class Question_Model_Question  extends iWebsite_Plugin_Mongo
{
    protected $name = 'iQuestionnaire_question';
    protected $dbName = 'question';
    
    private $nTrueTimes = 0;
    private $nFalseTimes = 0;
    private $nScore = 0;
    
    private $arrayNaire;
    
    /**
     * 随机获取题目
     * @param array $arrayQuery 筛选条件
     * @param int $number		数量
     * @param array $arrayData		返回数据
     */
    public function getQuestion($arrayQuery = array(),$number = 1,$arrayData = array())
    {
    	$nRand = rand(0, 100);
    	$arrayQuery['$or'] = array(array('weighted'=>array('$gte'=>$nRand)),array('weighted'=>0));
    	$arrayResult = $this->find($arrayQuery);
    	if($arrayResult['total'])
    	{
    		$nTmp = array_rand($arrayResult['datas']);    //随机
    		$_id = $arrayResult['datas'][$nTmp]['_id']->__toString();
    		if(!isset($arrayData[$_id]))
    		{
    			$oOption = new Question_Model_Option();
    			$arrayOptions = $oOption->findAll(array('question_id'=>$_id),array('key'=>1),array('key'=>true,'option'=>true));
    			$arrayResult['datas'][$nTmp]['options'] = $arrayOptions;
    			$arrayData[$_id] = $arrayResult['datas'][$nTmp];
    			if(!isset($arrayQuery['_id']))
    				$arrayQuery['_id'] = array('$nin'=>array());
    			$arrayQuery['_id']['$nin'][] = $_id;
    			$this->update(array('_id'=>$_id), array('$inc'=>array('use_times'=>1)));
    		}
    	}
    	if(count($arrayData)>=$number)
    	{
    		return $arrayData;
    	}
    	else
    		return $this->getQuestion($arrayQuery,$number,$arrayData);
    }
    
    /*
    * 验证答题	//暂不考虑填空题
    * @para	m string $_id MONGODB ID
    * @param string $strUserAnswer	用户答案
    *
    * */
    public function checkAnswer($questionId,$strUserAnswer)
    {
        
    	$arrayReturn = array('result'=>false,'score'=>0);
    	$arrayQuestion = $this->findOne(array('_id'=>$questionId));
    	$arrayUserAnswer = array();
    	if(isset($arrayQuestion['answer']) && $arrayQuestion['answer'] != '')	//无正确答案，默认都正确
    	{
	    	$arrayUserAnswer = str_split(strtoupper($strUserAnswer)); //大写 数组 用户答案
	    	asort($arrayUserAnswer);		//排序
	    	$strUserAnswer = implode('', $arrayUserAnswer);
	    	if(strtoupper($arrayQuestion['answer']) == $strUserAnswer)
	    	{
	    	    $this->nTrueTimes++;
	    		$arrayReturn['result'] = true;
	    		$arrayReturn['score'] = $arrayQuestion['score'];
	    	}
	    	else 
	    	{
	    		$this->nFalseTimes++;
	    	}
    	}
    	else
    	{
    	    if($arrayQuestion['answer'] == '')
    	    {
    	        $arrayUserAnswer = array($strUserAnswer);
    	    }
    		$arrayReturn['result'] = true;
    		$arrayReturn['score'] = $arrayQuestion['score'];
    		$this->nTrueTimes++;
    	}
    	$this->nScore+=$arrayReturn['score'];
    	
    	$strField = $arrayReturn['result']?'true_times':'false_times';
    	$this->update(array('_id'=>$questionId),array('$inc'=>array($strField=>1)));
    	
    	if($this->arrayNaire['is_count_option'])   //统计被选项次数
    	{
        	$oOption = new Question_Model_Option();
        	foreach ($arrayUserAnswer as $key => $val)
        	{
        		$oOption->addTimes($val, $questionId);
        	}
    	}
    	return $arrayReturn;
    }
    
    /*
     * $arrayAnswer 用户答案
     * $userId 用户ID
     * $naireId 问卷ID
     * 
     * */
    public function answer($arrayAnswer,$userId,$naireId,$randId)
    {
        $oNaire = new Question_Model_Naire();
        $this->arrayNaire = $oNaire->findOne(array('_id'=>$naireId));
    	foreach ($arrayAnswer as $key => $val)
    	{
    		$arrayReturn[] = $this->checkAnswer($key, $val);
    	}
    	
    	if($randId)
    	{
    		$oRand = new Question_Model_Rand();
    		$oRand->update(array('_id'=>$randId),array('$set'=>array('is_finish'=>true,'finish_time'=>new MongoDate())));
    	}
    	
    	$oAnswer = new Question_Model_Answer();
    	$arrayData = array();
    	$arrayData['user_id'] = $userId;
    	$arrayData['naire_id'] = $naireId;
    	$arrayData['rand_id'] = $randId;
    	$arrayData['answer'] = json_encode($arrayAnswer);
    	$arrayData['score'] = $this->nScore;
    	$arrayData['correct'] = $this->nTrueTimes.'/'.count($arrayAnswer);
    	$arrayData = $oAnswer->insertRef($arrayData);
    	
    	unset($arrayData['answer']);
    	return $arrayData;
    }
}