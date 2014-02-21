<?php 
class iWebsite_Router_Cli extends Zend_Controller_Router_Abstract {
      public function route (Zend_Controller_Request_Abstract $dispatcher) {
		$getopt 	= new Zend_Console_Getopt (array ());
		$arguments 	= $getopt->getRemainingArgs();

		$module     = 'cronjob';
		$controller = 'index';
		$action 	= 'index';
		$params		= array();

        if ($arguments) {
        	foreach($arguments as $index => $command) {

        		$details = explode("=", $command);
        		if($details[0] == "module") {
        		    $module = $details[1];
        		}
        		elseif($details[0] == "controller") {
        			$controller = $details[1];
        		} 
        		elseif($details[0] == "action") {
        			$action = $details[1];
        		} 
        		else {
        			$params[$details[0]] = $details[1];
        		}
        	}

        	if($action == "" || $controller == "") {
        		die("
        			Missing Controller and Action Arguments
        			==
        			You should have:
        			php cronjob.php controller=[controllername] action=[action] [argument]=[value]
        		");
        	}

			$dispatcher->setControllerName($controller);
			$dispatcher->setActionName($action);
			$dispatcher->setParams($params);
			
			$front = Zend_Controller_Front::getInstance();
			$front->setRequest(new Zend_Controller_Request_Simple($action,$controller,$module,$params));
			
			//加载全部module
			$modules = array_keys($front->getDispatcher()->getControllerDirectory());
			if(is_array($modules) && !empty($modules)) {
    			foreach($modules as $k=>$v) {
        			$autoloader = Zend_Loader_Autoloader::getInstance();
        			//为了方便调用，加载所有的模块
                    $autoloader->pushAutoloader(
                        new Zend_Application_Module_Autoloader(
                            array (
                                'namespace' => ucfirst($v), 
                                'basePath'=> APPLICATION_PATH . '/modules/'.$v
                            )
                        )
                    );
    			}
			}
			return $dispatcher;
		}
		echo "Invalid command.\n", exit;
        echo "No command given.\n", exit;
    }
    
    public function assemble ($userParams, $name = null, $reset = false, $encode = true) {
        throw new Exception("Assemble isnt implemented ", print_r($userParams, true));
    }
}