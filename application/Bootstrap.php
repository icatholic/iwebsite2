<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{

    private $_front;

    protected function _initConst()
    {}

    protected function _initFront()
    {
        $this->_front = Zend_Controller_Front::getInstance();
    }
    
    // 对于Cli调用模式进行处理开始，比如执行计划任务等
    protected function _initCronjobRouter()
    {
        if (PHP_SAPI === 'cli') {
            $this->bootstrap('FrontController');
            $front = $this->getResource('FrontController');
            $front->setRouter(new iWebsite_Router_Cli());
            $front->registerPlugin(new iWebsite_Plugin_Cli());
        }
    }
    // 对于Cli调用模式进行处理结束
    protected function _initSession()
    {
        Zend_Session::setOptions(array(
            'strict' => 'on'
        ));
        if (isset($_GET['iWebSiteSessionId']) && strlen($_GET['iWebSiteSessionId']) >= 24) {
            Zend_Session::setId($_GET['iWebSiteSessionId']);
        }
        Zend_Session::start();
    }

    protected function _initConfig()
    {
        $this->_config = $this->getOptions();
        Zend_Registry::set('config', $this->_config);
        defined('HOST_URL') || define('HOST_URL', 'http://' . $_SERVER['HTTP_HOST'] . $this->_config['resources']['frontController']['baseUrl']);
    }

    protected function _initLoader()
    {
        require 'functions.php';
        requireDir(APPLICATION_PATH . '/../library/');
    }

    /**
     * 初始化核心缓存
     */
    protected function _initCacheCore()
    {
        // 数据缓存设定
        $frontendOptions = array(
            'caching' => $this->_config['global']['cache']['core'],
            'lifetime' => $this->_config['global']['cache_lifetime']['core'],
            'automatic_serialization' => true
        );
        
        if (APPLICATION_ENV === 'production') {
            $backendOptions = array(
                'servers' => array(
                    array(
                        'host' => MEMCACHED_SERVER_01,
                        'port' => MEMCACHED_SERVER_PORT_01,
                        'persistent' => false,
                        'weight' => 1
                    ),
                    array(
                        'host' => MEMCACHED_SERVER_02,
                        'port' => MEMCACHED_SERVER_PORT_02,
                        'persistent' => false,
                        'weight' => 1
                    )
                ),
                'compression' => true,
                'compatibility' => false
            );
        } else {
            $backendOptions = array(
                'hashed_directory_level' => 2
            );
        }
        
        if (APPLICATION_ENV == 'production') {
            // 集群环境请使用如下memcache配置
            $cache = Zend_Cache::factory('Core', 'Memcached', $frontendOptions, $backendOptions);
        } else {
            $cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
        }
        
        Zend_Registry::set('cache', $cache);
    }

    /**
     * 建立连接服务器的物理连接
     */
    protected function _initMongoDBConnect()
    {
        if (APPLICATION_ENV == 'production') {
            $options = array();
            $options['connectTimeoutMS'] = 60000;
            $options['socketTimeoutMS'] = 60000;
            $options['w'] = WRITE_CONCERN;
            $options['wTimeout'] = 60000;
            
            $mongos = array(
                MONGOS_DEFAULT_01,
                MONGOS_DEFAULT_02,
                MONGOS_DEFAULT_03
            );
            
            shuffle($mongos);
            $dnsString = 'mongodb://' . join(',', $mongos);
            
            $connect = new \MongoClient($dnsString, $options);
            $connect->setReadPreference(DEFAULT_READ_PERFORMANCE);
            Zend_Registry::set('mongoConnect', $connect);
        }
    }

    protected function _initMongoDB()
    {
        // 支持连接多个idb数据库
        $db = array();
        $default = new iDatabase('52dce281489619e902452b46', '11111111', '53059145489619c06a3dc01f');
        $db['default'] = $default;
        $db['weixin'] = $default;//微信
        $db['lottery'] = $default;
        $db['weixinshop'] = $default;//微信商城
        $db['iFreight'] = $default;
        $db['weixininvitation'] = $default;//微信邀请
        $db['weibo'] = $default;//微博
        $db['weixinsign'] = $default;//微信签到
        $db['weather'] = $default;//天气
        
        Zend_Registry::set('db', $db);
    }

    /**
     * 初始化缓存配置，提供三种缓存方式，请查阅相应的zend framework文档学习相应的细节
     *
     * 三种模式分别是：Page整页的缓存 Core通用性的数据缓存 Output局部页面缓存
     */
    protected function _initCache()
    {
        // 数据缓存设定
        $frontendOptions = array(
            'caching' => $this->_config['global']['cache']['core'],
            'lifetime' => $this->_config['global']['cache_lifetime']['core'],
            'automatic_serialization' => true
        );
        
        if (APPLICATION_ENV == 'production') {
            $backendOptions = array(
                'servers' => array(
                    array(
                        'host' => MEMCACHED_SERVER_01,
                        'port' => MEMCACHED_SERVER_PORT_01,
                        'persistent' => false,
                        'weight' => 1
                    ),
                    array(
                        'host' => MEMCACHED_SERVER_02,
                        'port' => MEMCACHED_SERVER_PORT_02,
                        'persistent' => false,
                        'weight' => 1
                    )
                ),
                'compression' => true,
                'compatibility' => false
            );
        } else {
            $backendOptions = array(
                'hashed_directory_level' => 2
            );
        }
        
        // 整站页面缓存设定
        $frontendOptions = array(
            'lifetime' => $this->_config['global']['cache_lifetime']['page'],
            'debug_header' => isset($_GET['debug_header']) ? $_GET['debug_header'] : false,
            'default_options' => array(
                'cache' => $this->_config['global']['cache']['page'],
                'cache_with_get_variables' => true,
                'make_id_with_get_variables' => true,
                'cache_with_post_variables' => true,
                'make_id_with_post_variables' => true,
                'cache_with_files_variables' => false,
                'make_id_with_files_variables' => false,
                'cache_with_cookie_variables' => false,
                'make_id_with_cookie_variables' => false,
                'cache_with_session_variables' => false,
                'make_id_with_session_variables' => false
            ),
            'regexps' => array(
                '^/' => array(
                    'cache' => $this->_config['global']['cache']['page']
                ),
                '^/cache/demo/' => array(
                    'cache' => false
                ),
                '^/cache/page/' => array(
                    'cache' => true
                )
            )
        );
        
        // 开启页面缓存
        if (APPLICATION_ENV == 'production') {
            $pageCache = Zend_Cache::factory('Page', 'Memcached', $frontendOptions, $backendOptions);
        } else {
            $pageCache = Zend_Cache::factory('Page', 'File', $frontendOptions, $backendOptions);
        }
        // 集群环境请使用如下memcache配置
        Zend_Registry::set('pageCache', $pageCache);
        
        // 启用output缓存
        if (APPLICATION_ENV == 'production') {
            $outputCache = Zend_Cache::factory('Output', 'Memcached', $frontendOptions, $backendOptions);
        } else {
            $outputCache = Zend_Cache::factory('Output', 'File', $frontendOptions, $backendOptions);
        }
        Zend_Registry::set('outputCache', $outputCache);
        
        $cache = Zend_Registry::get('cache');
        
        // 手动清空缓存
        if (isset($_GET['page_cache_clean_all']) && (isset($_GET['password']) && $_GET['password'] == date("ymdh"))) {
            switch ($_GET['page_cache_clean_all']) {
                case 'page':
                    $pageCache->clean(Zend_Cache::CLEANING_MODE_ALL);
                    break;
                case 'core':
                    $cache->clean(Zend_Cache::CLEANING_MODE_ALL);
                    break;
                case 'output':
                    $outputCache->clean(Zend_Cache::CLEANING_MODE_ALL);
                    break;
                default:
                    $cache->clean(Zend_Cache::CLEANING_MODE_ALL);
                    $pageCache->clean(Zend_Cache::CLEANING_MODE_ALL);
                    $outputCache->clean(Zend_Cache::CLEANING_MODE_ALL);
                    break;
            }
        }
        
        if (isset($_SERVER['REQUEST_URI'])) {
            $page_cache_clean = false;
            if (isset($_GET['page_cache_clean'])) {
                $page_cache_clean = true;
                unset($_GET['page_cache_clean']);
            }
            
            $cacheId = md5($_SERVER["HTTP_HOST"] . $_SERVER['REQUEST_URI'] . serialize($_GET) . serialize($_POST));
            
            // 手动清除单个页面的缓存
            if ($page_cache_clean) {
                $pageCache->remove($cacheId);
            }
            
            // 开启页面缓存处理流程
            $pageCache->start($cacheId);
        }
    }

    protected function _initQueue()
    {}

    protected function _initActionHelpers()
    {}
    
    // 控制URL路由规则
    protected function _initRouter()
    {
        $router = $this->_front->getRouter();
        // 重写规则示例
        // $router->addRoute(
        // 'redirect',
        // new Zend_Controller_Router_Route(
        // 'r/:id',
        // array('module' => 'soa', 'controller' => 'redirect', 'action' =>
        // 'index')
        // )
        // );
    }

    protected function _initLayout()
    {
        // 支持不同域名多模板Layout
        return Zend_Layout::startMvc();
    }

    protected function _initView()
    {
        // 支持不同域名多模板操作
    }
    
    // 控制前段显示模板的插件
    protected function _initPlugin()
    {
        // 注册前端控制插件
        $this->_front->registerPlugin(new iWebsite_Plugin_Front());
        
        // 注册移动设备判断插件
        $this->_front->registerPlugin(new iWebsite_Plugin_Device());
        
        // CDN路径替换
        $this->_front->registerPlugin(new iWebsite_Plugin_Files());
        
        // 权限控制
        $this->_front->registerPlugin(new iWebsite_Plugin_Privileges());
    }

    /**
     * 微信附件参数处理与微信内容变量替换
     */
    protected function _initWeixinTemplate()
    {
        if (! Zend_Registry::isRegistered('weixinTemplate'))
            Zend_Registry::set('weixinTemplate', array());
    }

    /**
     * 全文检索索引设定
     */
    protected function _initLucene()
    {
        Zend_Search_Lucene_Storage_Directory_Filesystem::setDefaultFilePermissions(0660);
        Zend_Search_Lucene_Analysis_Analyzer::setDefault(new iWebsite_Plugin_Lucene_Scws());
        Zend_Search_Lucene::setResultSetLimit(0); // 0表示无限制，全部返回
    }
}

