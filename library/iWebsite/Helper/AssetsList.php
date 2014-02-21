<?php

class iWebsite_Helper_AssetsList extends Zend_Controller_Action_Helper_Abstract
{

    /**
     * 对于注释中，使用@name 的变量内容，自动读取为方法名
     * 
     * @return array
     */
    public function getList ()
    {
        $module_dir = $this->getFrontController()->getControllerDirectory();
        $resources = array();
        foreach ($module_dir as $dir => $dirpath) {
            if (is_dir($dirpath)) {
                $diritem = new DirectoryIterator($dirpath);
                foreach ($diritem as $item) {
                    if ($item->isFile()) {
                        if (strstr($item->getFilename(), 'Controller.php') !=
                                 FALSE) {
                            include_once $dirpath . '/' . $item->getFilename();
                        }
                    }
                }
                foreach (get_declared_classes() as $class) {
                    if (is_subclass_of($class, 'Zend_Controller_Action') &&
                             substr($class, - 10) == 'Controller') {
                        $c = substr($class, 0, strpos($class, "Controller"));
                        $c = $this->methodToRouter($c);
                        $c = strtolower($c);
                        if (strpos($c, $dir) === 0) {
                            $functions = array();
                            foreach (get_class_methods($class) as $method) {
                                if (strstr($method, 'Action') != false) {
                                    try {
                                        $r = new Zend_Reflection_Method($class, 
                                                $method);
                                        $docblock = $r->getDocblock();
                                        
                                        $method = substr($method, 0,
                                                strpos($method, "Action"));
                                        $method = $this->methodToRouter(
                                                $method);
                                        if ($docblock->hasTag('name')) {
                                            $tag = $docblock->getTag('name');
                                            $name = $tag->getDescription();
                                        } else {
                                            $name = $c . '::' . $method;
                                        }
                                    } catch (Exception $e) {
                                        $method = substr($method, 0, 
                                                strpos($method, "Action"));
                                        $method = $this->methodToRouter($method);
                                        $name = $c . '::' . $method;
                                    }
                                    
                                    $function = array(
                                            'name' => $name,
                                            'method' => $method
                                    );
                                    array_push($functions, $function);
                                }
                            }
                            
                            $resources[$dir][$c] = $functions;
                        }
                    }
                }
            }
        }
        return $resources;
    }

    private function methodToRouter ($name)
    {
        $name = preg_replace("/([a-z0-9])([A-Z])/", "$1-$2", trim($name));
        return strtolower($name);
    }
}
