<?php
namespace TCrypto\CryptoHandler;

/**
 * 
 * @author timoh <timoh6@gmail.com>
 * @license Public Domain
 */
class McryptAes256Cbc implements CryptoInterface
{
    protected $_td = null;
    
    public function __construct()
    {
        // AES in CBC mode.
        if (false === ($td = mcrypt_module_open('rijndael-128', '', 'cbc', '')))
        {
            return false;
        }
        
        $this->_td = $td;
    }
    
    /**
     *
     * @param string $data
     * @param string $iv
     * @param string $key
     * @return string|false
     */
    public function encrypt($data, $iv, $key)
    {
        // Max. 2^32 blocks with a same key (not realistic in a web application).
        if (mcrypt_generic_init($this->_td, $key, $iv) !== 0)
        {
            return false;
        }
        
        $data = rtrim($data, "\0");
        $cipherText = mcrypt_generic($this->_td, $data);
        mcrypt_generic_deinit($this->_td);
        unset($data, $iv, $key);

        return $cipherText;
    }
    
    /**
     *
     * @param string $data
     * @param string $iv
     * @param string $key
     * @return string|false 
     */
    public function decrypt($data, $iv, $key)
    {
        if (mcrypt_generic_init($this->_td, $key, $iv) !== 0)
        {
            return false;
        }

        $plainText = mdecrypt_generic($this->_td, $data);

        $plainText = rtrim($plainText, "\0");
        
        mcrypt_generic_deinit($this->_td);
        unset($data, $iv, $key);

        return $plainText;
    }
    
    /**
     * Returns the needed IV length in bytes.
     * 
     * @return int 
     */
    public function getIvLen()
    {
        // AES block size is 128 bits (16 bytes).
        return 16;
    }
    
    /**
     * Returns the needed key length in bytes.
     * 
     * @return int 
     */
    public function getKeyLen()
    {
        // 32 bytes for AES-256
        return 32;
    }
    
    public function __destruct()
    {
        if ($this->_td !== null)
        {
            mcrypt_module_close($this->_td);
        }
    }
}