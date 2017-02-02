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

    /**
     * @var Router
     */
    private $router;

    /**
     * Application constructor.
     *
     * @param string $configuration
     */
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

        /*****************************
         *       Configuration       *
         *****************************/
        $configuration = $configuration ?? 'config/config.php';
        $config        = new Config($configuration);

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

        /**
         * Load PSR-15 middlewares into the Middelware Stack
         */
        $middlewares = require ROOT . "app/middleware/middlewares.php";
        $this->kernel->addMiddleware($middlewares);

        $this->router = $di->get('router');
        $this->router->addFile(ROOT . "app/app.routes.php", "app");
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