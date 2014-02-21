<?php

class Default_TestController extends Zend_Controller_Action
{

    public function init ()
    {
        $this->_helper->viewRenderer->setNoRender(true);
    }

    public function indexAction ()
    {
    	$groupList =array();
    	
    	
    	$list =array();
    	$list[] =array('allow_probability'=>100,'prize_name'=>'1');
    	$list[] =array('allow_probability'=>100,'prize_name'=>'2');
    	 
    	$list[] =array('allow_probability'=>1000,'prize_name'=>'3');
    	$list[] =array('allow_probability'=>1000,'prize_name'=>'4');
    	$list[] =array('allow_probability'=>1000,'prize_name'=>'5');
    	 
    	$list[] =array('allow_probability'=>2000,'prize_name'=>'6');
    	 
    	array_map(function($row) use (&$groupList){
    		$groupList["key".$row['allow_probability']][] = $row;
    	},$list);
    	
    	foreach ($groupList as $key => $row) {
    		shuffle($row);
    		$groupList[$key] = $row;
    	}
    	    		
    	print_r($groupList);
    	die('aaaaaaaaaaa');
    	
    	
    }

    public function testAction ()
    {
        $m = new Default_Model_Test();
        $datas = array();
        $datas['a'] = 123;
        $m->setDebug(true);
        $m->insert($datas);
    }

    public function test1Action ()
    {
        $d = new iDatabase('51ee42974996198b64deb712', 
                '1234567890abcdefghijklop');
        $d->setDebug(true);
        // $c = new iColor('51ee42974996198b64deb712',
        // '1234567890abcdefghijklop');
        // $c->setDebug(true);
        var_dump($d->findAll('product_images', array()));
    }

    public function sendAction ()
    {
        $o = new SocketSMS();
        $o->sendSMS();
    }

    public function sendHttpAction ()
    {
        $enterpriseid = "10657109041165"; // 10657109041165
        $accountid = '111';
        $timestamp = date("YmdHi");
        $password = "99_121314_luolai@*"; // "zaq12wsx";//
        $auth = md5($enterpriseid . $password . $timestamp);
        $params = array();
        $params['enterpriseid'] = $enterpriseid;
        $params['accountid'] = $accountid;
        $params['timestamp'] = $timestamp;
        $params['auth'] = $auth;
        $params['mobs'] = '18616613190';
        $params['msg'] = 'test';
        // $url = "http://211.136.163.68:9981/httpserver";
        // $url = "http://211.136.163.68:9981/qxt/index.jsp";
        $url = "http://211.136.163.68:8000/httpserver?";
        
        echo '<a href="' . $url . http_build_query($params) .
                 '" target="_blank">url</a>';
        
        echo '<br />';
        echo md5('1065710901234511111201101251837');
        // $resp = doGet($url,$params);
        // var_dump($resp);
    }

    public function file2Action ()
    {
        $iFile2 = new iFile2('51ee42974996198b64deb712', 
                '1234567890abcdefghijklop'); // 罗莱家纺
                                             // 先通过路径获取图片资源
        $url = "http://scrm.umaman.com/soa/image/get/id/520998c24796199e4569e273";
        if (filter_var($url, FILTER_VALIDATE_URL) !== false) {
            $client = new Zend_Http_Client($url, 
                    array(
                            'maxredirects' => 3,
                            'timeout' => 300
                    ));
            $response = $client->request('GET');
            if ($response->isError())
                throw new Exception($url . ', $response is error！');
            $imageContent = $response->getBody();
        } else {
            $imageContent = file_get_contents($url);
        }
        
        $image = $imageContent;
        $fileName = uniqid() . ".jpg";
        // $picUrl = $iFile->store($fileName, base64_encode($image));//过期时间为5分钟
        $picId = $iFile2->store($fileName, base64_encode($image), 0, "id"); // 过期时间为5分钟
        die($picId);
    }

    public function faceAction ()
    {
        $this->_helper->viewRenderer->setNoRender(false);
        if (isset($_FILES['image'])) {
            $o = new iFace($_FILES['image']['tmp_name']);
            $coordinates = $o->face_detect();
            var_dump($coordinates);
            $i = 0;
            $src = imagecreatefromstring(
                    file_get_contents($_FILES['image']['tmp_name']));
            $skin = new iSkin($src);
            foreach ($coordinates as $coordinate) {
                // 增加肤色的判断
                if ($skin->isSkinColorFromPicture($coordinate['x'], 
                        $coordinate['y'], $coordinate['w'], $coordinate['h'])) {
                    $cx = $coordinate['x'] + floor($coordinate['w'] / 2);
                    $cy = $coordinate['y'] + floor($coordinate['h'] / 2);
                    $width = $coordinate['w'];
                    $height = $coordinate['h'];
                    
                    if ($width > 20 && $height > 20) {
                        $i ++;
                        imageellipse($src, $cx, $cy, $width, $height, 
                                imagecolorallocate($src, 255, 0, 0));
                    }
                } else {
                    echo 'is not skin<br />';
                }
            }
            
            ob_start();
            imagepng($src);
            imagedestroy($src);
            $image = ob_get_contents();
            ob_end_clean();
            
            $this->view->assign('face_number', $i);
            $this->view->assign('face_image_data', 
                    'data:image/png;base64,' . base64_encode($image));
        }
    }

    public function faceImageAction ()
    {
        $o = new iFace('http://scrm.umaman.com/soa/file/get/id/52450338489619f34c270e22');
        $coordinates = $o->face_detect();
        var_dump($coordinates);
    }
    
    public function categoryAction() {
        $obj = new Default_Model_Test();
        echo '<pre>';
        for($i=0;$i<100;$i++) {
            var_dump($obj->findOne(array()));
        }
    }
    
    
    public function lockAction() {
        $cache = Zend_Registry::get('cache');
        if(($datas = $cache->load("cacheKey"))===false) {
            lockForGenerateCache("cacheKey");
            sleep(15);
            echo 'no cache';
            $time = time();
            $cache->save($time,"cacheKey");
            unlockForGenerateCache("cacheKey");
        }
        echo $datas;
    }

    public function __destruct ()
    {}
}

