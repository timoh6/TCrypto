<?php
namespace TCrypto\KeyManager;

/**
 * 
 * @author timoh <timoh6@gmail.com>
 * @license Public Domain
 */
interface KeyManagerInterface
{
    /**
     * Returns the keydata.
     */
    public function getKeyByVersion($type, $version);
    
    public function getPrimaryKeyVersion();
}