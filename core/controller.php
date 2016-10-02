<?php
namespace Leap\Core;

/**
 * Class Controller
 *
 * @package Leap\Core
 */
class Controller
{
    protected $model;
    protected $page;
    protected $template;
    protected $hooks;
    protected $plugin_manager;
    public    $access;

    /**
     * Whenever controller is created, load the model and the template.
     *
     * @param $route
     * @param $hooks
     * @param $plugin_manager
     * @param $pdo
     */
    public function __construct($route, $hooks, $plugin_manager, $pdo)
    {
        if ($this->grantAccess()) {
            $model = $route['model']['class'];
            /* Check if model class extends the core model */
            if ($model == 'Leap\Core\Model' || is_subclass_of($model, "Leap\\Core\\Model")) {
                /* Create the model instance */
                $this->model = new $model($pdo);
            } else if (class_exists($route['model']['class'])) {
                printr("Model class '" . $model . "' does not extend the base 'Leap\\Core\\Model' class");
            } else {
                printr("Model class '" . $model . "' not found");
            }
            $this->hooks          = $hooks;
            $this->plugin_manager = $plugin_manager;
            $this->template       = new Template($route['template'], $route['page'], $hooks, $this->plugin_manager->enabled_plugins, $route['stylesheets'], $route['scripts']);
            $this->page           = $route['page'];
            $this->init();
            $this->access = true;
            if (isset($route['title'])) {
                $this->set('title', $route['title']);
            } else {
                $tmp_page = explode("/", explode(".", $this->page['value'])[0]);
                $this->set('title', ucfirst(end($tmp_page)));
            }
        } else {
            $this->access = false;
        }
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
    public function set($name, $value)
    {
        $this->template->set($name, $value);
    }

    /**
     * Function to check whether the user has access to the page
     *
     * @return bool
     */
    public function grantAccess()
    {
        /* this core controller has to return true as access to be able to access core pages */
        return true;
    }

    /**
     * Render the template
     */
    public function render()
    {
        $this->template->render();
    }
}
