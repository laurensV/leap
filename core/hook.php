<?php
class Hooks
{
    private $hooks;
    public function __construct($hooks = array())
    {
        $this->hooks = array();
        foreach($hooks as $hook){
            $this->create($hook);
        }
    }
    public function getHooks(){
        return array_keys($this->hooks);
    }
    public function add($name, $callback) {
        // callback parameters must be at least syntactically
        // correct when added.
        if (!isset($this->hooks[$name])){
            return;
        }
        if (!is_callable($callback, true))
        {
            throw new InvalidArgumentException(sprintf('Invalid callback: %s.', print_r($callback, true)));
        }
        $this->hooks[$name][] = $callback;
    }
    public function getCallbacks($name)
    {
        return isset($this->hooks[$name]) ? $this->hooks[$name] : array();
    }
    public function create($name) {
        $this->hooks[$name]  = array();
    }
    public function fire($name, $args = array())
    {
        foreach($this->getCallbacks($name) as $callback)
        {
            // prevent fatal errors, do your own warning or
            // exception here as you need it.
            if (!is_callable($callback))
                continue;

            call_user_func_array($callback, $args);
        }
    }
}

