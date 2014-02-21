<?php
/**
 * 新浪微博接口API
 * @author Young
 * @version 1.0
 * @date 2013-04-05
 *
 * 使用方法：
 * $o = new Plugin_Weibo('微博项目ID');
 * 设置缓存
 * $o->setCache();
 * 获取微博列表的信息
 * $o->getWeibos($projectId);
 */

include_once 'iSina.php';
include_once 'iWeibo2.php';

class Plugin_Weibo
{
	private $cache_key = "";
	
	public function __construct() {
		$this->cache_key = md5("cache_weibo".uniqid());
		$this->setCache();
	}
	
	public function setCache($lifetime=300,$cache_dir="")
	{
		$frontendOptions = array(
				'caching' => true,
				'lifetime' => $lifetime,
				'automatic_serialization' => true
		);
		if(empty($cache_dir))
		{
			$cache_dir=APPLICATION_PATH.'/../cache/';
		}
		$backendOptions = array(
				'cache_dir' => $cache_dir //放缓存文件的目录
		);
		// 取得一个Zend_Cache_Core 对象
		$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
		Zend_Registry::set($this->cache_key, $cache);
	}
	
	private function getCache()
	{
		if(Zend_Registry::isRegistered($this->cache_key))
		{
			$cache = Zend_Registry::get($this->cache_key);
		}else{
			throw new ErrorException("Cache未指定");
		}
		return $cache;
	}
	
	protected function getKeywords($projectId)
	{
		$cache = $this->getCache();
		$cacheKey = md5($projectId."Keywords");
		if (($keywords = $cache->load($cacheKey)) === false) {
			$o = new iWeibo($projectId);
			$keywords = array();
			foreach($o->getKeywords() as $row) {
				$keywords[] = $row['_id'];
			}
			$cache->save($keywords, $cacheKey);//利用zend_cache对象缓存查询出来的结果
		}
		return $keywords;
	}
	
	/**
	 *
	 * 获取微博列表的信息
	 *
	 * @param string $projectId
	 * @param array $keywords
	 * @param array $tag 微博标签筛选，数组型可以多个标签复合
	 * @param string $original  是否只显示原创微博 1为只显示原创内容 空为显示全部
	 * @param string $hasPic  是否微博必须包含图片  1包含 空表示全部
	 * @param string $search  微博中必须包含指定词语
	 * @param string $nickname 指定微博昵称
	 * @param string $startTime 截取指定时间段内的微博 开始时间 格式为date("Y-m-d H:i:s")
	 * @param string $endTime 截取指定时间段内的微博 开始时间 格式为date("Y-m-d H:i:s")
	 * @param string $order  排序
	 * @param int $start
	 * @param int $limit
	 * @return array 返回数组
	 *
	 */
    public function getWeibos($projectId,
    		array $keywords= array(), 
    		array $tag= array(),
    		$original = "",$hasPic = '',$search = '',
    		$nickname = '',$startTime = '',$endTime = '',
    		$order ='createTime', $start =0,$limit =10,$functionNames=array()) {
    	
    	try {
    		$cache = $this->getCache();
    		
    		//获得所有的Keywords
    		if(empty($keywords)){
    			$keywords = $this->getKeywords($projectId);
    		}
    		
    		$cacheKey = md5($projectId.implode(",", $keywords).implode(",", $tag).$original.$hasPic.$search.$nickname.$startTime.$endTime.$order.$start.$limit);
    		if (($rst = $cache->load($cacheKey)) === false) {
    			$condition = array(
    					'tag'=>$tag,  //微博标签筛选，数组型可以多个标签复合
    					'original'=>$original,  //是否只显示原创微博 1为只显示原创内容 空为显示全部
    					'hasPic'=>$hasPic,     //是否微博必须包含图片  1包含 空表示全部
    					'search'=>$search,    //微博中必须包含指定词语
    					'nickname'=>$nickname,  //指定微博昵称
    					'startTime'=>$startTime, //截取指定时间段内的微博 开始时间 格式为date("Y-m-d H:i:s")
    					'endTime'=>$endTime,   //截取指定时间段内的微博 开始时间 格式为date("Y-m-d H:i:s")
    					'debug'=>false);
    			
    			$o = new iWeibo($projectId,$keywords);
    			$orderby=array('order'=>$order,'by'=>-1);
    			$limit=array('start'=>$start,'limit'=>$limit);
    			$weiboList = $o->getWeibos($condition,$orderby,$limit);
    			 
    			$client = new iSina($projectId);
    			$cacheKey4UmaId = md5($projectId."umaId");
    			if (($umaId = $cache->load($cacheKey4UmaId)) === false) {
    				$tokenList = $client->getAccessTokenList(1);
    				$token = array_shift($tokenList);
    				$umaId= $token['_id'];
    				$cache->save($umaId, $cacheKey4UmaId);//利用zend_cache对象缓存查询出来的结果
    			}
    			
    			$rst = array();
    			foreach ($weiboList as $weibo) {
    				//获取微博的tags标签
    				//$weibo['Tags'] = $o->getWeiboTags($weibo['_id']);
    				if(!empty($functionNames))
    				{
    					foreach ($functionNames as $funcname) {
    						$weibo[$funcname['key']] = call_user_func($funcname['funcname'], $weibo);
    					}
    				}
    				//$userInfo = $client->getUserInfo($umaId, $weibo['nickname']);
    				$mid = empty($weibo['mid'])?$weibo['postId']:$weibo['mid'];
    				//通过mid获取id
					if(!is_numeric($mid)) {
						$result = $client->get($umaId, 'statuses/queryid', array('mid' => $mid, 'type' => '1', 'isBase62' => '1'));
						$weiboId = $result['id'];
					}
					else {
						$weiboId = $mid;
					}
					//根据ID获取单条微博信息
					$sina_weibo = $client->get($umaId,'statuses/show',array('id'=>$weiboId));
					$userInfo = $sina_weibo['user'];
    				$rst[] = array('weibo' => $weibo, 'userInfo' => $userInfo, 'sina_weibo' => $sina_weibo);
    			}
    			$cache->save($rst, $cacheKey);//利用zend_cache对象缓存查询出来的结果
    		}
    		return array("success"=>true,"result"=>$rst,"msg"=>"取得成功");
    	} catch (Exception $e) {
    		return array("success"=>false,"result"=>null,"msg"=>$e->getMessage());
    	}
    }
}