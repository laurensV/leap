<?php
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
    public function __construct($model, $template, $page, $hooks, $plugin_manager, $pdo, $stylesheets_route, $scripts_route)
    {
        if ($this->grant_access()) {
            $this->model    = new $model($pdo);
            $this->hooks    = $hooks;
            $this->plugin_manager = $plugin_manager;
            $this->template = new Template($template, $page, $hooks, $this->plugin_manager->enabled_plugins, $stylesheets_route, $scripts_route);
            $this->page     = $page;
            $this->set('site_title', config('application')['site_name']);
            $this->init();
            $this->result = 1;
        } else {
            $this->result = -1;
        }
    }

    public function init() {}
    public function default_action($params) {}
    public function include_header_hook()
    {
        return array();
    }

    public function include_footer_hook()
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

    public function grant_access()
    {
        return true;
    }

    public function render()
    {
        $this->template->render();
    }
}
