<?php

class Weather_IndexController extends iWebsite_Controller_Action
{

    public function init()
    {
        parent::init();
        $this->getHelper('viewRenderer')->setNoRender(true);
    }

    /**
     * 首页
     */
    public function indexAction()
    {
        $modelWeather = new Weather_Model_Weather();
        $weatherList = $modelWeather->getAllWeather();
        print_r($weatherList);
        die('aaaaaaaaaaaa');
        
        $weatherInfo = "晴|多云|阴|阵雨|雷阵雨|雷阵雨伴有冰雹|雨夹雪|小雨|中雨|大雨|暴雨|大暴雨|特大暴雨|阵雪|小雪|中雪|大雪|暴雪|雾|冻雨|沙尘暴|小雨转中雨|中雨转大雨|大雨转暴雨|暴雨转大暴雨|大暴雨转特大暴雨|小雪转中雪|中雪转大雪|大雪转暴雪|浮尘|扬沙|强沙尘暴|霾";
        $arrWeather = explode('|', $weatherInfo);
        foreach ($arrWeather as $key => $weather) {
            $weather_code=$key+1;
            $modelWeather->log($weather_code, $weather);
            	
        }
        
        die('bbbbbbbbbbb');
    	$modelSettings = new Weather_Model_Settings();
    	$weatherInfo = "晴|多云|阴|阵雨|雷阵雨|雷阵雨伴有冰雹|雨夹雪|小雨|中雨|大雨|暴雨|大暴雨|特大暴雨|阵雪|小雪|中雪|大雪|暴雪|雾|冻雨|沙尘暴|小雨转中雨|中雨转大雨|大雨转暴雨|暴雨转大暴雨|大暴雨转特大暴雨|小雪转中雪|中雪转大雪|大雪转暴雪|浮尘|扬沙|强沙尘暴|霾";
    	$arrWeather = explode('|', $weatherInfo);
    	foreach ($arrWeather as $key => $weather) {
    	    $weather_code=$key+1;
    	    $modelSettings->log($weather_code, $weather);
    	    
    	}    	
    	die('aaaaaaaaaaa');
    	
    }
    
    public function sceneAction()
    {
        $modelScene = new Weather_Model_Scene();
        $info = $modelScene->getInfoByWeather("晴");
        print_r($info);
        die('OK');
    }

}

