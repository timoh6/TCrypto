<?php

class TCrypto_CryptoTest extends PHPUnit_Framework_TestCase
{
    public function testGetRandomBytes()
    {
        $bytes1 = TCrypto\Crypto::getRandomBytes(16);
        $bytes2 = TCrypto\Crypto::getRandomBytes(16);
        
        $this->assertNotEquals($bytes1, $bytes2);
        $this->assertEquals(16, strlen($bytes1));
        $this->assertEquals(16, strlen($bytes2));
    }
    
    public function testCompareString()
    {
        $string1 = 'abcde';
        $string2 = 'abcde';
        $string3 = 'Abcde';
        
        $compareResult1 = TCrypto\Crypto::compareString($string1, $string2);
        $compareResult2 = TCrypto\Crypto::compareString($string1, $string3);
        
        $this->assertTrue($compareResult1);
        $this->assertFalse($compareResult2);
    }
}
