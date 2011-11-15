<?php
namespace TCrypto\Tools\Cli;

use TCrypto\Tools\Cli\Horde\Cli as HordeCli;
use TCrypto\Tools\SettingStore;
use TCrypto\Tools\Keytool\Filesystem;
use TCrypto\Tools\Serializer\SerializerInterface;
use TCrypto\Tools\Keytool\KeyParser;

/**
 * Quick and dirty wrapper.
 */
class AppCore
{
    /**
     *
     * @var TCrypto\Tools\Cli\Horde\Cli
     */
    protected $_cli = null;
    
    /**
     *
     * @var TCrypto\Tools\SettingStore
     */
    protected $_settings = null;
    
    /**
     *
     * @var TCrypto\Tools\Keytool\Filesystem
     */
    protected $_helper = null;
    
    /**
     *
     * @var TCrypto\Tools\Keytool\KeyParser
     */
    protected $_keyParser = null;
    
    /**
     *
     * @var bool
     */
    protected $_isRunning = true;
    
    public function __construct(HordeCli $cli, SettingStore $settings, Filesystem $helper, KeyParser $keyParser)
    {
        $this->_cli = $cli;
        $this->_settings = $settings;
        $this->_helper = $helper;
        $this->_keyParser = $keyParser;
    }
    
    public function shutDown()
    {
        $this->_isRunning = false;
    }
    
    public function isRunning()
    {
        return $this->_isRunning;
    }
    
    public function cli()
    {
        return $this->_cli;
    }
    
    public function settings()
    {
        return $this->_settings;
    }
    
    public function helper()
    {
        return $this->_helper;
    }
    
    public function serializer()
    {
        return $this->_serializer;
    }
    
    public function keyParser()
    {
        return $this->_keyParser;
    }
}
