<?php
namespace Leap\Core;

/**
 * Class Hooks
 *
 * @package Leap\Core
 */
class Hooks
{
    private $hooks;

    /**
     * Hooks constructor.
     *
     * @param array $hooks
     */
    public function __construct($hooks = [])
    {
        $this->hooks = [];
        foreach ($hooks as $hook) {
            $this->create($hook);
        }
    }

    /**
     * Return all hook names
     *
     * @return array
     */
    public function getHooks()
    {
        return array_keys($this->hooks);
    }

    /**
     * Add a callback to a hook. If they hook does not exist, it is created.
     *
     * @param $name
     * @param $namespace
     */
    public function add($name, $namespace)
    {
        // callback parameters must be at least syntactically
        // correct when added.
        if (!isset($this->hooks[$name])) {
            $this->create($name);
        }
        /* TODO: change namespace to Leap\Plugins\[pluginname]\Hooks ? */
        $callback             = "Leap\\Hooks\\" . $namespace . "\\" . $name;
        $this->hooks[$name][] = $callback;
    }

    /**
     * Get all callbacks for a given hook
     *
     * @param $name
     *
     * @return array|mixed
     */
    public function getCallbacks($name)
    {
        return isset($this->hooks[$name]) ? $this->hooks[$name] : [];
    }

    /**
     * Create a hook
     *
     * @param $name
     */
    public function create($name)
    {
        $this->hooks[strtolower($name)] = [];
    }

    /**
     * Fire a hook
     *
     * @param       $name
     * @param array $args
     */
    public function fire($name, $args = [])
    {
        foreach ($this->getCallbacks(strtolower($name)) as $callback) {
            call_user_func_array($callback, $args);
        }
    }
}
