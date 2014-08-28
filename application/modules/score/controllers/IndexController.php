<?php
class Score_IndexController extends Zend_Controller_Action
{
	
	public function init()
	{
		$this->_helper->viewRenderer->setNoRender(true);
	}

	public function indexAction()
	{
		//echo "hello world";
        //var_dump($this);
        //$id = intval($_GET['id']);
        //echo $id;

        //http://www.iwebsite.com/score/index/index?FromUserName=1233&rule_id=1
        //1.获取用户信息 可以是微信OpenID 用户名等来确定用户唯一性
        $FromUserName ='';
        $username = '';
        //积分规则id
        $rule_id='';

        $username = $_GET['username'];
        //echo $username;
        $rule_name = $_GET["rule_name"];
        //echo $rule_name;

        //从积分机制集合获取规则
        $modelScore =new Score_Model_User();

        //检查用户是否存在
        $check_user = $modelScore->count(array('name'=>$username));
        if($check_user==0){
            echo "用户不存在";
            $modelScore->insert(array('name'=>$username));
        }else{
            echo "用户存在";
        }


        //2.按积分机制给用户积分
        //修改用户kenneth的积分
        //$modelScore->scoreByName('kenneth','签到机制');
        //$modelScore->scoreByName('kenneth','游戏积点机制');
        $modelScore->scoreByName($username,$rule_name);

	}

    public function testAction()
    {
        //注册奖励
        //http://www.iwebsite.com/score/index/test?mobile=13388889999&rule_id=53733dcc499619366b8b456c
        //血糖日记
        //http://www.iwebsite.com/score/index/test?mobile=13388889999&rule_id=5374290a4a961993218b4597
        //$rule_id = '53733dcc499619366b8b456c';
        //$modelRule = new Score_Model_Rule();
        //$rule = $modelRule->findOne(array('_id'=>$rule_id));
        //var_dump($rule);

        $mobile = trim($this->_request->get('mobile'));
        $rule_id = trim($this->_request->get('rule_id'));

        $modelScore =new Score_Model_User();
        $modelScore->scoreByMobile($mobile,$rule_id);

        $this->_redirect("/score/test/result?mobile=".$mobile);
    }

    public function exchangeAction()
    {
        //积分兑换使用
    }

    public function managerAction()
    {
        //查询用户积分流水
    }

    public function demoAction()
    {

    }

}