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

        /* check for local settings */
//        $parts = split('.', $configFile);
//        $extension = $parts[-1];
//        array_pop($parts);
//        $localConfigFile = ('.', ;
        $localConfigFile = ROOT . 'config/config.local.php';
        if (file_exists($localConfigFile)) {
            $localConfig = require $localConfigFile;
            self::$config      = array_replace_recursive(self::$config, $localConfig);
        }
        if (!isset(self::$config['database']['db_type'])) {
            self::$config['database']['db_type'] = "";
        }
        if (!isset(self::$config['database']['plugins_from_db'])) {
            self::$config['general']['plugins_from_db'] = true;
        }
    }

}