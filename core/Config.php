<?php
namespace Leap\Core;
use Leap\Core\Interfaces\ConfigInterface;

/**
 * Class Config
 *
 * @package Leap\Core
 */
class Config implements ConfigInterface
{

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @param string|array $config
     */
    public function __construct($config)
    {
        $this->load($config);
    }

    /**
     * Loads a supported configuration file format.
     *
     * @param string|array $config
     */
    public function load($config)
    {
        if (is_string($config)) {
            $configFile = $config;
            $config     = require(ROOT . $configFile);
            $this->addConfigArray($config);

            /* check for local config file with same name as main config file */
            $parts           = explode('.', $configFile);
            $extension       = array_pop($parts);
            $localConfigFile = implode(".", $parts) . '.local.' . $extension;
            $localConfigFile = ROOT . $localConfigFile;
            if (file_exists($localConfigFile)) {
                $localConfig = require $localConfigFile;
                $this->addConfigArray($localConfig);
            }
        } else if (is_array($config)) {
            $this->addConfigArray($config);
        }
    }

    private function addConfigArray(array $config): void
    {
        $this->config = array_replace_recursive($this->config, $config);
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key, $default = null)
    {
        if ($this->has($key)) {
            return $this->config[$key];
        }

        return $default;
    }

    /**
     * Magic function so that $obj->value will work.
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, $value): void
    {
        // Assign value at target node
        $this->config[$key] = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        return isset($this->config[$key]);
    }

    /**
     * {@inheritDoc}
     */
    public function all(): array
    {
        return $this->config;
    }
}