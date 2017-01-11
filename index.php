<?php
/**
 * Leap - Lightweight Extensible Adjustable PHP Framework
 *
 * @package  Leap
 * @author   Laurens Verspeek
 *
 * The Front Controller that serves all page requests for the Leap framework.
 *
 * All Leap code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt files in the "core" directory.
 */

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Leap uses Composer' autoloader to automatically load classes into the
| framework. Programmers are far too lazy to manually include all the
| class files. Simply include it and we'll get autoloading for free.
*/

use Aura\Di\ContainerBuilder;
use Leap\Core\Controller;
use Leap\Core\Hooks;
use Leap\Core\PdoPlus;
use Leap\Core\PluginManager;
use Leap\Core\Router;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

$autoloader = require 'vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Include configuration handler
|--------------------------------------------------------------------------
|
| Include the configuration handler.
| Configurations can be filled in in the file `configHandler.php` or
| config.local.php`.
*/
require 'core/configHandler.php';

/*
|--------------------------------------------------------------------------
| Include helper functions
|--------------------------------------------------------------------------
|
| Include useful helper functions that can be used throughout the
| whole Leap framework.
*/
require 'core/include/helpers.php';

/*
|--------------------------------------------------------------------------
| Setup the Leap application
|--------------------------------------------------------------------------
|
| This bootstraps the Leap framework and gets it ready for use.
| (core/Kernel.php)
|
*/
error_reporting(E_ALL);
ini_set('display_errors', 1);

/* Create PSR7 Request and Response */
$request  = ServerRequestFactory::fromGlobals();
$response = new Response();

/* Create and config Dependency Injector Container */
$builder  = new ContainerBuilder();
$di = $builder->newInstance();

$di->set('hooks', $di->lazyNew(Hooks::class));
$di->set('router', $di->lazyNew(Router::class));
$di->set('pluginManager', $di->lazyNew(PluginManager::class));
$di->set('request', $di->lazy(['ServerRequestFactory', 'fromGlobals']));
$di->set('route', $di->lazyGetCall('router', 'match', $di->lazyGet('request')));
//$di->set('controller', $di->lazyGetCall('ControllerFactory', 'make', $di->lazyGet('route')));

$di->set('controller', $di->lazy(function () use ($di) {
    $route = $di->get('route');
    return $di->newInstance($route->controller['class']);
}));

/* Set database service if specified in config */
$db_conf = config('database');
if ($db_conf['db_type'] === "mysql") {
    if (!isset($db_conf['db_host']) || !isset($db_conf['db_user']) || !isset($db_conf['db_pass']) || !isset($db_conf['db_name'])) {
        // TODO: error handling
        die('not enough database info');
    }
    /* Create PdoPlus object with pdo connection inside */
    $di->set('pdo', $di->lazyNew(PdoPlus::class));
    $di->params[PdoPlus::class]['host']     = $db_conf['db_host'];
    $di->params[PdoPlus::class]['username'] = $db_conf['db_user'];
    $di->params[PdoPlus::class]['password'] = $db_conf['db_pass'];
    $di->params[PdoPlus::class]['dbName']   = $db_conf['db_name'];
}

$di->params[PluginManager::class]['pdo']         = $di->has('pdo') ? $di->lazyGet('pdo') : null;

$di->params[Controller::class]['route']          = $di->lazyGet('route');
$di->params[Controller::class]['hooks']          = $di->lazyGet('hooks');
$di->params[Controller::class]['plugin_manager'] = $di->lazyGet('pluginManager');
$di->params[Controller::class]['pdo']            = $di->has('pdo') ? $di->lazyGet('pdo') : null;

/* Set plugin manager in router to support making routes dependent on plugins (optional) */
$di->setters[Router::class]['setPluginManager'] = $di->lazyGet('pluginManager');;

/* Fetch objects from DI Container */
$di->get('hooks');
$di->get('router');
$di->get('pluginManager');
\Leap\Core\ControllerFactory::make($di->get('router')->match($request), $di);
die('test');



$kernel = new Leap\Core\Kernel();

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Time to run our bootstrapped Leap application!
*/
$kernel->run();

/* TODO: implement unit testing with PHPUnit */
/* TODO: error handling */
/* TODO: phpdoc */
/* TODO: composer: repo maken voor plugins */
/* TODO: composer: eigen custom installer maken */
/* TODO: move file directory */
/* TODO: move autoloader to core folder */
/* TODO: namespace function for plugins */
/* TODO: find out differences between hooks and events and pick one */