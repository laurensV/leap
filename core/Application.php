<?php
namespace Leap\Core;

/**
 * (optional) Wrapper to start the Leap Framework:
 *   1.  include helpers
 *   2.  specify config
 *   3.  setup dependencies
 *   4.  resolve kernel from DIC
 *   5.  run kernel
 *
 * @package Leap\Core
 */
class Application
{
    private $kernel;

    function __construct($configFileOrArray = 'config/config.php')
    {
        /*
        |--------------------------------------------------------------------------
        | Include Global helper functions and Constants
        |--------------------------------------------------------------------------
        |
        | Include useful helper functions and constants that can be used throughout
        | the whole Leap framework.
        */
        require 'include/helpers.php';

        /*
        |--------------------------------------------------------------------------
        | Setup the Leap Kernel
        |--------------------------------------------------------------------------
        |
        | Create the Dependency Injection Container and resolve the
        | kernel (core/Kernel.php) of the Leap framework from the DIC.
        */
        $di           = require 'dependencies.php';

        global $config;
        $config = $di->get('config');

        $this->kernel = $di->get('kernel');
    }

    public function run(): void
    {
        $this->kernel->run();
    }

}