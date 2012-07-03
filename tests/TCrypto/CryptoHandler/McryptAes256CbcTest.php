<?php

class TCrypto_CryptoHandler_McryptAes256CbcTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        if (!extension_loaded('mcrypt'))
        {
            $this->markTestSkipped('The Mcrypt extension is not available');
        }
    }
    
    public function testEncryptWithAesVectors()
    {
        $key = pack("H*" , '603deb1015ca71be2b73aef0857d77811f352c073b6108d72d9810a30914dff4');
        $iv = pack("H*" , '000102030405060708090A0B0C0D0E0F');
        $plaintext = pack("H*" , '6bc1bee22e409f96e93d7e117393172a');
        $expectedCiphertext = pack("H*" , 'f58c4c04d6e5f1ba779eabfb5f7bfbd6485a5c81519cf378fa36d42b8547edc0');
        
        $aes = new TCrypto\CryptoHandler\McryptAes256Cbc();
        $ciphertext = $aes->encrypt($plaintext, $iv, $key);
        
        $this->assertEquals($expectedCiphertext, $ciphertext);
    }
    
    public function testDecryptWithAesVectors()
    {
        $key = pack("H*" , '603deb1015ca71be2b73aef0857d77811f352c073b6108d72d9810a30914dff4');
        $iv = pack("H*" , '000102030405060708090A0B0C0D0E0F');
        $expectedPlaintext = pack("H*" , '6bc1bee22e409f96e93d7e117393172a');
        $ciphertext = pack("H*" , 'f58c4c04d6e5f1ba779eabfb5f7bfbd6485a5c81519cf378fa36d42b8547edc0');
        
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
    
    public function testPkcs7Pad()
    {
        if (version_compare(PHP_VERSION, '5.3.2') >= 0)
        {
            $string1 = 'abcdefg';
            $expectedPaddedString1 = 'abcdefg' . str_repeat(chr(9), 9);
            $padder = new ReflectionMethod('TCrypto\\CryptoHandler\\McryptAes256Cbc', '_pkcs7Pad');
            $padder->setAccessible(true);
            $padder->invokeArgs(new TCrypto\CryptoHandler\McryptAes256Cbc(), array(&$string1));
            
            $string2 = 'aaaaaaaaaaaaaaaa';
            $expectedPaddedString2 = 'aaaaaaaaaaaaaaaa' . str_repeat(chr(16), 16);
            $padder->invokeArgs(new TCrypto\CryptoHandler\McryptAes256Cbc(), array(&$string2));
            
            $this->assertEquals($string1, $expectedPaddedString1);
            $this->assertEquals($string2, $expectedPaddedString2);
        }
        else
        {
            $this->markTestSkipped('Need PHP 5.3.2+ to test private methods.');
        }
    }
    
    public function testPkcs7Strip()
    {
        if (version_compare(PHP_VERSION, '5.3.2') >= 0)
        {
            $paddedString1 = 'abcdefg' . str_repeat(chr(9), 9);
            $expectedStrippedString1 = 'abcdefg';
            $stripper = new ReflectionMethod('TCrypto\\CryptoHandler\\McryptAes256Cbc', '_pkcs7Strip');
            $stripper->setAccessible(true);
            $stripper->invokeArgs(new TCrypto\CryptoHandler\McryptAes256Cbc(), array(&$paddedString1));
            
            $paddedString2 = 'aaaaaaaaaaaaaaaa' . str_repeat(chr(16), 16);
            $expectedStrippedString2 = 'aaaaaaaaaaaaaaaa';
            $stripper->invokeArgs(new TCrypto\CryptoHandler\McryptAes256Cbc(), array(&$paddedString2));
            
            $this->assertEquals($paddedString1, $expectedStrippedString1);
            $this->assertEquals($paddedString2, $expectedStrippedString2);
        }
        else
        {
            $this->markTestSkipped('Need PHP 5.3.2+ to test private methods.');
        }
    }
    
    public function testPkcs7StripSetsDataAsFalseOnInvalidPad()
    {
        if (version_compare(PHP_VERSION, '5.3.2') >= 0)
        {
            $result1 = 'abc';
            $expectedResult1 = false;
            $stripper = new ReflectionMethod('TCrypto\\CryptoHandler\\McryptAes256Cbc', '_pkcs7Strip');
            $stripper->setAccessible(true);
            $stripper->invokeArgs(new TCrypto\CryptoHandler\McryptAes256Cbc(), array(&$result1));

            $result2 = 'aaaaaaaaaaaaaaa' . chr(2);
            $expectedResult2 = false;
            $stripper->invokeArgs(new TCrypto\CryptoHandler\McryptAes256Cbc(), array(&$result2));

            $result3 = 'aaaaaaaaaaaaaaaa';
            $expectedResult3 = false;
            $stripper->invokeArgs(new TCrypto\CryptoHandler\McryptAes256Cbc(), array(&$result3));

            $this->assertEquals($result1, $expectedResult1);
            $this->assertEquals($result2, $expectedResult2);
            $this->assertEquals($result3, $expectedResult3);
        }
        else
        {
            $this->markTestSkipped('Need PHP 5.3.2+ to test private methods.');
        }
    }
}
