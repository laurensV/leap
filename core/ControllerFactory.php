<?php
namespace Leap\Core;

use Aura\Di\Container;
use Interop\Container\ContainerInterface;
use Leap\Plugins\Admin\Controllers\AdminController;

class ControllerFactory
{

    static public function make(Route $route, Container $di) : Controller {
        if (isset($route->controller['file'])) {
            global $autoloader;
            $autoloader->addClassMap(["Leap\\Plugins\\" . ucfirst($route->controller['plugin']) . "\\Controllers\\" . $route->controller['class'] => $route->controller['file']]);
        }

        /* If the controller class name does not contain the namespace yet, add it */
        if (strpos($route->controller['class'], "\\") === false && isset($route->controller['plugin'])) {
            $namespace                  = getNamespace($route->controller['plugin'], "controller");
            $route->controller['class'] = $namespace . $route->controller['class'];
        }
        /* Check if controller class extends the core controller */
        if ($route->controller['class'] == 'Leap\Core\Controller' || is_subclass_of($route->controller['class'], "Leap\\Core\\Controller")) {
            /* Create the controller instance */
            $di->set('controller', $di->lazyNew($route->controller['class']));
        } else if (class_exists($route->controller['class'])) {
            /* TODO: error handling */
            printr("Controller class '" . $route->controller['class'] . "' does not extend the base 'Leap\\Core\\Controller' class", true);
        } else {
            /* TODO: error handling */
            printr("Controller class '" . $route->controller['class'] . "' not found", true);
        }

        /** @var Controller $controller */
        $controller = $di->get('controller');
        return $controller;
    }
}