<?php
/**
 * 微信商城--测试
 * @author 郭永荣
 *
 */
class Weixinshop_TestController extends Zend_Controller_Action
{
    public function init()
    {
        $this->getHelper('viewRenderer')->setNoRender(true);
    }

    public function indexAction() {
        $obj = new iWeixinOauth('wx907e2f9c52a7df08','d2c7fd365cc9386bf23bb94d7857f29b','http://guotaiweixin.umaman.com/weixin/test/index','snsapi_userinfo');
        if(isset($_GET['code'])) {
            echo 'has code';
            $obj->getAccessToken();
            var_dump($obj->getUserInfo());
        }
        else {
            $obj->getCode();
            echo 'get code';
        }  
        echo 'finish';
    }
    
    
    public function goodsAction() {
    	try {
    		$modelGoods = new Weixinshop_Model_Goods();
    		$id="5367056f4a961948378b4584";
    		$info = $modelGoods->getInfoById($id);
    		print_r($info);
    		
    		$gid="goo1";
    		$info = $modelGoods->getInfoByGid($gid);
    		print_r($info);
    		
    		die('aaaaaaaaaaa');
    		$gnum=1;
    		$modelGoods->getList();
    		$modelGoods->hasStock($gid, $gnum);
    		
    		$out_trade_no= uniqid();
    		$modelGoods->subStock($out_trade_no, $gid, $gnum);
    		
    	} catch (Exception $e) {
    	}
    }
}

