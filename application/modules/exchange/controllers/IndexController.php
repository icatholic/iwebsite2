<?php

class Exchange_IndexController extends iWebsite_Controller_Action
{
    
    public function init()
    {
        
    }
    
    public function indexAction()
    {
        $strUserId = $this->get('user_id','');
        $oExchangeRule = new Exchange_Model_Rule();
        $oExchangeSuccess = new Exchange_Model_Success();
        $oScore = new Score_Model_User($strUserId);
        echo '您的当前积分:<br>';
        $arrayScore = $oScore->getAllScore();
        foreach ($arrayScore as $key => $val)
        {
        	echo $val['source'].':'.$val['score'].'<br>';
        }
        $arrayRule = $oExchangeRule->exchangeNow();
        $this->view->rule = $arrayRule;
        $this->view->list = $oExchangeSuccess->getPrizeList($strUserId);
        $this->view->user = $strUserId;
    }
    
    public function exAction()
    {
        $strUserId = $this->get('user_id','');
        $strRuleId = $this->get('rule','');
        $nQuantity = $this->get('quantity',1);
        $nQuantity = $nQuantity?$nQuantity:1;
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