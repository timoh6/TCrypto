<?php

namespace TCrypto;

use TCrypto\StorageHandler\StorageInterface,
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
     * The MAC key. Must be at least 40 bytes.
     *
     * @var string
     */
    protected $_macKey = '';
    
    /**
     * The Encryption key. Must be at least 40 bytes if data encryption
     * will be used. Otherwise empty.
     *
     * @var string
     */
    protected $_cipherKey = '';
    
    /**
     * Maximum lifetime of the data (in seconds).
     * "Lifetime" means the maximum allowed time between
     * save() and _extractData() calls.
     *
     * @var int
     */
    protected $_macMaxLifetime = 3600;
    
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
     *
     * @param TCrypto\StorageHandler\StorageInterface $storage
     * @param TCrypto\CryptoHandler\CryptoInterface $crypto
     * @param TCrypto\PluginContainer $plugins
     * @param array $options
     */
    public function __construct(StorageInterface $storage,
                                PluginContainer $plugins,
                                CryptoInterface $crypto = null,
                                array $options = array())
    {
        $this->_storageHandler = $storage;
        $this->_cryptoHandler = $crypto;
        $this->_pluginContainer = $plugins;

        $this->_setOptions($options);
        unset($options);

        // A quick and dirty check if $_macKey contains at least 40 bytes.
        if (!isset($this->_macKey[39]))
        {
            throw new Exception('Insufficient parameters: $_macKey must be at least 40 bytes');
        }

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
     * @thows TCrypto\Exception
     * @return boolean
     */
    public function save()
    {
        if (count($this->_data) > 0)
        {
            $data = $this->_pluginContainer->saveDispatcher($this->_data);

            $timestamp = time();
            $macExpire = $timestamp + (int) $this->_macMaxLifetime;

            if ($this->_cryptoHandler !== null)
            {
                $ivLen = $this->_cryptoHandler->getIvLen();
                $keyLen = $this->_cryptoHandler->getKeyLen();

                // A quick and dirty check if $_cipherKey contains at least 40 bytes.
                if (!isset($this->_cipherKey[39]))
                {
                    throw new Exception('Insufficient parameters: $_cipherKey must be at least 40 bytes');
                }

                if (false === ($iv = $this->getRandomBytes($ivLen)))
                {
                    throw new Exception('Could not get random bytes');
                }

                try
                {
                    // Mix $_cipherKey with variables to make it unique (and random) for each encryption.
                    $cryptoKey = $this->_setupKey(array($timestamp, $macExpire, $iv, $this->_cipherKey));
                    $cryptoKey = $this->_hash($cryptoKey, $keyLen);
                    $data = $iv . $this->_cryptoHandler->encrypt($data, $iv, $cryptoKey);
                    unset($cryptoKey);
                }
                catch (Exception $e)
                {
                    throw $e;
                }
            }

            if (false === ($randomBytes = $this->getRandomBytes(8)))
            {
                throw new Exception('Could not get random bytes');
            }

            // "Compress" $timestamp and $macExpire to save some space.
            $dataString = base_convert($timestamp, 10, 36) .
                    base_convert($macExpire, 10, 36) .
                    $randomBytes .
                    $data;

            try
            {
                // Mix $_macKey with variables to make it unique (and random) for each dispatch.
                $macKey = $this->_setupKey(array($timestamp, $macExpire, $randomBytes, $this->_macKey));
                $mac = $this->_hmac($dataString, $macKey);
                $dataString = $mac . $dataString;
                unset($macKey);

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

        // A quick check if $liveData has at least the minimum needed amount of bytes.
        if ($liveData !== false && isset($liveData[52]))
        {
            $currentMac = (string) substr($liveData, 0, 32);
            $timestamp = (int) base_convert((string) substr($liveData, 32, 6), 36, 10);
            $macExpire = (int) base_convert((string) substr($liveData, 38, 6), 36, 10);
            $randomBytes = (string) substr($liveData, 44, 8);

            // Make sure the $timestamp and $macExpire are correct.
            if (time() >= $timestamp && time() <= $macExpire)
            {
                $dataString = (string) substr($liveData, 32);
                $macKey = $this->_setupKey(array($timestamp, $macExpire, $randomBytes, $this->_macKey));
                $mac = $this->_hmac($dataString, $macKey);

                // "Constant time" string comparison to prevent timing attacks.
                // (==/=== string comparison).
                if ($this->compareString($currentMac, $mac) === true)
                {
                    $data = substr($dataString, 20);

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
                                $cryptoKey = $this->_setupKey(array($timestamp, $macExpire, $iv, $this->_cipherKey));
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
     * @thows TCrypto\Exception
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
        if (isset($options['mac_key']))
        {
            $this->_macKey = (string) $options['mac_key'];
        }
        if (isset($options['cipher_key']))
        {
            $this->_cipherKey = (string) $options['cipher_key'];
        }
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

        if (strlen($stringA) === 0 || strlen($stringB) === 0)
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
