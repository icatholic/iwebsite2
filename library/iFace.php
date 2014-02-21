<?php

/**
 * 人脸识别模块
 * 
 * @author  Young
 * @date    2013.09.29
 * @version 1.0
 *
 */
class iFace
{

    private $_temp;

    private $_data_path;

    private $_datas = array( // 'haarcascade_frontalface_alt.xml',
            'haarcascade_frontalface_default.xml'
    // 'haarcascade_lowerbody.xml',
    // 'haarcascade_upperbody.xml',
    // 'haarcascade_fullbody.xml',
    // 'haarcascade_profileface.xml',
    // 'haarcascade_smile.xml'
        );

    public function __construct ($url)
    {
        $this->_data_path = implode(DIRECTORY_SEPARATOR, 
                array(
                        __DIR__,
                        'FaceDetection',
                        ''
                ));
        $this->getImage($url);
    }

    /**
     * 获取指定资源位置的图片
     *
     * @param string $url            
     * @throws Exception
     * @return bool true or false
     */
    private function getImage ($url)
    {
        try {
            // 先通过路径获取图片资源,解决file_get_content不发送connect：close导致获取某些特定服务器资源缓慢的问题
            if (filter_var($url, FILTER_VALIDATE_URL) !== false) {
                $client = new Zend_Http_Client($url, 
                        array(
                                'maxredirects' => 3,
                                'timeout' => 300
                        ));
                $response = $client->request('GET');
                if ($response->isError())
                    throw new iFaceException($url . ', $response is error！');
                $image = $response->getBody();
            } else {
                $image = file_get_contents($url);
            }
            
            $this->_temp = tempnam(sys_get_temp_dir(), 'iFace_');
            file_put_contents($this->_temp, $image);
            return true;
        } catch (Exception $e) {
            throw new iFaceException($e->getMessage());
        }
    }

    /**
     * 统计图片中笑脸的数量
     */
    public function face_count ()
    {
        foreach ($this->_datas as $xml) {
            $rst = face_count($this->_temp, $this->_data_path . $xml);
            if ($rst !== false)
                break;
        }
        return $rst;
    }

    /**
     * 识别图片中笑脸的位置
     * 
     * @param number $maxNumber            
     * @return 最大人脸识别数量
     */
    public function face_detect ($maxNumber = 5)
    {
        foreach ($this->_datas as $xml) {
            $rst = face_detect($this->_temp, $this->_data_path . $xml);
            if ($rst !== false)
                break;
        }
        
        $queue = new iFacePriorityQueue();
        foreach ($rst as $row) {
            $queue->insert($row, $row['w'] * $row['h']);
        }
        
        $faces = array();
        $i = 0;
        $queue->top();
        while ($queue->valid()) {
            $faces[] = $queue->current();
            $queue->next();
            if ($i ++ >= $maxNumber)
                break;
        }
        return $faces;
    }

    /**
     * 删除临时文件
     */
    public function __destruct ()
    {
        unlink($this->_temp);
    }
}

class iFaceException extends Exception
{
}

class iFacePriorityQueue extends SplPriorityQueue
{

    public function compare ($priority1, $priority2)
    {
        if ($priority1 === $priority2)
            return 0;
        return $priority1 < $priority2 ? - 1 : 1;
    }
}
