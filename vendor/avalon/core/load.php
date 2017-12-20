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

namespace avalon\core;

/**
 * Avalon's loader class.
 *
 * @author Jack P.
 * @package Avalon
 * @subpackage Core
 */
class Load
{
    private static $undo = array('my_sql' => 'mysql', 'java_script' => 'javascript');
    private static $libs = array();
    private static $helpers = array();
    private static $search_paths = array();

    /**
     * Loads the specified configuration file.
     *
     * @param string $file
     *
     * @return string
     */
    public static function config($file)
    {
        $file = basename(strtolower($file), '.php');
        $paths = array(APPPATH, SYSPATH);

        foreach ($paths as $dir) {
            if (file_exists("$dir/config/$file.php")) {
                return require "$dir/config/$file.php";
            }
        }

        Error::halt("Loader Error", "Unable to load config '{$file}'");
        return false;
    }

    /**
     * Loads the specified configuration file.
     *
     * @param string $file
     *
     * @return string
     */
    public static function find($name, $paths = array(), $default_subdir = '')
    {
        $default_paths = array_merge(array(APPPATH, SYSPATH), self::$search_paths);
        $name = self::lowercase($name);
        $files = array();

        foreach ($paths as $path) {
            if ($file = glob("$path/$name", GLOB_BRACE)) {
                $files = array_merge($files, $file);
            }
        }

        foreach ($default_paths as $path) {
            if ($file = glob("$path/$default_subdir/$name", GLOB_BRACE)) {
                $files = array_merge($files, $file);
            }
        }

        return array_map('realpath', $files);
    }

    /**
     * Library loader.
     *
     * @param string $class The class name
     * @param boolean $init Initialize the class or not
     *
     * @return object
     */
    public static function lib($class, $init = true)
    {
        // If it already loaded?
        if (isset(static::$libs[$class])) {
            return static::$libs[$class];
        }

        // Set the class and file name
        $class_name = ucfirst($class);
        $file_name = static::lowercase($class);

        // App library
        if (file_exists(APPPATH . '/libs/' . $file_name . '.php')) {
            require_once APPPATH . '/libs/' . $file_name . '.php';
        }
        // Avalon library
        elseif (file_exists(SYSPATH . '/libs/' . $file_name . '.php')) {
            require_once SYSPATH . '/libs/' . $file_name . '.php';
        }
        // Not found
        else {
            Error::halt("Loader Error", "Unable to load library '{$class}'");
            return false;
        }

        // Initiate the class?
        if ($init) {
            static::$libs[$class] = new $class_name();
        }
        // No, just load it
        else {
            static::$libs[$class] = $class_name;
        }

        return static::$libs[$class];
    }

    /**
     * Helper loader.
     *
     * @param mixed $helper
     *
     * @return bool
     */
    public static function helper()
    {
        // In case we're loading multiple helpers
        $class = func_num_args() > 1 ? func_get_args() : func_get_arg(0);

        // Multiple helpers
        if (is_array($class)) {
            foreach ($class as $helper) {
                static::helper($helper);
            }
            return;
        }

        // Is it already loaded?
        if (in_array($class, static::$helpers)) {
            return true;
        }

        // Lowercase the file name
        $file_name = static::lowercase($class);

        // App helper
        if (file_exists(APPPATH . '/helpers/' . $file_name . '.php')) {
            require_once APPPATH . '/helpers/' . $file_name . '.php';
        }
        // Avalon helper
        elseif (file_exists(SYSPATH . '/helpers/' . $file_name . '.php')) {
            require_once SYSPATH . '/helpers/' . $file_name . '.php';
        }
        // Not found
        else {
            Error::halt("Loader Error", "Unable to load helper '{$class}'");
            return false;
        }

        static::$helpers[] = $class;
        return true;
    }

    /**
     * Adds a path to be searched when loading controllers and views.
     *
     * @param string $path
     */
    public static function register_path($path)
    {
        static::$search_paths[] = $path;
    }

    /**
     * Lower cases the specified string.
     */
    private static function lowercase($string) {
        $string = strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_' . '\\1', $string));
        return strtr($string, static::$undo);
    }
}
