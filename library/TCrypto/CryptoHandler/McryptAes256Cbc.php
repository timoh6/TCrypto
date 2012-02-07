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
        
        $this->_Pkcs7Pad($data); 
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
        $this->_Pkcs7Strip($plainText);
        
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
    
    /**
     * Pads the data using PKCS7 padding scheme, as described in RFC 5652.
     * See http://tools.ietf.org/html/rfc5652#section-6.3
     * 
     * We do not want to rely on Mcrypt's zero-padding, because it differs from
     * OpenSSL's PKCS7 padding.
     * 
     * Note: $data is passed by reference.
     * 
     * @param string &$data 
     */
    protected function _pkcs7Pad(&$data)
    {
        $blockSize = $this->getIvLen();
        $padding = $blockSize - (strlen($data) % $blockSize);
        
        $data .= str_repeat(chr($padding), $padding);
    }
    
    /**
     * Removes the (PKCS7) padding bytes from $data.
     * 
     * Note: $data is passed by reference.
     * 
     * @param string &$data 
     */
    protected function _pkcs7Strip(&$data)
    {
        $paddingByte = substr($data, -1);
        $paddingLen = ord($paddingByte);
        $dataLen = strlen($data) - $paddingLen;
        
        // Simple sanity check to make sure we have correct padding bytes. If padding
        // is not correct, we simply set $data to false. At this point, there
        // should be no need to worry about leaking side-channels.
        if (!isset($data[15]) || $paddingLen < 1 || $paddingLen > $this->getIvLen())
        {
            $data = false;
        }
        else if (substr($data, $dataLen) === str_repeat($paddingByte, $paddingLen))
        {
            // Padding is correct, strip it off.
            $data = substr($data, 0, $dataLen);
        }
        else
        {
            $data = false;
        }
    }
}