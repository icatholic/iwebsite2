<?php

/**
 * SkinDetection class
 *
 * @copyright	Copyright (c) Kevin Kettinger <kkettinger@gmail.com>
 */
class iSkin
{

    /**
     * threshold for hue (H)
     */
    private $threshold_h_min = 0.04;

    private $threshold_h_max = 0.04;

    /**
     * threshold for saturation (S)
     */
    private $threshold_s_min = 0.09;

    private $threshold_s_max = 0.09;

    /**
     * skin colors in rgb-format
     */
    private $skin_colors = array(
            array(
                    207,
                    126,
                    97
            ),
            array(
                    209,
                    136,
                    95
            ),
            array(
                    186,
                    122,
                    97
            ),
            array(
                    188,
                    124,
                    96
            ),
            array(
                    223,
                    168,
                    161
            ),
            array(
                    184,
                    147,
                    128
            ),
            array(
                    170,
                    117,
                    77
            ),
            array(
                    199,
                    142,
                    99
            ),
            array(
                    205,
                    123,
                    83
            ),
            array(
                    219,
                    169,
                    160
            ),
            array(
                    175,
                    120,
                    79
            ),
            array(
                    190,
                    97,
                    82
            ),
            array(
                    193,
                    94,
                    71
            ),
            array(
                    218,
                    147,
                    115
            ),
            array(
                    197,
                    110,
                    90
            ),
            array(
                    202,
                    136,
                    120
            ),
            array(
                    155,
                    83,
                    61
            ),
            array(
                    226,
                    127,
                    88
            ),
            array(
                    238,
                    128,
                    101
            ),
            array(
                    172,
                    96,
                    72
            ),
            array(
                    207,
                    173,
                    146
            ),
            array(
                    189,
                    140,
                    126
            ),
            array(
                    210,
                    146,
                    134
            ),
            array(
                    237,
                    189,
                    169
            ),
            array(
                    146,
                    71,
                    52
            ),
            array(
                    203,
                    126,
                    84
            ),
            array(
                    226,
                    180,
                    164
            ),
            array(
                    143,
                    61,
                    21
            ),
            array(
                    194,
                    133,
                    112
            ),
            array(
                    193,
                    129,
                    85
            ),
            array(
                    222,
                    187,
                    167
            ),
            array(
                    251,
                    180,
                    138
            ),
            array(
                    200,
                    139,
                    118
            ),
            array(
                    229,
                    182,
                    138
            )
    );

    private $_image;

    /**
     *
     * @param mixed $src
     *            resource|string(path or url)
     */
    public function __construct ($src)
    {
        if (is_resource($src)) {
            $this->_image = $src;
        } else {
            if (filter_var($src, FILTER_VALIDATE_URL) !== false) {
                $client = new Zend_Http_Client($url, 
                        array(
                                'maxredirects' => 3,
                                'timeout' => 10
                        ));
                $response = $client->request('GET');
                if ($response->isError())
                    throw new Exception($url . ', $response is error！');
                $src = $response->getBody();
            } else {
                $src = file_get_contents($url);
            }
            $this->_image = imagecreatefromstring($src);
        }
    }

    /**
     * 返回指定图片区域的肤色
     *
     * @param int $x            
     * @param int $y            
     * @param int $w            
     * @param int $h            
     * @return bool true or false
     */
    public function isSkinColorFromPicture ($x, $y, $w, $h)
    {
        $image = $this->_image;
        $dst = imagecreatetruecolor($w, $h);
        imagecopy($dst, $image, 0, 0, $x, $y, $w, $h);
        $color_index = imagecolorat($dst, 0, 0);
        $color_tran = imagecolorsforindex($dst, $color_index);
        
        $skin = 0;
        for ($yy = 0; $yy < $w; $yy ++) {
            for ($xx = 0; $xx < $h; $xx ++) {
                $color = imagecolorsforindex($dst, imagecolorat($dst, $xx, $yy));
                if ($this->isSkin($color["red"], $color["green"], 
                        $color["blue"])) {
                    $skin ++;
                }
            }
        }
        
        $percent = $skin / ($xx * $yy);
        imagedestroy($dst);
        unset($image);
        if ($percent > 0.5)
            return true;
        return false;
    }

    /**
     * Main method for comparing a color against
     * the skin-color database
     */
    private function isSkin ($r, $g, $b)
    {
        
        // transform to hsv-colorspace
        $hsv = $this->transformRGB2HSV($r, $g, $b);
        
        // go trough each entry of the skin-colors
        foreach ($this->skin_colors as $color) {
            
            // transform each entry to the hsv-colorspace
            // @todo color-database in hsv
            $hsv_skin = $this->transformRGB2HSV($color[0], $color[1], $color[2]);
            
            // range for hue
            $range_h_min = ($hsv_skin["h"] - $this->threshold_h_min < 0) ? 0 : $hsv_skin["h"] -
                     $this->threshold_h_min;
            $range_h_max = ($hsv_skin["h"] + $this->threshold_h_max > 1) ? 1 : $hsv_skin["h"] +
                     $this->threshold_h_max;
            
            // range for saturation
            $range_s_min = ($hsv_skin["s"] - $this->threshold_s_min < 0) ? 0 : $hsv_skin["s"] -
                     $this->threshold_s_min;
            $range_s_max = ($hsv_skin["s"] + $this->threshold_s_max > 1) ? 1 : $hsv_skin["s"] +
                     $this->threshold_s_max;
            
            // test color
            if ($hsv["h"] >= $range_h_min and $hsv["h"] <= $range_h_max and
                     $hsv["s"] >= $range_s_min and $hsv["s"] <= $range_s_max) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Method for transforming the rgb-colorspace
     * to the hsv-colorspace.
     *
     * @copyright Not me.
     */
    private function transformRGB2HSV ($r, $g, $b)
    {
        $v = max($r, $g, $b);
        $t = min($r, $g, $b);
        $s = ($v == 0) ? 0 : ($v - $t) / $v;
        if ($s == 0)
            $h = - 1;
        else {
            $a = $v - $t;
            $cr = ($v - $r) / $a;
            $cg = ($v - $g) / $a;
            $cb = ($v - $b) / $a;
            $h = ($r == $v) ? $cb - $cg : (($g == $v) ? 2 + $cr - $cb : (($b ==
                     $v) ? $h = 4 + $cg - $cr : 0));
            $h = 60 * $h;
            $h = ($h < 0) ? $h + 360 : $h;
        }
        return array(
                'h' => $h,
                's' => $s,
                'v' => $v
        );
    }
}
