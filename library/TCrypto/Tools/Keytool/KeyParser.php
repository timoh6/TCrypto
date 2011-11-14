<?php

namespace TCrypto\Tools\Keytool;

use TCrypto\Crypto;

/**
 * 
 * @author timoh <timoh6@gmail.com>
 * @license Public Domain
 */ 
class KeyParser
{
    /**
     * 
     * @param array $keyDataArray
     * @return string
     */
    public function constructExportKeyString(array $keyDataArray)
    {
        return '<?php return ' . var_export($keyDataArray, true) . ';';
    }
    
    /**
     * 
     * @param int $bytesInKeyName
     * @return array
     * @throws Exception
     */
    public static function getRandomKeys($bytesInKeyName = 3)
    {
        $bytesInKeyName = (int) $bytesInKeyName;
        if ($bytesInKeyName < 1)
        {
            throw new \Exception('Setting "bytes_in_key_name" must be greater than 0.');
        }
        
        $key = Crypto::getRandomBytes($bytesInKeyName);
        $authentication = Crypto::getRandomBytes(32);
        $encryption = Crypto::getRandomBytes(32);

        if ($key === false || $authentication === false || $encryption === false)
        {
            unset($authentication, $encryption, $key);
            throw new \Exception('Could not get random bytes for the key.');
        }
        else
        {
            $key = bin2hex($key);
            $authentication = bin2hex($authentication);
            $encryption = bin2hex($encryption);
            $keyValueArray = array('key' => $key, 'authentication' => $authentication, 'encryption' => $encryption);
            unset($authentication, $encryption, $key);

            return $keyValueArray;
        }
    }
}
