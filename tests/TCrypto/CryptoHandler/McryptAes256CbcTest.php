<?php

class TCrypto_CryptoHandler_McryptAes256CbcTest extends PHPUnit_Framework_TestCase
{
    public function testEncryptWithAesVectors()
    {
        $key = pack("H*" , '603deb1015ca71be2b73aef0857d77811f352c073b6108d72d9810a30914dff4');
        $iv = pack("H*" , '000102030405060708090A0B0C0D0E0F');
        $plaintext = pack("H*" , '6bc1bee22e409f96e93d7e117393172a');
        $expectedCiphertext = pack("H*" , 'f58c4c04d6e5f1ba779eabfb5f7bfbd6');
        
        $aes = new TCrypto\CryptoHandler\McryptAes256Cbc();
        $ciphertext = $aes->encrypt($plaintext, $iv, $key);
        
        $this->assertEquals($expectedCiphertext, $ciphertext);
    }
    
    public function testDecryptWithAesVectors()
    {
        $key = pack("H*" , '603deb1015ca71be2b73aef0857d77811f352c073b6108d72d9810a30914dff4');
        $iv = pack("H*" , '000102030405060708090A0B0C0D0E0F');
        $expectedPlaintext = pack("H*" , '6bc1bee22e409f96e93d7e117393172a');
        $ciphertext = pack("H*" , 'f58c4c04d6e5f1ba779eabfb5f7bfbd6');
        
        $aes = new TCrypto\CryptoHandler\McryptAes256Cbc();
        $plaintext = $aes->decrypt($ciphertext, $iv, $key);
        
        $this->assertEquals($expectedPlaintext, $plaintext);
    }
    
    public function testEncryptAndDecrypt()
    {
        $originalPlaintext = 'Lets study the safety instructions later.';
        $iv = str_repeat(chr(65), 16);
        $key = str_repeat(chr(66), 32);
        
        $aes = new TCrypto\CryptoHandler\McryptAes256Cbc();
        $ciphertext = $aes->encrypt($originalPlaintext, $iv, $key);
        $plainText = $aes->decrypt($ciphertext, $iv, $key);
        
        $this->assertEquals($originalPlaintext, $plainText);
    }
}
