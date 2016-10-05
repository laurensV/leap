<?php
/**
 * Leap - Lightweight Extensible Adjustable PHP Framework
 *
 * @package  Leap
 * @author   Laurens Verspeek
 *
 * The index page that serves all page requests on a Leap installation.
 *
 * All Leap code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt files in the "core" directory.
 */

/* TODO: implement unit testing with PHPUnit */
/* TODO: error handling */
/* TODO: phpdoc */
/* TODO: composer: repo maken voor plugins */
/* TODO: composer: eigen custom installer maken */
/* TODO: move file directory */
/* TODO: move autoloader to core folder */
/* TODO: namespace function for plugins */
/* TODO: find out differences between hooks and events and pick one */

/* include the autoloader from Composer */
$autoloader = require 'libraries/autoload.php';

/* include the configuration handler. 
 * Configurations can be filled in in the file `config.ini` or `config.local.ini` */
require 'core/config.php';

/* include helper functions */
require 'core/include/helpers.php';

/* start the Leap application (core/leapp.php) */
$app = new Leap\Core\LeApp();
