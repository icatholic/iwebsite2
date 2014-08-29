<?php

class Privileges_Model_Role extends iWebsite_Plugin_Mongo
{

    protected $name = 'iPrivileges_role';

    protected $dbName = 'privileges';
    
    protected $secondary = true;
}