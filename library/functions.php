<?php

/**
 * 递归的创建多层级的目录
 *
 * @param string $dir
 */
function forceDirectory($dir)
{
    return is_dir($dir) or (forceDirectory(dirname($dir)) and mkdir($dir, 0777));
}

/**
 * 记录错误日志
 *
 * @param string $info            
 */
function logError($info)
{
    if (is_string($info))
        error_log($info);
    return true;
}

/**
 * 记录错误调试信息
 * 请使用/admin/index/debug 查看调试信息
 *
 * @param mixed $var            
 */
function debugVar()
{
    ob_start();
    print_r(func_get_args());
    $info = ob_get_contents();
    ob_get_clean();
    return $info;
}

/**
 * 检测是否为有效的电子邮件地址
 *
 * @param string $email            
 * @param int $getmxrr
 *            0表示关闭mx检查 1表示开启mx检查 window下开启需要php5.3+
 * @return bool true/false
 *        
 */
function isValidEmail($email, $getmxrr = 0)
{
    if ((strpos($email, '..') !== false) or (! preg_match('/^(.+)@([^@]+)$/', $email, $matches))) {
        return false;
    }
    $_localPart = $matches[1];
    $_hostname = $matches[2];
    if ((strlen($_localPart) > 64) || (strlen($_hostname) > 255)) {
        return false;
    }
    $atext = 'a-zA-Z0-9\x21\x23\x24\x25\x26\x27\x2a\x2b\x2d\x2f\x3d\x3f\x5e\x5f\x60\x7b\x7c\x7d\x7e';
    if (! preg_match('/^[' . $atext . ']+(\x2e+[' . $atext . ']+)*$/', $_localPart)) {
        return false;
    }
    if ($getmxrr == 1) {
        $mxHosts = array();
        $result = getmxrr($_hostname, $mxHosts);
        if (! $result) {
            return false;
        }
    }
    return true;
}

/**
 * 检测是否为有效的手机号码
 *
 * @param string $mobile            
 * @return bool true/false
 */
function isValidMobile($mobile)
{
    if (preg_match("/^1[3,4,5,7,8]{1}[0-9]{9}$/", $mobile))
        return true;
    return false;
}

/**
 * 生成10位的随机数字
 *
 * @return string
 */
function createRandNumber10()
{
    return sprintf("%010d", abs(crc32(uniqid())));
}

/**
 * 根据IP获取该用户所在的地址，依据是纯真数据库
 *
 * @param $ip string            
 * @return string
 */
function convertIp($ip)
{
    // IP数据文件路径
    $dat_path = ROOT_PATH . DIRECTORY_SEPARATOR . 'datas' . DIRECTORY_SEPARATOR . 'qqwry.dat';
    // 检查IP地址
    if (! preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/", $ip)) {
        return 'IP Address Error';
    }
    // 打开IP数据文件
    if (! $fd = @fopen($dat_path, 'rb')) {
        return 'IP date file not exists or access denied';
    }
    // 分解IP进行运算，得出整形数
    $ip = explode('.', $ip);
    $ipNum = $ip[0] * 16777216 + $ip[1] * 65536 + $ip[2] * 256 + $ip[3];
    // 获取IP数据索引开始和结束位置
    $DataBegin = fread($fd, 4);
    $DataEnd = fread($fd, 4);
    $ipbegin = implode('', unpack('L', $DataBegin));
    if ($ipbegin < 0)
        $ipbegin += pow(2, 32);
    $ipend = implode('', unpack('L', $DataEnd));
    if ($ipend < 0)
        $ipend += pow(2, 32);
    $ipAllNum = ($ipend - $ipbegin) / 7 + 1;
    $BeginNum = 0;
    $EndNum = $ipAllNum;
    // 使用二分查找法从索引记录中搜索匹配的IP记录
    while ($ip1num > $ipNum || $ip2num < $ipNum) {
        $Middle = intval(($EndNum + $BeginNum) / 2);
        // 偏移指针到索引位置读取4个字节
        fseek($fd, $ipbegin + 7 * $Middle);
        $ipData1 = fread($fd, 4);
        if (strlen($ipData1) < 4) {
            fclose($fd);
            return 'System Error';
        }
        // 提取出来的数据转换成长整形，如果数据是负数则加上2的32次幂
        $ip1num = implode('', unpack('L', $ipData1));
        if ($ip1num < 0)
            $ip1num += pow(2, 32);
            // 提取的长整型数大于我们IP地址则修改结束位置进行下一次循环
        if ($ip1num > $ipNum) {
            $EndNum = $Middle;
            continue;
        }
        // 取完上一个索引后取下一个索引
        $DataSeek = fread($fd, 3);
        if (strlen($DataSeek) < 3) {
            fclose($fd);
            return 'System Error';
        }
        $DataSeek = implode('', unpack('L', $DataSeek . chr(0)));
        fseek($fd, $DataSeek);
        $ipData2 = fread($fd, 4);
        if (strlen($ipData2) < 4) {
            fclose($fd);
            return 'System Error';
        }
        $ip2num = implode('', unpack('L', $ipData2));
        if ($ip2num < 0)
            $ip2num += pow(2, 32);
            // 没找到提示未知
        if ($ip2num < $ipNum) {
            if ($Middle == $BeginNum) {
                fclose($fd);
                return 'Unknown';
            }
            $BeginNum = $Middle;
        }
    }
    $ipFlag = fread($fd, 1);
    if ($ipFlag == chr(1)) {
        $ipSeek = fread($fd, 3);
        if (strlen($ipSeek) < 3) {
            fclose($fd);
            return 'System Error';
        }
        $ipSeek = implode('', unpack('L', $ipSeek . chr(0)));
        fseek($fd, $ipSeek);
        $ipFlag = fread($fd, 1);
    }
    if ($ipFlag == chr(2)) {
        $AddrSeek = fread($fd, 3);
        if (strlen($AddrSeek) < 3) {
            fclose($fd);
            return 'System Error';
        }
        $ipFlag = fread($fd, 1);
        if ($ipFlag == chr(2)) {
            $AddrSeek2 = fread($fd, 3);
            if (strlen($AddrSeek2) < 3) {
                fclose($fd);
                return 'System Error';
            }
            $AddrSeek2 = implode('', unpack('L', $AddrSeek2 . chr(0)));
            fseek($fd, $AddrSeek2);
        } else {
            fseek($fd, - 1, SEEK_CUR);
        }
        while (($char = fread($fd, 1)) != chr(0))
            $ipAddr2 .= $char;
        $AddrSeek = implode('', unpack('L', $AddrSeek . chr(0)));
        fseek($fd, $AddrSeek);
        while (($char = fread($fd, 1)) != chr(0))
            $ipAddr1 .= $char;
    } else {
        fseek($fd, - 1, SEEK_CUR);
        while (($char = fread($fd, 1)) != chr(0))
            $ipAddr1 .= $char;
        $ipFlag = fread($fd, 1);
        if ($ipFlag == chr(2)) {
            $AddrSeek2 = fread($fd, 3);
            if (strlen($AddrSeek2) < 3) {
                fclose($fd);
                return 'System Error';
            }
            $AddrSeek2 = implode('', unpack('L', $AddrSeek2 . chr(0)));
            fseek($fd, $AddrSeek2);
        } else {
            fseek($fd, - 1, SEEK_CUR);
        }
        while (($char = fread($fd, 1)) != chr(0)) {
            $ipAddr2 .= $char;
        }
    }
    fclose($fd);
    // 最后做相应的替换操作后返回结果
    if (preg_match('/http/i', $ipAddr2)) {
        $ipAddr2 = '';
    }
    $ipaddr = "$ipAddr1 $ipAddr2";
    $ipaddr = preg_replace('/CZ88\.Net/is', '', $ipaddr);
    $ipaddr = preg_replace('/^\s*/is', '', $ipaddr);
    $ipaddr = preg_replace('/\s*$/is', '', $ipaddr);
    if (preg_match('/http/i', $ipaddr) || $ipaddr == '') {
        $ipaddr = 'Unknown';
    }
    return iconv("gb2312", "utf-8//IGNORE", $ipaddr);
}

/**
 * 将数组数据导出为csv文件
 *
 * @param string $name            
 * @param array $datas            
 */
function arrayToCVS($name, $datas)
{
    resetTimeMemLimit();
    $result = array_merge(array(
        $datas['title']
    ), $datas['result']);
    $tmpname = tempnam(sys_get_temp_dir(), 'export_cvs_');
    $fp = fopen($tmpname, 'w');
    foreach ($result as $row) {
        fputcsv($fp, $row, "\t", '"');
    }
    fclose($fp);
    
    header('Content-type: text/csv;');
    header('Content-Disposition: attachment; filename="' . $name . '.csv"');
    header("Content-Length:" . filesize($tmpname));
    echo file_get_contents($tmpname);
    unlink($tmpname);
    exit();
}

/**
 * 计算cell所在的位置
 */
function excelTitle($i)
{
    $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $divisor = floor($i / 26);
    $remainder = $i % 26;
    if ($divisor > 0) {
        return $str[$divisor - 1] . $str[$remainder];
    } else {
        return $str[$remainder];
    }
}

/**
 * 导出excel表格
 *
 * @param $name excel表格的名称，不包含.xlsx            
 * @param $datas 二维数据
 *            填充表格的数据
 * @example $datas['title'] = array('col1','col2','col3','col4');
 *          $datas['result'] = array(array('v11','v12','v13','v14')
 *          array('v21','v22','v23','v24'));
 * @return 直接浏览器输出excel表格 注意这个函数前不能有任何形式的输出
 *        
 */
function arrayToExcel($name, $datas)
{
    resetTimeMemLimit();
    include_once ("PHPExcel/PHPExcel.php");
    // 便于处理大的大型excel表格，存储在磁盘缓存中
    $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_discISAM;
    PHPExcel_Settings::setCacheStorageMethod($cacheMethod);
    $objPHPExcel = new PHPExcel();
    $objPHPExcel->getProperties()->setCreator('Automation');
    $objPHPExcel->getProperties()->setLastModifiedBy('Automation');
    $objPHPExcel->getProperties()->setTitle($name);
    $objPHPExcel->getProperties()->setSubject($name);
    $objPHPExcel->getProperties()->setDescription($name);
    $objPHPExcel->setActiveSheetIndex(0);
    $total = count($datas['title']);
    for ($i = 0; $i < $total; $i ++) {
        $objPHPExcel->getActiveSheet()
            ->getColumnDimension(excelTitle($i))
            ->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->SetCellValue(excelTitle($i) . '1', $datas['title'][$i]);
    }
    $i = 2;
    foreach ($datas['result'] as $data) {
        $j = 0;
        foreach ($data as $cell) {
            // 判断是否为图片，如果是图片，那么绘制图片
            if (is_array($cell) && $cell['type'] == 'image') {
                $coordinate = excelTitle($j) . $i;
                $cellName = isset($cell['name']) ? $cell['name'] : '';
                $cellDesc = isset($cell['desc']) ? $cell['desc'] : '';
                $cellType = isset($cell['type']) ? $cell['type'] : '';
                $cellUrl = isset($cell['url']) ? $cell['url'] : '';
                $cellHeight = isset($cell['height']) ? intval($cell['height']) : '';
                if ($cellType == 'image') {
                    if ($cellHeight == 0)
                        $cellHeight = 20;
                    $image = imagecreatefromstring(file_get_contents($cellUrl));
                    $objDrawing = new PHPExcel_Worksheet_MemoryDrawing();
                    $objDrawing->setName($cellName);
                    $objDrawing->setDescription($cellDesc);
                    $objDrawing->setImageResource($image);
                    $objDrawing->setRenderingFunction(PHPExcel_Worksheet_MemoryDrawing::RENDERING_JPEG);
                    $objDrawing->setMimeType(PHPExcel_Worksheet_MemoryDrawing::MIMETYPE_DEFAULT);
                    $objDrawing->setHeight($cellHeight);
                    $objDrawing->setCoordinates($coordinate); // 填充到某个单元格
                    $objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
                    $objPHPExcel->getActiveSheet()
                        ->getRowDimension($i)
                        ->setRowHeight($cellHeight);
                } else {
                    $objPHPExcel->getActiveSheet()->setCellValueExplicit($coordinate, $cellName, PHPExcel_Cell_DataType::TYPE_STRING);
                }
                // 添加链接
                $objPHPExcel->getActiveSheet()
                    ->getCell($coordinate)
                    ->getHyperlink()
                    ->setUrl($cellUrl);
                $objPHPExcel->getActiveSheet()
                    ->getCell($coordinate)
                    ->getHyperlink()
                    ->setTooltip($cellName . ':' . $cellDesc);
            } else 
                if (is_array($cell)) {
                    $objPHPExcel->getActiveSheet()->setCellValueExplicit(excelTitle($j) . $i, json_encode($cell), PHPExcel_Cell_DataType::TYPE_STRING);
                } else {
                    $objPHPExcel->getActiveSheet()->setCellValueExplicit(excelTitle($j) . $i, $cell, PHPExcel_Cell_DataType::TYPE_STRING);
                }
            $j ++;
        }
        $i ++;
    }
    $objPHPExcel->getActiveSheet()->setTitle($name);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $name . '.xlsx"');
    header('Cache-Control: max-age=0');
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save('php://output');
}

/**
 * 提取邮件的用户名
 *
 * @param string $email            
 * @return mixed string|bool
 */
function getEmailName($email)
{
    if (isValidEmail($email, 0)) {
        $tmp = explode('@', $email);
        return ucfirst($tmp[0]);
    }
    return false;
}

/**
 * 发送邮件
 *
 * @param mixed $to
 *            (array|string)
 * @param string $subject            
 * @param string $content            
 * @param string $type
 *            默认是html邮件
 */
function sendEmail($to, $subject, $content, $type = 'html')
{
    try {
        $config = Zend_Registry::get('config');
        $smtpConfig = array();
        $smtpConfig['auth'] = 'login';
        // 是否加密tls ssl等
        if (isset($config['smtp']['ssl']) && $config['smtp']['ssl'] != '') {
            $smtpConfig['ssl'] = $config['smtp']['ssl'];
        }
        $smtpConfig['port'] = $config['smtp']['port'];
        $smtpConfig['username'] = $config['smtp']['username'];
        $smtpConfig['password'] = $config['smtp']['password'];
        $transport = new Zend_Mail_Transport_Smtp($config['smtp']['server'], $smtpConfig);
        $mail = new Zend_Mail('UTF-8');
        $mail->setHeaderEncoding(Zend_Mime::ENCODING_BASE64);
        $mail->setFrom($config['smtp']['username'], $config['smtp']['username']);
        if (is_array($to)) {
            foreach ($to as $one) {
                $mail->addTo($one, getEmailName($one));
            }
        } else {
            if (isValidEmail($to)) {
                $mail->addTo($to, getEmailName($to));
            }
        }
        $mail->setSubject($subject);
        if ($type != 'html') {
            $mail->setBodyText($content);
        } else {
            $mail->setBodyHtml($content);
        }
        $mail->send($transport);
        return true;
    } catch (Exception $e) {
        logError(exceptionMsg($e));
        return false;
    }
}

/**
 * 获取整形的IP地址
 *
 * @return int
 */
function getIp()
{
    if (getenv('HTTP_X_REAL_IP') != '')
        return getenv('HTTP_X_REAL_IP');
    return $_SERVER['REMOTE_ADDR'];
}

/**
 * 针对需要长时间执行的代码，放宽执行时间和内存的限制
 */
function resetTimeMemLimit()
{
    set_time_limit(3600);
    ini_set('memory_limit', '2048M');
}

/**
 * SOAP返回的错误数组数据类型
 *
 * @param string $msg            
 * @return array
 */
function soapError($msg)
{
    return array(
        'error' => true,
        'msg' => $msg
    );
}

/**
 * 调用SOAP服务
 *
 * @param string $wsdl            
 */
function callSoap($wsdl, $refresh = false)
{
    try {
        ini_set('default_socket_timeout', '3600'); // 保持与SOAP服务器的连接状态
        $options = array(
            'soap_version' => SOAP_1_2,
            'exceptions' => true,
            'trace' => true,
            'connection_timeout' => 120
        );
        if ($refresh == true) {
            $options['cache_wsdl'] = WSDL_CACHE_NONE;
        } else {
            $options['cache_wsdl'] = WSDL_CACHE_MEMORY;
        }
        $client = new SoapClient($wsdl, $options);
        return $client;
    } catch (Exception $e) {
        logError(exceptionMsg($e));
        return false;
    }
}

/**
 * 通过服务存储图片
 *
 * @param string $fileName
 *            $_FILES['fieldname']['name']
 * @param string $filePath
 *            $_FILES['fieldname']['tmp_name']
 * @param
 *            int outId 是否输出ID 还是url路径 默认为输出url路径
 * @return url
 */
function dealUploadFileBySoap($fileName, $filePath, $outId = 0)
{
    $config = Zend_Registry::get('config');
    if ($fileName == '')
        return false;
    $client = callSoap($config['uma']['server'] . 'soa/image/soap?wsdl');
    if ($client != false) {
        $fileByte = base64_encode(file_get_contents($filePath));
        $explode = explode('.', $fileName);
        $ext = end($explode);
        if (in_array(strtolower($ext), array(
            'jpg',
            'png',
            'gif',
            'jpeg'
        ))) {
            $_id = $client->storeImage($fileName, $fileByte);
            if ($outId == 0)
                return $config['uma']['server'] . '/soa/image/get/id/' . $_id;
            return $_id;
        } else {
            $_id = $client->storeFile($fileName, $fileByte);
            if ($outId == 0)
                return $config['uma']['server'] . 'soa/file/get/id/' . $_id;
            return $_id;
        }
    }
    return false;
}

/**
 * 转化mongo db的输出结果为纯数组
 *
 * @param array $arr            
 */
function convertToPureArray($arr)
{
    if (! is_array($arr) || count($arr) == 0)
        return array();
    $newArr = array();
    foreach ($arr as $key => $value) {
        if (is_array($value)) {
            $newArr[$key] = convertToPureArray($value);
        } else {
            if ($value instanceof MongoId || $value instanceof MongoInt64 || $value instanceof MongoInt32) {
                $value = $value->__toString();
            } elseif ($value instanceof MongoDate || $value instanceof MongoTimestamp) {
                $value = date("Y-m-d H:i:s", $value->sec);
            }
            $newArr[$key] = $value;
        }
    }
    return $newArr;
}

/**
 * 设定浏览器头的缓存时间，默认是一年
 *
 * @param int $expireTime            
 */
function setHeaderExpires($expireTime = 31536000)
{
    $expireTime = (int) $expireTime;
    if ($expireTime == 0)
        $expireTime = 31536000;
    $ts = gmdate("D, d M Y H:i:s", time() + $expireTime) . " GMT";
    header("Expires: $ts");
    header("Pragma: cache");
    header("Cache-Control: max-age=$expireTime");
    return true;
}

/**
 * 检测一个字符串否为Json字符串
 *
 * @param string $string            
 * @return true/false
 */
function isJson($string)
{
    if (strpos($string, "{") !== false) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    } else {
        return false;
    }
}

/**
 * 范围cache key字符串
 *
 * @return string
 */
function cacheKey()
{
    $args = func_get_args();
    return md5(serialize($args));
}

/**
 * 中奖概率 百分比 0.0001-100之间的浮点数
 *
 * @param double $percent            
 */
function getProbability($percent)
{
    if (rand(0, pow(10, 6)) <= $percent * pow(10, 4)) {
        return true;
    }
    return false;
}

/**
 * 断点续传,仅适合当线程断点续传
 *
 * @param string $file
 *            文件名
 */
function rangeDownload($file)
{
    $fp = @fopen($file, 'rb');
    
    $size = filesize($file); // File size
    $length = $size; // Content length
    $start = 0; // Start byte
    $end = $size - 1; // End byte
                      // Now that we've gotten so far without errors we send
                      // the accept range header
    /*
     * At the moment we only support single ranges. Multiple ranges requires some more work to ensure it works correctly and comply with the spesifications: http://www.w3.org/Protocols/rfc2616/rfc2616-sec19.html#sec19.2 Multirange support annouces itself with: header('Accept-Ranges: bytes'); Multirange content must be sent with multipart/byteranges mediatype, (mediatype = mimetype) as well as a boundry header to indicate the various chunks of data.
     */
    header("Accept-Ranges: 0-$length");
    // header('Accept-Ranges: bytes');
    // multipart/byteranges
    // http://www.w3.org/Protocols/rfc2616/rfc2616-sec19.html#sec19.2
    if (isset($_SERVER['HTTP_RANGE'])) {
        
        $c_start = $start;
        $c_end = $end;
        // Extract the range string
        list (, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
        // Make sure the client hasn't sent us a multibyte range
        if (strpos($range, ',') !== false) {
            
            // (?) Shoud this be issued here, or should the first
            // range be used? Or should the header be ignored and
            // we output the whole content?
            header('HTTP/1.1 416 Requested Range Not Satisfiable');
            header("Content-Range: bytes $start-$end/$size");
            // (?) Echo some info to the client?
            exit();
        }
        // If the range starts with an '-' we start from the beginning
        // If not, we forward the file pointer
        // And make sure to get the end byte if spesified
        if ($range0 == '-') {
            
            // The n-number of the last bytes is requested
            $c_start = $size - substr($range, 1);
        } else {
            
            $range = explode('-', $range);
            $c_start = $range[0];
            $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
        }
        /*
         * Check the range and make sure it's treated according to the specs. http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
         */
        // End bytes can not be larger than $end.
        $c_end = ($c_end > $end) ? $end : $c_end;
        // Validate the requested range and return an error if it's not correct.
        if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
            
            header('HTTP/1.1 416 Requested Range Not Satisfiable');
            header("Content-Range: bytes $start-$end/$size");
            // (?) Echo some info to the client?
            exit();
        }
        $start = $c_start;
        $end = $c_end;
        $length = $end - $start + 1; // Calculate new content length
        fseek($fp, $start);
        header('HTTP/1.1 206 Partial Content');
    }
    // Notify the client the byte range we'll be outputting
    header("Content-Range: bytes $start-$end/$size");
    header("Content-Length: $length");
    
    // Start buffered download
    $buffer = 1024 * 8;
    while (! feof($fp) && ($p = ftell($fp)) <= $end) {
        
        if ($p + $buffer > $end) {
            
            // In case we're only outputtin a chunk, make sure we don't
            // read past the length
            $buffer = $end - $p + 1;
        }
        set_time_limit(0); // Reset time limit for big files
        echo fread($fp, $buffer);
        flush(); // Free up memory. Otherwise large files will trigger PHP's
                     // memory limit.
    }
    
    fclose($fp);
}

/**
 * 获取异常信息的细节
 *
 * @param Exception $e            
 */
function exceptionMsg($e)
{
    if (is_subclass_of($e, 'Exception') || $e instanceof Exception)
        return $e->getFile() . $e->getLine() . $e->getMessage() . $e->getTraceAsString();
    return false;
}

/**
 * 处理mime type
 */
function dealMimeType($mime)
{
    $mime = strtolower($mime);
    if (strpos($mime, 'image/jpg') !== false)
        return 'image/jpg';
    else 
        if (strpos($mime, 'image/jpeg') !== false)
            return 'image/jpeg';
        else 
            if (strpos($mime, 'image/pjpeg') !== false)
                return 'image/pjpeg';
            else 
                if (strpos($mime, 'image/png') !== false)
                    return 'image/png';
                else 
                    if (strpos($mime, 'image/gif') !== false)
                        return 'image/gif';
                    else
                        return $mime;
}

/**
 * 执行GET操作
 *
 * @param string $url            
 * @param array $params            
 * @return string
 */
function doGet($url, $params = array())
{
    try {
        $url = trim($url);
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid URL');
            return false;
        }
        
        $client = new Zend_Http_Client();
        $client->setUri($url);
        $client->setParameterGet($params);
        $client->setEncType(Zend_Http_Client::ENC_URLENCODED);
        $client->setConfig(array(
            'maxredirects' => 5
        ));
        $response = $client->request('GET');
        return $response->getBody();
    } catch (Exception $e) {
        logError(exceptionMsg($e));
        return false;
    }
}

/**
 * 执行POST操作
 *
 * @param string $url            
 * @param array $params            
 * @return string
 */
function doPost($url, $params = array())
{
    try {
        $url = trim($url);
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid URL');
            return false;
        }
        
        $client = new Zend_Http_Client();
        $client->setUri($url);
        $client->setParameterPost($params);
        $client->setEncType(Zend_Http_Client::ENC_URLENCODED);
        $client->setConfig(array(
            'maxredirects' => 5
        ));
        $response = $client->request('POST');
        return $response->getBody();
    } catch (Exception $e) {
        logError(exceptionMsg($e));
        return false;
    }
}

/**
 * 构造POST和GET组合的请求 返回相应请求
 *
 * @param string $url            
 * @param array $get            
 * @param array $post            
 * @return Zend_Http_Response false
 */
function doRequest($url, $get = array(), $post = array())
{
    try {
        $url = trim($url);
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid URL');
            return false;
        }
        $client = new Zend_Http_Client();
        $client->setUri($url);
        
        if (count($get) > 0 && is_array($get))
            $client->setParameterGet($get);
        
        if (count($post) > 0 && is_array($post))
            $client->setParameterPost($post);
        
        $client->setEncType(Zend_Http_Client::ENC_URLENCODED);
        $client->setConfig(array(
            'maxredirects' => 5
        ));
        if (! empty($post))
            $response = $client->request('POST');
        else
            $response = $client->request('GET');
        
        if ($response->isSuccessful()) {
            return $response->getBody();
        } else {
            throw new Exception('error status is ' . $response->getStatus());
        }
    } catch (Exception $e) {
        logError(exceptionMsg($e));
        return false;
    }
}

/**
 * baidu地图API的文档地址为：
 * http://developer.baidu.com/map/geocoding-api.htm
 * 实例链接：
 * http://api.map.baidu.com/geocoder?address=地址&output=json&key=1f9eda8f2585572ed2b1d45c37ecfd78&city=城市名
 *
 * 通过地址和城市的名称获取该地址的坐标
 *
 * @param string $address
 *            必填参数
 * @param string $city
 *            可选参数
 * @return array 返回的baidu的api的json格式为
 *        
 *         {
 *         "status":"OK",
 *         "result":{
 *         "location":{
 *         "lng":121.591841,
 *         "lat":29.880224
 *         },
 *         "precise":0,
 *         "confidence":80,
 *         "level":"\u8d2d\u7269"
 *         }
 *         }
 */
function addrToGeo($address, $city = '')
{
    try {
        $params = array();
        $params['address'] = $address;
        $params['output'] = 'json';
        $params['key'] = '6d882b440c48d5e2d6cd11ab88a03eda'; // UMA使用baidu地图api的密钥
        $params['city'] = $city;
        
        $body = doGet('http://api.map.baidu.com/geocoder', $params);
        $rst = json_decode($body, true);
        return $rst;
    } catch (Exception $e) {
        logError(exceptionMsg($e));
        return array();
    }
}

/**
 * 根据$fields中的元素获取提交表单$_REQUEST(POST|GET|COOKIES)中的数据
 * 增加这个方法的适应性，进行基本的类型转换
 *
 * @param array $fields
 *            数组格式为：字段名=>类型（string|int|float|double|bool|array|strtotime）
 * @param boolen $onlyOne
 *            是否为单个变量获取 直接返回变量的值 而不是数组
 * @return array
 */
function getRequestDatas($fields, $onlyOne = false)
{
    $datas = array();
    if (is_array($fields) && count($fields) > 0) {
        foreach ($fields as $field => $type) {
            
            $field = trim($field);
            $type = strtolower(trim($type));
            
            if (isset($_REQUEST[$field])) {
                switch ($type) {
                    case 'str':
                        $value = trim(strval($_REQUEST[$field]));
                        break;
                    case 'string':
                        $value = trim(strval($_REQUEST[$field]));
                        break;
                    case 'integer':
                        $value = intval($_REQUEST[$field]);
                        break;
                    case 'int':
                        $value = intval($_REQUEST[$field]);
                        break;
                    case 'float':
                        $value = floatval($_REQUEST[$field]);
                        break;
                    case 'double':
                        $value = doubleval($_REQUEST[$field]);
                        break;
                    case 'boolean':
                        $value = is_bool($_REQUEST[$field]) ? $_REQUEST[$field] : false;
                        break;
                    case 'bool':
                        $value = is_bool($_REQUEST[$field]) ? $_REQUEST[$field] : false;
                        break;
                    case 'array':
                        $value = is_array($_REQUEST[$field]) ? $_REQUEST[$field] : array();
                        break;
                    case 'strtotime':
                        $value = strtotime(trim($_REQUEST[$field]));
                        break;
                    default:
                        if (function_exists($type)) {
                            $value = call_user_func($type, $_REQUEST[$field]);
                        } else {
                            $value = trim(strval($_REQUEST[$field]));
                        }
                        break;
                }
                $datas[$field] = $value;
            }
        }
    }
    if ($onlyOne)
        return array_shift(array_values($datas));
    return $datas;
}

/**
 * 列出某个目录下文件的最终修改时间
 *
 * @param string $dir            
 * @return number
 */
function dirmtime($dir)
{
    $last_modified = 0;
    $files = glob($dir . '/*');
    foreach ($files as $file) {
        if (is_dir($file)) {
            $modified = dirmtime($file);
        } else {
            $modified = filemtime($file);
        }
        if ($modified > $last_modified) {
            $last_modified = $modified;
        }
    }
    return $last_modified;
}

/**
 * 对于fastcgi模式加快返回速度
 */
if (! function_exists("fastcgi_finish_request")) {

    function fastcgi_finish_request()
    {}
}

/**
 * 分词处理，需要服务器安装scwc分词库作为支持
 *
 * @param string $str            
 * @return Array
 */
function scws($str)
{
    if (! function_exists('scws_open'))
        return false;
    
    $rst = array();
    $str = preg_replace("/[\s\t\r\n]+/", '', $str);
    if (! empty($str)) {
        $sh = scws_open();
        scws_set_charset($sh, 'utf8');
        scws_set_ignore($sh, true);
        scws_set_multi($sh, SCWS_MULTI_SHORT | SCWS_MULTI_DUALITY);
        scws_set_duality($sh, true);
        scws_send_text($sh, $str);
        while ($row = scws_get_result($sh)) {
            $rst = array_merge($rst, $row);
        }
        scws_close($sh);
    }
    return $rst;
}

/**
 * 分词处理，取出词频最高的词组，并可以指定词性进行查找
 *
 * @param string $str            
 * @param int $limit
 *            可选参数，返回的词的最大数量，缺省是 10
 * @param string $attr
 *            可选参数，是一系列词性组成的字符串，各词性之间以半角的逗号隔开，
 *            这表示返回的词性必须在列表中，如果以~开头，则表示取反，词性必须不在列表中，缺省为NULL，返回全部词性，不过滤。
 * @return multitype:
 */
function scwsTop($str, $limit = 10, $attr = null)
{
    if (! function_exists('scws_open'))
        return false;
    
    $rst = array();
    $str = preg_replace("/[\s\t\r\n]+/", '', $str);
    if (! empty($str)) {
        $sh = scws_open();
        scws_set_charset($sh, 'utf8');
        scws_set_ignore($sh, true);
        scws_set_multi($sh, SCWS_MULTI_SHORT | SCWS_MULTI_DUALITY);
        scws_set_duality($sh, true);
        scws_send_text($sh, $str);
        $rst = scws_get_tops($sh, $limit, $attr);
        scws_close($sh);
    }
    return $rst;
}

/**
 * 获取指定年份和星期的第一天和最后一天范围
 *
 * @param int $week            
 * @param int $year            
 * @return array
 */
function getWeekRange($week, $year)
{
    $timestamp = mktime(1, 0, 0, 1, 1, $year);
    $firstday = date("N", $timestamp);
    if ($firstday > 4)
        $firstweek = strtotime('+' . (8 - $firstday) . ' days', $timestamp);
    else
        $firstweek = strtotime('-' . ($firstday - 1) . ' days', $timestamp);
    
    $monday = strtotime('+' . ($week - 1) . ' week', $firstweek);
    $sunday = strtotime('+6 days', $monday);
    
    $start = date("Y-m-d", $monday);
    $end = date("Y-m-d", $sunday);
    
    return array(
        $start,
        $end
    );
}

/**
 * 加载某个目录下的全部文件
 *
 * @param string $dir            
 */
function requireDir($dir)
{
    if (! is_dir($dir))
        return false;
    
    $dir = realpath($dir);
    $files = glob("{$dir}/*.php");
    if (! is_array($files) || count($files) == 0)
        return false;
    
    foreach ($files as $filename) {
        if (basename($filename) !== basename(__FILE__))
            require_once $filename;
    }
    return true;
}

/**
 * 获取脚本的运行时间信息
 */
function getScriptExecuteInfo()
{
    $rst = array();
    $rst['cpuTimeSec'] = 0.000000; // CPU计算时间
    $rst['scriptTimeSec'] = 0.000000; // 脚本运行时间
    $rst['memoryPeakMb'] = (double) sprintf("%.6f", memory_get_peak_usage() / 1024 / 1024); // 内存使用峰值
    
    $scriptTime = 0.000000;
    $cpuTime = 0.000000;
    
    if (isset($_SERVER["REQUEST_TIME_FLOAT"]))
        $scriptTime = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
        
        // 计算CPU的使用时间
    if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
        $systemInfo = getrusage();
        $cpuTime = ($systemInfo["ru_utime.tv_sec"] + $systemInfo["ru_utime.tv_usec"] / 1e6) - PHP_CPU_RUSAGE;
        
        $rst['cpuTimeSec'] = (double) sprintf("%.6f", $cpuTime);
        $rst['scriptTimeSec'] = (double) sprintf("%.6f", $scriptTime);
    }
    
    return $rst;
}

/**
 * 从指定的URL上获取内容
 *
 * @param string $url            
 * @return string
 */
function getContentFromUrl($url)
{
    // 先通过路径获取图片资源,解决file_get_content不发送connect：close
    // 导致获取某些特定服务器资源缓慢的问题
    if (filter_var($url, FILTER_VALIDATE_URL) !== false) {
        $client = new Zend_Http_Client($url, array(
            'maxredirects' => 3,
            'timeout' => 300
        ));
        $response = $client->request('GET');
        if ($response->isError())
            throw new Exception($url . ', $response is error！');
        $content = $response->getBody();
    } else {
        $content = file_get_contents($url);
    }
    return $content;
}

/**
 * 获取手机归属地信息
 *
 * @param unknown_type $mobile
 *            手机号码
 * @return boolean arrary 手机号码不对
 *         array() {
 *         [mobile]=>
 *         "18799903355"
 *         [province]=>
 *         "新疆"
 *         [city]=>
 *         "阿克苏"
 *         [supplier]=>
 *         "新疆移动全球通卡"
 *         }
 */
function getMobileFrom($mobile)
{
    $url = "http://life.tenpay.com/cgi-bin/mobile/MobileQueryAttribution.cgi?chgmobile=$mobile";
    $back = simplexml_load_file($url);
    
    $result = array();
    if ($back->retmsg == 'OK') {
        $result['mobile'] = $mobile;
        $result['province'] = '' . $back->province;
        $result['city'] = '' . $back->city;
        $result['supplier'] = '' . $back->supplier;
    }
    return $result;
}

/**
 * 在生成缓存的过程中，锁定等待缓存完成，防止缓存被击穿,30秒后自动解锁
 *
 * @param string $cacheKey            
 * @return boolean
 */
function lockForGenerateCache($cacheKey)
{
    try {
        $lockFile = sys_get_temp_dir() . '/cache_lock_' . md5($_SERVER['HTTP_HOST'] . $cacheKey);
        if (! file_exists($lockFile)) {
            file_put_contents($lockFile, time());
            return true;
        } else {
            $lockTime = (int) file_get_contents($lockFile);
            if (time() - $lockTime > 30) {
                unlink($lockFile);
                return false;
            } else {
                exit('please wait generate cache, left time:' . (time() - $lockTime));
            }
        }
    } catch (Exception $e) {
        var_dump($e->getLine() . $e->getFile() . $e->getMessage());
        return false;
    }
}

/**
 * 针对上面的函数解锁
 *
 * @param string $cacheKey            
 * @return boolean
 */
function unlockForGenerateCache($cacheKey)
{
    try {
        $lockFile = sys_get_temp_dir() . '/cache_lock_' . md5($_SERVER['HTTP_HOST'] . $cacheKey);
        unlink($lockFile);
        return true;
    } catch (Exception $e) {
        var_dump($e->getLine() . $e->getFile() . $e->getMessage());
        return false;
    }
}

/**
 * 如果特殊情况，清楚文件未生效，清理全部缓存锁文件
 *
 * @return boolean
 */
function clearCacheLockTempFile()
{
    $pattern = sys_get_temp_dir() . '/cache_lock_*';
    $files = glob($pattern);
    foreach ($files as $file) {
        unlink($file);
    }
    return true;
}

/**
 * 创建分页信息
 *
 * @param string $url
 *            URL
 * @param string $record_count
 *            记录总数
 * @param string $page
 *            当前页
 * @param string $size
 *            每页记录数
 * @param string $sch
 *            查询关键字
 * @return pager array
 */
function createPager($url, $record_count, $page = 1, $size = 10, $sch = array())
{
    $url .= "?" . http_build_query($sch);
    $url_format = $url . "&page=";
    
    $page = intval($page);
    if ($page < 1) {
        $page = 1;
    }
    $page_count = $record_count > 0 ? intval(ceil($record_count / $size)) : 1;
    $pager['page'] = $page;
    $pager['size'] = $size;
    $pager['record_count'] = $record_count;
    $pager['page_count'] = $page_count;
    $pager['url'] = $url;
    
    /* 分页样式 */
    $pager['styleid'] = 1;
    $page_prev = ($page > 1) ? $page - 1 : 1;
    $page_next = ($page < $page_count) ? $page + 1 : $page_count;
    
    if ($pager['styleid'] == 0) {
        $pager['page_first'] = $url_format . 1;
        $pager['page_prev'] = $url_format . $page_prev;
        $pager['page_next'] = $url_format . $page_next;
        $pager['page_last'] = $url_format . $page_count;
        $pager['page_number'] = array();
        for ($i = 1; $i <= $page_count; $i ++) {
            $pager['page_number'][$i] = $i;
        }
    } else {
        $_pagenum = 10; // 显示的页码
        $_offset = 2; // 当前页偏移值
        $_from = $_to = 0; // 开始页, 结束页
        if ($_pagenum > $page_count) {
            $_from = 1;
            $_to = $page_count;
        } else {
            $_from = $page - $_offset;
            $_to = $_from + $_pagenum - 1;
            if ($_from < 1) {
                $_to = $page + 1 - $_from;
                $_from = 1;
                if ($_to - $_from < $_pagenum) {
                    $_to = $_pagenum;
                }
            } elseif ($_to > $page_count) {
                $_from = $page_count - $_pagenum + 1;
                $_to = $page_count;
            }
        }
        
        $pager['page_first'] = ($page - $_offset > 1 && $_pagenum < $page_count) ? $url_format . 1 : '';
        $pager['page_prev'] = ($page > 1) ? $url_format . $page_prev : '';
        $pager['page_next'] = ($page < $page_count) ? $url_format . $page_next : '';
        $pager['page_last'] = ($_to < $page_count) ? $url_format . $page_count : '';
        $pager['page_kbd'] = ($_pagenum < $page_count) ? true : false;
        $pager['page_number'] = array();
        for ($i = $_from; $i <= $_to; ++ $i) {
            $pager['page_number'][$i] = $url_format . $i;
        }
    }
    return $pager;
}

/**
 * 生成n位的随机码
 *
 * @return string
 */
function createRandVCode($n = 4, $session_start = false)
{
    $str = Array(); // 用来存储随机码
    $string = "ABCDEFGHIJKLMNPQRSTUVWXY3456789"; // 随机挑选其中4个字符，也可以选择更多，注意循环的时候加上，宽度适当调整
                                                 // $string =
                                                 // "1234567890";//随机挑选其中4个字符，也可以选择更多，注意循环的时候加上，宽度适当调整
    $vcode = "";
    $strlen = strlen($string) - 1;
    for ($i = 0; $i < $n; $i ++) {
        $str[$i] = $string[rand(0, $strlen)];
        $vcode .= $str[$i];
    }
    if ($session_start) {
        session_start(); // 启用超全局变量session
    }
    $_SESSION["codevalue"] = $vcode;
    return $vcode;
}

/**
 * 进行mongoid和tostring之间的转换
 * 增加函数mongoid用于mongoid和字符串形式之间的自动转换
 *
 * @param mixed $var            
 * @return string MongoId
 */
function myMongoId($var = null)
{
    if (is_array($var)) {
        $newArray = array();
        foreach ($var as $row) {
            if ($row instanceof MongoId) {
                $newArray[] = $row->__toString();
            } else {
                try {
                    $newArray[] = new MongoId($row);
                } catch (Exception $e) {
                    continue;
                }
            }
        }
        return $newArray;
    } else {
        if ($var instanceof MongoId) {
            return $var->__toString();
        } else {
            $var = ! empty($var) && strlen($var) == 24 ? $var : null;
            try {
                return new MongoId($var);
            } catch (Exception $e) {
                fb(exceptionMsg($e), 'LOG');
                return new MongoId();
            }
        }
    }
}

/**
 * 查找文档中的链接，并在URL后面追加参数
 *
 * @param array $extraArray            
 * @return string unknown mixed
 */
function followUrl($body, $extraArray)
{
    $filters = array(
        'jpg',
        'jpeg',
        'png',
        'js',
        'css',
        'gif',
        'mp3'
    ); // 过滤指定后缀名URL
    $regex = "(?:http|https|ftp|ftps)://(?:[a-zA-Z0-9\-]*\.)+[a-zA-Z0-9]{2,4}(?:/[a-zA-Z0-9=.\?&\-\%/_,]*)?";
    // 为外链增加相应的需要传递的微信变量
    $body = preg_replace_callback("#$regex#im", function ($matchs) use($extraArray, $filters)
    {
        $url = $matchs[0];
        $parseUrl = parse_url($url);
        if (isset($parseUrl['path'])) {
            $tmp = explode('.', $parseUrl['path']);
            if (! in_array(end($tmp), $filters)) {
                $replace = strpos($url, '?') === false ? $url . '?' . http_build_query($extraArray) : $url . '&' . http_build_query($extraArray);
                return $replace;
            } else {
                return $url;
            }
        } else 
            if (isset($parseUrl['host'])) {
                return strpos($url, '?') === false ? $url . '?' . http_build_query($extraArray) : $url . '&' . http_build_query($extraArray);
            } else {
                return $url;
            }
    }, $body);
    return $body;
}

/**
 * 效仿数组函数的写法，实现复制数组。目的是为了解除内部变量的引用关系
 *
 * @param array $arr            
 * @return array
 */
function array_copy($arr)
{
    $newArray = array();
    foreach ($arr as $key => $value) {
        if (is_array($value))
            $newArray[$key] = array_copy($value);
        else 
            if (is_object($value))
                $newArray[$key] = clone $value;
            else
                $newArray[$key] = $value;
    }
    return $newArray;
}

/**
 * 递归方法unset数组里面的元素
 *
 * @param array $array            
 * @param array|string $fields            
 * @param boolean $remove
 *            true表示删除数组$array中的$fields属性 false表示保留数组$array中的$fields属性
 */
function array_unset_recursive(&$array, $fields, $remove = true)
{
    if (! is_array($fields)) {
        $fields = array(
            $fields
        );
    }
    foreach ($array as $key => &$value) {
        if ($remove) {
            if (in_array($key, $fields, true)) {
                unset($array[$key]);
            } else {
                if (is_array($value)) {
                    array_unset_recursive($value, $fields, $remove);
                }
            }
        } else {
            if (! in_array($key, $fields, true)) {
                unset($array[$key]);
            } else {
                if (is_array($value)) {
                    array_unset_recursive($value, $fields, $remove);
                }
            }
        }
    }
}

/**
 * 判断是否为微信的浏览器
 *
 * @return boolean
 */
function isWeixinBrowser()
{
    if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), strtolower('MicroMessenger')) !== false) {
        return true;
    } else {
        return false;
    }
}

/**
 * 获取微信版本
 *
 * @return string unknown
 */
function getWeixinVersion()
{
    $patten = "/MicroMessenger\/(.*)/i";
    preg_match($patten, $_SERVER['HTTP_USER_AGENT'], $matchs);
    if (empty($matchs[1])) {
        return "";
    } else {
        return $matchs[1];
    }
}

/**
 * 标准化数据返回结果json或jsonp返回信息
 *
 * @param string $jsonpcallback            
 * @param boolean $stat            
 * @param string $msg            
 * @param string $result            
 * @return mixed
 */
function jsonpcallback($jsonpcallback = "", $stat = true, $msg = "OK", $result = "")
{
    if (! empty($jsonpcallback)) {
        return $jsonpcallback . '(' . json_encode(array(
            'success' => $stat,
            'message' => $msg,
            'result' => $result
        )) . ')';
    } else {
        return json_encode(array(
            'success' => $stat,
            'message' => $msg,
            'result' => $result
        ));
    }
}

// 限制请求的数量
function isRequestRestricted($cacheKey, $timeSpanLimit = 300, $numLimit = 10)
{
    $isRestrict = false;
    
    $cache = Zend_Registry::get('cache');
    if (($cacheInfo = $cache->load($cacheKey)) === false) {
        $cacheInfo['requestnum'] = 0;
        $cacheInfo['cachetime'] = time();
    }
    $requestnum = $cacheInfo['requestnum'];
    $cachetime = $cacheInfo['cachetime'];
    $requestnum ++;
    $cacheInfo['requestnum'] = $requestnum;
    $cache->save($cacheInfo, $cacheKey, array(), $timeSpanLimit);
    $now = time();
    if (($now - $cachetime) > $timeSpanLimit && $requestnum <= $numLimit)     // 如果超过$timeSpanLimit秒并且没有达到$numLimit那个阀值
    {
        // 清除缓存重新计数
        $cache->remove($cacheKey);
    }
    if ($requestnum > $numLimit) {
        $isRestrict = true;
    }
    return $isRestrict;
}

// 将对象转化成数组
function object2Array($object)
{
    return @json_decode(@json_encode($object), 1);
}

/**
 * 生成n位的随机码
 *
 * @return string
 */
function createRandCode($n = 32)
{
    $str = Array(); // 用来存储随机码
    $string = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    $code = "";
    for ($i = 0; $i < $n; $i ++) {
        $str[$i] = $string[rand(0, $n - 1)];
        $code .= $str[$i];
    }
    return $code;
}

/**
 * 获取某个方法的参数的唯一签名
 *
 * @param string $class            
 * @param string $method            
 * @return number
 */
function getClassMethodArgumentCacheKey($class, $method)
{
    $obj = new ReflectionMethod($class, $method);
    return crc32(serialize($obj->getParameters()));
}

/**
 * 显示金额
 *
 * @param number $money            
 * @return string
 */
function showMoney($money)
{
    return sprintf("%01.2f", $money);
}

/**
 * 获取弧度
 *
 * @param number $d            
 * @return number
 */
function rad($d)
{
    return $d * M_PI / 180.0;
}

/**
 * 获取2个经纬度之间的距离（米）
 *
 * @param number $lat1            
 * @param number $lng1            
 * @param number $lat2            
 * @param number $lng2            
 * @return number
 */
function GetDistance($lat1, $lng1, $lat2, $lng2)
{
    $EARTH_RADIUS = 6378.137; // 地球半径 千米
    $radlat1 = rad($lat1);
    $radlat2 = rad($lat2);
    $a = $radlat1 - $radlat2;
    $b = rad($lng1) - rad($lng2);
    
    $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radlat1) * cos($radlat2) * pow(sin($b / 2), 2)));
    $s = $s * $EARTH_RADIUS;
    $s = round($s * 10000) / 10000;
    return $s * 1000; // 米
}

/**
 * 删除整个目录
 *
 * @param string $dir            
 * @return boolean
 */
function delDir($dir)
{
    $files = array_diff(scandir($dir), array(
        '.',
        '..'
    ));
    foreach ($files as $file) {
        (is_dir("$dir/$file")) ? delDir("$dir/$file") : unlink("$dir/$file");
    }
    return rmdir($dir);
}

/**
 * 调用其他资源
 *
 * @param string $module            
 * @param string $controller            
 * @param string $action            
 * @param array $params
 *            $_GET的方式传递参数
 * @return string
 */
function invokeResource($module, $controller, $action, $params)
{
    $module = ucfirst(strtolower($module));
    $controller = ucfirst(strtolower($module));
    $action = preg_replace_callback("/-\[a-z]/", function ($match)
    {
        return strtoupper(str_replace('-', '', $match));
    }, $action);
    
    $loader = new Zend_Application_Module_Autoloader(array(
        'namespace' => $module,
        'basePath' => APPLICATION_PATH . '/modules/' . strtolower($module)
    ));
    
    $__OLD_GET__ = $_GET;
    $_GET = array_merge($_GET, $params);
    ob_start();
    $invoke = call_user_func_array(array(
        "{$module}_{$controller}Controller" => "{$action}Action"
    ), array());
    $response = ob_get_content();
    ob_end_clean();
    $_GET = $__OLD_GET__;
    
    return $response;
}
