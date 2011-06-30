<?php
namespace TCrypto;

use TCrypto\Plugin\PluginInterface,
    TCrypto\Plugin\DefaultPlugin;

/**
 * The PluginContainer.
 * 
 * @author timoh <timoh6@gmail.com>
 * @license Public Domain
 */
class PluginContainer extends \SplObjectStorage
{
    public function __construct()
    {
        parent::attach(new DefaultPlugin());
    }
    
    /**
     *
     * @param TCrypto\Plugin\PluginInterface $plugin 
     */
    public function attach(PluginInterface $plugin)
    {
        parent::attach($plugin);
    }
    
    /**
     *
     * @param mixed $data
     * @return string|false 
     */
    public function saveDispatcher($data)
    {
        foreach ($this as $plugin)
        {
            $data = $plugin->saveAction($data);
        }
        unset($plugin);
        
        if (isset($data[0]))
        {
            return $data;
        }
        else
        {
            return false;
        }
    }
    
    /**
     *
     * @param mixed $data
     * @return mixed
     */
    public function extractDispatcher($data)
    {
        // Reverse plugins. Plugins need to be called in reverse order
        // when extracting data. This "double foreach" smells a bit hacky...
        $reverseContainer = array();
        
        foreach ($this as $plugin)
        {
            $reverseContainer[] = $plugin;
        }
        
        $reverseContainer = array_reverse($reverseContainer);
        
        foreach ($reverseContainer as $plugin)
        {
            $data = $plugin->extractAction($data);
        }
        unset($plugin);
        
        return $data;
    }
}
