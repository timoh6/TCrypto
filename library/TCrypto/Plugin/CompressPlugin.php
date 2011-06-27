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
        return gzdeflate($data);
    }
    
    public function extractAction($data)
    {
        return gzinflate($data);
    }
}
