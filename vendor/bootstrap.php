<?php
/*!
 * Traq
 * Copyright (C) 2009-2013 Traq.io
 *
 * This file is part of Traq.
 *
 * Traq is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 3 only.
 *
 * Traq is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Traq. If not, see <http://www.gnu.org/licenses/>.
 */

// Define the paths needed
define("SYSPATH", __DIR__ . '/avalon');
define("APPPATH", __DIR__ . '/traq');
define("DOCROOT", dirname(__DIR__));

// Load the framework
require SYSPATH . '/base.php';
use avalon\Database;
use avalon\Autoloader;
use avalon\core\Load;
use avalon\core\Error;
use traq\models\Plugin;
use traq\libraries\Locale;

// Setup the autoloader and global vendor directory
Autoloader::registerNamespace('\\', __DIR__);
Autoloader::register();

// Alias classes so we dont need to
// have "use ...." in all files.
Autoloader::aliasClasses(array(
    'avalon\core\Kernel' => 'Avalon',
    'avalon\http\Router' => 'Router',
    'avalon\output\View' => 'View',
    'avalon\http\Request' => 'Request',
    'avalon\http\Session' => 'Session',
    'avalon\http\Cookie'  => 'Cookie',

    // Helpers
    'avalon\helpers\Time' => 'Time',

    // Traq helpers
    'traq\helpers\API' => 'API',
));

// Register the exception handler
Error::register();

// Fetch the routes
require_once APPPATH . '/config/routes.php';

// Load common functions and version file
require_once APPPATH . '/common.php';
require_once APPPATH . '/version.php';

// Check for the database config file
if (!file_exists(APPPATH . '/config/database.php')) {
    Request::fromGlobals()->redirectTo('install');
}

// Include config and connect
require APPPATH . '/config/database.php';
Database::factory($db, 'main');

// Load the plugins
foreach(Plugin::select('file')->where('enabled', '1')->fetch_all() as $plugin) {
    // Add plugin file path to our loaders
    Autoloader::registerNamespace('traq\plugins', APPPATH . '/plugins/' . $plugin->file);
    Load::register_path(APPPATH . '/plugins/' . $plugin->file);

    if ($plugin->is_valid()) {
        ($plugin->get_class())::init();
    }
}
unset($plugin);
