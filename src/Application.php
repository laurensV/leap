<?php

namespace Leap;

use Leap\Interfaces\ConfigInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;

/**
 * Wrapper to start the Leap Framework
 *
 * @package Leap
 */
class Application
{
    /**
     * Leap Kernel
     *
     * @var Kernel
     */
    private $kernel;

    /**
     * Container
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     * Configuration
     *
     * @var ConfigInterface
     */
    private $configuration;

    /**
     * Application constructor.
     *
     * @param string $configuration
     */
    function __construct($configuration = [], $custom_container = null)
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
        | Create Configuration
        |--------------------------------------------------------------------------
        |
        | Create the Configuration object
        */
        // Check if we already have a Config object. If not, create one
        if (!$configuration instanceof ConfigInterface) {
            $this->configuration = new Config($configuration);
        } else {
            $this->configuration = $configuration;
        }
        /*
        |--------------------------------------------------------------------------
        | Create Container
        |--------------------------------------------------------------------------
        |
        | Create the Dependency Injection Container
        */
        if ($custom_container) {
            // Use a custom container. When you do this you have to register the default services yourself
            $this->container = $custom_container;
        } else {
            $this->container = new Container();
            $this->registerDefaultServices();
        }
    }

    /**
     * Register default services needed to run Leap
     *
     */
    private function registerDefaultServices()
    {
        /*****************************
         *       Configuration       *
         *****************************/
        $this->container['config'] = $this->configuration;

        /*****************************
         *       Hook System         *
         *****************************/
        $this->container['hooks'] = function ($container) {
            return new Hooks();
        };

        /*****************************
         *          Router           *
         *****************************/
        $this->container['router'] = function ($container) {
            $router = new Router();
            /* Set plugin manager in router to support making routes dependent on plugins (optional) */
            $router->setPluginManager($container->get('pluginManager'));
            return $router;
        };

        /*****************************
         *       Plugin Manager      *
         *****************************/
        $this->container['pluginManager'] = function ($container) {
            return new PluginManager();
        };

        /*****************************
         *   Controller (Factory)    *
         *****************************/
        $this->container['controllerFactory'] = function ($container) {
            /* Normally it would be bad practice to inject DI Container into a class,
             * because it can be misused as a service locator. However, for this
             * purpose it is OK, as this is a special case, because the Controller
             * class can be anything and the DIC is only used to resolve the Controller,
             * not to retrieve other services.
             */
            return new ControllerFactory($container);
        };

        $this->container['kernel'] = function ($container) {
            $hooks             = $container->get('hooks');
            $router            = $container->get('router');
            $pluginManager     = $container->get('pluginManager');
            $controllerFactory = $container->get('controllerFactory');
            $config            = $container->get('config');
            return new Kernel($hooks, $router, $pluginManager, $controllerFactory, $config);
        };
    }

    /**
     * Enable access to the DI container from the Application
     *
     * @return \Psr\Container\ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Enable access to the configuration from the Application
     *
     * @return ConfigInterface
     */
    public function getConfiguration()
    {
        return $this->configuration;
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