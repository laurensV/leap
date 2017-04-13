<?php
namespace Leap\Core;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Class Controller
 *
 * @package Leap\Core
 */
class Controller
{
    /**
     * @var \Leap\Core\Hooks
     */
    protected $hooks;
    /**
     * @var \Leap\Core\Route
     */
    protected $route;
    /**
     * @var \Leap\Core\PluginManager
     */
    protected $plugin_manager;
    /**
     * @var \Leap\Core\PdoPlus|null
     */
    protected $pdo;
    /**
     * @var \Leap\Core\Config
     */
    protected $config;
    /**
     * @var bool
     */
    public $access = false;

    /**
     *
     * @param \Leap\Core\Route         $route
     * @param \Leap\Core\Hooks         $hooks
     * @param \Leap\Core\PluginManager $plugin_manager
     * @param \Leap\Core\Config        $config
     * @param \Leap\Core\PdoPlus|null  $pdo
     */
    public function __construct(Route $route, Hooks $hooks, PluginManager $plugin_manager, Config $config, ?PdoPlus $pdo)
    {
        $this->pdo            = $pdo;
        $this->config         = $config;
        $this->route          = $route;
        $this->hooks          = $hooks;
        $this->plugin_manager = $plugin_manager;
    }

    /**
     * Check if we have a database connection
     *
     * @return bool
     */
    public function hasConnection(): bool
    {
        return ($this->pdo instanceof PdoPlus && $this->pdo->hasConnection());
    }

    /**
     * init function called after constructing the controller.
     * Can be overriden in classes that extend this class
     */
    public function init()
    {
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface|null $request
     *
     * @return mixed
     */
    public function __invoke($parameters, ServerRequestInterface $request = null)
    {
        return;
    }

    /**
     * Function to check whether the user has access to the page
     *
     * @return bool
     */
    public function hasAccess(): bool
    {
        /* this core controller has to return true to be able to access core pages */
        return true;
    }
}
