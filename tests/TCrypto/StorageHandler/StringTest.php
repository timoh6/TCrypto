<?php
class TCrypto_StorageHandler_StringTest extends PHPUnit_Framework_TestCase
{
    public function testReturnFalseOnEmpty()
    {
        $s = new TCrypto\StorageHandler\String('');
        $data = $s->fetch();

        $this->assertFalse($data);
    }

    public function testReturnFalseOnInvalidBase64Data()
    {
        $s = new TCrypto\StorageHandler\String('%');
        $data = $s->fetch();

        $this->assertFalse($data);
    }
}