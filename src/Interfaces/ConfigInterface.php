<?php
namespace Leap\Interfaces;

/**
 * Interface ConfigInterface
 *
 * @package Leap\Interfaces
 */
interface ConfigInterface
{
    /**
     * Gets a configuration setting using a simple or nested key.
     * Nested keys are similar to JSON paths that use the dot
     * dot notation.
     *
     * @param  string $key
     * @param  mixed  $default
     *
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * Function for setting configuration values, using
     * either simple or nested keys.
     *
     * @param  string $key
     * @param  mixed  $value
     *
     * @return void
     */
    public function set(string $key, $value): void;

    /**
     * Function for checking if configuration values exist, using
     * either simple or nested keys.
     *
     * @param  string $key
     *
     * @return boolean
     */
    public function has(string $key): bool;

    /**
     * Get all of the configuration items
     *
     * @return array
     */
    public function all(): array;
}