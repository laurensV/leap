<?php
namespace Leap\Core;

/* Create and config Dependency Injector Container */
use Aura\Di\ContainerBuilder;
use Zend\Diactoros\ServerRequestFactory;

$builder = new ContainerBuilder();
$di      = $builder->newInstance();

$di->set('hooks', $di->lazyNew(Hooks::class));
$di->set('router', $di->lazyNew(Router::class));
$di->set('pluginManager', $di->lazyNew(PluginManager::class));
$di->set('controllerFactory', $di->lazyNew(ControllerFactory::class));
$di->set('kernel', $di->lazyNew(Kernel::class));
$di->set('request', $di->lazy([ServerRequestFactory::class, 'fromGlobals']));

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

$di->params[PluginManager::class]['pdo'] = $di->has('pdo') ? $di->lazyGet('pdo') : null;

$di->params[Kernel::class]['hooks']             = $di->lazyGet('hooks');
$di->params[Kernel::class]['pluginManager']     = $di->lazyGet('pluginManager');
$di->params[Kernel::class]['router']            = $di->lazyGet('router');
$di->params[Kernel::class]['controllerFactory'] = $di->lazyGet('controllerFactory');

/* Normally not so oke to inject DI Container into a class,
* because it can be misused as a service locator. However, for this
* purpose it is OK, as this is a special case, because the Controller
* class can be anything and the DIC is only used to resolve the Controller,
* not to retrieve other services. */
$di->params[ControllerFactory::class]['di'] = $di;

$di->params[Controller::class]['hooks']          = $di->lazyGet('hooks');
$di->params[Controller::class]['plugin_manager'] = $di->lazyGet('pluginManager');
$di->params[Controller::class]['pdo']            = $di->has('pdo') ? $di->lazyGet('pdo') : null;

/* Set plugin manager in router to support making routes dependent on plugins (optional) */
$di->setters[Router::class]['setPluginManager'] = $di->lazyGet('pluginManager');

return $di;