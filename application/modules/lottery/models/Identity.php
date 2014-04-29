<?php

class Lottery_Model_Identity extends iWebsite_Plugin_Mongo
{

    protected $name = 'iLottery_identity';

    protected $dbName = 'lottery';

    private $_source;

    private $_keys = array(
        'activity_id',
        'diaplay_name',
        'name',
        'nickname',
        'mobile',
        'tel',
        'address',
        'zip',
        'id_number',
        'weibo_id',
        'weixin_openid',
        'other_id',
        'other_string_id',
        'others',
        'source_data'
    );

    const SOURCE_WEIXIN = 'weixin';

    const SOURCE_WEIBO = 'weibo';

    const SOURCE_OTHERS = 'others';

    /**
     * 设定抽奖活动来源信息
     *
     * @param string $source            
     */
    public function setSource($source)
    {
        $sourceModel = new Lottery_Model_Source();
        $sources = $sourceModel->getSource();
        if (in_array($source, $sources)) {
            $this->_source = $source;
        } else {
            throw new Exception("活动来源类型不存在");
        }
    }

    /**
     * 根据身份信息获取用户的身份数据
     *
     * @param string $indentity_id            
     */
    public function getIdentityById($indentity_id)
    {
        return $this->findOne(array(
            '_id' => $indentity_id
        ));
    }

    /**
     * 根据不同来源的唯一数据类型进行匹配
     *
     * @param mixed $uniqueId            
     */
    public function getIdentityByUnique($uniqueId)
    {
        $query = $this->queryUnique($uniqueId);
        return $this->findOne($query);
    }

    /**
     * 获取唯一查询条件
     */
    private function queryUnique($uniqueId)
    {
        $query = array();
        switch ($this->_source) {
            case 'weixin':
                $query['weixin_openid'] = (string) $uniqueId;
                break;
            case 'weibo':
                $query['weibo_id'] = (int) $uniqueId;
                break;
            default:
                if (preg_match("/^[0-9]+$/", $uniqueId)) {
                    $query['other_id'] = intval($uniqueId);
                } else {
                    $query['other_string_id'] = $uniqueId;
                }
                break;
        }
        return $query;
    }

    /**
     * 格式化信息
     *
     * @param array $info            
     * @return array
     */
    private function formatInfo($info)
    {
        $rst = array();
        foreach ($this->_keys as $key) {
            if (isset($info[$key])) {
                $rst[$key] = $info[$key];
            }
        }
        return $rst;
    }

    /**
     *
     * @param string $info            
     */
    public function record($uniqueId, $info)
    {
        $info = $this->formatInfo($info);
        $query = $this->queryUnique($uniqueId);
        $info = array_merge($info, $query);
        
        $identityInfo = $this->findOne($query);
        if($identityInfo!=null) {
            $query['_id'] = $identityInfo['_id'];
        }
        
        $options = array();
        $options['query'] = $query;
        $options['update'] = array(
            '$set' => $info
        );
        $options['new'] = true;
        $options['upsert'] = true;
        $rst = $this->findAndModify($options);
        if (! empty($rst['value'])) {
            return $rst['value'];
        }
        return false;
    }

    /**
     * 更新用户个人信息
     */
    public function updateIdentityInfo($identity_id, $info)
    {
        $identity_id = $identity_id instanceof MongoId ? $identity_id : myMongoId($identity_id);
        $options = array();
        $options['query'] = array(
            '_id' => $identity_id
        );
        $options['update'] = array(
            '$set' => $info
        );
        $rst = $this->findAndModify($options);
        return $rst['value'];
    }
}