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
    
    public function testSaveExtract()
    {
        $value1 = 'Press any key to continue.';
        
        $keymanager = $this->getMock('TCrypto\\KeyManager\\Filesystem');
        $keymanager->expects($this->any())->method('getKeyByVersion')->will($this->returnValue('xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'));
        $keymanager->expects($this->any())->method('getPrimaryKeyVersion')->will($this->returnValue('xxx'));
        
        $storage = new TCrypto\StorageHandler\ArrayStorage();
        
        $plugins = $this->getMock('TCrypto\\PluginContainer');
        $plugins->expects($this->once())->method('saveDispatcher')->will($this->returnCallback('serialize'));
        $plugins->expects($this->once())->method('extractDispatcher')->will($this->returnCallback('unserialize'));
        
        $tc = new TCrypto\Crypto($keymanager, $storage, $plugins);  
        $tc->setValue('key1', $value1);
        $tc->save();
        unset($tc);
        
        $tc2 = new TCrypto\Crypto($keymanager, $storage, $plugins);
        $this->assertSame($tc2->getValue('key1'), $value1);
    }
    
    public function testGetValueReturnsDefault()
    {
        $default = 'default';
        
        $keymanager = $this->getMock('TCrypto\\KeyManager\\Filesystem');
        $keymanager->expects($this->any())->method('getKeyByVersion')->will($this->returnValue('xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'));
        
        $storage = new TCrypto\StorageHandler\ArrayStorage();  
        $plugins = $this->getMock('TCrypto\\PluginContainer');
        
        $tc = new TCrypto\Crypto($keymanager, $storage, $plugins);
        
        $notExists = $tc->getValue('key1', $default);
        $notExists2 = $tc->getValue('key1');
        $this->assertEquals($notExists, $default);
        $this->assertEquals($notExists2, null);
    }
    
    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testConstructFailsWithoutArguments()
    {
        new TCrypto\Crypto();
    }
    
    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testConstructFailsWithInvalidArguments()
    {
        new TCrypto\Crypto(null, null, null, null);
    }
    
    /**
     * @expectedException TCrypto\Exception
     */
    public function testCannotHashKeysLongerThan32Bytes()
    {
        if (version_compare(PHP_VERSION, '5.3.2') >= 0)
        {
            $keymanager = $this->getMock('TCrypto\\KeyManager\\Filesystem');
            $storage = new TCrypto\StorageHandler\ArrayStorage();  
            $plugins = $this->getMock('TCrypto\\PluginContainer');

            $hasher = new ReflectionMethod('TCrypto\\Crypto', '_hash');
            $hasher->setAccessible(true);
            $hasher->invokeArgs(new TCrypto\Crypto($keymanager, $storage, $plugins), array('text', 33));
        }
        else
        {
            $this->markTestSkipped('Need PHP 5.3.2+ to test private methods.');
        }
    }
}
