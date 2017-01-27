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
    protected $hooks;
    protected $route;
    protected $plugin_manager;
    protected $pdo;
    protected $config;
    public    $access = false;

    /**
     * Whenever controller is created, load the template.
     *
     * @param                        $route
     * @param                        $hooks
     * @param                        $plugin_manager
     * @param                        $pdo
     */
    public function __construct(Route $route, Hooks $hooks, PluginManager $plugin_manager, Config $config, ?PdoPlus $pdo)
    {
        $this->pdo            = $pdo;
        $this->config         = $config;
        $this->route          = $route;
        $this->hooks          = $hooks;
        $this->plugin_manager = $plugin_manager;
    }

    public function hasConnection(): bool
    {
        return ($this->pdo instanceof PdoPlus && $this->pdo->hasConnection());
    }

    /**
     *
     */
    public function init()
    {
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface|null $request
     *
     * @return mixed
     */
    public function __invoke(ServerRequestInterface $request = null, $parameters)
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
        /* this core controller has to return true as access to be able to access core pages */
        return true;
    }
}
