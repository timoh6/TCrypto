<?php
namespace TCrypto\CompressHandler;

/**
 * 
 * @author timoh <timoh6@gmail.com>
 * @license Public Domain
 */
interface CompressInterface
{
    /**
     * @param string $data
     */
    public function compress($data);
    
    /**
     * @param string $data
     */
    public function decompress($data);
}
