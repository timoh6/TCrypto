<?php
namespace TCrypto\Tools\Cli;

/**
 * A simple base class for command-line applications.
 */
class App
{
    /**
     *
     * @var array 
     */
    protected $_apps = null;
    
    /**
     *
     * @var TCrypto\Tools\Cli\AppCore
     */
    protected $_core = null;
    
    public function __construct(AppCore $core, array $apps)
    {
        $this->_apps = $apps;
        $this->_core = $core;
    }
    
    public function run()
    {
        $appTitles = array();
        $appFunctions = array();
        
        if (!empty($this->_apps))
        {
            foreach ($this->_apps as $key => $app)
            {
                $appTitles[] = $app['title'];
                $appFunctions[$key] = $app['function'];
            }

            while ($this->_core->isRunning() === true)
            {
                $menu = $this->_core->cli()->prompt('TCrypto Keytool:', $appTitles);

                $appFunctions[$menu]($this->_core);
            }
        }
    }
}