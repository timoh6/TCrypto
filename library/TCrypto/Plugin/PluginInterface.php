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

    /**
     * Returns whether this plugin can be safely used with data encryption.
     * I.e. data compression plugins will not be safe to use if data encryption
     * is also being used. This is because compression leaks information about
     * the plain text.
     *
     * @return boolean
     */
    public function isEncryptionCompatible();
}