<?php
/*!
 * Avalon
 * Copyright (C) 2011-2012 Jack Polgar
 *
 * This file is part of Avalon.
 *
 * Avalon is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation; version 3 only.
 *
 * Avalon is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with Avalon. If not, see <http://www.gnu.org/licenses/>.
 */

namespace avalon\output;

use avalon\core\Load;
use avalon\core\Error;

/**
 * View class.
 *
 * @author Jack P.
 * @package Avalon
 */
class View
{
    public static $theme;
    public static $inherit_from;
    private static $vars = array();

    /**
     * Renders the specified file.
     *
     * @param string $file
     * @param array $vars Variables to be passed to the view.
     */
    public static function render($file, array $vars = array())
    {
        // Get the file name/path
        $_file = static::find($file);

        // Check if the theme has this view
        if (!$_file) {
            Error::halt("View Error", "Unable to load view '{$file}'", 'HALT');
        }

        extract(self::$vars);
        extract($vars);

        // Load up the view and get the contents
        ob_start();
        include $_file;
        return ob_get_clean();
    }

    /**
     * Renders and returns the specified file.
     *
     * @deprecated Deprecated since 0.6
     */
    public static function get($file, array $vars = array())
    {
        return static::render($file, $vars);
    }

    /**
     * Scan the search paths to find the view file
     *
     * @param string $var The view name.
     * @param mixed $val The file path or false on failure
     */
    public static function find($name)
    {
        // Add the theme and inherit path
        $dirs = array_filter(array(APPPATH . '/views/' . static::$theme . '/', static::$inherit_from));
        $view = Load::find("$name.{phtml,php}", $dirs, 'views');

        return $view ? $view[0] : false;
    }

    /**
     * Sends the variable to the view.
     *
     * @param string $var The variable name.
     * @param mixed $val The variables value.
     */
    public static function set($var, $val = null)
    {
        // Mass set
        if (is_array($var)) {
            self::$vars = $var + self::$vars;
        } else {
            self::$vars[$var] = $val;
        }
    }

    /**
     * Returns the variables array.
     *
     * @return array
     */
    public static function vars()
    {
        return self::$vars;
    }
}
