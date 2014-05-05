<?php
// 酌情考虑该参数是否开启，如果遇到CC请关闭此参数，默认关闭，有特殊需求再开启
ignore_user_abort(false);
// 计算脚本的执行时间与CPU使用时间，仅限linux系统使用
if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
    if (! isset($_SERVER["REQUEST_TIME_FLOAT"]))
        $_SERVER["REQUEST_TIME_FLOAT"] = microtime(true);
    $systemInfo = getrusage();
    defined('PHP_CPU_RUSAGE') || define('PHP_CPU_RUSAGE', $systemInfo["ru_utime.tv_sec"] + $systemInfo["ru_utime.tv_usec"] / 1e6);
}

defined('APPLICATION_PATH') || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));
defined('APPLICATION_ENV') || define('APPLICATION_ENV', 'development'); // [production|development]

set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/../library'),
    get_include_path()
)));

if (APPLICATION_ENV === 'production') {
    require 'iWebsite2/define.php';
}
require 'Zend/Application.php';

// 自动加载系统类库
include_once (APPLICATION_PATH . '/../vendor/autoload.php');

$application = new Zend_Application(APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini');
$application->bootstrap()->run();
