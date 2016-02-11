<?php
class Controller
{
    protected $model;
    protected $page;
    protected $template;

    /**
     * Whenever controller is created, load the model and the template.
     */
    function __construct($model, $template, $page) {
        $this->model = new $model();
        $this->template = new Template($template, $page);
        $this->page = $page;
    }

    function default_action($params){
        if(!empty($params)){
            header('location: ' . URL . '/404');
        }
        global $config;
        $this->set('site_title', $this->page . " - " . $config['application']['site_name']);

    }

    public function include_header_hook(){
        return array();
    }
    public function include_footer_hook(){
        return array();
    }

    /**
     * Set Variables 
     */
    public function set($name,$value) {
        $this->template->set($name,$value);
    }

    public function render() {
        $this->template->render();
    }
}