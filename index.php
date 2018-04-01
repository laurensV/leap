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
$autoloader = require 'vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Create the Leap Application
|--------------------------------------------------------------------------
|
| Wrapper for the Leap Framework. It can take two arguments:
| 1. Configuration (optional) - Can be an array, filename or a Config Object
|    that implements ConfigInterface
| 2. Container (optional) - Custom PSR-11 Container. If you use this you
|    you have to register default services yourself
*/
$app = new Leap\Application(['routes' => ['app.routes.php']]);

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| One small step for man, one giant leap for mankind!
*/
$app->run();

/* TODO: implement unit testing with PHPUnit */
/* TODO: error handling */
/* TODO: phpdoc */
/* TODO: composer: repo maken voor plugins */
/* TODO: composer: eigen custom installer maken */
/* TODO: move file directory */
/* TODO: namespace function for plugins */
/* TODO: find out differences between hooks and events and pick one */