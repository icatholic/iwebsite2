<?php

class Exchange_IndexController extends iWebsite_Controller_Action
{
    
    public function init()
    {
        
    }
    
    public function indexAction()
    {
//         $strUserId = 'a';
//     	$oRule = new Exchange_Model_Rule();
//     	try {
//     	   $arrayRule = $oRule->getRuleByPrizeId('539130324996199e238b4591');
//     	   if(isset($arrayRule['score']) && $arrayRule['score'])   //需要积分
//     	   {
//     	   	   if(class_exists('Score_Model_User'))
//     	   	   {
//     	   	   	   $oUserScore = new Score_Model_User($strUserId);
//     	   	   	   if($oUserScore->getScore()<$arrayRule['score'])
//     	   	   	   {
    	   	   	   
//     	   	   	   }
//     	   	   }
//     	   }
//     	}
//     	catch (Exception $e)
//     	{
//     		echo $e->getMessage();
//     		echo $e->getCode();
//     	}
//     	var_dump($arrayRule);exit;
        $strUserId = $this->get('user_id','');
        $oExchangeRule = new Exchange_Model_Rule();
        $oExchangeSuccess = new Exchange_Model_Success();
        $arrayRule = $oExchangeRule->exchangeNow(0);
        $this->view->rule = $arrayRule;
        $this->view->list = $oExchangeSuccess->getPrizeList($strUserId);
        $this->view->user = $strUserId;
    }
    
    public function exAction()
    {
        $strUserId = $this->get('user_id','');
        $strRuleId = $this->get('rule','');
        $nQuantity = $this->get('quantity',1);
        $oRule = new Exchange_Model_Rule();
        $arrayResult = $oRule->exchange($strUserId, $strRuleId,$nQuantity);
        if(isset($arrayResult['result_code']) && $arrayResult['result_code'] == 0)
        {
            $this->_redirect('/exchange/?user_id='.$strUserId);
        }
        else 
        {
        	echo $arrayResult['msg'];
        	echo '<br><a href="/exchange/?user_id='.$strUserId.'">兑换奖品</a>';
        }
        exit;
    }
}