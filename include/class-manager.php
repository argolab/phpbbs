<?php

class Manager
{
    protected $customer;
    private $customer_urec;
    static $urec_cache = array();
    
    function __construct($customer, $urec=null)
    {
        $this->customer = $customer;
    }

    function _cb($name)
    {
        return array($this, $name);
    }

    function get_customer_perm()
    {
        if(!array_key_exists($this->customer, self::$urec_cache))
            self::$urec_cache[$this->customer] = ext_get_urec($this->customer);
        return self::$urec_cache[$this->customer]['userlevel'];
    }

    function get_customer_ip()
    {
        return $_SERVER['REMOTE_ADDR'];
    }
        
}

