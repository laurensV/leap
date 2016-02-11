<?php
class Template {
     
    protected $template;
    protected $page;
    protected $styles;
    protected $scripts;
     
    function __construct($template, $page) {
        $this->template = $template;
        $this->page = $page;
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

    function addScripts($scriptArray){
        foreach($scriptArray as $script){
            $this->scripts[] = $script;
        }
    }
 
    /** Set Variables **/
    function set($name,$value) {
        $this->variables[$name] = $value;
    }

    /* get all javascript and css files to be included */
    function include_scripts_css(){
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
            $this->addScripts($scripts);
        }
    }
 
    /** Display Template **/
    function render() {
        extract($this->variables);

        /* get all javascript and css files to be included */
        $this->include_scripts_css();

        /* include the start of the html page */
        require_once(ROOT . "/core/include/start_page.php");

        /* include the content */
        $template_path = ROOT . "/site/templates/" . $this->template . ".php";
        $page_path = ROOT . "/site/pages/" . $this->page . ".php";

        if(file_exists($page_path)) {
            ob_start();
            call_user_func(function() {
                extract($this->variables);
                require_once(ROOT . "/site/pages/" . $this->page . ".php");
            });
            $page = ob_get_contents();
            ob_end_clean();
            $this->set('page', $page);
        } else {
            header('location: ' . URL . '/404');
        }
        if(file_exists($template_path)) {
            /* call anonymous function to hide variables */
            call_user_func(function() {
                extract($this->variables);
                require_once(ROOT . "/site/templates/" . $this->template . ".php");
            });
        } else {
            echo $page;
        }
        /* include the end of the html page */
        require_once(ROOT . "/core/include/end_page.php");
    }
 
}