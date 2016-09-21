<?php
namespace Leap\Core;

class Controller
{
    protected $model;
    protected $page;
    protected $template;
    protected $hooks;
    protected $plugin_manager;
    public $access;

    /**
     * Whenever controller is created, load the model and the template.
     */
    public function __construct($route, $hooks, $plugin_manager, $pdo)
    {
        if ($this->grantAccess()) {
            /* TODO: rewrite in same way as controller */
            $model = "Leap\\Core\\" . $route['model'];
            $this->model          = new $model($pdo);
            $this->hooks          = $hooks;
            $this->plugin_manager = $plugin_manager;
            $this->template       = new Template($route['template'], $route['page'], $hooks, $this->plugin_manager->enabled_plugins, $route['stylesheets'], $route['scripts']);
            $this->page           = $route['page'];
            $this->init();
            $this->access = true;
            if($route['title']){
                $this->set('title', $route['title']);
            } else {
                $tmp_page = explode("/", explode(".", $this->page['value'])[0]);
                $this->set('title', ucfirst(end($tmp_page)));
            }
        } else {
            $this->access = false;
        }
    }

    public function init()
    {}
    public function defaultAction($params)
    {}
    public function includeHeaderHook()
    {
        return array();
    }

    public function includeFooterHook()
    {
        return array();
    }

    /**
     * Set Variables
     */
    public function set($name, $value)
    {
        $this->template->set($name, $value);
    }

    public function grantAccess()
    {
        return true;
    }

    public function render()
    {
        $this->template->render();
    }
}
