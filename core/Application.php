<?php
namespace Leap\Core;

use Psr\Http\Message\ServerRequestInterface;

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

    /**
     * @var Kernel
     */
    private $kernel;

    function __construct($configuration = 'config/config.php')
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
        $di = require 'dependencies.php';

        $this->kernel = $di->get('kernel');
    }

    /**
     * Run the Leap Application
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     */
    public function run(ServerRequestInterface $request = null): void
    {
        $this->kernel->run($request);
    }
}