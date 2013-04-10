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
    /**
     * By default, PluginContainer is constructed using DefaultPlugin
     * as the first plugin. DefaultPlugin will serialize/unserialize TCrypto's
     * $_data array. If you wish to use your own "DefaultPlugin", make sure it
     * serializes/unserializes the data.
     * 
     * As an example, you could use a custom plugin which uses Igbinary
     * instead of the standard PHP serializer:
     * $plugins = new TCrypto\PluginContainer(new MyIgbinarySerializerPlugin());
     *
     * @param \TCrypto\Plugin\PluginInterface $firstPlugin
     */
    public function __construct(PluginInterface $firstPlugin = null)
    {
        if ($firstPlugin === null)
        {
            $this->attachPlugin(new DefaultPlugin());
        }
        else
        {
            $this->attachPlugin($firstPlugin);
        }
    }
    
    /**
     *
     * @param \TCrypto\Plugin\PluginInterface $plugin
     */
    public function attachPlugin(PluginInterface $plugin)
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
        // when extracting data.
        $reverseContainer = iterator_to_array($this);
        $reverseContainer = array_reverse($reverseContainer);
        
        foreach ($reverseContainer as $plugin)
        {
            $data = $plugin->extractAction($data);
        }
        unset($plugin);
        
        return $data;
    }
}
