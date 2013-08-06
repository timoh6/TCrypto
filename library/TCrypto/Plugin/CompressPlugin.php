<?php
namespace TCrypto\Plugin;

/**
 * 
 * @author timoh <timoh6@gmail.com>
 * @license Public Domain
 */
class CompressPlugin implements PluginInterface
{
    public function saveAction($data)
    {
        if ($data === false)
        {
            return false;
        }
        
        return gzdeflate($data);
    }
    
    public function extractAction($data)
    {
        return gzinflate($data);
    }

    public function isEncryptionCompatible()
    {
        return false;
    }
}
