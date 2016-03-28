<?php
namespace Leap\Core;

class Hooks
{
    private $hooks;
    public function __construct($hooks = array())
    {
        $this->hooks = array();
        foreach ($hooks as $hook) {
            $this->create($hook);
        }
    }

    public function getHooks()
    {
        return array_keys($this->hooks);
    }

    public function add($name, $namespace)
    {
        // callback parameters must be at least syntactically
        // correct when added.
        if (!isset($this->hooks[$name])) {
            $this->create($name);
        }
        $callback             = "Leap\\Hooks\\" . $namespace . "\\" . $name;
        $this->hooks[$name][] = $callback;
    }

    public function getCallbacks($name)
    {
        return isset($this->hooks[$name]) ? $this->hooks[$name] : array();
    }

    public function create($name)
    {
        $this->hooks[strtolower($name)] = array();
    }

    public function fire($name, $args = array())
    {
        foreach ($this->getCallbacks(strtolower($name)) as $callback) {
            call_user_func_array($callback, $args);
        }
    }
}
