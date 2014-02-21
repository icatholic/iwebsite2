<?php 
class iWebsite_Plugin_Weixin extends Zend_Controller_Plugin_Abstract
{
    function postDispatch(Zend_Controller_Request_Abstract $request) {
        $module = strtolower($request->getModuleName());
        if($module=='weixin') {
            $body = $this->getResponse()->getBody();
            if(!empty($_REQUEST['FromUserName'])) {
                $extraArray = array();//可进行自定义扩展
                $extraArray['FromUserName'] = isset($_REQUEST['FromUserName']) ? $_REQUEST['FromUserName'] : '';
                $filters = array('jpg','jpeg','png','js','css','gif','mp3');//过滤指定后缀名URL
                $regex = "(?:http|https|ftp|ftps)://(?:[a-zA-Z0-9\-]*\.)+[a-zA-Z0-9]{2,4}(?:/[a-zA-Z0-9=.\?&\-\%/_,]*)?";
                //为外链增加相应的需要传递的微信变量
                $body = preg_replace_callback("#$regex#im",function($matchs) use($extraArray,$filters) {
                    $url = $matchs[0];
                    $parseUrl = parse_url($url);
                    if(isset($parseUrl['path'])) {
                        $tmp = explode('.', $parseUrl['path']);
                        if(!in_array(end($tmp),$filters)) {
                            $replace = strpos($url, '?')===false ? $url.'?'.http_build_query($extraArray) : $url.'&'.http_build_query($extraArray);
                            return $replace;
                        }
                        else {
                            return $url;
                        }
                    }
                    else {
                        return $url;
                    }
                },$body);
            }
            
            $template = array();
            if(Zend_Registry::isRegistered('weixinTemplate')) {
                $template = array_merge($template,Zend_Registry::get('weixinTemplate'));
            }

            //编写代码获取相应的数组模块
            //替换规则 {$a}=>$template['a']  {$abc}=>$template['abc']  
            //推荐在之前代码中注册weixinTemplate然后执行替换，不推荐这里进行数据处理
            
            
            
            //编写代码结束
            
            //正则替换变量
            $regexVar = '{\$([a-z0-9_\-]+)}';
            $body = preg_replace_callback("#$regexVar#im",function($matchs) use($template) {
                $var = $matchs[0];
                $key = $matchs[1];
                return isset($template[$key]) ? $template[$key] : $var;
            },$body);
            
            $this->getResponse()->setBody($body);
        }
    }
}