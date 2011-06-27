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
        return serialize($data);
    }
    
    public function extractAction($data)
    {
        return unserialize($data);
    }
}