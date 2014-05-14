<?php

class Weixin_Model_Keyword extends iWebsite_Plugin_Mongo
{

    protected $name = 'iWeixin_keyword';

    protected $dbName = 'weixin';

    /**
     * 获取符合指定类型的关键词列表
     *
     * @param bool $fuzzy            
     * @return array
     */
    public function getKeywordByType($fuzzy)
    {
        $keywordList = array();
        $fuzzy = is_bool($fuzzy) ? $fuzzy : false;
        $rst = $this->findAll(array(
            'fuzzy' => $fuzzy
        ));
        if (! empty($rst)) {
            foreach ($rst as $row) {
                $keywordList[$row['keyword']] = $row;
            }
        }
        return $keywordList;
    }

    public function matchKeyWord($msg, $fuzzy = false)
    {
        $msg = trim($msg);
        $fuzzy = is_bool($fuzzy) ? $fuzzy : false;
        $keywordList = $this->getKeywordByType($fuzzy);
        if (! $fuzzy) {
            $msg = strtolower($msg);
            if (isset($keywordList[$msg])) {
                $this->incHitNumber($keywordList[$msg]);
                return $keywordList[$msg];
            } else {
                return $this->matchKeyWord($msg, true);
            }
        } else {
            $split = $this->split($msg, 1, 10);
            $keys = array();
            if (count($split) > 0) {
                $keys = array_keys($keywordList);
                $keys = array_intersect($split, $keys);
            }
            if (count($keys) == 0) {
                return array();
            }
            $queue = new iWebsite_Stdlib_SplPriorityQueue();
            foreach ($keys as $key) {
                $queue->insert($keywordList[$key], $keywordList[$key]['priority']);
            }
            $queue->top();
            
            $result = $queue->current();
            $this->incHitNumber($result);
            return $result;
        }
    }

    /**
     * 对于文本内容进行一元拆分，但是保留完整的英文单词、网址、电子邮箱、数字信息,注意不区分大小写，全部转换为小写进行匹配
     *
     * @param string $str            
     * @return array
     */
    private function match($str)
    {
        $str = strtolower(trim($str));
        if (preg_match_all("/(?:[a-z'\-\.\/\:_@0-9#\?\!\,\;]+|[\x80-\xff]{3})/i", $str, $match)) {
            return $match[0];
        }
        return array();
    }

    /**
     * 对于分词结果进行$elementMin元至$elementMax元的分词组合
     *
     * @param string $str
     *            字符串
     * @param int $elementMin
     *            最小分词元数
     * @param int $elementMax
     *            最大分词元数
     * @return array
     */
    public function split($str, $elementMin = 1, $elementMax = 0)
    {
        $elementMin = (int) $elementMin;
        $elementMax = (int) $elementMax;
        $elements = $this->match($str);
        $elementsNumber = count($elements);
        if ($elementsNumber == 0)
            return array();
        
        $elementMin = $elementMin <= 0 ? 1 : $elementMin;
        $elementMax = $elementMax == 0 ? $elementsNumber : $elementMax;
        $elementMax = $elementMax > $elementsNumber ? $elementsNumber : $elementMax;
        $elementMax = $elementMin > $elementMax ? $elementMin : $elementMax;
        
        $arrSplit = array();
        do {
            foreach ($elements as $key => $element) {
                if ($elementsNumber >= $key + $elementMin)
                    $arrSplit[] = implode(array_slice($elements, $key, $elementMin));
            }
            $elementMin += 1;
        } while ($elementMin <= $elementMax);
        return $arrSplit;
    }

    /**
     * 记录关键词的命中次数
     */
    public function incHitNumber($keywordInfo)
    {
        return $this->update(array(
            '_id' => $keywordInfo['_id']
        ), array(
            '$inc' => array(
                'times' => 1
            )
        ));
    }
}