<?php
namespace TCrypto\Tools;

/**
 * SettingStore.
 * 
 * @author timoh <timoh6@gmail.com>
 * @license Public Domain
 */ 
class SettingStore
{
    private $_settings = array();
    
    public function __construct()
    {
        //
    }
    
    public function set($key, $val)
    {
        $this->_settings[$key] = $val;
    }
    
    public function get($key)
    {
        return isset($this->_settings[$key]) ? $this->_settings[$key] : null;
    }
}