<?php
class Question_Model_Naire  extends iWebsite_Plugin_Mongo
{
    protected $name = 'iQuestionnaire';
    protected $dbName = 'question';
    
    public function getQuestion($naireId,$userId)
    {
        $arrayNaire = $this->findOne(array('_id'=>$naireId));
        $oQuestion = new Question_Model_Question();
        
        if($arrayNaire)
        {
        	$arrayReturn = array();
        	$arrayReturn['randId'] = '';
        	if(!$arrayNaire['is_rand'])	//非随机，获取所有题目
        	{
        		$arrayQuestion = $oQuestion->findAll(array('naire_id'=>$naireId));
        		$arrayQuestion = $arrayQuestion['datas'];
        	}
        	else //随机获得N题，组成问卷
        	{
        		$oRand = new Question_Model_Rand();
        		$arrayQuestion = $oRand->get($naireId, $userId);
        		if($arrayQuestion === false)	//没有未完成的随机问卷
        		{
        			$nCount = $oQuestion->count(array('naire_id'=>$naireId));
        			if($nCount>$arrayNaire['rand_number'])	//随机数必须小于总数量
        			{
        				$arrayQuestion = $oQuestion->getQuestion(array('naire_id'=>$naireId),$arrayNaire['rand_number']);
        			}
        			else
        			{
        				$arrayQuestion = $oQuestion->findAll(array('naire_id'=>$naireId));
        				$arrayQuestion = $arrayQuestion['datas'];
        			}
        			$arrayReturn['randId'] = $oRand->add($naireId, $userId,$arrayQuestion);
        		}
        		else 
        		{
        		    $arrayReturn['randId'] = $arrayQuestion['_id']->__toString();
        		    
        		    $arrayQuestion = json_decode($arrayQuestion['question'],true);
        		}
        	}
        	$arrayReturn['question'] = $arrayQuestion;
        	return $arrayReturn;
//         	echo $this->result(true,'',$arrayReturn);
        }
        else
        {
        	throw new Exception('问卷不存在',701);
        	return false;
        }
    }
}