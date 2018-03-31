<?php
namespace Leap;

use Leap\Interfaces\ConfigInterface;

/**
 * Class Config
 *
 * @package Leap
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
    public function __construct($config = [])
    {
        if (is_string($config)) {
            $this->loadFromFile($config);
        } else if (is_array($config)) {
            $this->addFromArray($config);
        }
    }

    /**
     * Adds config from a supported configuration file format.
     *
     * @param string|array $config
     */
    public function loadFromFile($config)
    {
        $configFile = $config;
        $config     = require(ROOT . $configFile);
        $this->addFromArray($config);

        /* check for local config file with same name as main config file */
        $parts           = explode('.', $configFile);
        $extension       = array_pop($parts);
        $localConfigFile = implode(".", $parts) . '.local.' . $extension;
        $localConfigFile = ROOT . $localConfigFile;
        if (file_exists($localConfigFile)) {
            $localConfig = require $localConfigFile;
            $this->addFromArray($localConfig);
        }
    }

    /**
     * Add configuration from an array
     *
     * @param array $config
     */
    private function addFromArray(array $config): void
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
     *
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