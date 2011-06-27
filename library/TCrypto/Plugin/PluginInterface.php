<?php
namespace TCrypto\Plugin;

/**
 * 
 * @author timoh <timoh6@gmail.com>
 * @license Public Domain
 */
interface PluginInterface
{
    /**
     * Returns the data as a string on success, or false on error.
     * 
     * @param mixed $data
     * @return string
     */
    public function saveAction($data);
    
    /**
     * Returns the data as a string on success, or false on error.
     * 
     * @param mixed $data
     * @return string
     */
    public function extractAction($data);
}