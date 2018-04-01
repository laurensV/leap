<?php

namespace Leap;


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
     *
     * @var \Psr\Container\ContainerInterface
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
        $this->registerDefaultServices($configuration);
    }

    /**
     * Register default services needed to run Leap
     *
     * @param ConfigInterface $configuration
     */
    private function registerDefaultServices($configuration) {
        /*****************************
         *       Configuration       *
         *****************************/
        $this->container['config'] = $configuration;

        /*****************************
         *       Hook System         *
         *****************************/
        $this->container['hooks'] = function($container) {
            return new Hooks();
        };

        /*****************************
         *          Router           *
         *****************************/
        $this->container['router'] = function($container) {
            $router = new Router();
            /* Set plugin manager in router to support making routes dependent on plugins (optional) */
            $router->setPluginManager($container->get('pluginManager'));
            return $router;
        };

        /*****************************
         *       Plugin Manager      *
         *****************************/
        $this->container['pluginManager'] =  function($container) {
            return new PluginManager();
        };

        /*****************************
         *   Controller (Factory)    *
         *****************************/
        $this->container['controllerFactory'] = function($container) {
            /* Normally it would be bad practice to inject DI Container into a class,
             * because it can be misused as a service locator. However, for this
             * purpose it is OK, as this is a special case, because the Controller
             * class can be anything and the DIC is only used to resolve the Controller,
             * not to retrieve other services.
             */
            return new ControllerFactory($container);
        };

        $this->container['kernel'] = function ($container) {
            $hooks = $container->get('hooks');
            $router = $container->get('router');
            $pluginManager = $container->get('pluginManager');
            $controllerFactory = $container->get('controllerFactory');
            $config = $container->get('config');
            return new Kernel($hooks, $router, $pluginManager, $controllerFactory, $config);
        };
    }

    /**
     * Enable access to the DI container by consumers of $app
     *
     * @return \Psr\Container\ContainerInterface
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