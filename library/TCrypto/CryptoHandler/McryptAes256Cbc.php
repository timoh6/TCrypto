<?php
namespace TCrypto\CryptoHandler;

/**
 * 
 * @author timoh <timoh6@gmail.com>
 * @license Public Domain
 */
class McryptAes256Cbc implements CryptoInterface
{
    
    public function __construct()
    {
        //
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
        // AES in CBC mode.
        if (false === ($td = $this->_moduleInit($iv, $key)))
        {
            return false;
        }
        
        $data = rtrim($data, "\0");
        $cipherText = mcrypt_generic($td, $data);
        
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        unset($data, $iv, $key, $td);

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
        // AES in CBC mode.
        if (false === ($td = $this->_moduleInit($iv, $key)))
        {
            return false;
        }

        $plainText = mdecrypt_generic($td, $data);
        $plainText = rtrim($plainText, "\0");
        
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        unset($data, $iv, $key, $td);

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
    
    /**
     * Initialize Mcrypt.
     * 
     * @param string $iv
     * @param string $key
     * @return Mcrypt resource|false 
     */
    protected function _moduleInit($iv, $key)
    {
        // AES in CBC mode.
        if (false === ($td = mcrypt_module_open('rijndael-128', '', 'cbc', '')))
        {
            return false;
        }
        
        // Max. 2^32 blocks with a same key (not realistic in a web application).
        if (mcrypt_generic_init($td, $key, $iv) !== 0)
        {
            unset($key, $iv);
            
            return false;
        }
        
        unset($key, $iv);
        
        return $td;
    }
}