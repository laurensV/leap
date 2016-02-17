<?php
class Controller
{
    protected $model;
    protected $page;
    protected $template;
    protected $hooks;
    protected $plugin_manager;
    /**
     * Whenever controller is created, load the model and the template.
     */
    public function __construct($model, $template, $page, $hooks, $plugin_manager)
    {
        $this->model    = new $model();
        $this->hooks    = $hooks;
        $this->plugin_manager = $plugin_manager;
        $this->template = new Template($template, $page, $hooks, $this->plugin_manager->enabled_plugins);
        $this->page     = $page;
        $this->access   = true;
        $this->set('site_title', get_config('application')['site_name']);
        $this->init();
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
        if ($this->grant_access()) {
            $this->template->render();
        } else {
            header("Location: " . BASE_URL . "/permission_denied");
        }
    }
}
