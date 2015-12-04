<?php
namespace TCrypto\StorageHandler;

/**
 * PlainString returns the TCrypto data as a plain string. The data will not be stored.
 *
 * Possible use case: Use TCrypto to sign and encrypt data and pass the data back to your application.
 * For example, you can encrypt user-specific third-party tokens before saving them into a database
 * (without the need to use a specific TCrypto "database StorageHandler").
 *
 * @author timoh <timoh6@gmail.com>
 * @license Public Domain
 */
class PlainString implements StorageInterface
{
    /**
     * @var string
     */
    protected $_data = '';
    protected $_useBase64 = true;

    /**
     *
     * @param string $data TCrypto payload from you application, empty string for no payload
     * @param bool $useBase64 True to base64 encode/decode TCrypto payload, false to return raw data
     */
    public function __construct($data = '', $useBase64 = true)
    {
        $this->_data = (string) $data;
        $this->_useBase64 = (boolean) $useBase64;
    }

    /**
     * Returns the data.
     *
     * @return string|false TCrypto payload or boolean false if the payload is empty
     */
    public function fetch()
    {
        if (!isset($this->_data[0]))
        {
            return false;
        }

        return $this->_useBase64 === true ? base64_decode($this->_data, true) : $this->_data;
    }

    /**
     *
     * @param string $data
     * @return string
     */
    public function save($data)
    {
        return $this->_useBase64 === true ? base64_encode($data) : $data;
    }

    /**
     *
     * @return boolean
     */
    public function remove()
    {
        $this->_data = '';

        return true;
    }
}
