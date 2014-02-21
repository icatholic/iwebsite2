<?php
// 发送被动响应消息
/**
 * 对于每一个POST请求，开发者在响应包（Get）中返回特定XML结构，
 * 对该消息进行响应（现支持回复文本、图片、图文、语音、视频、音乐）。
 * 请注意，回复图片等多媒体消息时需要预先上传多媒体文件到微信服务器，
 * 只支持认证服务号。微信服务器在五秒内收不到响应会断掉连接，
 * 如果在调试中，发现用户无法收到响应的消息，可以检查是否消息处理超时。
 *
 * @author guoyongrong
 *        
 */
class WeixinReplyMsgSender
{

    protected $weixinMsgManager;

    protected $toUser;

    protected $fromUser;

    private $_length = 240;

    public function __construct(WeixinMsgManager $weixinMsgManager)
    {
        $this->weixinMsgManager = $weixinMsgManager;
        $this->toUser = $weixinMsgManager->getWeixin()->getToUser();
        $this->fromUser = $weixinMsgManager->getWeixin()->getFromUser();
        $this->_length = $weixinMsgManager->getLength();
    }

    /**
     * 回复文本
     *
     * @param string $content            
     * @return string
     */
    public function replyText($content)
    {
        $time = time();
        return "<xml>" . "<ToUserName><![CDATA[{$this->toUser}]]></ToUserName>" . "<FromUserName><![CDATA[{$this->fromUser}]]></FromUserName>" . "<CreateTime>{$time}</CreateTime>" . "<MsgType><![CDATA[text]]></MsgType>" . "<Content><![CDATA[{$content}]]></Content>" . "</xml>";
    }

    /**
     * 回复图片消息
     *
     * @param string $media_id            
     * @return string
     */
    public function replyImage($media_id)
    {
        $time = time();
        return "<xml>" . "<ToUserName><![CDATA[{$this->toUser}]]></ToUserName>" . "<FromUserName><![CDATA[{$this->fromUser}]]></FromUserName>" . "<CreateTime>{$time}</CreateTime>" . "<MsgType><![CDATA[image]]></MsgType>" . "<Image>" . "<MediaId><![CDATA[{$media_id}]]></MediaId>" . "</Image>" . "</xml>";
    }

    /**
     * 回复语音消息
     *
     * @param string $media_id            
     * @return string
     */
    public function replyVoice($media_id)
    {
        $time = time();
        return "<xml>" . "<ToUserName><![CDATA[{$this->toUser}]]></ToUserName>" . "<FromUserName><![CDATA[{$this->fromUser}]]></FromUserName>" . "<CreateTime>{$time}</CreateTime>" . "<MsgType><![CDATA[voice]]></MsgType>" . "<Voice>" . "<MediaId><![CDATA[{$media_id}]]></MediaId>" . "</Voice>" . "</xml>";
    }

    /**
     * 回复视频消息
     *
     * @param string $media_id            
     * @param string $title            
     * @param string $description            
     * @return string
     */
    public function replyVideo($media_id, $title, $description)
    {
        $time = time();
        return "<xml>" . "<ToUserName><![CDATA[{$this->toUser}]]></ToUserName>" . "<FromUserName><![CDATA[{$this->fromUser}]]></FromUserName>" . "<CreateTime>{$time}</CreateTime>" . "<MsgType><![CDATA[video]]></MsgType>" . "<Video>" . "<MediaId><![CDATA[{$media_id}]]></MediaId>" . "<Title><![CDATA[{$title}]]></Title>" . "<Description><![CDATA[{$description}]]></Description>" . "</Video> " . "</xml>";
    }

    /**
     * 回复音乐
     *
     * @param string $title            
     * @param string $description            
     * @param string $musicUrl            
     * @param string $hqMusicUrl            
     * @param string $media_id            
     * @return string
     */
    public function replyMusic($title, $description, $musicUrl, $hqMusicUrl = '', $thumbMediaId = 0)
    {
        $time = time();
        $hqMusicUrl = $hqMusicUrl == '' ? $musicUrl : $hqMusicUrl;
        
        return "<xml>" . "<ToUserName><![CDATA[{$this->toUser}]]></ToUserName>" . "<FromUserName><![CDATA[{$this->fromUser}]]></FromUserName>" . "<CreateTime>{$time}</CreateTime>" . "<MsgType><![CDATA[music]]></MsgType>" . "<Music>" . "<Title><![CDATA[{$title}]]></Title>" . "<Description><![CDATA[{$description}]]></Description>" . "<MusicUrl><![CDATA[{$musicUrl}]]></MusicUrl>" . "<HQMusicUrl><![CDATA[{$hqMusicUrl}]]></HQMusicUrl>" . "<ThumbMediaId><![CDATA[{$thumbMediaId}]]></ThumbMediaId>" . "</Music>" . "</xml>";
    }

    /**
     * 回复图文信息
     *
     * @param array $articles
     *            子元素
     *            $articles[] = $article
     *            子元素结构
     *            $article['title']
     *            $article['description']
     *            $article['picurl'] 图片链接，支持JPG、PNG格式，较好的效果为大图640*320，小图80*80
     *            $article['url']
     *            
     * @return string
     */
    public function replyGraphText(Array $articles)
    {
        $time = time();
        if (! is_array($articles) || count($articles) == 0)
            return '';
        $items = '';
        $articles = array_slice($articles, 0, 10);
        $articleCount = count($articles);
        foreach ($articles as $article) {
            if (mb_strlen($article['description'], 'utf-8') > $this->_length) {
                $article['description'] = mb_substr($article['description'], 0, $this->_length, 'utf-8') . '……';
            }
            $items .= "<item>" . "<Title><![CDATA[{$article['title']}]]></Title>" . "<Description><![CDATA[{$article['description']}]]></Description>" . "<PicUrl><![CDATA[{$article['picurl']}]]></PicUrl>" . "<Url><![CDATA[{$article['url']}]]></Url>" . "</item>";
        }
        return "<xml>" . "<ToUserName><![CDATA[{$this->toUser}]]></ToUserName>" . "<FromUserName><![CDATA[{$this->fromUser}]]></FromUserName>" . "<CreateTime>{$time}</CreateTime>" . "<MsgType><![CDATA[news]]></MsgType>" . "<ArticleCount>{$articleCount}</ArticleCount>" . "<Articles>{$items}</Articles>" . "</xml>";
    }
}

/**
 * 发送客服消息
 * 当用户主动发消息给公众号的时候，
 * 微信将会把消息数据推送给开发者，
 * 开发者在一段时间内（目前为24小时）可以调用客服消息接口，
 * 通过POST一个JSON数据包来发送消息给普通用户，
 * 在24小时内不限制发送次数。
 * 此接口主要用于客服等有人工消息处理环节的功能，
 * 方便开发者为用户提供更加优质的服务。
 */
class WeixinCustomMsgSender
{

    protected $weixinMsgManager;

    protected $toUser;

    protected $fromUser;

    private $_length = 240;

    public function __construct(WeixinMsgManager $weixinMsgManager)
    {
        $this->weixinMsgManager = $weixinMsgManager;
        $this->toUser = $weixinMsgManager->getWeixin()->getToUser();
        $this->fromUser = $weixinMsgManager->getWeixin()->getFromUser();
        $this->_length = $weixinMsgManager->getLength();
    }

    /**
     * 发送消息
     * 该接口用于发送从iwebsite 微信模块发送过来的消息
     *
     * @param array $msg            
     * @return string
     */
    public function send(array $msg)
    {
        $rst = $this->weixinMsgManager->getWeixin()->post("message/custom/send", array(
            'msg' => $msg
        ));
    }

    /**
     * 发送文本消息
     *
     * @param string $content            
     * @return string
     */
    public function sendText($content)
    {
        $ret = array();
        $ret['touser'] = $this->toUser;
        $ret['msgtype'] = "text";
        $ret['text']["content"] = $content;
        return $this->send($ret);
    }

    /**
     * 发送图片消息
     *
     * @param string $media_id            
     * @return string
     */
    public function sendImage($media_id)
    {
        $ret = array();
        $ret['touser'] = $this->toUser;
        $ret['msgtype'] = "image";
        $ret['image']["media_id"] = $media_id;
        return $this->send($ret);
    }

    /**
     * 发送语音消息
     *
     * @param string $media_id            
     * @return string
     */
    public function sendVoice($media_id)
    {
        $ret = array();
        $ret['touser'] = $this->toUser;
        $ret['msgtype'] = "voice";
        $ret['image']["media_id"] = $media_id;
        return $this->send($ret);
    }

    /**
     * 发送视频消息
     *
     * @param string $media_id            
     * @param string $thumb_media_id            
     * @return string
     */
    public function sendVideo($media_id, $thumb_media_id)
    {
        $ret = array();
        $ret['touser'] = $this->toUser;
        $ret['msgtype'] = "video";
        $ret['image']["media_id"] = $media_id;
        $ret['image']["thumb_media_id"] = $thumb_media_id;
        return $this->send($ret);
    }

    /**
     * 发送音乐消息
     *
     * @param string $title            
     * @param string $description            
     * @param string $musicurl            
     * @param string $hqmusicurl            
     * @param string $thumb_media_id            
     * @return string
     */
    public function sendMusic($title, $description, $musicurl, $hqmusicurl, $thumb_media_id)
    {
        $hqmusicurl = $hqmusicurl == '' ? $musicurl : $hqmusicurl;
        $ret = array();
        $ret['touser'] = $this->toUser;
        $ret['msgtype'] = "video";
        $ret['music']["title"] = $title;
        $ret['music']["description"] = $description;
        $ret['music']["musicurl"] = $musicurl;
        $ret['music']["hqmusicurl"] = $hqmusicurl;
        $ret['music']["thumb_media_id"] = $thumb_media_id;
        return $this->send($ret);
    }

    /**
     * 发送图文消息
     *
     * @param array $articles            
     * @return string
     */
    public function sendGraphText(Array $articles)
    {
        if (! is_array($articles) || count($articles) == 0)
            return '';
        $items = array();
        $articles = array_slice($articles, 0, 10); // 图文消息条数限制在10条以内。
        $articleCount = count($articles);
        foreach ($articles as $article) {
            if (mb_strlen($article['description'], 'utf-8') > $this->_length) {
                $article['description'] = mb_substr($article['description'], 0, $this->_length, 'utf-8') . '……';
            }
            $items[] = array(
                'title' => $article['title'],
                'description' => $article['description'],
                'url' => $article['url'],
                'picurl' => $article['picurl']
            );
        }
        $ret = array();
        $ret['touser'] = $this->toUser;
        $ret['msgtype'] = "news";
        $ret['news']["articles"] = $items;
        return $this->send($ret);
    }
}

/**
 * 消息管理
 * 
 * @author DMT-053
 *        
 */
class WeixinMsgManager
{

    private $_length = 240;

    protected $weixinReplyMsgSender;

    protected $weixinCustomMsgSender;

    protected $weixin;

    public function getLength()
    {
        return $this->_length;
    }

    /**
     * 设定图文消息中，文章内容截取字符串长度。默认为240个字符
     *
     * @param int $length            
     * @return bool
     */
    public function setSubStrLen($length)
    {
        $length = (int) $length;
        $this->_length = $length == 0 ? $length : 240;
        return true;
    }

    public function getWeixinReplyMsgSender()
    {
        return $this->weixinReplyMsgSender;
    }

    public function getWeixinCustomMsgSender()
    {
        return $this->weixinCustomMsgSender;
    }

    public function getWeixin()
    {
        return $this->weixin;
    }

    public function __construct(iWeixin $weixin)
    {
        $this->weixin = $weixin;
        // 发送被动响应消息发射器
        $this->weixinReplyMsgSender = new WeixinReplyMsgSender($this);
        // 发送客服消息发射器
        $this->weixinCustomMsgSender = new WeixinCustomMsgSender($this);
    }
}

/**
 * 分组管理接口
 * 开发者可以使用接口，
 * 对公众平台的分组进行查询、创建、修改操作，
 * 也可以使用接口在需要时移动用户到某个分组。
 */
class WeixinGroupsManager
{

    protected $weixin;

    public function __construct(iWeixin $weixin)
    {
        $this->weixin = $weixin;
    }

    /**
     * 查询分组
     *
     * @return mixed
     */
    public function get()
    {
        return $this->weixin->get("groups/get", array());
    }

    /**
     * 创建分组
     * 一个公众账号，最多支持创建500个分组
     *
     * @param
     *            $name
     * @return mixed
     */
    public function create($name)
    {
        return $this->weixin->post("groups/create", array(
            "name" => $name
        ));
    }

    /**
     * 修改分组名
     *
     * @param int $id            
     * @param string $name            
     * @return mixed
     */
    public function update($id, $name)
    {
        return $this->weixin->post("groups/update", array(
            "id" => $id,
            "name" => $name
        ));
    }

    /**
     * 更新分组会员
     *
     * @param string $openid            
     * @param string $to_groupid            
     * @return mixed
     */
    public function membersUpdate($openid, $to_groupid)
    {
        return $this->weixin->post("groups/members/update", array(
            "openid" => $openid,
            "to_groupid" => $to_groupid
        ));
    }
}

class WeixinUserManager
{

    protected $weixin;

    public function getWeixin()
    {
        return $this->weixin;
    }

    public function __construct(iWeixin $weixin)
    {
        $this->weixin = $weixin;
    }

    /**
     * 获取用户基本信息
     * 在关注者与公众号产生消息交互后，
     * 公众号可获得关注者的OpenID
     * （加密后的微信号，每个用户对每个公众号的OpenID是唯一的。
     * 对于不同公众号，同一用户的openid不同）。
     * 公众号可通过本接口来根据OpenID获取用户基本信息，
     * 包括昵称、头像、性别、所在城市、语言和关注时间。
     */
    public function getUserInfo($openid)
    {
        return $this->weixin->get("user/info", array(
            "openid" => $openid
        ));
    }

    /**
     * 获取关注者列表
     * 公众号可通过本接口来获取帐号的关注者列表，
     * 关注者列表由一串OpenID（加密后的微信号，每个用户对每个公众号的OpenID是唯一的）组成。
     * 一次拉取调用最多拉取10000个关注者的OpenID，
     * 可以通过多次拉取的方式来满足需求。
     */
    public function getUserList($next_openid = "")
    {
        // access_token 是 调用接口凭证
        // next_openid 是 第一个拉取的OPENID，不填默认从头开始拉取
        /* {"total":2,"count":2,"data":{"openid":["","OPENID1","OPENID2"]},"next_openid":"NEXT_OPENID"} */
        return $this->weixin->get("user/get", array(
            "next_openid" => $next_openid
        ));
    }
}

/**
 * 微信二维码推广
 *
 * @author DMT-053
 *        
 */
class WeixinQrcodeManager
{

    protected $weixin;

    public function __construct(iWeixin $weixin)
    {
        $this->weixin = $weixin;
    }

    /**
     * 创建二维码ticket
     * 每次创建二维码ticket需要提供一个开发者自行设定的参数（scene_id），
     * 分别介绍临时二维码和永久二维码的创建二维码ticket过程。
     *
     * @param int $scene_id            
     * @param string $isTemporary            
     * @param number $expire_seconds            
     * @return mixed
     */
    public function create($scene_id, $isTemporary = true, $expire_seconds = 0)
    {
        $params = array();
        if ($isTemporary) {
            $params['expire_seconds'] = min($expire_seconds, 1800);
            $params['scene_id'] = $scene_id;
        } else {
            $params['scene_id'] = min($scene_id, 1000);
        }
        $params['isTemporary'] = $isTemporary;
        return $this->weixin->post("qrcode/create", $params);
    }

    /**
     * 通过ticket换取二维码
     * 获取二维码ticket后，开发者可用ticket换取二维码图片。请注意，本接口无须登录态即可调用
     *
     * @param string $ticket            
     * @return string
     */
    public function getQrcodeUrl($ticket)
    {
        // 请求说明
        // HTTP GET请求（请使用https协议）
        // https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=TICKET
        // 返回说明
        // ticket正确情况下，http 返回码是200，是一张图片，可以直接展示或者下载
        return "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket={$ticket}";
    }
}

// 自定义菜单
class WeixinMenuManager
{

    protected $weixin;

    public function __construct(iWeixin $weixin)
    {
        $this->weixin = $weixin;
    }

    /**
     * 自定义菜单创建接口
     * 目前自定义菜单最多包括3个一级菜单，
     * 每个一级菜单最多包含5个二级菜单。
     * 一级菜单最多4个汉字，二级菜单最多7个汉字，
     * 多出来的部分将会以“...”代替。请注意，
     * 创建自定义菜单后，由于微信客户端缓存，
     * 需要24小时微信客户端才会展现出来。
     * 建议测试时可以尝试取消关注公众账号后再次关注，
     * 则可以看到创建后的效果。
     * 目前自定义菜单接口可实现两种类型按钮，如下：
     * click：
     * 用户点击click类型按钮后，
     * 微信服务器会通过消息接口推送消息类型为event	的结构给开发者
     * （参考消息接口指南），并且带上按钮中开发者填写的key值，
     * 开发者可以通过自定义的key值与用户进行交互；
     * view：
     * 用户点击view类型按钮后，
     * 微信客户端将会打开开发者在按钮中填写的url值	（即网页链接），
     * 达到打开网页的目的，建议与网页授权获取用户基本信息接口结合，
     * 获得用户的登入个人信息。
     *
     * @author Kan
     *        
     */
    public function create(array $menus)
    {
        return $this->weixin->post("menu/create", $menus);
    }

    /**
     * 自定义菜单查询接口
     * 使用接口创建自定义菜单后，开发者还可使用接口查询自定义菜单的结构。
     */
    public function get()
    {
        return $this->weixin->get("menu/get", array());
    }

    /**
     * 自定义菜单删除接口
     * 使用接口创建自定义菜单后，开发者还可使用接口删除当前使用的自定义菜单。
     *
     * @return array
     */
    public function delete()
    {
        return $this->weixin->post("menu/delete", array());
    }
}

// 上传下载多媒体文件
class WeixinMediaManager
{

    protected $weixin;

    public function __construct(iWeixin $weixin)
    {
        $this->weixin = $weixin;
    }

    /**
     * 上传多媒体文件
     * 公众号可调用本接口来上传图片、语音、视频等文件到微信服务器，
     * 上传后服务器会返回对应的media_id，公众号此后可根据该media_id来获取多媒体。
     * 请注意，media_id是可复用的，调用该接口需http协议。
     * 注意事项
     * 上传的多媒体文件有格式和大小限制，如下：
     * 图片（image）: 128K，支持JPG格式
     * 语音（voice）：256K，播放长度不超过60s，支持AMR\MP3格式
     * 视频（video）：1MB，支持MP4格式
     * 缩略图（thumb）：64KB，支持JPG格式
     * 媒体文件在后台保存时间为3天，即3天后media_id失效。
     */
    public function upload($type, $media)
    {
        try {
            $url = 'http://file.api.weixin.qq.com/cgi-bin/media/upload' . '?access_token=' . $this->weixin->getAccessToken() . '&type=' . $type;
            $client = new Zend_Http_Client();
            $client->setUri($url);
            $client->setEncType(Zend_Http_Client::ENC_FORMDATA);
            $fileInfo = $this->getFileByUrl($media);
            $client->setFileUpload($fileInfo['name'], 'media', $fileInfo['bytes']);
            $client->setConfig(array(
                'maxredirects' => 5
            ));
            $response = $client->request('POST');
            return json_decode($response->getBody(), true);
        } catch (Exception $e) {
            throw new Exception(exceptionMsg($e));
        }
    }

    /**
     * 下载多媒体文件
     * 公众号可调用本接口来获取多媒体文件。请注意，调用该接口需http协议。
     */
    public function get($media_id)
    {
        $url = 'http://file.api.weixin.qq.com/cgi-bin/media/get' . '?access_token=' . $this->weixin->getAccessToken() . '&media_id=' . $media_id;
        return $this->getFileByUrl($url);
    }

    public function getFileByUrl($url)
    {
        $client = new Zend_Http_Client();
        $client->setUri($url);
        $client->setConfig(array(
            'maxredirects' => 5
        ));
        $response = $client->request('GET');
        if ($response->isSuccessful()) {
            $disposition = $response->getHeader('Content-disposition');
            // $reDispo = '/^Content-Disposition:.*?filename=(?<f>[^\s]+|\x22[^\x22]+\x22)\x3B?.*$/m';
            $reDispo = '/^.*?filename=(?<f>[^\s]+|\x22[^\x22]+\x22)\x3B?.*$/m';
            if (preg_match($reDispo, $disposition, $mDispo)) {
                $filename = trim($mDispo['f'], ' ";');
                $fileBytes = $response->getBody();
                return array(
                    'name' => $filename,
                    'bytes' => $fileBytes
                );
            } else {
                return json_decode($response->getBody(), true);
            }
        } else {
            throw new Exception("获取文件失败");
        }
    }
}

class iWeixin
{

    private $_serviceUrl = 'https://api.weixin.qq.com/cgi-bin/';

    private $_accessToken = null;

    private $_toUser;

    private $_fromUser;

    public function getServiceUrl()
    {
        return $this->_serviceUrl;
    }

    public function getAccessToken()
    {
        return $this->_accessToken;
    }

    public function getToUser()
    {
        return $this->_toUser;
    }

    public function getFromUser()
    {
        return $this->_fromUser;
    }

    public function getWeixinMsgManager()
    {
        return new WeixinMsgManager($this);
    }

    public function getWeixinGroupsManager()
    {
        return new WeixinGroupsManager($this);
    }

    public function getWeixinQrcodeManager()
    {
        return new WeixinQrcodeManager($this);
    }

    public function getWeixinMenuManager()
    {
        return new WeixinMenuManager($this);
    }

    public function getWeixinUserManager()
    {
        return new WeixinUserManager($this);
    }

    public function getWeixinMediaManager()
    {
        return new WeixinMediaManager($this);
    }

    /**
     *
     * @param string $accessToken            
     * @param string $fromUserName            
     * @param string $toUserName            
     */
    public function __construct($accessToken)
    {
        $this->_accessToken = $accessToken;
    }

    /**
     * 通过外部数据获取来源和发送方的openid
     *
     * @param string $fromUserName            
     * @param string $toUserName            
     */
    public function setFromAndTo($fromUserName, $toUserName)
    {
        $this->_toUser = $fromUserName;
        $this->_fromUser = $toUserName;
    }

    /**
     * 执行get请求
     *
     * @param string $url            
     * @param array $parameters            
     * @throws Exception
     * @return mixed
     */
    public function get($url, $parameters = array())
    {
        if (empty($this->_accessToken)) {
            throw new Exception("access token为空");
        }
        return json_decode(file_get_contents($this->_serviceUrl . $url . '?access_token=' . $this->_accessToken . '&' . http_build_query($parameters)), true);
    }

    /**
     * 执行post请求
     *
     * @param string $url            
     * @param array $parameters            
     * @throws Exception
     * @return mixed
     */
    public function post($url, $parameters = array())
    {
        if (empty($this->_accessToken)) {
            throw new Exception("access token为空");
        }
        $url = $this->_serviceUrl . $url . '?access_token=' . $this->_accessToken;
        $client = new Zend_Http_Client();
        $client->setUri($url);
        $client->setParameterPost($parameters);
        $client->setRawData(json_encode($parameters,JSON_UNESCAPED_UNICODE),'application/json');
        $client->setConfig(array(
            'maxredirects' => 5
        ));
        $response = $client->request('POST');

        return json_decode($response->getBody(), true);
    }

    public function __destruct()
    {}
}

class iWeixinAccessToken
{

    private $_appid;

    private $_secret;

    public function __construct($appid, $secret)
    {
        $this->_appid = $appid;
        $this->_secret = $secret;
    }

    public function get()
    {
        return json_decode(file_get_contents("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->_appid}&secret={$this->_secret}"), true);
    }

    public function __destruct()
    {}
}
