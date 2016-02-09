<?php
class Controller
{
    protected $model = null;
    protected $page = null;
    protected $variables = array();
    protected $styles;
    protected $scripts;

    /**
     * Whenever controller is created, open a database connection too and load "the model".
     */
    function __construct($model, $page) {
        $this->model = new $model();
        $this->page = $page;
    }

    function default_action($params){
        if(!empty($params)){
            header('location: ' . URL . '/404');
        }
        global $config;
        $this->set('site_title', $this->page . " - " . $config['application']['site_name']);

    }

    function addStyles($styleArray){
        foreach($styleArray as $style){
            if(substr($style, -5) == ".less") {
                $less_file = array($style => "/");
                $options = array('cache_dir' => ROOT . '/site/files/css', 'compress' => true);
                $this->styles[] = "/site/files/css/" . Less_Cache::Get( $less_file, $options );
            } else {
                /* file is not a less file, so no need to compile to css */
                $this->styles[] = $style;
            }
        }
    }

    public function addScripts($scriptArray){
        foreach($scriptArray as $script){
            $this->scripts[] = $script;
        }
    }

    /**
     * Set Variables 
     */
    public function set($name,$value) {
        $this->variables[$name] = $value;
    }

    public function render() {
        extract($this->variables);
        /* get all javascript and css files to be included */
        $include_always = ROOT . "/site/always_include.php";
        $include_view = ROOT . "/site/pages/".$this->page."_include.php";
        if(file_exists($include_always)){
            require_once($include_always);
        }
        if(file_exists($include_view)){
            require_once($include_view);
        }

        if(isset($styles)){
            $this->addStyles($styles);
        }
        if(isset($scripts)){
            $this->addStyles($styles);
        }

        /* include the start of the html page */
        require_once(ROOT . "/core/include/start_page.php");
        /* include the content */
        $view_path = ROOT . "/site/pages/" . $this->page . ".php";
        $header_path = ROOT . "/sites/pages/". $this->page . "_header.php";
        $footer_path = ROOT . "/sites/pages/". $this->page . "_footer.php";
        if(file_exists($view_path)){
            if(file_exists($header_path)){
                require_once($header_path);
            }
            require_once($view_path);
            if(file_exists($footer_path)){
                require_once($footer_path);
            }
        } else {
            header('location: ' . URL . '/404');
        }
        /* include the end of the html page */
        require_once(ROOT . "/core/include/end_page.php");
    }
}