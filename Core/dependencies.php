<?php
namespace Leap\Core;

/* Create and config Dependency Injector Container */
use Aura\Di\ContainerBuilder;

$builder = new ContainerBuilder();
$di      = $builder->newInstance();

/**
 * @var Config $config
 */
$config = $config ?? null;

/*****************************
 *         Database          *
 *****************************/
$db_conf = $config->database;
if ($db_conf['type'] === 'mysql') {
    if (!isset($db_conf['db_host']) || !isset($db_conf['db_user']) || !isset($db_conf['db_pass']) || !isset($db_conf['db_name'])) {
        // TODO: error handling
        die('not enough database info');
    }
    $di->params[PdoPlus::class]['host']     = $db_conf['host'];
    $di->params[PdoPlus::class]['username'] = $db_conf['username'];
    $di->params[PdoPlus::class]['password'] = $db_conf['password'];
    $di->params[PdoPlus::class]['database'] = $db_conf['database'];

    /* Create PdoPlus object with pdo connection inside */
    $di->set('pdo', $di->lazyNew(PdoPlus::class));
}

/*****************************
 *       Hook System         *
 *****************************/
$di->set('hooks', $di->lazyNew(Hooks::class));

/*****************************
 *          Router           *
 *****************************/
$di->set('router', $di->lazyNew(Router::class));
/* Set plugin manager in router to support making routes dependent on plugins (optional) */
$di->setters[Router::class]['setPluginManager'] = $di->lazyGet('pluginManager');

/*****************************
 *       Plugin Manager      *
 *****************************/
$di->set('pluginManager', $di->lazyNew(PluginManager::class));
$di->params[PluginManager::class]['pdo'] = $di->has('pdo') ? $di->lazyGet('pdo') : null;

/*****************************
 *   Controller (Factory)    *
 *****************************/
$di->set('controllerFactory', $di->lazyNew(ControllerFactory::class));
/* Normally not so oke to inject DI Container into a class,
 * because it can be misused as a service locator. However, for this
 * purpose it is OK, as this is a special case, because the Controller
 * class can be anything and the DIC is only used to resolve the Controller,
 * not to retrieve other services.
 */
$di->params[ControllerFactory::class]['di']      = $di;
$di->params[Controller::class]['hooks']          = $di->lazyGet('hooks');
$di->params[Controller::class]['plugin_manager'] = $di->lazyGet('pluginManager');
$di->params[Controller::class]['config']         = $config;
$di->params[Controller::class]['pdo']            = $di->has('pdo') ? $di->lazyGet('pdo') : null;

/*****************************
 *       Leap Kernel         *
 *****************************/
$di->set('kernel', $di->lazyNew(Kernel::class));
$di->params[Kernel::class]['hooks']             = $di->lazyGet('hooks');
$di->params[Kernel::class]['pluginManager']     = $di->lazyGet('pluginManager');
$di->params[Kernel::class]['router']            = $di->lazyGet('router');
$di->params[Kernel::class]['controllerFactory'] = $di->lazyGet('controllerFactory');
$di->params[Kernel::class]['config']            = $config;

return $di;