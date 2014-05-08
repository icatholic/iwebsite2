<?php

abstract class iWebsite_Controller_Admin_Action extends iWebsite_Controller_Action
{

    protected function _getValidationMessage($input)
    {
        $messageInfo = "";
        foreach ($input->getMessages() as $messageID => $message) {
            if (is_array($message)) {
                $messageInfo .= "Validation failure '$messageID':<br/>";
                foreach ($message as $key => $value) {
                    $messageInfo .= "Validation failure '$key': $value<br/>";
                }
            } else {
                $messageInfo .= "Validation failure '$messageID': $message<br/>";
            }
        }
        return $messageInfo;
    }

    /**
     * 根据过滤条件获得排序的标记
     *
     * @access public
     * @param array $filter            
     * @return array
     */
    public function sortFlag($filter)
    {
        $path = $this->view->getResourceUrl();
        $flag['tag'] = 'sort_' . preg_replace('/^.*\./', '', $filter['sort_by']);
        $flag['img'] = "<img src=\"" . $path . "/img/" . ($filter['sort_order'] == "DESC" ? 'sort_desc.gif' : 'sort_asc.gif') . '"/>';
        
        return $flag;
    }

    public function sysMsg($msg_detail, $msg_type = 0, $links = array(), $auto_redirect = true)
    {
        if (count($links) == 0) {
            $links[0]['text'] = '返回上一页';
            $links[0]['href'] = 'javascript:history.go(-1)';
        }
        
        $this->view->assign('ur_here', '系统信息');
        $this->view->assign('msg_detail', $msg_detail);
        $this->view->assign('msg_type', $msg_type);
        $this->view->assign('links', $links);
        $this->view->assign('default_url', $links[0]['href']);
        $this->view->assign('auto_redirect', $auto_redirect);
        
        $this->view->addScriptPath(APPLICATION_PATH . '/modules/admin/views/scripts');
        echo $this->view->render('error/message.phtml');
        exit();
    }
}