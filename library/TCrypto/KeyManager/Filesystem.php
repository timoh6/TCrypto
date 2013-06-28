<?php
namespace TCrypto\KeyManager;

/**
 * This is the default, filesystem based, storage implementation for encryption
 * and authentication keys. The key file must return TCrypto key data array, i.e.
 * 
 * return array('tcrypto_key_data' => array(...).
 * 
 * @author timoh <timoh6@gmail.com>
 * @license Public Domain
 */
class Filesystem implements KeyManagerInterface
{
    /**
     * Key file location.
     * 
     * @var string 
     */
    protected $_keyfile = '';
    
    /**
     * Plain PHP array which holds the TCrypto key data.
     *
     * @var array 
     */
    protected $_keyDump = null;

    /**
     * @var null
     */
    protected $_primaryKeyVersion = null;
    
    public function __construct($keyfile = null)
    {
        if ($keyfile === null)
        {
            $keyfile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Keystore' . DIRECTORY_SEPARATOR . 'default';
        }
        
        $this->_keyfile = $keyfile;
    }
    
    /**
     * Set the key data as a plain PHP array.
     * 
     * @param array $keysArray
     */
    public function setKeysAsArray(array $keysArray)
    {
        $this->_keyDump = $keysArray;
    }

    /**
     * Returns the requested key.
     *
     * @param string $typeIndex An identifier ('authentication' or 'encryption') to specify the type of the key
     * @param string|null $versionIndex Version index of the key. Null for primary keys
     * @throws KeyManagerException
     * @return string 
     */
    public function getKeyByVersion($typeIndex, $versionIndex = null)
    {
        $typeIndex = (string) $typeIndex;
        $key = '';
        $data = $this->_fetchData();
        
        // Primarykey
        if ($versionIndex === null)
        {
            $versionIndex = isset($data['tcrypto_key_data']['meta_data']['primary_index']) ? $data['tcrypto_key_data']['meta_data']['primary_index'] : '';
            $this->_primaryKeyVersion = $versionIndex;
        }
        $versionIndex = (string) $versionIndex;
        
        if (isset($data['tcrypto_key_data']['keys'][$versionIndex][$typeIndex]))
        {
            $key = (string) $data['tcrypto_key_data']['keys'][$versionIndex][$typeIndex];
        }
        unset($data);
        
        return (string) $key;
    }

    /**
     * @return string
     */
    public function getPrimaryKeyVersion()
    {
        if ($this->_primaryKeyVersion !== null)
        {
            return $this->_primaryKeyVersion;
        }
        else
        {
            $versionIndex = '';
            $data = $this->_fetchData();
            
            $versionIndex = isset($data['tcrypto_key_data']['meta_data']['primary_index']) ? $data['tcrypto_key_data']['meta_data']['primary_index'] : '';
            unset($data);
            
            return (string) $versionIndex;
        }
    }

    /**
     * @return array
     * @throws KeyManagerException
     */
    protected function _fetchData()
    {
        if (!empty($this->_keyDump))
        {
            return $this->_keyDump;
        }
        
        if (file_exists($this->_keyfile) && is_readable($this->_keyfile) && is_file($this->_keyfile))
        {
            return (array) include $this->_keyfile;
        }
        else
        {
            throw new KeyManagerException('The specified keyfile is not available');
        }
    }
}
