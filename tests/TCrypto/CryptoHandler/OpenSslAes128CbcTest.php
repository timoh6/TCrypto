<?php

class TCrypto_CryptoHandler_OpenSslAes128CbcTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        if (!extension_loaded('openssl') || version_compare(PHP_VERSION, '5.3.3') < 0)
        {
            $this->markTestSkipped('The OpenSSl extension is not available, or PHP version is less than 5.3.3.');
        }
    }
    
    public function testEncryptWithAesVectors()
    {
        $key = pack("H*" , '2b7e151628aed2a6abf7158809cf4f3c');
        $iv = pack("H*" , '000102030405060708090A0B0C0D0E0F');
        $plaintext = pack("H*" , '6bc1bee22e409f96e93d7e117393172a');
        $expectedCiphertext = pack("H*", '7649abac8119b246cee98e9b12e9197d8964e0b149c10b7b682e6e39aaeb731c');
        
        $aes = new TCrypto\CryptoHandler\OpenSslAes128Cbc();
        $ciphertext = $aes->encrypt($plaintext, $iv, $key);
        
        $this->assertEquals($expectedCiphertext, $ciphertext);
    }
    
    public function testDecryptWithAesVectors()
    {
        $key = pack("H*" , '2b7e151628aed2a6abf7158809cf4f3c');
        $iv = pack("H*" , '000102030405060708090A0B0C0D0E0F');
        $expectedPlaintext = pack("H*" , '6bc1bee22e409f96e93d7e117393172a');
        $ciphertext = pack("H*" , '7649abac8119b246cee98e9b12e9197d8964e0b149c10b7b682e6e39aaeb731c');
        
        $aes = new TCrypto\CryptoHandler\OpenSslAes128Cbc();
        $plaintext = $aes->decrypt($ciphertext, $iv, $key);
        
        $this->assertEquals($expectedPlaintext, $plaintext);
    }
    
    public function testEncryptAndDecrypt()
    {
        $originalPlaintext = 'Lets study the safety instructions later.';
        $iv = str_repeat(chr(65), 16);
        $key = str_repeat(chr(66), 16);
        
        $aes = new TCrypto\CryptoHandler\OpenSslAes128Cbc();
        $ciphertext = $aes->encrypt($originalPlaintext, $iv, $key);
        $plainText = $aes->decrypt($ciphertext, $iv, $key);
        
        $this->assertEquals($originalPlaintext, $plainText);
    }
}
