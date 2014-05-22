<?php
use Weixin\Client;

class Weixin_Model_Reply extends iWebsite_Plugin_Mongo
{

    protected $name = 'iWeixin_reply';

    protected $dbName = 'weixin';

    private $_weixin;

    const MULTI = 1;

    const MUSIC = 2;

    const TEXT = 3;

    const VOICE = 4;

    const VIDEO = 5;

    const IMAGE = 6;

    public function setWeixinInstance(Client $weixin)
    {
        $this->_weixin = $weixin;
    }

    public function answer($match)
    {
        $replys = $this->getReplyDetail($match);
        if (empty($replys)) {
            return false;
        }
        
        switch ($match['reply_type']) {
            case self::MULTI:
                $articles = array();
                foreach ($replys as $index => $reply) {
                    array_push($articles, array(
                        'title' => $reply['title'],
                        'description' => $reply['description'],
                        'picurl' => $index == 0 ? $reply['picture'] : $reply['icon'],
                        'url' => isset($reply['page']) ? 'http://' . $_SERVER["HTTP_HOST"] . '/weixin/page/index/id/' . $reply['page'] : $reply['url']
                    ));
                }
                return $this->_weixin->getMsgManager()
                    ->getReplySender()
                    ->replyGraphText($articles);
                break;
            case self::MUSIC:
                return $this->_weixin->getMsgManager()
                    ->getReplySender()
                    ->replyMusic($replys[0]['title'], $replys[0]['description'], $replys[0]['music']);
                break;
            case self::TEXT:
                return $this->_weixin->getMsgManager()
                    ->getReplySender()
                    ->replyText($replys[0]['description']);
                break;
            case self::VOICE:
                $media_id = $this->getMediaId('voice', $replys[0]);
                return $this->_weixin->getMsgManager()
                    ->getReplySender()
                    ->replyVoice($media_id);
                break;
            case self::VIDEO:
                $media_id = $this->getMediaId('video', $replys[0]);
                return $this->_weixin->getMsgManager()
                    ->getReplySender()
                    ->replyVideo($replys[0]['title'], $replys[0]['description'], $media_id);
                break;
            case self::IMAGE:
                $media_id = $this->getMediaId('image', $replys[0]);
                return $this->_weixin->getMsgManager()
                    ->getReplySender()
                    ->replyImage($media_id);
                break;
        }
    }

    private function getMediaId($type, $reply)
    {
        $created_at = 0;
        if (isset($reply[$type . '_media_result']['created_at'])) {
            $created_at = $reply[$type . '_media_result']['created_at'];
            $media_result = $reply[$type . '_media_result'];
        }
        
        if ($created_at + 24 * 3600 * 3 < time()) {
            $media_result = $this->_weixin->getMediaManager()->upload($type, $reply[$type]);
            $this->update(array(
                '_id' => $reply['_id']
            ), array(
                '$set' => array(
                    $type . '_media_result' => $media_result
                )
            ));
        }
        return $media_result['media_id'];
    }

    /**
     * 获取指定回复内容的回复内容
     *
     * @param array $match            
     * @return array
     */
    public function getReplyDetail($match)
    {
        if (isset($match['reply_ids'])) {
            $rst = $this->findAll(array(
                'reply_type' => $match['reply_type'],
                '_id' => array(
                    '$in' => myMongoId($match['reply_ids'])
                )
            ), array(
                'priority' => - 1,
                '_id' => - 1
            ));
            return $rst;
        }
        return false;
    }
}