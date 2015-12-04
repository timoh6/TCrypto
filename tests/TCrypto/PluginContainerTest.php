<?php
class TCrypto_PluginContainerTest extends PHPUnit_Framework_TestCase
{
   public function testAttachPlugin()
   {
       $pc = new TCrypto\PluginContainer();
       $pc->attachPlugin(new TCrypto\Plugin\CompressPlugin());
   }
   
   public function testPluginChain()
   {
       $originalData = array('foo' => 123, 'bar' => 'string');
       
       $pc = new TCrypto\PluginContainer();
       $pc->attachPlugin(new TCrypto\Plugin\CompressPlugin());
       
       $dataAfterSaveDispatcher = $pc->saveDispatcher($originalData, false);
       $dataAfterExtractDispatcher = $pc->extractDispatcher($dataAfterSaveDispatcher, false);
       
       $this->assertSame($originalData, $dataAfterExtractDispatcher);
   }
}
