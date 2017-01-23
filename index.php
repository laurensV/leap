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
| Setup the Leap application
|--------------------------------------------------------------------------
|
| (optional) Wrapper for the Leap Framework that executes the following:
|   1.  include helpers (constants and functions)
|   2.  specify config (file)
|   3.  setup dependencies (in DIC)
|   4.  resolve kernel from DIC
|   5.  run kernel
*/
$app = new Leap\Core\Application();

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