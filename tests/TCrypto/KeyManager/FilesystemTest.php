<?php
class TCrypto_KeyManager_FilesystemTest extends PHPUnit_Framework_TestCase
{
    public function testCanSetKeysAsArray()
    {
        $keysArray = include dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'Data' . DIRECTORY_SEPARATOR . 'dummyKey.php';
        
        $primaryIndex = $keysArray['tcrypto_key_data']['meta_data']['primary_index'];
        $primaryAuthenticationKey = $keysArray['tcrypto_key_data']['keys'][$primaryIndex]['authentication'];
        $primaryEncryptionKey = $keysArray['tcrypto_key_data']['keys'][$primaryIndex]['encryption'];
        
        $km = new TCrypto\KeyManager\Filesystem();
        $km->setKeysAsArray($keysArray);
        
        $this->assertEquals($primaryAuthenticationKey, $km->getKeyByVersion('authentication'));
        $this->assertEquals($primaryEncryptionKey, $km->getKeyByVersion('encryption'));
        
    }
    
    public function testCanSetKeysAsFile()
    {
        $keysFile = dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'Data' . DIRECTORY_SEPARATOR . 'dummyKey.php';
        $keysArray = include $keysFile;
        
        $primaryIndex = $keysArray['tcrypto_key_data']['meta_data']['primary_index'];
        $primaryAuthenticationKey = $keysArray['tcrypto_key_data']['keys'][$primaryIndex]['authentication'];
        $primaryEncryptionKey = $keysArray['tcrypto_key_data']['keys'][$primaryIndex]['encryption'];
        
        $km = new TCrypto\KeyManager\Filesystem($keysFile);
        
        $this->assertEquals($primaryAuthenticationKey, $km->getKeyByVersion('authentication'));
        $this->assertEquals($primaryEncryptionKey, $km->getKeyByVersion('encryption'));
        
    }
}