<?php
namespace TCrypto\CryptoHandler;

/**
 * 
 * @author timoh <timoh6@gmail.com>
 * @license Public Domain
 */
class McryptAes128Cbc extends McryptAes256Cbc
{
    public function getKeyLen()
    {
        // 16 bytes for AES-128.
        return 16;
    }
}