<?php
class Zend_View_Helper_ShowPageSelect extends Zend_View_Helper_Abstract
{
	public function showPageSelect($name,
									$count = 1,
									$value = 1,
									$attribs = null,
									$listsep = "<br />\n")
	{
		$page_list = array();
		for($i=1;$i<=$count;$i++){
			$page_list["$i"] = $i;
		}
		ksort($page_list);
		return $this->view->formSelect($name,$value,$attribs,$page_list,$listsep);
    }
}