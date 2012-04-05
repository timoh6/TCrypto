<?php
namespace TCrypto\Tools\Keytool;

/**
 * Filesystem based checks and operations.
 * 
 * @author timoh <timoh6@gmail.com>
 * @license Public Domain
 */ 
class Filesystem
{
    public function __construct()
    {
    }
    
    /**
     * Check whether the file is a valid and usable TCrypto keyfile.
     * 
     * @todo Separate filesystem checks and file content checks.
     * 
     * @param string keyfile A path to the TCrypto key file
     */
    public function isValidFile($keyfile)
    {
        $dir = dirname($keyfile);
        
        if (!isset($keyfile[0]))
        {
            throw new \Exception('No keyfile selected.');
        }

        if (!is_dir($dir))
        {
            throw new \Exception('The specified directory is not available. Create a directory for the keyfile first.');
        }

        if (is_dir($keyfile))
        {
            throw new \Exception('The specified keyfile is a directory.');
        }

        if (!is_writable($dir))
        {
            throw new \Exception('The specified directory is not writable: ' . $dir);
        }

        if (file_exists($keyfile))
        {
            $tmp = include $keyfile;
            
            if (empty($tmp))
            {
                // Valid keyfile.
                unset($tmp);
            }
            else
            {
                // Empty file (valid keyfile)
                $tmpString = @file_get_contents($keyfile);
                if ($tmpString === '')
                {
                    return;
                }
                unset($tmpString);
            
                // Valid keyfile.
                if (is_array($tmp) && isset($tmp['tcrypto_key_data']))
                {
                    unset($tmp);
                }
                else
                {
                    unset($tmp);
                    throw new \Exception('The specified file does not look like a valid TCrypto keyfile.');
                }
            }
        }

        return;
    }

    /**
     * Writes an empty file.
     * 
     * @param string keyfile
     * @param int keyfilePermissions Optional Unix filesystem permission for the created file.
     */
    public static function createKeyfile($keyfile, $keyfilePermissions = 0600)
    {
        if (@file_put_contents($keyfile, '') === false)
        {
            throw new \Exception('The keyfile could not be created.');
        }
        
        if ($keyfilePermissions !== null)
        {
            chmod($keyfile, $keyfilePermissions);
        }
    }

    /**
     * Writes the $contents to the keyfile.
     * 
     * @param string keyfile
     * @param string $contents
     * @param int keyfilePermissions Optional Unix filesystem permission for the updated file.
     */
    public static function updateKeyfile($keyfile, $contents, $keyfilePermissions = 0600)
    {
        if (@file_put_contents($keyfile, $contents, LOCK_EX) === false)
        {
            unset($contents);
            throw new \Exception('Could not write data to the keyfile.');
        }
        
        if ($keyfilePermissions !== null)
        {
            chmod($keyfile, $keyfilePermissions);
        }
    }
}