<?php
namespace Frameworkname\Core;

class Controller
{
    protected $model;
    protected $page;
    protected $template;
    protected $hooks;
    protected $plugin_manager;
    public $result;

    /**
     * Whenever controller is created, load the model and the template.
     */
    public function __construct($model, $template, $page, $hooks, $plugin_manager, $pdo, $stylesheets_route, $scripts_route, $title)
    {
        if ($this->grantAccess()) {
            $model = "Frameworkname\\Core\\" . $model;
            $this->model          = new $model($pdo);
            $this->hooks          = $hooks;
            $this->plugin_manager = $plugin_manager;
            $this->template       = new Template($template, $page, $hooks, $this->plugin_manager->enabled_plugins, $stylesheets_route, $scripts_route);
            $this->page           = $page;
            $this->init();
            $this->result = 1;
            if($title){
                $this->set('title', $title);
            } else {
                $tmp_page = explode("/", explode(".", $this->page['value'])[0]);
                $this->set('title', ucfirst(end($tmp_page)));
            }
        } else {
            $this->result = -1;
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
