<?php
class Integral_IndexController extends iWebsite_Controller_Action
{
    public function init()
    {
    	parent::init();
    	$this->getHelper('viewRenderer')->setNoRender(false);
    }
	
    /**
	 * 微信注册画面
	 *
	 * */
	public function registerAction()
	{
		try {
			
		}
		catch (Exception $e) {
			exit($this->response(false,$e->getMessage()));
		}
	}
	
	/**
	 * 微信注册处理
	 *
	 * */
	public function handleRegisterAction()
	{
		try {
			$FromUserName = trim($this->get('FromUserName'));//微信号
			$name = trim($this->get('name'));//注册用户名
			if(empty($name)) {
				throw new Exception('名字不能为空');
			}
			$mobile = trim($this->get('mobile'));//注册用手机
			if(empty($mobile)) {
				throw new Exception('手机号码不能为空');
			}
			if(!isValidMobile($mobile)) {
				throw new Exception('手机格式不正确');
			}
			//判断手机是否已使用
			$modelMember = new Integral_Model_MemberInfo();
			$is_registed = $modelMember->is_registed($mobile);
			if($is_registed){
				throw new Exception('该手机号码已被其他会员使用，请重新输入另外的手机号');
			}
			
			$referee_mobile = trim($this->get('referee_mobile'));//推荐人手机
			if(!empty($referee_mobile)){	
				if(!isValidMobile($referee_mobile)) {
					throw new Exception('推荐人手机格式不正确');
				}
				if($mobile ==$referee_mobile)
				{
					throw new Exception('推荐人手机号码不能和注册用的手机号码相同');
				}
			}
			//会员注册处理
			$modelMember->registMember($mobile, $name, $referee_mobile, $FromUserName);
		}
		catch (Exception $e) {
			exit($this->response(false,$e->getMessage()));
		}
	}
	
	/**
	 * 会员信息画面
	 *
	 * */
	public function infoAction()
	{
		try {
				
		}
		catch (Exception $e) {
			exit($this->response(false,$e->getMessage()));
		}
	}
	
	/**
	 * 发送验证码
	 *
	 * */
	public function sendVcodeAction()
	{
		try {
			//获得参数
			$mobile = trim($this->get('mobile'));//手机
			if(empty($mobile)) {
				throw new Exception('手机号码不能为空');
			}
			if(!isValidMobile($mobile)) {
				throw new Exception('手机格式不正确');
			}
			//生成验证码
			$vcode = createRandVCode();
			//发送手机短信
			$formvars['uid'] = 'zgtgg';
			$formvars['pwd'] = 'catholic';
			$formvars['mobile'] = $mobile;
			$message = "验证码为{$vcode},请在5分钟内注册,否则会失效。";
			$formvars['msg'] =  mb_convert_encoding($message,"GB2312", "UTF-8");
			$info = doPost("http://www.smsadmin.cn/smsmarketing/wwwroot/api/post_send/",$formvars);
				
			exit($this->response(true,'验证码发送成功',$vcode));
		} catch (Exception $e) {
			exit($this->response(false,$e->getMessage()));
		}
	}
	
	/**
	 * 验证手机号码
	 *
	 * */
	public function validateMobileAction()
	{
		try {
			//获得参数
			$mobile = trim($this->get('mobile'));//手机
			if(empty($mobile)) {
				throw new Exception('手机号码不能为空');
			}
			if(!isValidMobile($mobile)) {
				throw new Exception('手机格式不正确');
			}
			//判断手机是否已使用
			$modelMember = new Integral_Model_MemberInfo();
			$is_registed = $modelMember->is_registed($mobile);
			if($is_registed){
				throw new Exception('该手机号码已被其他会员使用，请重新输入另外的手机号');
			}
			exit($this->response(true,'验证成功'));
		} catch (Exception $e) {
			exit($this->response(false,$e->getMessage()));
		}
	}
	
	/**
	 * 验证验证码
	 *
	 * */
	public function validateVcodeAction()
	{
		try {
			//获得参数
			$vcode = trim($this->get('vcode'));//手机
			if(empty($vcode)) {
				throw new Exception('验证码不能为空');
			}
			if(empty($_SESSION["codevalue"]))
			{
				throw new Exception('验证码还未获得');
			}
			if(strtolower($_SESSION["codevalue"]) == strtolower($vcode))
			{
				throw new Exception('验证码不正确');
			}
			exit($this->response(true,'验证成功'));
		} catch (Exception $e) {
			exit($this->response(false,$e->getMessage()));
		}
	}

	/**
	 * 验证推荐人手机
	 *
	 * */
	public function validateRefereeMobileAction()
	{
	try {
			//获得参数
			$mobile = trim($this->get('mobile'));//注册用手机
			$referee_mobile = trim($this->get('referee_mobile'));//推荐人手机
			if(!isValidMobile($referee_mobile)) {
				throw new Exception('推荐人手机格式不正确');
			}
			if($mobile ==$referee_mobile)
			{
				throw new Exception('推荐人手机号码不能和注册用的手机号码相同');
			}
			exit($this->response(true,'验证成功'));
		} catch (Exception $e) {
			exit($this->response(false,$e->getMessage()));
		}
	}	

}

