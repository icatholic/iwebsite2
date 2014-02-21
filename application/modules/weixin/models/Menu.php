<?php

class Weixin_Model_Menu extends iWebsite_Plugin_Mongo
{

    protected $name = 'iWeixin_menu';

    protected $dbName = 'weixin';

    private $_weixin;

    private $_hookKey = '';

    /**
     * 设置微信对象
     */
    public function setWeixinInstance(iWeixin $weixin)
    {
        $this->_weixin = $weixin;
    }

    /**
     * 构建菜单
     * @return array
     */
    public function buildMenu()
    {
        $menus = $this->findAll(array(), array(
            'priority' => - 1
        ), array(
            '_id' => false,
            'parent' => true,
            'type' => true,
            'name' => true,
            'key' => true,
            'url' => true
        ));
        if (! empty($menus)) {
            
            $parent = array();
            $new = array();
            foreach ($menus as $a) {
                if (empty($a['parent']))
                    $parent[] = $a;
                
                $new[$a['parent']][] = $a;
            }
            
            $tree = $this->buildTree($new, $parent);
        }
        return array(
            'button' => $tree
        );
    }

    /**
     * 循环处理菜单
     * @param array $menus
     * @param array $parent
     * @return array
     */
    private function buildTree(&$menus, $parent)
    {
        $tree = array();
        foreach ($parent as $k => $l) {
            $type = $l['type'];
            if (isset($menus[$l['key']])) {
                $l['sub_button'] = $this->buildTree($menus, $menus[$l['key']]);
                unset($l['type'], $l['key'], $l['url']);
            }
            if ($type == 'view' && isset($l['key']))
                unset($l['key']);
            if ($type == 'click' && isset($l['url']))
                unset($l['url']);
            unset($l['parent'], $l['priority']);
            $tree[] = $l;
        }
        return $tree;
    }
}