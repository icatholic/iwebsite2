<?php
class Integral_Model_RefereeDetailTrack extends iWebsite_Plugin_Mongo
{
    protected $name = 'Referee_detail_track';
    protected $dbName = 'integral';
    
    //处理
    public function handle($integralIdentity,$fromMobile,$ruleInfo)
    {
    	$get_by=$ruleInfo['name'];
    	$integral=$ruleInfo['integral'];
    	$data=array();
    	$data['integral_identity_id']=$integralIdentity['_id'];
    	$data['integral']=$integral;
    	$data['get_time']=date('Y-m-d H:i:s');
    	$data['get_by']=$get_by;
    	$data['fromMobile']=fromMobile;
    	$info=$this->insert($data);
    	return $info;
    }
    
    //获取当月推荐次数
    public function getMonthlyNum($integralIdentity)
    {
    	$query=array();
    	$query['integral_identity_id']=$integralIdentity['_id'];
    	$now = date("Y-m-d H:i:s");
    	$query['get_time']=array('$lte'=>$now,'$gte'=>$now);
    	$num = $this->count($query);
    	return $num;
    }
    
}