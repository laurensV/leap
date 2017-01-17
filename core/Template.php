<?php
namespace Leap\Core;

use Psr\Http\Message\ServerRequestInterface;

class Template
{
    private $template;
    private $page;
    private $stylesheets;
    private $scripts;
    private $stylesheets_route;
    private $scripts_route;
    private $hooks;
    private $config;

    public function __construct(Route $route, Hooks $hooks, Config $config)
    {
        $this->template          = $route->template;
        $this->page              = $route->page;
        $this->hooks             = $hooks;
        $this->stylesheets_route = $route->stylesheets;
        $this->scripts_route     = $route->scripts;
        $this->config            = $config;
        $this->initVars();
    }

    private function initVars()
    {
        $this->set('site_title', $this->config->application['site_name']);
        $this->set('messages', $this->render_messages(get_messages()));
    }

    private function render_messages($messages_array)
    {
        $messages = "";
        foreach ($messages_array as $type => $messages_of_type) {
            switch ($type) {
                case 'error':
                    $class = 'danger';
                    break;
                default:
                    $class = $type;
                    break;
            }
            foreach ($messages_of_type as $message_of_type) {
                $messages .= '<div class="alert alert-' . $class . ' alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' . $message_of_type . '</div>';
            }

        }
        return $messages;
    }

    public function addStylesheet($styleArray, $base_path)
    {
        if (is_array($styleArray)) {
            foreach ($styleArray as $style) {
                $this->stylesheets[] = $this->parseStylesheet($style, $base_path);
            }
        } else {
            $this->stylesheets[] = $this->parseStylesheet($styleArray, $base_path);
        }
    }

    public function parseStylesheet($style, $base_path)
    {
        $this->hooks->fire("hook_parseStylesheet", [&$style, $base_path]);

        if (!filter_var($style, FILTER_VALIDATE_URL)) {
            if ($style[0] != "/") {
                $style = strReplaceFirst(ROOT, BASE_URL, $base_path) . $style;
            }
        }

        return $style;
    }

    public function parseScript($script, $base_path)
    {
        if (!filter_var($script, FILTER_VALIDATE_URL)) {
            if ($script[0] != "/") {
                $script = strReplaceFirst(ROOT, BASE_URL, $base_path) . $script;
            }
        }
        return $script;
    }

    public function addScript($scriptArray, $base_path)
    {
        if (is_array($scriptArray)) {
            foreach ($scriptArray as $script) {
                $this->scripts[] = $this->parseScript($script, $base_path);
            }
        } else {
            $this->scripts[] = $this->parseScript($scriptArray, $base_path);
        }
    }

    /**
     * Set Variables *
     *
     * @param $name
     * @param $value
     */
    public function set($name, $value)
    {
        $this->variables[$name] = $value;
    }

    /* get all javascript and css files to be included */
    public function includeScriptsCss()
    {
        foreach ($this->stylesheets_route as $styleArray) {
            $this->addStylesheet($styleArray['value'], $styleArray['path']);
        }
        foreach ($this->scripts_route as $scriptArray) {
            $this->addScript($scriptArray['value'], $scriptArray['path']);
        }
        /* remove duplicates */
        $this->stylesheets = array_unique($this->stylesheets);
        $this->scripts     = array_unique($this->scripts);
    }

    /**
     * Display Template
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return string
     */
    public function render(ServerRequestInterface $request): string
    {
        if (!empty($this->variables)) {
            extract($this->variables);
        }

        /* get all javascript and css files to be included */
        $this->includeScriptsCss();

        ob_start();
        /* include the start of the html page */
        require_once ROOT . "core/include/start_page.php";

        chdir($this->page['path']);
        ob_start();
        call_user_func(function () {
            if (!empty($this->variables)) {
                extract($this->variables);
            }
            /* no need to check if file exists, as we already did that in the router */
            require_once($this->page['value']);
        });
        $page = ob_get_contents();
        ob_end_clean();
        $this->set('page', $page);

        chdir($this->template['path']);
        if (file_exists($this->template['value'])) {
            /* call anonymous function to hide variables */
            call_user_func(function () {
                extract($this->variables);
                require_once($this->template['value']);
            });
        } else {
            echo $page;
        }
        /* include the end of the html page */
        require_once ROOT . "core/include/end_page.php";
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

}
