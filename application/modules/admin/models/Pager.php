<?php

class Admin_Model_Pager
{

    /**
     * 分页的信息加入条件的数组
     *
     * @access public
     * @return array
     */
    public function getPage($filter, $input)
    {
        if (isset($input->page_size) && intval($input->page_size) > 0) {
            $filter['page_size'] = intval($input->page_size);
        } elseif (isset($_COOKIE['ECSCP']['page_size']) && intval($_COOKIE['ECSCP']['page_size']) > 0) {
            $filter['page_size'] = intval($_COOKIE['ECSCP']['page_size']);
        } else {
            $filter['page_size'] = 10;
        }
        
        /* 每页显示 */
        $filter['page'] = (empty($input->page) || intval($input->page) <= 0) ? 1 : intval($input->page);
        
        $filter['start'] = ($filter['page'] - 1) * $filter['page_size'];
        
        return $filter;
    }

    /**
     * 分页的信息加入条件的数组
     *
     * @access public
     * @return array
     */
    public function setPage($filter, $input)
    {
        $filter['page_count'] = (! empty($filter['record_count']) && $filter['record_count'] > 0) ? ceil($filter['record_count'] / $filter['page_size']) : 1;
        
        /* 边界处理 */
        if ($filter['page'] > $filter['page_count']) {
            $filter['page'] = $filter['page_count'];
        }
        
        return $filter;
    }

    /**
     * 取得上次的过滤条件
     *
     * 参数字符串，由list函数的参数组成
     *
     * @return 如果有，返回array('filter' => $filter)；否则返回false
     */
    public function get_filter()
    {
        if (isset($_GET['uselastfilter']) && isset($_COOKIE['ECSCP']['lastfilterfile'])) {
            return array(
                'filter' => unserialize(urldecode($_COOKIE['ECSCP']['lastfilter']))
            );
        } else {
            return false;
        }
    }

    /**
     * 保存过滤条件
     *
     * @param array $filter
     *            过滤条件
     */
    public function set_filter($filter)
    {
        setcookie('ECSCP[lastfilter]', urlencode(serialize($filter)), time() + 600);
    }
}