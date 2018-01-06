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
    private static $undo = ['my_sql' => 'mysql', 'java_script' => 'javascript'];
    private static $libs = [];
    private static $helpers = [];
    private static $search_paths = [];

    /**
     * Loads the specified configuration file.
     *
     * @param string $file
     *
     * @return string
     */
    public static function config($file)
    {
        if ($file = self::find($file.'.php', 'config', [], false)) {
            return require $file[0];
        }
        //Error::halt("Loader Error", "Unable to load config '{$file}'");
        return false;
    }

    /**
     * Find the specified file in standard locations plus added search paths.
     *
     * @param string $name File name we're looking for. It can be a glob pattern
     * @param string $subdir string to append to global search_paths. views/config/libs/helpers...
     * @param array $extra_path paths to look in first. $subdir has no effect on them
     * @param boolean $use_search_paths Also search paths registered through register_path(), usually by plugins
     *
     * @return array
     */
    public static function find($name, $subdir = '', array $extra_paths = [], $use_search_paths = true)
    {
        $paths = [APPPATH, SYSPATH];
        $name = self::lowercase($name);
        $files = [];

        if ($use_search_paths) {
            $paths = array_merge($paths, self::$search_paths);
        }

        foreach ($extra_paths as $path) {
            if ($file = glob("$path/$name", GLOB_BRACE)) {
                $files = array_merge($files, $file);
            }
        }

        foreach ($paths as $path) {
            if ($file = glob("$path/$subdir/$name", GLOB_BRACE)) {
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
     * @param array $args Values to pass to the lib constructor if $init
     *
     * @return object|string
     */
    public static function lib($class, $init = true, $args = [])
    {
        // If it already loaded?
        if (isset(static::$libs[$class])) {
            return static::$libs[$class];
        }

        // Set the class and file name
        $class_name = ucfirst($class);
        
        // Let's find it!
        if ($file = static::find($class.'.php', 'libs')) {
            require_once $file[0];
            static::$libs[$class] = $init ? new $class_name($args) : $class_name;
            return static::$libs[$class];
        }

        Error::halt("Loader Error", "Unable to load library '{$class}'");
        return false;
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
            return array_map('static::helper', $class);
        }

        // Is it already loaded?
        if (in_array($class, static::$helpers)) {
            return true;
        }

        // Let's find it!
        if ($file = static::find($class.'.php', 'helpers')) {
            require_once $file[0];
            static::$helpers[] = $class;
            return true;
        }

        Error::halt("Loader Error", "Unable to load helper '{$class}'");
        return false;
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
