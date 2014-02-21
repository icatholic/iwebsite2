<?php
class Lottery_Model_SmsLog extends iWebsite_Plugin_Mongo
{
    protected $name = 'sms_log';
    protected $dbName = 'lottery_sample';
    
    public function sendSms($user_id,$user_name,$mobile,$message)
    {
    	$params = array(
    			'amount'=>'guotai',
    			'password'=>'^guotai!@#^',
    			'telephonenum'=>$mobile,
    			'message'=>$message);
    	$url = 'http://sm.laiyifen.com/sendmessage.do';
    	$result = doPost($url,$params);
    	$result = json_decode($result,true);
    	
    	$data =array('user_id'=>$user_id,
    					'user_name'=>$user_name,
    					'mobile'=>$mobile,'message'=>$message,
    					'state'=>$result['state'],'result'=>$result['message']);
    	$this->insertAsync($data);
    }
}