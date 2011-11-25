<?php
class TCrypto_Plugin_DefaultPluginTest extends PHPUnit_Framework_TestCase
{
    public function testSaveActionReturnsString()
    {
        $originalData = array('foo' => 'bar');
        
        $dp = new TCrypto\Plugin\DefaultPlugin();
        $data = $dp->saveAction($originalData);
        
        $this->assertInternalType('string', $data);
    }
    
    public function testExtractActionReturnsArray()
    {
        $originalData = 'a:1:{s:3:"foo";s:3:"bar";}';
        
        $dp = new TCrypto\Plugin\DefaultPlugin();
        $data = $dp->extractAction($originalData);
        
        $this->assertInternalType('array', $data);
    }
    
    public function testSaveAndExtract()
    {
        $data = array('data' => 'There are a number... in discussions and presentations about various protocols');
        
        $dp = new TCrypto\Plugin\DefaultPlugin();
        $savedData = $dp->saveAction($data);
        $extractedData = $dp->extractAction($savedData);
        
        $this->assertSame($data, $extractedData);
    }
    
    public function testSaveActionReturnsFalseOnNonArray()
    {
        $originalData = 'foo123';
        
        $dp = new TCrypto\Plugin\DefaultPlugin();
        $data = $dp->saveAction($originalData);
        
        $this->assertFalse($data);
    }
}
