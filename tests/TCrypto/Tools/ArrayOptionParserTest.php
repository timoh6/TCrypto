<?php

class TCrypto_Tools_ArrayOptionParserTest extends PHPUnit_Framework_TestCase
{
    public function testCanParseValues()
    {
        $path = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'Data' . DIRECTORY_SEPARATOR . 'sampleOptions.php';
        $aop = new TCrypto\Tools\ArrayOptionParser($path);
        
        $expected1 = 3;
        $expected2 = '/path/to/Keystore/default';
        $expected3 = 0600;
        
        $option1 = $aop->getOption('bytes_in_key_name');
        $option2 = $aop->getOption('keyfile_location');
        $option3 = $aop->getOption('keyfile_permissions');
        
        $this->assertEquals($expected1, $option1);
        $this->assertEquals($expected2, $option2);
        $this->assertEquals($expected3, $option3);
    }
}
