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
$di = require 'core/dependencies.php';
$kernel = $di->get('kernel');

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
/* TODO: namespace function for plugins */
/* TODO: find out differences between hooks and events and pick one */