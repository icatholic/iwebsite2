<?php

class iWebsite_View_Helper_GetResourceUrl extends Zend_View_Helper_Abstract
{

    /**
     * 获取当前Modules下的资源的URL
     *
     * @access public
     * @param string $dirName 子目录名
     * @param boolean $absUrl 是否要绝对地址
     * @return string URL
     */
    public function getResourceUrl($dirName = null, $absUrl = false)
    {
        $font = Zend_Controller_Front::getInstance();
        $request = $font->getRequest();
        $module = $request->getModuleName();
        if ($module == 'default') {
            $module = 'pc';
        } elseif ($module == 'mobile') {
            $module = 'm';
        }
        $baseUrl = $font->getBaseUrl();
        $resourceUrl = "$baseUrl/html/$module/";
        
        // 分析该目录下的子目录
        if (! is_null($dirName)) {
            $resourceUrl .= $dirName . '/';
        }
        $resourceUrl = str_replace('//', "/", $resourceUrl);
        if ($absUrl) {
            $scheme = $request->getScheme();
            $host = $request->getHttpHost();
            $resourceUrl = "{$scheme}://{$host}{$resourceUrl}";
        }
        return $resourceUrl;
    }
}