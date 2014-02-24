<?php 
class iWebsite_Plugin_Files extends Zend_Controller_Plugin_Abstract
{
    function postDispatch(Zend_Controller_Request_Abstract $request) {
        //从配置文件中获取
        $config    = Zend_Registry::get('config');
        $status    = isset($config['global']['cdn']['status']) ? intval($config['global']['cdn']['status']) : 0;
        $cdnDomain = isset($config['global']['cdn']['domain']) ? $config['global']['cdn']['domain'] : 'http://scrm.umaman.com';
        
        if($status===1) {
            //替换所有页面中src="http://scrm.umaman.com/"为特定的cdn分发域名
            $body = $this->getResponse()->getBody();
    
            //正则替换页面中的全部url路径信息，仅限图片、视频等文件
            //$regexVar = 'src[\s|\n|\t|\r]*=[\s|\n|\t|\r]*[\"|\']http://scrm.umaman.com/soa/(?:image|file2|file)';
            $regexVar = 'http://scrm.umaman.com/soa/(?:image|file2|file)/get/id/';
            $body = preg_replace_callback("#$regexVar#im",function($matchs) use($cdnDomain) {
                return str_ireplace('http://scrm.umaman.com', $cdnDomain, $matchs[0]);
            },$body);
            
            $this->getResponse()->setBody($body);
        }
    }
}