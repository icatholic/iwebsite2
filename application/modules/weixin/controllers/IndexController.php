<?php

class Weixin_IndexController extends Zend_Controller_Action
{

    private $_source;

    private $_sourceDatas;

    private $_keyword;

    private $_reply;

    private $_app;

    private $_token;

    private $_user;

    private $_not_keyword;

    private $_menu;

    private $_weixin;

    public function init()
    {
        $this->getHelper('viewRenderer')->setNoRender(true);
        $this->_source = new Weixin_Model_Index();
        $this->_keyword = new Weixin_Model_Keyword();
        $this->_reply = new Weixin_Model_Reply();
        $this->_app = new Weixin_Model_Application();
        $this->_user = new Weixin_Model_User();
        $this->_not_keyword = new Weixin_Model_NotKeyword();
        $this->_menu = new Weixin_Model_Menu();
        
        // 可以考虑添加缓存
        $this->_source->setDebug(true);
        $this->_token = $this->_app->getToken();
        if ($this->_token == null) {
            throw new Exception('应用管理信息未设定');
        }
        // 判断access token是否过期，如果过期更新token
        $accessToken = $this->updateAccessToken();
        if (! empty($accessToken)) {
            $this->_weixin = new iWeixin($accessToken);
        }
    }

    public function indexAction()
    {}

    /**
     * 处理微信的回调数据
     *
     * @return boolean
     */
    public function callbackAction()
    {
        $onlyRevieve = false;
        
        // 合法性校验
        $this->verify();
        
        if (! $this->checkSignature()) {
            throw new Exception('签名错误');
        }
        
        // 将接受到的xml转化为数组数据
        $datas = $this->revieve();
        
        // 调试接口信息
        // $datas['Content'] = '图片';
        // $datas['FromUserName'] = 'FromUserName';
        // $datas['ToUserName'] = 'ToUserName';
        // $datas['MsgType'] = 'text';
        
        // 开始处理相关的业务逻辑
        $content = isset($datas['Content']) ? trim($datas['Content']) : '';
        $FromUserName = isset($datas['FromUserName']) ? trim($datas['FromUserName']) : '';
        $ToUserName = isset($datas['ToUserName']) ? trim($datas['ToUserName']) : '';
        $MsgType = isset($datas['MsgType']) ? trim($datas['MsgType']) : '';
        $Event = isset($datas['Event']) ? trim($datas['Event']) : '';
        $EventKey = isset($datas['EventKey']) ? trim($datas['EventKey']) : '';
        $media_id = isset($datas['media_id']) ? trim($datas['media_id']) : '';
        $Ticket = isset($datas['Ticket']) ? trim($datas['Ticket']) : '';
        
        // 设定来源和目标用户的openid
        $this->_weixin->setFromAndTo($FromUserName, $ToUserName);
        
        // 为回复的Model装载weixin对象
        $this->_reply->setWeixinInstance($this->_weixin);
        
        // 转化为关键词方式，表示关注
        if ($MsgType == 'event') { // 接收事件推送
            if ($Event == 'subscribe') { // 关注事件
                                         // EventKey 事件KEY值，qrscene_为前缀，后面为二维码的参数值
                                         // Ticket 二维码的ticket，可用来换取二维码图片
                if (! empty($Ticket) || ! empty($EventKey)) { // 扫描带参数二维码事件 用户未关注时，进行关注后的事件推送
                                                                  // 不同项目特定的业务逻辑开始
                                                                  // 不同项目特定的业务逻辑结束
                }
                $content = 'Hello2BizUser';
            } elseif ($Event == 'scan') { // 扫描带参数二维码事件 用户已关注时的事件推送
                                              // EventKey 事件KEY值，是一个32位无符号整数
                                              // Ticket 二维码的ticket，可用来换取二维码图片
                                              // 不同项目特定的业务逻辑开始
                                              // 不同项目特定的业务逻辑结束
            } elseif ($Event == 'unsubscribe') { // 取消关注事件
                                                     // 不同项目特定的业务逻辑开始
                                                     // 不同项目特定的业务逻辑结束
            } elseif ($Event == 'LOCATION') { // 上报地理位置事件
                                              // Latitude 地理位置纬度
                                              // Longitude 地理位置经度
                                              // Precision 地理位置精度
                $Latitude = isset($datas['Latitude']) ? trim($datas['Latitude']) : 0;
                $Longitude = isset($datas['Longitude']) ? trim($datas['Longitude']) : 0;
                $Precision = isset($datas['Precision']) ? trim($datas['Precision']) : 0;
                $onlyRevieve = true;
                // 不同项目特定的业务逻辑开始
                // 不同项目特定的业务逻辑结束
            } elseif ($Event == 'CLICK') { // 自定义菜单事件推送
                                           // 相对点击事件做特别处理，请在这里，并删除$content = $EventKey;
                $content = $EventKey;
            }
        }
        
        // 语音逻辑开始
        if ($MsgType == 'voice') { // 接收普通消息----语音消息 或者接收语音识别结果
                                   // MediaID 语音消息媒体id，可以调用多媒体文件下载接口拉取该媒体
                                   // Format 语音格式：amr
                                   // Recognition 语音识别结果，UTF8编码
            $Recognition = isset($datas['Recognition']) ? trim($datas['Recognition']) : '';
            // 不同项目特定的业务逻辑开始
            // 不同项目特定的业务逻辑结束
            return $this->anwser('默认语音回复');
        }
        // 语音逻辑结束
        
        // 图片逻辑开始
        if ($MsgType == 'image') { // 接收普通消息----图片消息
                                   // PicUrl 图片链接
                                   // MediaId 图片消息媒体id，可以调用多媒体文件下载接口拉取数据。
            $PicUrl = isset($datas['PicUrl']) ? trim($datas['PicUrl']) : '';
            
            return $this->anwser('默认图片回复');
        }
        // 图片逻辑结束
        
        // 不同项目特定的业务逻辑开始
        if ($MsgType == 'text') { // 接收普通消息----文本消息
        }
        // 不同项目特定的业务逻辑结束
        
        // 不同项目特定的业务逻辑开始
        if ($MsgType == 'video') { // 接收普通消息----视频消息
                                   // MediaId 视频消息媒体id，可以调用多媒体文件下载接口拉取数据。
                                   // ThumbMediaId 视频消息缩略图的媒体id，可以调用多媒体文件下载接口拉取数据。
            $ThumbMediaId = isset($datas['ThumbMediaId']) ? trim($datas['ThumbMediaId']) : '';
        }
        // 不同项目特定的业务逻辑结束
        
        // 处理地理位置信息开始
        if ($MsgType == 'location') { // 接收普通消息----地理位置消息
                                      // Location_X 地理位置维度
                                      // Location_Y 地理位置精度
                                      // Scale 地图缩放大小
            $Location_X = isset($datas['Location_X']) ? trim($datas['Location_X']) : 0;
            $Location_Y = isset($datas['Location_Y']) ? trim($datas['Location_Y']) : 0;
            $Scale = isset($datas['Scale']) ? trim($datas['Scale']) : 0;
        }
        
        // 不同项目特定的业务逻辑开始
        if ($MsgType == 'link') { // 接收普通消息----链接消息
                                  // Title 消息标题
                                  // Description 消息描述
                                  // Url 消息链接
            $Title = isset($datas['Title']) ? trim($datas['Title']) : '';
            $Description = isset($datas['Description']) ? trim($datas['Description']) : '';
            $Url = isset($datas['Url']) ? trim($datas['Url']) : '';
        }
        // 不同项目特定的业务逻辑结束
        if ($onlyRevieve)
            return false;
        
        return $this->anwser($content);
    }

    /**
     * 同步菜单选项
     */
    public function syncMenuAction()
    {
        $menus = $this->_menu->buildMenu();
        var_dump($this->_weixin->getWeixinMenuManager()->create($menus));
        return true;
    }

    /**
     * 获取信息接收信息
     *
     * @return array
     */
    private function revieve()
    {
        $postStr = file_get_contents('php://input');
        $datas = (array) simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        $this->_source->save($datas);
        $this->_sourceDatas = $datas;
        return $datas;
    }

    /**
     * 匹配文本并进行自动回复
     *
     * @param string $content            
     * @return boolean
     */
    private function anwser($content)
    {
        $match = $this->_keyword->matchKeyWord($content);
        if (empty($match)) {
            $this->_not_keyword->record($content);
            $match = $this->_keyword->matchKeyWord('默认回复');
        }
        echo $response = $this->_reply->answer($match);
        $this->_sourceDatas['response'] = $response;
        $this->_sourceDatas['response_time'] = new MongoDate();
        $this->_source->save($this->_sourceDatas);
        return true;
    }




}

