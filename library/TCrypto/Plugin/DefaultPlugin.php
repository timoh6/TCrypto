<?php
namespace TCrypto\Plugin;

/**
 * 
 * @author timoh <timoh6@gmail.com>
 * @license Public Domain
 */
class DefaultPlugin implements PluginInterface
{
    public function saveAction($data)
    {
        if (is_array($data))
        {
            return serialize($data);
        }
        else
        {
            return false;
        }
    }
    
    public function extractAction($data)
    {
        return unserialize($data);
    }

    public function isEncryptionCompatible()
    {
        return true;
    }
}