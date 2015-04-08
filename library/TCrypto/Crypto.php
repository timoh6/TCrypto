<?php

namespace TCrypto;

use TCrypto\KeyManager\KeyManagerInterface;
use TCrypto\StorageHandler\StorageInterface;
use TCrypto\CryptoHandler\CryptoInterface;
use TCrypto\Tools\StringUtil;

/**
 * The main workhorse.
 *
 * @author timoh <timoh6@gmail.com>
 * @license Public Domain
 */
class Crypto
{
    /**
     * Maximum lifetime of the data (in seconds).
     * "Lifetime" means the maximum allowed time between
     * save() and _extractData() calls.
     *
     * @var int
     */
    protected $_macMaxLifetime = 3600;
    
    /**
     * @var \TCrypto\KeyManager\KeyManagerInterface
     */
    protected $_keyManager = null;
    
    /**
     * @var \TCrypto\StorageHandler\StorageInterface
     */
    protected $_storageHandler = null;
    
    /**
     * @var \TCrypto\CryptoHandler\CryptoInterface
     */
    protected $_cryptoHandler = null;
    
    /**
     * @var \TCrypto\pluginContainer
     */
    protected $_pluginContainer = null;
    
    /**
     * The data as key-value pairs.
     *
     * @var array
     */
    protected $_data = array();
    
    /**
     * Extra "entropy sources" to mix the MAC / encryption keys.
     * For example array($_SERVER['REMOTE_ADDR']) etc.
     *
     * @var array
     */
    protected $_entropyPool = array();
    
    /**
     * Whether to call save() automatically after setValue() or not.
     *
     * @var bool
     */
    protected $_saveOnSet = false;
    
    /**
    * The version delimiter is the (one-byte) character that separates the key
    * version/data. Must be one character (one byte).
    *
    * @var string
    */
    const VERSION_DELIMITER = '$';

    /**
     *
     * @param \TCrypto\KeyManager\KeyManagerInterface $keyManager
     * @param \TCrypto\StorageHandler\StorageInterface $storage
     * @param \TCrypto\PluginContainer $plugins
     * @param \TCrypto\CryptoHandler\CryptoInterface $crypto
     * @param array $options
     */
    public function __construct(
            KeyManagerInterface $keyManager = null,
            StorageInterface $storage = null,
            PluginContainer $plugins = null,
            CryptoInterface $crypto = null,
            array $options = array()
            )
    {
        if ($keyManager === null)
        {
            // Default KeyManager, uses a default keyfile: 
            // /path/to/TCrypto/keystore/default.
            $keyManager = new KeyManager\Filesystem();
        }
        
        if ($storage === null)
        {
            // Default StorageHandler. Uses cookies with name "my_cookie" and
            // requires an HTTPS connection.
            $storage = new StorageHandler\Cookie();
        }

        if ($plugins === null)
        {
            // Default PluginContainer. Serialize/unserialize data.
            $plugins = new PluginContainer();
        }
        
        $this->_keyManager = $keyManager;
        $this->_storageHandler = $storage;
        $this->_cryptoHandler = $crypto;
        $this->_pluginContainer = $plugins;

        $this->_setOptions($options);
        unset($options);

        // Extracts the data from a storage.
        $this->_extractData();
    }

    /**
     * Set a key-value pair to be stored. If $_saveOnSet is true,
     * the data will be immediately saved to the storage.
     *
     * @param string $key
     * @param mixed $data
     */
    public function setValue($key, $data = null)
    {
        $this->_data[$key] = $data;

        if ($this->_saveOnSet === true)
        {
            $this->save();
        }
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getValue($key, $default = null)
    {
        return array_key_exists($key, $this->_data) ? $this->_data[$key] : $default;
    }

    /**
     * @param string $key
     * @return boolean
     */
    public function removeValue($key)
    {
        if (array_key_exists($key, $this->_data))
        {
            unset($this->_data[$key]);

            return true;
        }

        return false;
    }

    /**
     * Saves the data to a storage.
     *
     * @throws \TCrypto\Exception
     * @return boolean|string Returns TCrypto payload as a string if "String()" StorageHandler is used, otherwise boolean
     */
    public function save()
    {
        $isUsingCrypto = $this->_cryptoHandler !== null ? true : false;

        if (count($this->_data) > 0 && (false !== ($data = $this->_pluginContainer->saveDispatcher($this->_data, $isUsingCrypto))))
        {
            $timestamp = time();
            $macExpire = $timestamp + (int) $this->_macMaxLifetime;

            if ($isUsingCrypto === true)
            {
                $ivLen = $this->_cryptoHandler->getIvLen();
                $keyLen = $this->_cryptoHandler->getKeyLen();
                
                $cryptoKeySeed = $this->_keyManager->getKeyByVersion('encryption');

                // A quick and dirty check if $cryptoKeySeed contains at least 32 bytes.
                if (!isset($cryptoKeySeed[31]))
                {
                    unset($cryptoKeySeed);
                    throw new Exception('Insufficient parameters: encryption key must be at least 32 bytes');
                }

                if (false === ($iv = $this->getRandomBytes($ivLen)))
                {
                    unset($cryptoKeySeed);
                    throw new Exception('Could not get random bytes');
                }

                try
                {
                    // Mix $_cipherKey with variables to make it unique (and random) for each encryption.
                    $cryptoKey = $this->_setupKey(array($timestamp, $macExpire, $iv, $cryptoKeySeed));
                    unset($cryptoKeySeed);
                    $cryptoKey = $this->_hash($cryptoKey, $keyLen);
                    $data = $iv . $this->_cryptoHandler->encrypt($data, $iv, $cryptoKey);
                    unset($cryptoKey);
                }
                catch (Exception $e)
                {
                    throw $e;
                }
            }
            
            $macKeySeed = (string) $this->_keyManager->getKeyByVersion('authentication');
            // A quick and dirty check if $macKeySeed contains at least 32 bytes.
            if (!isset($macKeySeed[31]))
            {
                unset($macKeySeed);
                throw new Exception('Insufficient parameters: authentication key must be at least 32 bytes');
            }
            
            // Version identifier.
            $keyVersion = (string) $this->_keyManager->getPrimaryKeyVersion();

            // "Compress" $timestamp and $macExpire to save some space.
            $dataString = $keyVersion . self::VERSION_DELIMITER .
                          base_convert($timestamp, 10, 36) .
                          base_convert($macExpire, 10, 36) .
                          $data;

            try
            {
                // Mix $_macKey with variables to add some (weak) entropy for the key.
                $macKey = $this->_setupKey(array($timestamp, $macExpire, $macKeySeed));
                $mac = $this->_hmac($dataString, $macKey);
                unset($macKeySeed, $macKey);
                $dataString = $mac . $dataString;

                return $this->_storageHandler->save($dataString);
            }
            catch (Exception $e)
            {
                throw $e;
            }
        }

        $this->destroy();

        return false;
    }

    /**
     * Destroys the data both from memory and storage.
     */
    public function destroy()
    {
        $this->_data = array();

        return $this->_storageHandler->remove();
    }

    /**
     * Extracts the data from a storage.
     */
    protected function _extractData()
    {
        $isUsingCrypto = $this->_cryptoHandler !== null ? true : false;
        $liveData = $this->_storageHandler->fetch();
        $data = '';
        $keyVersionDelimiterPosition = false;
        $keyVersion = '';
        $keyVersionLength = 0;
        // $keyVersionLength + VERSION_DELIMITER
        $keyVersionLengthTotal = 0;
        
        if ($liveData !== false)
        {
            $keyVersionDelimiterPosition = StringUtil::byteStrpos($liveData, self::VERSION_DELIMITER, 16);
        }
        
        // Version delimiter position must be greater than 16.
        if ($keyVersionDelimiterPosition !== false && (int) $keyVersionDelimiterPosition > 16)
        {
            $keyVersionLength = $keyVersionDelimiterPosition - 16;
            
            // Key version plus version delimiter ("$" character).
            $keyVersionLengthTotal = $keyVersionLength + 1;
            if (StringUtil::byteStrlen($keyVersionLength) > 0)
            {
                $keyVersion = StringUtil::byteSubstr($liveData, 16, $keyVersionLength);
            }
        }

        // A quick check if $liveData and $keyVersion has at least the minimum needed amount of bytes.
        if ($liveData !== false && isset($liveData[28 + $keyVersionLengthTotal]) && isset($keyVersion[0]))
        {
            $currentMac = (string) StringUtil::byteSubstr($liveData, 0, 16);
            $timestamp = (int) base_convert((string) StringUtil::byteSubstr($liveData, 16 + $keyVersionLengthTotal, 6), 36, 10);
            $macExpire = (int) base_convert((string) StringUtil::byteSubstr($liveData, 22 + $keyVersionLengthTotal, 6), 36, 10);
            
            // Make sure the $timestamp and $macExpire are correct. Also, get random
            // bytes for a HMAC key (HMAC is applied two times, the second HMAC round
            // uses this random key).
            if (time() >= $timestamp && time() <= $macExpire && (false !== ($secondRoundMacKey = $this->getRandomBytes(16))))
            {
                $dataString = (string) StringUtil::byteSubstr($liveData, 16, StringUtil::byteStrlen($liveData));
                $macKeySeed = (string) $this->_keyManager->getKeyByVersion('authentication', $keyVersion);
                $macKey = $this->_setupKey(array($timestamp, $macExpire, $macKeySeed));
                unset($macKeySeed);
                $mac = $this->_hmac($dataString, $macKey);
                unset($macKey);

                // We "double HMAC" to randomize the bytes.
                // Now we do not have to worry about timing leaks.
                $currentMac = $this->_hmac($currentMac, $secondRoundMacKey);
                $mac = $this->_hmac($mac, $secondRoundMacKey);
                unset($secondRoundMacKey);
                
                if ($currentMac === $mac)
                {
                    $data = StringUtil::byteSubstr($dataString, 12 + $keyVersionLengthTotal, StringUtil::byteStrlen($dataString));

                    if ($isUsingCrypto === true)
                    {
                        $ivLen = $this->_cryptoHandler->getIvLen();
                        $keyLen = $this->_cryptoHandler->getKeyLen();

                        // A quick check if $data has at least the minimum needed amount of bytes.
                        if (isset($data[$ivLen]))
                        {
                            $iv = (string) StringUtil::byteSubstr($data, 0, $ivLen);

                            try
                            {
                                $cryptoKeySeed = (string) $this->_keyManager->getKeyByVersion('encryption', $keyVersion);
                                $cryptoKey = $this->_setupKey(array($timestamp, $macExpire, $iv, $cryptoKeySeed));
                                unset($cryptoKeySeed);
                                $cryptoKey = $this->_hash($cryptoKey, $keyLen);
                                $data = $this->_cryptoHandler->decrypt(StringUtil::byteSubstr($data, $ivLen, StringUtil::byteStrlen($data)), $iv, $cryptoKey);
                                unset($cryptoKey);
                            }
                            catch (Exception $e)
                            {
                                $data = false;
                            }
                        }
                    }

                    if ($data !== false)
                    {
                        $data = $this->_pluginContainer->extractDispatcher($data, $isUsingCrypto);

                        if ($data !== false && is_array($data))
                        {
                            foreach ($data as $k => $v)
                            {
                                $this->setValue($k, $v);
                            }
                            unset($v);

                            return;
                        }
                    }
                }
            }
        }

        $this->destroy();

        return;
    }

    /**
     * Constructs a key string for MAC/encryption
     *
     * @param array $fields
     * @return string
     * @throws \TCrypto\Exception
     */
    protected function _setupKey(array $fields = array())
    {
        $key = '';

        if (empty($fields))
        {
            throw new Exception('Key construction failed: $fields must not be empty');
        }

        foreach ($this->_entropyPool as $field)
        {
            $key .= $field;
        }

        foreach ($fields as $field)
        {
            $key .= $field;
        }
        unset($field);

        return (string) $key;
    }

    /**
     *
     * @param array $options
     */
    protected function _setOptions(array $options = array())
    {
        if (isset($options['entropy_pool']))
        {
            // For example array($_SERVER['REMOTE_ADDR'])
            $this->_entropyPool = (array) $options['entropy_pool'];
        }
        if (isset($options['max_lifetime']))
        {
            $this->_macMaxLifetime = (int) $options['max_lifetime'];
        }
        if (isset($options['save_on_set']))
        {
            $this->_saveOnSet = (bool) $options['save_on_set'];
        }

        unset($options);
    }

    /**
     *
     * @param string $data
     * @param int $len
     * @return string
     * @throws \TCrypto\Exception
     */
    protected function _hash($data, $len = 32)
    {
        $len = (int) $len;

        if ($len > 32)
        {
            throw new Exception('Key construction failed: Cannot hash keys longer than 32 bytes');
        }

        $data = hash('sha512', $data, true);

        // Truncate the data. This is to prevent leaking information about the key.
        // Even though "length extension" is probably not a problem here,
        // we do not want to take changes.
        return StringUtil::byteSubstr($data, 0, $len);
    }

    /**
     *
     * @param string $data
     * @param string $key
     * @return string
     */
    protected function _hmac($data, $key)
    {
        // Truncate the output to 128 bits.
        return StringUtil::byteSubstr(hash_hmac('sha256', $data, $key, true), 0, 16);
    }

    /**
     * http://code.google.com/p/oauth/
     *
     * @param string $stringA
     * @param string $stringB
     * @return boolean
     */
    public static function compareString($stringA, $stringB)
    {
        $stringA = (string) $stringA;
        $stringB = (string) $stringB;

        if (StringUtil::byteStrlen($stringA) === 0)
        {
            return false;
        }

        if (StringUtil::byteStrlen($stringA) !== StringUtil::byteStrlen($stringB))
        {
            return false;
        }

        $result = 0;
        $len = StringUtil::byteStrlen($stringA);

        for ($i = 0; $i < $len; $i++)
        {
            $result |= ord($stringA{$i}) ^ ord($stringB{$i});
        }

        return $result === 0;
    }

    /**
     * Generate a random string of bytes.
     *
     * @param int $count
     * @return string
     */
    public static function getRandomBytes($count)
    {
        $count = (int) $count;
        $bytes = '';
        $hasBytes = false;

        // Make sure PHP version is at least 5.3. We do this because
        // mcrypt_create_iv() on older versions of PHP
        // does not give "strong" random data on Windows systems.
        if (version_compare(PHP_VERSION, '5.3.0') >= 0 && function_exists('mcrypt_create_iv'))
        {
            $tmp = mcrypt_create_iv($count, MCRYPT_DEV_URANDOM);
            if ($tmp !== false)
            {
                $bytes = $tmp;
                $hasBytes = true;
            }
        }

        if ($hasBytes === false && file_exists('/dev/urandom') && is_readable('/dev/urandom') && (false !== ($fh = fopen('/dev/urandom', 'rb'))))
        {
            if (function_exists('stream_set_read_buffer'))
            {
                stream_set_read_buffer($fh, 0);
            }

            $tmp = fread($fh, $count);
            fclose($fh);
            if ($tmp !== false)
            {
                $bytes = $tmp;
            }
        }

        /*
         * We want to play it safe and disable openssl_random_pseudo_bytes() for now.
         * This is due to the OpenSSL "PID wrapping bug", which potentially could affect TCrypto.
         * 
        if ($hasBytes === false && version_compare(PHP_VERSION, '5.3.4') >= 0 && function_exists('openssl_random_pseudo_bytes'))
        {
            $tmp = openssl_random_pseudo_bytes($count, $cryptoStrong);
            if ($tmp !== false && $cryptoStrong === true)
            {
                $bytes = $tmp;
                $hasBytes = true;
            }
        }
        */

        if (StringUtil::byteStrlen($bytes) === $count)
        {
            return $bytes;
        }
        else
        {
            return false;
        }
    }
}
