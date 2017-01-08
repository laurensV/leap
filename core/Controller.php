<?php
namespace Leap\Core;

use Psr\Http\Message\{
    ResponseInterface, ServerRequestInterface
};

/**
 * Class Controller
 *
 * @package Leap\Core
 */
class Controller
{
    protected $page;
    protected $template;
    protected $hooks;
    protected $plugin_manager;
    protected $pdo;
    public    $access;

    /**
     * Whenever controller is created, load the template.
     *
     * @param                        $route
     * @param                        $hooks
     * @param                        $plugin_manager
     * @param                        $pdo
     */
    public function __construct(Route $route, Hooks $hooks, PluginManager $plugin_manager, ?PdoPlus $pdo)
    {
        $this->pdo = $pdo;
        $this->hooks          = $hooks;
        $this->plugin_manager = $plugin_manager;
        if ($this->grantAccess()) {
            /* TODO: pass whole route variable */
            $this->template = new Template($route, $hooks);
            $this->page     = $route->page;
            $this->init();
            $this->access = true;
            if (isset($route->title)) {
                $this->set('title', $route->title);
            } else {
                $tmp_page = explode("/", explode(".", $this->page['value'])[0]);
                $this->set('title', ucfirst(end($tmp_page)));
            }
        } else {
            $this->access = false;
        }
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
     * @param $params
     */
    public function defaultAction()
    {
    }

    /**
     * @return array
     */
    public function includeHeaderHook()
    {
        return [];
    }

    /**
     * @return array
     */
    public function includeFooterHook()
    {
        return [];
    }

    /**
     * Set Variables
     *
     * @param $name
     * @param $value
     */
    public function set($name, $value): void
    {
        $this->template->set($name, $value);
    }

    /**
     * Function to check whether the user has access to the page
     *
     * @return bool
     */
    public function grantAccess(): bool
    {
        /* this core controller has to return true as access to be able to access core pages */
        return true;
    }

    /**
     * Render the template
     *
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    public function render(ServerRequestInterface $request): string
    {
        return $this->template->render($request);
    }
}
