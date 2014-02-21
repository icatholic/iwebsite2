<?php
class iWebsite_View_Helper_GetResourceUrl extends Zend_View_Helper_Abstract
{	
	/**
	 * 获取当前Modules下的资源的URL
	 *
	 * @access public
	 * @param string $dirName 子目录名
	 * @return string    URL
	 */
	public function getResourceUrl($dirName = null) 
	{
		$font = Zend_Controller_Front::getInstance();
		$request = $font->getRequest();
		$module = $request->getModuleName();
		if($module=='default'){
			$module = 'pc';
		}elseif($module=='mobile'){
			$module = 'm';
		}
	    $baseUrl =$font->getBaseUrl();
	    $resourceUrl =  "$baseUrl/html/$module/";
	    
		//分析该目录下的子目录
		if (!is_null($dirName)) {
			$resourceUrl .= $dirName . '/';
		}
		return str_replace('//', "/", $resourceUrl);
	}
}