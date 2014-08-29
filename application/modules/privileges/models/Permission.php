<?php

class Privileges_Model_Permission extends iWebsite_Plugin_Mongo
{

    protected $name = 'iPrivileges_permission';

    protected $dbName = 'privileges';
    
    protected $secondary = true;
}