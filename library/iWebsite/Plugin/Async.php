<?php 
class iWebsite_Plugin_Async extends Zend_Controller_Plugin_Abstract
{
    public function __destruct() {
        @fastcgi_finish_request();
        //执行全部异步请求操作
        if(SoapClientSocketsRegistry::isRegistered('idbAsync')) {
            $asyncs = SoapClientSocketsRegistry::get('idbAsync');
            if(is_array($asyncs)) {
                foreach($asyncs as $async) {
                    if($async instanceof SoapClientAsync)
                        $async->wait();
                }
            }
            SoapClientSocketsRegistry::_unsetInstance();
        }
    }
    
}