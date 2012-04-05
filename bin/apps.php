<?php

$displayKeyfile = function($core)
{
    $core->cli()->clearScreen();
    $core->cli()->writeln( $core->cli()->blue('Using keyfile: ') . $core->settings()->get('keyfile'));
    $core->cli()->writeln();
    
    return;
};

$addKeys = function($core)
{
    $core->cli()->clearScreen();
    $keyfile = $core->settings()->get('keyfile');
    $keyfilePermissions = $core->settings()->get('keyfile_permissions');
    $bytesInKeyName = $core->settings()->get('bytes_in_key_name');
    $lastKeyAddedTime = false;

    try
    {
        $core->helper()->isValidFile($keyfile);

        $keyContents = '';
        if (file_exists($keyfile))
        {
            $keyContents = (array) include $keyfile;
        }
        else
        {
            $core->helper()->createKeyfile($keyfile, $keyfilePermissions);
        }

        $keysArray = array();

        if (isset($keyContents['tcrypto_key_data']))
        {
            if (isset($keyContents['tcrypto_key_data']['keys']) && is_array($keyContents['tcrypto_key_data']['keys']))
            {
                foreach ($keyContents['tcrypto_key_data']['keys'] as $k => $v)
                {
                    if (isset($v['encryption'], $v['authentication'], $v['time']))
                    {
                        $keysArray[$k] = array('encryption' => $v['encryption'], 'authentication' => $v['authentication'], 'time' => $v['time']);
                    }
                }
            }

            if (isset($keyContents['tcrypto_key_data']['meta_data']['last_key_creation_time']) && ctype_digit($keyContents['tcrypto_key_data']['meta_data']['last_key_creation_time']))
            {
                $lastKeyAddedTime = $keyContents['tcrypto_key_data']['meta_data']['last_key_creation_time'];
            }
        }
        unset($keyContents);

        $timestamp = time();
        $prompt = false;

        if ($lastKeyAddedTime !== false && $lastKeyAddedTime < $timestamp)
        {
            $diff = $timestamp - $lastKeyAddedTime;
            if ($diff < 3600)
            {
                $prompt = true;
            }
        }

        if ($prompt === true)
        {
            $core->cli()->message('The current primary key is less than an hour old. Most probably it is not necessary to add a new primary key. Continue?');
            $core->cli()->writeln();

            while (true)
            {
                $menu = $core->cli()->prompt('Confirm:', array('No', 'Yes'), 0);

                switch ($menu)
                {
                    case 0:
                        $core->cli()->clearScreen();
                        $core->cli()->message('Cancelled adding new keys');
                        $core->cli()->writeln();
                        
                        return;
                    case 1:
                        break 2;
                }
            }
        }

        // Add the new key.
        $newKey = $core->keyParser()->getRandomKeys($bytesInKeyName);
        $keysArray[(string) $newKey['key']] = array('encryption' => (string) $newKey['encryption'], 'authentication' => (string) $newKey['authentication'], 'time' => $timestamp);
        $keyDataArray = array('tcrypto_key_data' => array('keys' => $keysArray,
                                                          'meta_data' => array('primary_index' => (string) $newKey['key'],
                                                                               'last_key_creation_time' => $timestamp)));
        
        unset($keysArray);
        $keyStringExport = $core->keyParser()->constructExportKeyString($keyDataArray);
        unset($newKey, $keyDataArray);
        
        $core->helper()->updateKeyfile($keyfile, $keyStringExport, $keyfilePermissions);
        unset($keyStringExport);

        $core->cli()->clearScreen();
        $core->cli()->message('New keys added', $type = 'cli.success');
    }
    catch (\Exception $e)
    {
        $core->cli()->clearScreen();
        $core->cli()->message($e->getMessage(), $type = 'cli.error');
    }
    $core->cli()->writeln();

    return;
};

$selectKeyfile = function($core)
{
    $core->cli()->clearScreen();
    $newPath = $core->cli()->prompt('Enter location of a keyfile: ', null, null);
    $newPath = trim($newPath);
    $core->cli()->clearScreen();
    
    try
    {
        $core->helper()->isValidFile($newPath);
        $core->settings()->set('keyfile', $newPath);
        $core->cli()->message('Keyfile changed', 'cli.success');
    }
    catch (\Exception $e)
    {
        $core->cli()->message($e->getMessage(), 'cli.error');
    }
    $core->cli()->writeln();

    return;
};

$displayKeyCount = function($core)
{
    $core->cli()->clearScreen();
    $keyfile = $core->settings()->get('keyfile');

    try
    {
        $core->helper()->isValidFile($keyfile);
        
        $keyCount = 0;
        $keyContents = '';
        
        if (file_exists($keyfile) && is_readable($keyfile))
        {
            $keyContents = (array) include $keyfile;
        }
        else
        {
            throw new \Exception('The specified keyfile is not available.');
        }

        if (isset($keyContents['tcrypto_key_data']))
        {
            if (isset($keyContents['tcrypto_key_data']['keys']) && is_array($keyContents['tcrypto_key_data']['keys']))
            {
                $keyCount = count($keyContents['tcrypto_key_data']['keys']);
            }
        }
        unset($keyContents);
        
        $core->cli()->writeln( $core->cli()->blue('Key count: ') . (int) $keyCount);
    }
    catch (\Exception $e)
    {
        $core->cli()->clearScreen();
        $core->cli()->message($e->getMessage(), 'cli.error');
    }
    $core->cli()->writeln();

    return;
};

$removeInactiveKeys = function($core)
{
    $defaultLifetime = (int) $core->settings()->get('key_max_lifetime');
    $keyfile = $core->settings()->get('keyfile');
    
    if ($defaultLifetime === 0)
    {
        $defaultLifetime = null;
    }
    
    $core->cli()->clearScreen();
    $core->cli()->message('To remove inactive keys, you need to enter TCrypto "max_lifetime" setting (in seconds). The primary keys won\'t be removed. If you are unsure, enter 3600.', $type = 'cli.message');
    $core->cli()->writeln();
    $core->cli()->message('Inactive keys are removed from the keyfile: ' . $keyfile, $type = 'cli.message');
    $lifetime = $core->cli()->prompt('Enter TCrypto "max_lifetime" in seconds: ', null, $defaultLifetime);
    
    $core->cli()->clearScreen();
    
    if ($lifetime > 0)
    {
        $core->cli()->message('Remove inactive keys that are older than ' . $lifetime . ' seconds?', 'cli.message');
        $core->cli()->writeln();
        $menu = $core->cli()->prompt('Confirm:', array('No', 'Yes'), 1);
        
        while (true)
        {
            switch ($menu)
            {
                case 0:
                    $core->cli()->clearScreen();
                    $core->cli()->message('Cancelled removing inactive keys');
                    $core->cli()->writeln();

                    return;
                case 1:
                    break 2;
            }
        }
        
        $core->cli()->clearScreen();
        
        try
        {   
            $core->helper()->isValidFile($keyfile);
            
            $keyContents = array();
            if (file_exists($keyfile))
            {
                $keyContents = (array) include $keyfile;
            }
            
            $keysArray = array();
            $inactiveKeys = 0;

            if (isset($keyContents['tcrypto_key_data']['meta_data']['last_key_creation_time'])
                && isset($keyContents['tcrypto_key_data']['meta_data']['primary_index'])
                && isset($keyContents['tcrypto_key_data']['keys'])
                && is_array($keyContents['tcrypto_key_data']['keys']))
            {
                $lastKeyAddedTime = (int) $keyContents['tcrypto_key_data']['meta_data']['last_key_creation_time'];
                $primaryIndex = (string) $keyContents['tcrypto_key_data']['meta_data']['primary_index'];
                
                // Remove all keys (except primary) after $lastKeyAddedTime + $lifetime
                if ($lastKeyAddedTime + $lifetime < time())
                {
                    $primaryKey = $keyContents['tcrypto_key_data']['keys'][$primaryIndex];
                    $totalKeys = count($keyContents['tcrypto_key_data']['keys']);
                    
                    if ($totalKeys > 1)
                    {
                        $inactiveKeys = $totalKeys - 1;
                        
                        $keysArray[$primaryIndex] = $primaryKey;
                    }
                }
            }
            
            if ($inactiveKeys > 0 && !empty($keysArray))
            {
                // Rebuild key data.
                $keyDataArray = array('tcrypto_key_data' => array('keys' => $keysArray, 'meta_data' => $keyContents['tcrypto_key_data']['meta_data']));
                $keyStringExport = $core->keyParser()->constructExportKeyString($keyDataArray);
                $keyfilePermissions = $core->settings()->get('keyfile_permissions');

                $core->helper()->updateKeyfile($keyfile, $keyStringExport, $keyfilePermissions);
                unset($keyDataArray, $keyStringExport);
                    
                $message = 'Inactive keys removed: ' . $inactiveKeys;
            }
            else
            {
                $message = 'No inactive keys found. Nothing to be done.';
            }
            
            unset($keysArray, $keyContents);
            
            $core->cli()->message($message, 'cli.success');
        }
        catch (\Exception $e)
        {
            $core->cli()->message('Could not remove inactive keys: ' . $e->getMessage(), 'cli.error');
        }
        
        $core->cli()->writeln();
    }
    else
    {
        $core->cli()->message('Invalid value.', 'cli.error');
        $core->cli()->writeln();
    }
    
    return;
};
