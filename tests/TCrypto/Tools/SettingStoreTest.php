<?php

class TCrypto_Tools_SettingStoreTest extends PHPUnit_Framework_TestCase
{
    public function testSetAndGet()
    {
        $ss = new TCrypto\Tools\SettingStore();
        
        $expectedValue1 = 0;
        $expectedValue2 = 'string 1';
        
        $ss->set('key1', $expectedValue1);
        $ss->set('key2', $expectedValue2);
        
        $value1 = $ss->get('key1');
        $value2 = $ss->get('key2');
        
        $this->assertEquals($expectedValue1, $value1);
        $this->assertEquals($expectedValue2, $value2);
    }
}
