<?php
namespace TCrypto\StorageHandler;

/**
 * 
 * @author timoh <timoh6@gmail.com>
 * @license Public Domain
 */
class Cookie implements StorageInterface
{
    protected $_cookieName = 'my_cookie';
    protected $_requireSecure = true;

    public function __construct($secure = true, $name = null)
    {
        $this->_requireSecure = (bool) $secure;
        
        if ($name !== null)
        {
            $this->_cookieName = $name;
        }
    }

    /**
     * Returns the data from a cookie.
     * 
     * @return string|false 
     */
    public function fetch()
    {
        return isset($_COOKIE[$this->_cookieName]) ? self::decodeBase64UrlSafe($_COOKIE[$this->_cookieName]) : false;
    }
    
    /**
     * Saves data to a cookie.
     * 
     * @param string $dataString
     * @return boolean
     */
    public function save($dataString)
    {
        $https = isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : '';
        if (strtolower($https) === 'off')
        {
            $https = '';
        }
        
        if ($this->_requireSecure === true && empty($https))
        {
            return false;
        }
        
        $dataString = self::encodeBase64UrlSafe($dataString);

        return setcookie($this->_cookieName, $dataString, 0, '/', '', $this->_requireSecure, true);
    }

    /**
     * Removes the cookie.
     * 
     * @return boolean
     */
    public function remove()
    {
        return setcookie($this->_cookieName, '', time() - 31104000, '/', '', '', true);
    }

    /**
     * URL safe Base64 encoding (suitable for a cookie).
     * 
     * @return string|false
     */
    public static function encodeBase64UrlSafe($value)
    {
        return str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($value));
    }

    /**
     * URL safe Base64 deconding (suitable for a cookie).
     * 
     * @return string|false
     */
    public static function decodeBase64UrlSafe($value)
    {
        $value = str_replace(array('-', '_'), array('+', '/'), $value);
        if (false === ($value = base64_decode($value, true)))
        {
            return false;
        }

        $mod = strlen($value) % 4;
        if ((int) $mod > 0)
        {
            $value = str_pad($value, $mod, '=');
        }

        return $value;
    }
}
