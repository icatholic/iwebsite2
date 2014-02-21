<?php
class Default_IndexController extends Zend_Controller_Action
{
    public $model;
    public $unique;
    
    public function init()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->model = new Default_Model_Test();
        $this->unique = new Default_Model_UniqueTable();
    }

    public function indexAction()
    {
        echo 'default index index';
    }
    
    public function testAction() {
        $this->_helper->viewRenderer->setNoRender(true);

        $str1 = "孕妇如何保持快乐的心情？准妈妈良好情绪六方法1. 转移法：烦恼时尽快离开使自己不愉快的地方，去做一件自己喜欢的事情，如听听音乐、看看画册、读读喜欢的刊物、看看电影或去郊外玩玩，尽量让高兴的东西转移自己的注意力，忘掉那些不愉快的事情。
        2. 告诫法：时时用名言、警句告诫自己，使自己保持良好的心情，每当生气或正要发脾气时，首先想到宝宝正看着妈妈呢！
        3. 释放法：向密友倾诉自己的烦恼，或写信或写日记，这几种方式都可以有效的释放自己的糟糕情绪。
        4. 社交法：广交性格开朗、积极向上的朋友，充分享受与好友相聚的快乐，让他们的心情感染自己。
        5. 散步法：在林荫大道、江边、田野散步，宜人的自然风光会无形中消除自己紧张不安的情绪。
        6. 改变形象法：换一个心仪已久的发型，买一件中意的衣服，或装点一下自己的房间，给自己带来一点新鲜感，
        ";
        
        echo strlen("新浪微博手机客户端, 新浪微博手机客户端");
        echo "<br />";
        echo strlen("孕妇如何保持快乐的心情？准妈妈良好情绪六方法");
        echo "<br />";
        $str = "新浪微博手机客户端, 新浪微博手机客户端是这样的啊啊啊啊你是谁啊啊。特色：即拍即发，手机拍照一键发送；节约流量，数据量更小，最多可节约80%；多账户支持，同时添加多账户，加快切换 ";
        
        $str = preg_replace("/[\t\r\n\s]+/", "", $str);
        $str =  str_replace('。', '', $str);
        var_dump(scws($str));
        var_dump(scws($str1));
    }
    
    public function imageAction()
    {
    	if(isset($_FILES['file1'])) {
    		$strImage1 = dealUploadFileBySoap($_FILES['file1']['name'],$_FILES['file1']['tmp_name']);
    		$strImage2 = dealUploadFileBySoap($_FILES['file2']['name'],$_FILES['file2']['tmp_name']);
    		$strHashA = ImageHash::pHash($strImage1);
    		$strHashB = ImageHash::pHash($strImage2);
    		echo ImageHash::getDistance($strHashA,$strHashB);
    		if(ImageHash::isSimilar($strHashA,$strHashB))
    			$this->view->xs = 1;
    		else
    			$this->view->xs = 0;
    		$this->view->img1 = $strImage1;
    		$this->view->img2 = $strImage2;
    		$this->view->p = 0;
    	}
    	else {
    		$this->view->p = 1;
    	}
    	
    }
    
    public function sendAction() {
        try {
            $o = new iColorRGB(256,0,255);
            echo 'OK';
        }
        catch(Exception $e) {
            var_dump($e->getMessage());
        }
    }
    
    public function colorTestAction() {
        $o = new iColor();
        var_dump($o->dominantColorByFilter('http://scrm.umaman.com/soa/file/get/id/51ee2f5e479619f15b99924f'));
    }
    
    public function colorAction() {
        //$url = 'http://scrm.umaman.com/soa/image/get/id/51f11dfe4896195153c10841/size/100x100';
        $url = isset($_POST['url']) ? $_POST['url']  : '';
        $k = isset($_POST['k']) ? (int) $_POST['k']  : 7;
        
        if(filter_var($url,FILTER_VALIDATE_URL)) {
            if($k<=0 || $k>20) {
                $k = 7;
            }
            
            $o = new iColor('51ece8bb479619fa5b08990c','1Qa@wS');
            $centers = $o->getColors($url,$k);
            
            
            
            ?>
            <form action="/default/index/color" method="post">
                Image URL:<input type="text" name="url" value=""/><br />
                kmeans<input type="text" name="k" value="7"/>(1-20)<br />
                <input type="submit" value="submit"/>
            </form>
            <br />
            <img src="<?php echo $url;?>" /><br />
            <pre>
            <?php 
            
            foreach($centers as $center) {
            ?>
            <div style="width:100px;height:24px;background-color:rgb(<?php echo $center['color'][0];?>,<?php echo $center['color'][1];?>,<?php echo $center['color'][2];?>)"><?php echo $center['count'];?></div>
            <?php
            }
            ?>
            <?php 
        }
        else {
        ?>
            <form action="/default/index/color" method="post">
                Image URL:<input type="text" name="url" value=""/><br />
                kmeans<input type="text" name="k" value="7"/>(1-20)<br />
                <input type="submit" value="submit"/>
            </form>
        <?php 
        }
    }
    
    public function colorSoapAction() {
        
        $url = 'http://mmsns.qpic.cn/mmsns/SqiaE15KWgOblHDzQT93gq41P6LnVQsEBlua43julVia1jgYPPVauwpw/0';
        //$url = 'http://scrm.umaman.com/soa/file/get/id/51f792d4489619a43d0628bb';
        //$url = 'http://scrm.umaman.com/soa/image/get/id/51f79309489619c83dbffc80';
        $url = 'http://scrm.umaman.com/soa/image/get/id/51f790e8489619963d9b94e3';
        $o = new iColor('51ef40d948961970533b4d73','1234567890asdfghjklpoiuy');
        var_dump($o->getColors($url, 7));
        var_dump($o->pHash($url));
        var_dump($o->aHash($url));
        var_dump($o->dHash($url));
        var_dump($o->dominantColor($url,true));
        var_dump($o->dominantColor($url));
    }
    
    public function phashAction() {

        file_get_contents('http://mmsns.qpic.cn/mmsns/DcicX4Tnsib9YDnfgaibnKLfZrPOSMTZrgiaawZQSrh78WiaUEib4TOZERrA/0');
        exit('ok');
        
        $client = new Zend_Http_Client(
            'http://mmsns.qpic.cn/mmsns/DcicX4Tnsib9YDnfgaibnKLfZrPOSMTZrgiaawZQSrh78WiaUEib4TOZERrA/0', 
            array(
                'maxredirects' => 3,
                'timeout'      => 30
            )
        );
        
        $response = $client->request('GET');
        echo $response->getRawBody();
        exit();
        
        echo ImageHash::pHash('http://mmsns.qpic.cn/mmsns/DcicX4Tnsib9YDnfgaibnKLfZrPOSMTZrgiaawZQSrh78WiaUEib4TOZERrA/0');
    }
        
    public function __destruct() {
        
    }


}

