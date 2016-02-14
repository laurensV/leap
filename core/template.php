<?php
class Template {
     
    protected $template;
    protected $page;
    protected $stylesheets;
    protected $scripts;
    protected $hooks;
     
    function __construct($template, $page, $hooks) {
        $this->template = $template;
        $this->page = $page;
        $this->hooks = $hooks;
    }

    function add_styles($styleArray){
        if(is_array($styleArray)){
            foreach($styleArray as $style){
                $this->stylesheets[] = $this->parse_stylesheet($style);
            }
        } else {
            $this->stylesheets[] = $this->parse_stylesheet($styleArray);
        }
    }

    function parse_less(&$style) {
        if(substr($style, -5) == ".less") {
            if($style[0] == "/"){
                $style  = ROOT . $style;
            }
            chdir(ROOT . "/site/");
            $less_file = array($style => "/");
            $options = array('cache_dir' => ROOT . '/site/files/css', 'compress' => true);
            $style = "/site/files/css/" . Less_Cache::Get( $less_file, $options );
        }
    }

    function parse_stylesheet($style) {
        $this->hooks->fire("parse_stylesheet", array(&$style));

        /* file is not a less file, so no need to compile to css */
        if(!filter_var($style, FILTER_VALIDATE_URL)){
            if($style[0] != "/") {
                $style = "/site/" . $style;
            }
        }
        
        return $style;
    }

    function parse_script($script) {
        if(!filter_var($script, FILTER_VALIDATE_URL)){
            if(substr($script, 0, 6) != "/site/"){
                $script_withroot  = "/site/" . $script;
                if(file_exists(ROOT . $script_withroot)){
                    $script = $script_withroot;
                }
            }
        }
        return $script;
    }

    function add_scripts($scriptArray){
        if(is_array($scriptArray)){
            foreach($scriptArray as $script){
                $this->scripts[] = $this->parse_script($script);
            }
        } else {
            $this->scripts[] = $this->parse_script($scriptArray);
        }
    }
 
    /** Set Variables **/
    function set($name,$value) {
        $this->variables[$name] = $value;
    }

    /* get all javascript and css files to be included */
    function include_scripts_css(){
        if (file_exists(ROOT . "/site/stylesheets.ini")) {
            extract(parse_ini_file(ROOT . "/site/stylesheets.ini"));
        }
        if(isset($stylesheets)){
            $this->add_styles($stylesheets);
        }
        if (file_exists(ROOT . "/site/scripts.ini")) {
            extract(parse_ini_file(ROOT . "/site/scripts.ini"));
        }
        if(isset($scripts)){
            $this->add_scripts($scripts);
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