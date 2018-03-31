<?php

namespace Leap;

use Aura\Di\ContainerBuilder;

use Leap\Interfaces\ConfigInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Wrapper to start the Leap Framework
 *
 * @package Leap
 */
class Application
{
    /**
     * @var Kernel
     */
    private $kernel;

    /**
     * Container
     */
    private $container;

    /**
     * Application constructor.
     *
     * @param string $configuration
     */
    function __construct($configuration = [])
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
        // Check if we already have a Config object. If not, create one
        if (!$configuration instanceof ConfigInterface) {
            $configuration = new Config($configuration);
        }
        /*
        |--------------------------------------------------------------------------
        | Create Container
        |--------------------------------------------------------------------------
        |
        | Create the Dependency Injection Container
        */
        $custom_container = $configuration->get('container');
        if ($custom_container) {
            $this->container = new $custom_container();
        } else {
            $this->container = new Container();
        }
        $this->container['config'] = $configuration;
        $this->container['kernel'] = function($container){return 'test';};
//        $di->set('kernel', $di->lazyNew(Kernel::class));
//        $di->params[Kernel::class]['hooks']             = $di->lazyGet('hooks');
//        $di->params[Kernel::class]['pluginManager']     = $di->lazyGet('pluginManager');
//        $di->params[Kernel::class]['router']            = $di->lazyGet('router');
//        $di->params[Kernel::class]['controllerFactory'] = $di->lazyGet('controllerFactory');
//        $di->params[Kernel::class]['config']            = $config;
    }

    /**
     * Enable access to the DI container by consumers of $app
     *
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Run the Leap Application
     *
     * @param Request $request
     */
    public function run(Request $request = null): void
    {
        /*
        |--------------------------------------------------------------------------
        | Run the Leap Kernel
        |--------------------------------------------------------------------------
        |
        | Resolve the kernel (core/Kernel.php) of the Leap framework from the DIC
        | and run the kernel.
        */
        $this->kernel = $this->container->get('kernel');
        $this->kernel->run($request);
    }
}