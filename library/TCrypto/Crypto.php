<?php

namespace TCrypto;

use TCrypto\KeyManager\KeyManagerInterface,
    TCrypto\StorageHandler\StorageInterface,
    TCrypto\CryptoHandler\CryptoInterface;

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
     * @var TCrypto\KeyManager\KeyManagerInterface
     */
    protected $_keyManager = null;
    
    /**
     * @var TCrypto\StorageHandler\StorageInterface
     */
    protected $_storageHandler = null;
    
    /**
     * @var TCrypto\CryptoHandler\CryptoInterface
     */
    protected $_cryptoHandler = null;
    
    /**
     * @var TCrypto\pluginContainer
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
     * @param TCrypto\KeyManager\KeyManagerInterface $keyManager
     * @param TCrypto\StorageHandler\StorageInterface $storage
     * @param TCrypto\CryptoHandler\CryptoInterface $crypto
     * @param TCrypto\PluginContainer $plugins
     * @param array $options
     */
    public function __construct(KeyManagerInterface $keyManager,
                                StorageInterface $storage,
                                PluginContainer $plugins,
                                CryptoInterface $crypto = null,
                                array $options = array())
    {
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
     * @throws TCrypto\Exception
     * @return boolean
     */
    public function save()
    {
        if (count($this->_data) > 0 && (false !== ($data = $this->_pluginContainer->saveDispatcher($this->_data))))
        {
            $timestamp = time();
            $macExpire = $timestamp + (int) $this->_macMaxLifetime;

            if ($this->_cryptoHandler !== null)
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
        $liveData = $this->_storageHandler->fetch();
        $data = '';
        $keyVersionDelimiterPosition = false;
        $keyVersion = '';
        $keyVersionLength = 0;
        // $keyVersionLength + VERSION_DELIMITER
        $keyVersionLengthTotal = 0;
        
        if ($liveData !== false)
        {
            $keyVersionDelimiterPosition = strpos($liveData, self::VERSION_DELIMITER, 32);
        }
        
        // Version delimeter position must be bigger than 32.
        if ($keyVersionDelimiterPosition !== false && (int) $keyVersionDelimiterPosition > 32)
        {
            $keyVersionLength = $keyVersionDelimiterPosition - 32;
            
            // Key version plus version delimeter ("$" character).
            $keyVersionLengthTotal = $keyVersionLength + 1;
            $keyVersion = substr($liveData, 32, $keyVersionLength);
        }

        // A quick check if $liveData and $keyVersion has at least the minimum needed amount of bytes.
        if ($liveData !== false && isset($liveData[44 + $keyVersionLengthTotal]) && isset($keyVersion[0]))
        {
            $currentMac = (string) substr($liveData, 0, 32);
            $timestamp = (int) base_convert((string) substr($liveData, 32 + $keyVersionLengthTotal, 6), 36, 10);
            $macExpire = (int) base_convert((string) substr($liveData, 38 + $keyVersionLengthTotal, 6), 36, 10);
            
            // Make sure the $timestamp and $macExpire are correct. Also, get random
            // bytes for a HMAC key (HMAC is applied two times, the second HMAC round
            // uses this random key).
            if (time() >= $timestamp && time() <= $macExpire && (false !== ($secondRoundMacKey = $this->getRandomBytes(128))))
            {
                $dataString = (string) substr($liveData, 32);
                $macKeySeed = (string) $this->_keyManager->getKeyByVersion('authentication', $keyVersion);
                $macKey = $this->_setupKey(array($timestamp, $macExpire, $macKeySeed));
                unset($macKeySeed);
                $mac = $this->_hmac($dataString, $macKey);
                unset($macKey);

                // We "double HMAC" to randomize the bytes. This should make
                // timing attacks infeasible.
                $currentMac = $this->_hmac($currentMac, $secondRoundMacKey);
                $mac = $this->_hmac($mac, $secondRoundMacKey);
                unset($secondRoundMacKey);
                
                if ($currentMac === $mac)
                {
                    $data = substr($dataString, 12 + $keyVersionLengthTotal);

                    if ($this->_cryptoHandler !== null)
                    {
                        $ivLen = $this->_cryptoHandler->getIvLen();
                        $keyLen = $this->_cryptoHandler->getKeyLen();

                        // A quick check if $data has at least the minimum needed amount of bytes.
                        if (isset($data[$ivLen]))
                        {
                            $iv = (string) substr($data, 0, $ivLen);

                            try
                            {
                                $cryptoKeySeed = (string) $this->_keyManager->getKeyByVersion('encryption', $keyVersion);
                                $cryptoKey = $this->_setupKey(array($timestamp, $macExpire, $iv, $cryptoKeySeed));
                                unset($cryptoKeySeed);
                                $cryptoKey = $this->_hash($cryptoKey, $keyLen);
                                $data = $this->_cryptoHandler->decrypt(substr($data, $ivLen), $iv, $cryptoKey);
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
                        $data = $this->_pluginContainer->extractDispatcher($data);

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
    }

    /**
     * Constructs a key string for MAC/encryption
     *
     * @param array $fields
     * @return string
     * @throws TCrypto\Exception
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
        return substr($data, 0, $len);
    }

    /**
     *
     * @param string $data
     * @param string $key
     * @return string
     */
    protected function _hmac($data, $key)
    {
        return hash_hmac('sha256', $data, $key, true);
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

        if (strlen($stringA) === 0)
        {
            return false;
        }

        if (strlen($stringA) !== strlen($stringB))
        {
            return false;
        }

        $result = 0;
        $len = strlen($stringA);

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

        if (file_exists('/dev/urandom') && is_readable('/dev/urandom') && (false !== ($fh = fopen('/dev/urandom', 'rb'))))
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
                $hasBytes = true;
            }
        }

        if ($hasBytes === false && PHP_VERSION >= '5.3.4' && function_exists('openssl_random_pseudo_bytes'))
        {
            $tmp = openssl_random_pseudo_bytes($count, $cryptoStrong);
            if ($tmp !== false && $cryptoStrong === true)
            {
                $bytes = $tmp;
                $hasBytes = true;
            }
        }

        // Make sure PHP version is at least 5.3. We do this because
        // mcrypt_create_iv() on older versions of PHP
        // does not give "strong" random data on Windows systems.
        if ($hasBytes === false && PHP_VERSION >= '5.3')
        {
            $tmp = mcrypt_create_iv($count, MCRYPT_DEV_URANDOM);
            if ($tmp !== false)
            {
                $bytes = $tmp;
                $hasBytes = true;
            }
        }

        if (strlen($bytes) === $count)
        {
            return $bytes;
        }
        else
        {
            return false;
        }
    }
}
