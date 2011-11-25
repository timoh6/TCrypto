<?php
namespace TCrypto\StorageHandler;

/**
 * ArrayStorage is used for mocking the storage implementation.
 * 
 * @author timoh <timoh6@gmail.com>
 * @license Public Domain
 */
class ArrayStorage implements StorageInterface
{
    protected $_data = array();

    /**
     * Returns the data.
     * 
     * @return string|false 
     */
    public function fetch()
    {
        return array_key_exists('storage', $this->_data) ? $this->_data['storage'] : false;
    }
    
    /**
     * 
     * @param string $dataString
     * @return boolean
     */
    public function save($dataString)
    {
        $this->_data['storage'] = $dataString;
        
        return true;
    }

    /**
     * 
     * @return boolean
     */
    public function remove()
    {
        $this->_data = array();
        
        return true;
    }
}
