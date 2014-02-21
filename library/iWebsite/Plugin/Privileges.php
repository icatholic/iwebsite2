<?php

class iWebsite_Plugin_Privileges extends Zend_Controller_Plugin_Abstract
{

    /**
     * 对下列模块执行权限控制,请使用首字母小写的module名称
     *
     * @var array
     */
    private $_privilegesModules = array(
            'privileges'
    );

    public function preDispatch (Zend_Controller_Request_Abstract $request)
    {
        $config = Zend_Registry::get('config');
        $status = isset($config['global']['privileges']['status']) ? intval(
                $config['global']['privileges']['status']) : 0;
        if ($status === 1) {
            $module = strtolower($request->getModuleName());
            if (! in_array($module, $this->_privilegesModules))
                return true;
            $cache = Zend_Registry::get('cache');
            
            try {
                $acl = new Zend_Acl();
                $role = new Zend_Session_Namespace('role');
                
                // 读取全部资源列表
                if (($resources = $cache->load(__CLASS__ . '->resource')) ===
                         false) {
                    $resourceModel = new Privileges_Model_Resource();
                    $resources = $resourceModel->distinct('resource', array());
                    if (! empty($resources))
                        $cache->save($resources);
                }
                
                if (empty($resources)) {
                    throw new Exception('权限资源未定义');
                }
                
                foreach ($resources as $resource) {
                    $acl->addResource(new Zend_Acl_Resource($resource));
                }
                
                /**
                 * 添加相应的权限给到相应的角色
                 * 在Session中存储
                 * object('roleAlias'->'XXX','permissions'->array(
                 * array('resource'=>xxx,'privilege'=>xxx),
                 * array('resource'=>xxx,'privilege'=>xxx)
                 * ));
                 */
                $guest = false;
                if (isset($role->roleAlias)) {
                    $roleAlias = $role->roleAlias;
                    $acl->addRole(new Zend_Acl_Role($roleAlias));
                    $permissions = $role->permissions;
                    foreach ($permissions as $permission) {
                        $acl->allow($roleAlias, $permission['resource'], 
                                $permission['privilege']);
                    }
                } else {
                    $guest = true;
                    // 如果未授权用户，开放guest用户的访问权限
                    $acl->addRole(new Zend_Acl_Role('guest'));
                    $resourceModel = new Privileges_Model_Resource();
                    $permissionModel = new Privileges_Model_Permission();
                    
                    $guestPermissions = $permissionModel->findAll(
                            array(
                                    'role' => 'guest'
                            ));
                    if (isset($guestPermissions['total']) &&
                             $guestPermissions['total'] > 0) {
                        
                        $resource_alias = array();
                        foreach ($guestPermissions['datas'] as $key => $value) {
                            $resource_alias[] = $value['resource_alias'];
                        }
                        
                        $guestResources = $resourceModel->findAll(
                                array(
                                        'alias' => array(
                                                '$in' => $resource_alias
                                        )
                                ));
                        
                        if (isset($guestResources['total']) &&
                                 $guestResources['total'] > 0) {
                            foreach ($guestResources['datas'] as $permission) {
                                $acl->allow('guest', $permission['resource'], 
                                        $permission['privilege']);
                            }
                        }
                    } else {
                        exit(
                                json_encode(
                                        array(
                                                'success' => false,
                                                'access' => 'deny',
                                                'msg' => "很抱歉，Guest用户权限未设定"
                                        ), JSON_UNESCAPED_UNICODE));
                    }
                }
                //Zend_Registry::set('acl', $acl);
                
                // 角色判断,当用户角色为非超级管理员时，进行权限判断superAdmin 特殊处理
                if ($role->roleAlias != 'superAdmin' || $guest) {
                    $controller = $request->getControllerName();
                    $action = $this->convert($request->getActionName());
                    $roleAlias = $guest ? 'guest' : $role->roleAlias;
                    if (! $acl->isAllowed($roleAlias, 
                            $module . '_' . $controller, $action)) {
                        exit(
                                json_encode(
                                        array(
                                                'success' => false,
                                                'access' => 'deny',
                                                'msg' => "很抱歉，您无权访问本资源!请重新登录、或者联系管理员开通该权限"
                                        ), JSON_UNESCAPED_UNICODE));
                    }
                }
            } catch (Exception $e) {
                exit(
                        json_encode(
                                array(
                                        'success' => false,
                                        'access' => 'deny',
                                        'msg' => "很抱歉，您无权访问本资源:" . $e->getLine() .
                                                 '|' . $e->getMessage()
                                ), JSON_UNESCAPED_UNICODE));
            }
        }
    }

    private function convert ($name)
    {
        $newName = '';
        $tmp = preg_split("[\.|\-]", $name);
        $i = 0;
        foreach ($tmp as $cell) {
            if ($i > 0)
                $cell = ucfirst($cell);
            $newName .= $cell;
            $i ++;
        }
        return $newName;
    }
}