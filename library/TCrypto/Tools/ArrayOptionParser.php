<?php
namespace TCrypto\Tools;

/**
 * ArrayOptionParser.
 * 
 * @author timoh <timoh6@gmail.com>
 * @license Public Domain
 */   
class ArrayOptionParser
{
    protected $_options = array();
    protected $_optionsFile = null;
    
    /**
     * 
     * @param string $optionsFile
     */
    public function __construct($optionsFile)
    {   
        $this->_parseFile($optionsFile);
    }
    
    /**
     * Returns a value from $_options array identified by the $key.
     * Null will be returned if the $key does not exist.
     * 
     * @param string $key 
     * @return mixed
     */
    public function getOption($key)
    {
        if (isset($this->_options[$key]))
        {
            return $this->_options[$key];
        }
        else
        {
            return null;
        }
    }
    
    protected function _parseFile($file)
    {
        if (file_exists($file) && is_file($file))
        {
            $optionsArray = include $file;
            
            if (is_array($optionsArray))
            {
                $this->_options = $optionsArray;
            }
        }
    }
}