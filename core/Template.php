<?php
namespace Leap\Core;

use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;

class Template
{
    private $template;
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
        $this->stylesheets_route = $route->parameters['stylesheets'] ?? [];
        $this->scripts_route     = $route->parameters['scripts'] ?? [];
        $this->config            = $config;
        $this->initVars();
    }

    private function initVars()
    {
        $this->set('site_title', $this->config->application['name']);
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
            $this->addStylesheet($styleArray, ROOT);
        }
        foreach ($this->scripts_route as $scriptArray) {
            $this->addScript($scriptArray, ROOT);
        }
        /* remove duplicates */
        if (is_array($this->stylesheets)) {
            $this->stylesheets = array_unique($this->stylesheets);
        }
        if (is_array($this->scripts)) {
            $this->scripts = array_unique($this->scripts);
        }
    }

    /**
     * Display Template
     *
     * @return mixed
     */
    public function render($page)
    {
        if (!empty($this->variables)) {
            extract($this->variables);
        }

        /* get all javascript and css files to be included */
        $this->includeScriptsCss();
        $path = ROOT;
        if ($page[0] === "/") {
            $page         = substr($page, 1);
        } else {
            $parts = explode(":", $page);
            if (isset($parts[1])) {
                $page = $parts[1];
                switch ($parts[0]) {
                    case 'app':
                        $path = ROOT . 'app';
                        break;
                    case 'core':
                        $path = ROOT . 'core';
                        break;
                }
            }
        }

        chdir($path);
        if (!file_exists($page)) {
            $response = new Response();
            $response->getBody()->write("page " . $page . " not found");
            $response->withStatus(404);
            return $response;
        }
        ob_start();
        /* include the start of the html page */
        require_once ROOT . "core/include/start_page.php";

        ob_start();
        call_user_func(function () use ($page) {
            if (!empty($this->variables)) {
                extract($this->variables);
            }
            require_once $page;
        });
        $page = ob_get_contents();
        ob_end_clean();
        $this->set('page', $page);
        $this->template    = ['path' => ROOT . 'app/templates/', 'value' => "default_template.php"];

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
