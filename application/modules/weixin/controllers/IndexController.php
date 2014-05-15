<?php

class Weixin_IndexController extends Zend_Controller_Action
{

    private $_source;

    private $_sourceDatas;

    private $_keyword;

    private $_reply;

    private $_app;

    private $_accessToken;

    private $_user;

    private $_not_keyword;

    private $_menu;

    private $_weixin;

    private $_qrcode;

    public function init()
    {
        try {
            $this->getHelper('viewRenderer')->setNoRender(true);
            $this->_source = new Weixin_Model_Source();
            $this->_keyword = new Weixin_Model_Keyword();
            $this->_reply = new Weixin_Model_Reply();
            $this->_app = new Weixin_Model_Application();
            $this->_user = new Weixin_Model_User();
            $this->_not_keyword = new Weixin_Model_NotKeyword();
            $this->_menu = new Weixin_Model_Menu();
            $this->_qrcode = new Weixin_Model_Qrcode();
            
            $this->_appConfig = $this->_app->getToken();
            
            $this->_weixin = new Weixin\Client();
            if (! empty($this->_appConfig['access_token'])) {
                $this->_weixin->setAccessToken($this->_appConfig['access_token']);
            }
        } catch (Exception $e) {
            var_dump($e);
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
        try {
            /**
             * ==================================================================================
             * ====================================以下逻辑请勿修改===================================
             * ==================================================================================
             */
            $onlyRevieve = false;
            $scriptStartTime = microtime(true);
            $__DEBUG__ = isset($_GET['__DEBUG__']) ? trim(strtolower($_GET['__DEBUG__'])) : false;
            $verifyToken = isset($this->_appConfig['verify_token']) ? $this->_appConfig['verify_token'] : '';
            if (empty($verifyToken)) {
                throw new Exception('application verify_token is null');
            }
            
            // 合法性校验
            $this->_weixin->verify($verifyToken);
            
            if (! $__DEBUG__) {
                if (! $this->_weixin->checkSignature($verifyToken)) {
                    $debug = debugVar($_GET, $this->_weixin->getSignature());
                    throw new Exception('签名错误' . $debug);
                }
            }
            
            // 签名正确，将接受到的xml转化为数组数据并记录数据
            $datas = $this->_source->revieve();
            $this->_sourceDatas = $datas;
            
            // 调试接口信息
            if ($__DEBUG__) {
                $datas = $this->_app->debug($__DEBUG__);
            }
            // 开始处理相关的业务逻辑
            $content = isset($datas['Content']) ? strtolower(trim($datas['Content'])) : '';
            
            $FromUserName = isset($datas['FromUserName']) ? trim($datas['FromUserName']) : '';
            Zend_Registry::set('__FROM_USER_NAME__', $FromUserName);
            $ToUserName = isset($datas['ToUserName']) ? trim($datas['ToUserName']) : '';
            $MsgType = isset($datas['MsgType']) ? trim($datas['MsgType']) : '';
            $Event = isset($datas['Event']) ? trim($datas['Event']) : '';
            $EventKey = isset($datas['EventKey']) ? trim($datas['EventKey']) : '';
            $MediaId = isset($datas['MediaId']) ? trim($datas['MediaId']) : '';
            $Ticket = isset($datas['Ticket']) ? trim($datas['Ticket']) : '';
            
            // 获取微信用户的个人信息
            $updateUserInfo = function () use($FromUserName)
            {
                if (! empty($this->_appConfig['access_token'])) {
                    $this->_user->setWeixinInstance($this->_weixin);
                    $this->_user->updateUserInfoByAction($FromUserName);
                }
            };
            // 设定来源和目标用户的openid
            $this->_weixin->setFromAndTo($FromUserName, $ToUserName);
            
            // 为回复的Model装载weixin对象
            $this->_reply->setWeixinInstance($this->_weixin);
            
            /**
             * ==================================================================================
             * ====================================以上逻辑请勿修改===================================
             * ==================================================================================
             */
            
            // 转化为关键词方式，表示关注
            if ($MsgType == 'event') { // 接收事件推送
                if ($Event == 'subscribe') { // 关注事件
                                             // EventKey 事件KEY值，qrscene_为前缀，后面为二维码的参数值
                                             
                    // Ticket 二维码的ticket，可用来换取二维码图片
                    if (! empty($Ticket) || ! empty($EventKey)) { // 扫描带参数二维码事件 用户未关注时，进行关注后的事件推送
                                                                  // var_dump($FromUserName, $Event, $EventKey, $Ticket);
                        $this->_qrcode->record($FromUserName, $Event, $EventKey, $Ticket);
                        // 不同项目特定的业务逻辑开始
                        $sence_id = intval(str_ireplace('qrscene_', '', $EventKey));
                        if ($sence_id > 0) {
                            $content = "扫描二维码{$sence_id}";
                        }
                        // 不同项目特定的业务逻辑结束
                    }
                    
                    // 扫描二维码送优惠券
                    if (empty($content)) {
                        $content = '首访回复';
                    }
                } elseif ($Event == 'SCAN') { // 扫描带参数二维码事件 用户已关注时的事件推送
                    $this->_qrcode->record($FromUserName, $Event, $EventKey, $Ticket);
                    //$onlyRevieve = true;
                    // EventKey 事件KEY值，是一个32位无符号整数
                    // Ticket 二维码的ticket，可用来换取二维码图片
                    // 不同项目特定的业务逻辑开始
                    $content = "扫描二维码{$EventKey}";
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
                $content = '默认语音回复';
            }
            // 语音逻辑结束
            
            // 图片逻辑开始
            if ($MsgType == 'image') { // 接收普通消息----图片消息
                                       // PicUrl 图片链接
                                       // MediaId 图片消息媒体id，可以调用多媒体文件下载接口拉取数据。
                $PicUrl = isset($datas['PicUrl']) ? trim($datas['PicUrl']) : '';
                
                // 使用闭包，提高相应速度
                $content = '默认图片回复';
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
            
            /**
             * ==================================================================================
             * ====================================以下逻辑请勿修改===================================
             * ==================================================================================
             */
            if ($onlyRevieve)
                return false;
            
            if ($__DEBUG__) {
                print_r($content);
            }
            
            if ($content == 'debug') {
                echo $this->_weixin->getMsgManager()
                    ->getReplySender()
                    ->replyText(debugVar($datas));
                return false;
            }
            
            $response = followUrl($this->anwser($content), array(
                'FromUserName' => $FromUserName
            ));
            // 输出响应结果
            echo $response;
            
            // 以下部分执行的操作，不影响执行速度，但是也将无法输出到返回结果中
            if (! $__DEBUG__) {
                fastcgi_finish_request();
            }
            $this->_sourceDatas['interval'] = microtime(true) - $scriptStartTime;
            $this->_sourceDatas['response'] = $response;
            $this->_sourceDatas['response_time'] = new MongoDate();
            $this->_source->save($this->_sourceDatas);
            if (isset($updateUserInfo)) {
                $updateUserInfo();
            }
            /**
             * ==================================================================================
             * ====================================以上逻辑请勿修改===================================
             * ==================================================================================
             */
            
            // 将一些执行很慢的逻辑，放在这里执行，提高微信的响应速度开始
            
            // 将一些执行很慢的逻辑，放在这里执行，提高微信的响应速度结束
            
            return true;
        } catch (Exception $e) {
            // 如果脚本执行中发现异常，则记录返回的异常信息
            $this->_sourceDatas['interval'] = microtime(true) - $scriptStartTime;
            $this->_sourceDatas['response'] = exceptionMsg($e);
            $this->_sourceDatas['response_time'] = new MongoDate();
            $this->_source->save($this->_sourceDatas);
            return false;
        }
    }

    /**
     * 同步菜单选项的Hook
     */
    public function syncMenuAction()
    {
        $menus = $this->_menu->buildMenu();
        var_dump($this->_weixin->getMenuManager()->create($menus));
        return true;
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
        return $this->_reply->answer($match);
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {}
}

