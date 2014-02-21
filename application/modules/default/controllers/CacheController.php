<?php

class Default_CacheController extends Zend_Controller_Action
{

    public $_cache;

    public function init ()
    {
        $this->_cache = Zend_Registry::get('cache');
    }

    public function indexAction ()
    {
        
        if (($view = $this->_cache->load('key')) === false) {
            echo 'cache missing';
            $a = 123;
            $b = 456;
            $this->view->assign('a', $a . time());
            $this->view->assign('b', $b . time());
            var_dump($this->view);
            var_dump($this->_cache->save($this->view));
        }
        else {
            echo 'cache ok';
            $this->view = $view;
        }
        $this->view->assign('c', time());
    }
    
    public function demoAction()
    {
    	//演示1 没有缓存的样子，每次请求画面都是动态变化的
    	//演示2 改变处理过程的时间为10s sleep(10);
    	//假如说某个页面的处理过程需要花费10s才能完成
    	//这个时候我们就要考虑性能优化的问题了
    	$this->view->startTime ="process start at: ".date("Y-m-d H:i:s");
    	//sleep(3);
    	$this->view->endTime ="process end at: ".date("Y-m-d H:i:s");
    }
    
	public function coreAction()
    {
		//演示1 core的用法，5s后缓存失效		
    	//数据缓存设定
    	$frontendOptions = array(
    			'caching' => true,
    			'lifetime' => 5,//5 秒过期
    			'automatic_serialization' => false //true
    	);
    	
    	//使用文件系统作为缓存
    	$backendOptions = array(
    			'hashed_directory_level'=>2
    	);
    	$cache = Zend_Cache::factory('Core', 'File', $frontendOptions,$backendOptions);
    	
    	//5秒之后，缓存失效
    	// we assume you already have $cache    	
    	$id = 'myData'; // cache id of "what we want to cache"
    	$cache_flag = "cache hit:";
    	if ( ($data = $cache->load($id)) === false ) {
    		// cache miss
    		$cache_flag = "cache miss:";
    		$data = date("Y-m-d H:i:s");
    		$cache->save($data,$id);
    	}
    	$this->view->currentTime =$cache_flag.$data;
    }
    
	//演示 页面部分缓存
    //没有做缓存
    public function outputnocacheAction()
    {
		//演示 没有缓存的时候
    	$this->view ->changedPart  =  'This is never cached ('.date("Y-m-d H:i:s").').';
    }
    
    //页面部分缓存，直接在view中处理
    public function outputinviewAction()
    {
    	//数据缓存设定
    	$frontendOptions = array(
    			'caching' => true,
    			'lifetime' => 10,
    			'automatic_serialization' => true
    	);
    	
    	//使用文件系统作为缓存
    	$backendOptions = array(
    			'hashed_directory_level'=>2
    	);
    	$cache = Zend_Cache::factory('Output', 'File', $frontendOptions,$backendOptions);
    	$this->view ->cache = $cache;
    	
    	//业务1的部分需要缓存,10秒后失效
    	$this->view ->dataList1 = range(1,20);//可以考虑数据缓存
    	
    	//一直变动的部分
    	$this->view ->changedPart  =  'This is never cached ('.date("Y-m-d H:i:s").').';
    	
    	//业务2的部分需要缓存,10秒后失效
    	$this->view ->dataList2 = range(21,40);//可以考虑数据缓存
    	//$this->_helper->viewRenderer->setNeverRender(true);
    }
    
    //页面部分缓存，直接在action中处理
    public function outputinactionAction()
    {
    	//数据缓存设定
    	$frontendOptions = array(
    			'caching' => true,
    			'lifetime' => 10,
    			'automatic_serialization' => true
    	);
    	
    	//使用文件系统作为缓存
    	$backendOptions = array(
    			'hashed_directory_level'=>2
    	);
    	$cache = Zend_Cache::factory('Output', 'File', $frontendOptions,$backendOptions);
    	
    	//业务1
    	if (!($cache->start('part1'))) {
    		$this->view ->dataList1 = range(1,20);
    		echo $this->view->partial('cache/output_part.phtml',array('dataList'=>$this->view ->dataList1));     		 
    		echo 'This is cached ('.date("Y-m-d H:i:s").') '."<br/>";
    		$cache->end(); // output buffering ends
    	 }
    	 
    	$this->view ->changedPart  =  'This is never cached ('.date("Y-m-d H:i:s").').';
    	echo $this->view->partial('cache/outputinaction.phtml',array('changedPart'=>$this->view ->changedPart));
    	
    	//业务2
    	if (!($cache->start('part2'))) {
    		$this->view ->dataList2 = range(21,40);
    		echo $this->view->partial('cache/output_part.phtml',array('dataList'=>$this->view ->dataList2));
    		echo 'This is cached ('.date("Y-m-d H:i:s").') '."<br/>";
    		$cache->end(); // output buffering ends
    	}
    	$this->_helper->viewRenderer->setNeverRender(true);
    }
    
    public function funcAction()
    {
		//演示1 缓存没有生效的时候，画面应该要10s才显示，画面上的时间相差10s才对 
		//      将以上画面重新刷新，就会看到画面很快就显示，画面上的时间几乎相同 说明缓存起作用
		//演示2 改变$params=array("one", "two");-->$params=array("1", "2"); 发现缓存失效，说明函数的参数的改变会影响缓存
		//演示3 增加'lifetime' => 10设置，10s之后 发现缓存失效
    	//cache_by_default	Boolean	TRUE	 if TRUE, function calls will be cached by default
    	//cached_functions	Array	 	function names which will always be cached
    	//non_cached_functions	Array	 	function names which must never be cached
    	//数据缓存设定
    	$frontendOptions = array(
				//'caching' => true,
    			//'lifetime' => 10,//演示3
    			'cache_by_default' => true,
    			'cached_functions' => array('foobar')
    	);
    	
    	//使用文件系统作为缓存
    	$backendOptions = array(
    			'hashed_directory_level'=>2
    	);
    	$cache = Zend_Cache::factory('Function', 'File', $frontendOptions,$backendOptions);
    	
		$this->view->startTime ="process start at: ".date("Y-m-d H:i:s");
		
    	$params=array("one", "two");
		//演示2 当改变参数的时候，应该cache失效才对
		//$params=array("3", "1");
    	$result = $cache->call('foobar', $params);		
		$this->view->result = $result;
    	//echo "Note: You can pass any built in or user defined function with the exception of array(), echo(), empty(), eval(), exit(), isset(), list(), print() and unset().<br/>";
    	//$this->_helper->viewRenderer->setNeverRender(true);
    	$this->view->endTime ="process end at: ".date("Y-m-d H:i:s");
    }
    
    public function classAction()
    {
    	//数据缓存设定
    	$op = $this-> getRequest()->getParam('op','static');
    	if($op=='static'){
	    	$frontendOptions = array(
					//'lifetime' => 10,//演示3
	    			'cached_entity' => 'foo', // The name of the class
	    	);
    	}else{
	    	//object
	    	$frontendOptions = array(
					//'lifetime' => 10,//演示3
	    			'cached_entity' => new foo(), // The name of the class
	    	);
    	}
    	//使用文件系统作为缓存
    	$backendOptions = array(
    			'hashed_directory_level'=>2
    	);
		
		$this->view->startTime ="process start at: ".date("Y-m-d H:i:s");
		
    	$cache = Zend_Cache::factory('Class', 'File', $frontendOptions,$backendOptions);
    	if($op=='static'){
    		$result = $result = $cache->foobar1('1', '2');
    	}else{
    		$result = $result = $cache->foobar2('1', '2');
    	}
    	$this->view->result = $result;
    	//$this->_helper->viewRenderer->setNeverRender(true);
		$this->view->endTime ="process end at: ".date("Y-m-d H:i:s");
    }
    
    public function fileAction()
    {
    	//演示1 第一次请求的生成缓存，第二次请求的缓存就生效了
		//演示2 当文件acl.xml 发生改变的时候，缓存会失效
    	//数据缓存设定
    	$frontendOptions = array(
    			'master_files' => array(APPLICATION_PATH."/configs/acl.xml")
    	);
    	//使用文件系统作为缓存
    	$backendOptions = array(
    			'hashed_directory_level'=>2
    	);
    	$cache = Zend_Cache::factory('File', 'File', $frontendOptions,$backendOptions);
    	// we assume you already have $cache    	
    	$id = 'config_file'; // cache id of "what we want to cache"
    	if ( ($data = $cache->load($id)) === false ) {
    		// cache miss
    		echo 'This is cached at ('.date("Y-m-d H:i:s").').'."<br/>";    		
    		$data = date("Y-m-d H:i:s");
    		$cache->save($data,$id);
    	}
    	echo "now:".$data."<br/>";    	
    	$this->_helper->viewRenderer->setNeverRender(true);
    }
	
	public function pageAction()
    {
    }
}

function foobar($arg, $arg2) {
	sleep(10);
	return "foobar( $arg and $arg2)\n";
}

class foo {
	// Static method
	public static function foobar1($param1, $param2) {
		sleep(10);
		return "foobar1_return($param1, $param2)";
	}
	// object method
	public function foobar2($param1, $param2) {
		sleep(10);
		return "foobar2_return($param1, $param2)";
	}
}