<?php
namespace TCrypto\CompressHandler;

/**
 * 
 * @author timoh <timoh6@gmail.com>
 * @license Public Domain
 */
class Gz implements CompressInterface
{
    /**
     * Returns the gzdeflated data.
     *
     * @param string $data
     * @return string|false 
     */
    public function compress($data)
    {
        return gzdeflate($data, 9);
    }
    
    /**
     * Returns the gzinflated data.
     *
     * @param type $data
     * @return string|false 
     */
    public function decompress($data)
    {
        return gzinflate($data);
    }
}
