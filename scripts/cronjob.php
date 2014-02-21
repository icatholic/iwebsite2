<?php
/**
 * 使用方法
 * 
 * php cronjob.php controller=index action=123 param1=1 key=2
 * 
 * 执行相应的方法即可
 * 
 */
defined('APPLICATION_PATH') || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));
defined('APPLICATION_ENV') || define('APPLICATION_ENV', 'production');
set_include_path(
    implode(
        PATH_SEPARATOR, 
        array(
            realpath(APPLICATION_PATH . '/../library'), 
            get_include_path()
        )
    )
);

require 'Zend/Application.php'; 

$application = new Zend_Application(APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini');
$application->bootstrap(
    array('Config','Loader','MongoDB','Front','CronjobRouter','FrontController','DbAsyncPlugin')
)->run();
