<?php
/**
 * 
 * 基于指定初始值K值的k均值聚类实现
 * 
 * @author Young
 * @date 2013-07-08
 * @desc
 * 支持两种模式的k-means 
 * 1. 给定分类文本数据的关键词设定 
 * 2. 给定分类数量进行自动分类算法
 * 
 */
class iKmeans
{
    private $_matrix = array();
    private $_category = array();
    private $_rows;
    private $_cols;
    private $_centroid = array();
    private $_k = 0;
    private $_scws_limit = 20;
    private $_scws_attrs = null;
    private $_classification_feature_words = null;
    
    public function __construct($datas,$categories) {
        if(is_int($categories)) {
            $keys = array_rand($datas,$categories);
            
            $categories = array();
            foreach ($keys as $key) {
                $categories[] = $datas[$key];
            }
        }
        
        $this->dealData($datas,$categories);
		$this->_k = count($this->_cols);
		$this->initCentroid();
    }
    
    /**
     * 对应各个分类的超大合集特征词汇特征词组成的m*n阶二维数组
     * array(
     *     array('c1_word1','c1_word2'……,'c1_wordn'),
     *     array('c2_word1','c2_word2'……,'c2_wordn'),
     *     ……
     *     array('cm_word1','cm_word2'……,'cm_wordn'),
     * )
     * @param array $words
     */
    public function setClassificationFeatureWords(Array $words) {
        $this->_classification_feature_words = $words;
    }
    
    /**
     * 设定单一文档的关键词提取数量 top $limit
     * @param unknown_type $limit
     */
    public function setLimit($limit=20) {
        $this->_scws_limit = $limit;
    }
    
    /**
     * 推荐可选参数如下：
     * 排除代词 连词 语气助词 拟声词等之后的属性类型
     * $attrs = array('Ag','a','ad','an','Dg','d','f','g','i','l','Ng','n','nr','ns','nt','nz','s','Tg','t','Vg','v','vd','vn','x','z');
     * 设定需要分词处理中返回的关键词词性，null表示全部词性 设定数组表示指定数组范围的词性返回
     * @param mixed $attrs null array
     */
    public function setAttrs($attrs=null) {
        $this->_scws_attrs = $attrs;
    }
    
    /**
     * 计算两个数组的余弦相似度
     * @param array $basic 
     * @param array $str
     * @return float 余弦相似度
     */
    private function cosine_distanse(Array $basic,Array $str) {
        if(!is_array($basic) || empty($basic) || !is_array($str) || empty($str)) {
            return 0;
        }
        
        $words = array_merge($basic,$str);
        $basic = array_count_values($basic);
        $str   = array_count_values($str);
        $a = $b = $c = 0;
        foreach($words as $word) {
            $x1 = isset($basic[$word]) ? intval($basic[$word]) : 0;
            $x2 = isset($str[$word]) ? intval($str[$word]) : 0;
            $a += $x1*$x2;
            $b += pow($x1,2);
            $c += pow($x2,2);
        }
        
        $denominator = sqrt($b)*sqrt($c);
        if($denominator==0)
            return 0;
        return (float) $a/$denominator;
    }
    
    /**
     * 
     * @param string $str
     * @return array
     */
    private function strToArray($str) {
        if(empty($str)) return array();
        $keywords = scws_top($str,$this->_scws_limit,$this->_scws_attrs);
        $rst = array();
        foreach($keywords as $keyword) {
            $rst[] = $keyword['word'];
        }
        return $rst;
    }
    
    /**
     * 处理数据
     */
    private function dealData($datas,$categories){
        array_walk($datas, function(&$value,$key) {
            $value = $this->strToArray($value);
        });
        
        array_walk($categories,function(&$value,$key) {
            if(is_string($value))
                $value = $this->strToArray($value);
        });
        
        $this->_rows = $datas;
        $this->_cols = $categories;
        
		$this->dealMatrix();
    }
    
    /**
     * 数据矩阵
     * @return array
     */
    private function dealMatrix() {
        foreach($this->_rows as $i=>$row) {
            foreach($this->_cols as $j=>$col) {
                if($this->_classification_feature_words==null)
                    $this->_matrix[$i][$j] = $this->cosine_distanse($row, $col);
                elseif(isset( $this->_classification_feature_words[$j])) 
                    $this->_matrix[$i][$j] = $this->cosine_distanse($row, $this->_classification_feature_words[$j]);
                else
                    throw new Exception('$this->_classification_feature_words[$j] is undefined');
            }
        }
        return $this->_matrix;
    }
    
    /**
     * 初始化k个质心位置 k数量和cols数量一致，指定初始的种子中心
     * @return array
     */
    private function initCentroid() {
        foreach($this->_cols as $i=>$col) {
            $centroid = array();
            foreach($this->_cols as $_col_clone) {
                if(isset( $this->_classification_feature_words[$i]))
                    $centroid[] = $this->cosine_distanse($_col_clone, $this->_classification_feature_words[$i]);
                else
                    $centroid[] = $this->cosine_distanse($_col_clone, $col);
            }
            $this->_centroid[] = $centroid;
        }
        return $this->_centroid;
    }
    
    /**
     * 执行一轮k-means运算
     * @return 质心移动小于0.01时，认为函数收敛；迭代次数达到最大时认为函数收敛
     */
    private function kmeans() {
        $categoryMatrix = array();
        $dist = array();
        $this->_category  = array();
        $oldCentroid =  $this->_centroid;
        
        foreach($this->_matrix as $i=>$row) {
            foreach($this->_centroid as $c=>$centroid) {
                $c_r_dist = $this->dist($centroid, $row);
                if($c_r_dist>0)
                    $dist[$c] = $c_r_dist;
            }
            
            if(!empty($dist)) {
                $minDist = min($dist);
                $category = array_search($minDist, $dist);
                $categoryMatrix[$category][] = $row;
                $this->_category[] = $category;
            }
        }

        //根据$categoryMatrix重新计算新的种子点
        $this->_centroid = array();
        foreach($categoryMatrix as $category=>$rows) {
            $this->_centroid[$category] = array();
            $centroid = array();
            foreach($rows as $r=>$row) {
                for($i=0;$i<$this->_k;$i++) {
                    if(!isset($centroid[$i]))
                        $centroid[$i] = 0;
                    $centroid[$i] += $row[$i];
                    if($i>0)
                        $centroid[$i] = $centroid[$i]/2;
                }
            }
            $this->_centroid[$category] = $centroid;
        }
        
        $break = 0;
        foreach($oldCentroid as $i=>$cent) {
            $curCent = isset($this->_centroid[$i]) ? $this->_centroid[$i] : 0;
            if($this->dist($cent, $curCent)<0.01) {
                $break++;
            }
            if($break==$this->_k) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * 执行k-means聚类
     * @param int $maxIter 最大迭代次数
     */
    public function deal($maxIter=10) {
        $maxIter = (int) $maxIter;
        if($maxIter>pow(10, 3) || $maxIter<0) $maxIter=10;
        do {
            $rst = $this->kmeans();
            $maxIter--;
        }
        while($rst && $maxIter>0);
        return $this->getCategory();
    }
    
    /**
     * 获取最终的分类结果
     * @return array
     */
    public function getCategory() {
        return $this->_category;
    }
    
    /**
     * 计算种子到所有点的欧式距离,相异度
     * d(A，B) =sqrt [ ∑( ( a[i] - b[i] )^2 ) ] (i = 1，2，…，n)
     * @param array $mean
     * @param array $sample
     */
    private function dist($centroid,$sample) {
        $dist = 0;
        foreach($centroid as $k=>$v) {
            $dist += pow(abs($centroid[$k]-$sample[$k]),2);
        }
        return sqrt($dist);
    }
    
}