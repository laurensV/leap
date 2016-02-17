<?php
class Template
{
    protected $template;
    protected $page;
    protected $stylesheets;
    protected $scripts;
    protected $hooks;
    protected $plugins;

    public function __construct($template, $page, $hooks, $plugins)
    {
        $this->template = $template;
        $this->page     = $page;
        $this->hooks    = $hooks;
        $this->plugins  = $plugins;
    }

    public function add_styles($styleArray, $base_path)
    {
        if (is_array($styleArray)) {
            foreach ($styleArray as $style) {
                $this->stylesheets[] = $this->parse_stylesheet($style, $base_path);
            }
        } else {
            $this->stylesheets[] = $this->parse_stylesheet($styleArray, $base_path);
        }
    }

    public function parse_stylesheet($style, $base_path)
    {
        $this->hooks->fire("parse_stylesheet", array(&$style, $base_path));

        if (!filter_var($style, FILTER_VALIDATE_URL)) {
            if ($style[0] != "/") {
                $style = str_replace_first(ROOT, BASE_URL, $base_path) . $style;
            }
        }

        return $style;
    }

    public function parse_script($script, $base_path)
    {
        if (!filter_var($script, FILTER_VALIDATE_URL)) {
            if ($script[0] != "/") {
                $script = str_replace_first(ROOT, BASE_URL, $base_path) . $script;
            }
        }
        return $script;
    }

    public function add_scripts($scriptArray, $base_path)
    {
        if (is_array($scriptArray)) {
            foreach ($scriptArray as $script) {
                $this->scripts[] = $this->parse_script($script, $base_path);
            }
        } else {
            $this->scripts[] = $this->parse_script($scriptArray, $base_path);
        }
    }

    /**
     * Set Variables *
     */
    public function set($name, $value)
    {
        $this->variables[$name] = $value;
    }

    /* get all javascript and css files to be included */
    public function include_scripts_css()
    {
        if (file_exists(ROOT . "/site/stylesheets.ini")) {
            extract(parse_ini_file(ROOT . "/site/stylesheets.ini"));
            if (isset($stylesheets)) {
                $this->add_styles($stylesheets, ROOT . "/site/");
                unset($stylesheets);
            }
        }

        if (file_exists(ROOT . "/site/scripts.ini")) {
            extract(parse_ini_file(ROOT . "/site/scripts.ini"));
            if (isset($scripts)) {
                $this->add_scripts($scripts, ROOT . "/site/");
                unset($scripts);
            }
        }

        foreach ($this->plugins as $path) {
            if (file_exists($path . "/stylesheets.ini")) {
                extract(parse_ini_file($path . "/stylesheets.ini"));
                if (isset($stylesheets)) {
                    $this->add_styles($stylesheets, $path);
                    unset($stylesheets);
                }
            }

            if (file_exists($path . "/scripts.ini")) {
                extract(parse_ini_file($path . "/scripts.ini"));
                if (isset($scripts)) {
                    $this->add_scripts($scripts, $path);
                    unset($scripts);
                }
            }
        }
    }

    /**
     * Display Template *
     */
    public function render()
    {
        extract($this->variables);

        /* get all javascript and css files to be included */
        $this->include_scripts_css();
        /* include the start of the html page */
        require_once ROOT . "/core/include/start_page.php";

        chdir($this->page['path']);
        if (file_exists($this->page['value'])) {
            ob_start();
            call_user_func(function () {
                extract($this->variables);
                require_once ($this->page['value']);
            });
            $page = ob_get_contents();
            ob_end_clean();
            $this->set('page', $page);
        } else {
            header('location: ' . BASE_URL . '/404');
        }
        chdir($this->template['path']);
        if (file_exists($this->template['value'])) {
            /* call anonymous function to hide variables */
            call_user_func(function () {
                extract($this->variables);
                require_once ($this->template['value']);
            });
        } else {
            echo $page;
        }
        /* include the end of the html page */
        require_once ROOT . "/core/include/end_page.php";
    }

}
