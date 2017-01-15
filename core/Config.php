<?php
/**
 * Created by PhpStorm.
 * User: Laurens
 * Date: 1/15/2017
 * Time: 5:27 PM
 */

namespace Leap\Core;

class Config
{
    public static $config = [];

    static function load($configFile) {
        self::$config = require(ROOT . $configFile);
        /* check for local config file with same name as main config file */
        $parts = explode('.', $configFile);
        $extension = array_pop($parts);
        $localConfigFile = implode(".", $parts) . '.local.' . $extension;
        $localConfigFile = ROOT . $localConfigFile;
        if (file_exists($localConfigFile)) {
            $localConfig = require $localConfigFile;
            self::$config      = array_replace_recursive(self::$config, $localConfig);
        }
    }
}