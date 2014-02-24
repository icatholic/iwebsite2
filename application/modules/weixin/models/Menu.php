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
            '_id' => true,
            'parent' => true,
            'type' => true,
            'name' => true,
            'key' => true,
            'url' => true
        ));
        if (! empty($menus)) {
            $menus = convertToPureArray($menus);
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
            if (isset($menus[$l['_id']])) {
                $l['sub_button'] = $this->buildTree($menus, $menus[$l['_id']]);
                unset($l['type'], $l['key'], $l['url'], $l['_id']);
            }
            if ($type == 'view' && isset($l['key']))
                unset($l['key']);
            if ($type == 'click' && isset($l['url']))
                unset($l['url']);
            unset($l['parent'], $l['priority'], $l['_id']);
            $tree[] = $l;
        }
        return $tree;
    }
}