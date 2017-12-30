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

namespace avalon;

/**
 * Avalon's Autoloader.
 *
 * @since 0.2
 * @package Avalon
 * @subpackage Core
 * @author Jack P.
 * @copyright (C) Jack P.
 */
class Autoloader
{
    private static $aliases = [];
    private static $prefixes = [];

    /**
     * Registers the class as the autoloader.
     */
    public static function register()
    {
        spl_autoload_register('avalon\Autoloader::load', true, true);
    }

    /**
     * Alias multiple classes at once.
     *
     * @param array $classes
     */
    public static function aliasClasses($classes)
    {
        foreach($classes as $original => $alias) {
            static::aliasClass($original, $alias);
        }
    }

    /**
     * Alias a class from a complete namespace to just it's name.
     *
     * @param string $original
     * @param string $alias
     */
    public static function aliasClass($original, $alias)
    {
        static::$aliases[$alias] = ltrim($original, '\\');
    }

    /**
     * Register multiple namespaces at once.
     *
     * @param array $namespaces
     */
    public static function registerNamespaces(array $namespaces)
    {
        foreach($namespaces as $vendor => $location) {
            static::registerNamespace($vendor, $location);
        }
    }

    /**
     * Registers a namespace location.
     *
     * @param string $vendor
     * @param string $location
     * @param bool $overwrite
     */
    public static function registerNamespace($vendor, $location, $overwrite = false)
    {
        $vendor = trim($vendor, '\\');

        if (!isset(static::$prefixes[$vendor]) || $overwrite) {
            static::$prefixes[$vendor] = [];
        }

        static::$prefixes[$vendor][] = $location;
    }

    /**
     * Sets the vendor location.
     *
     * @param string $location
     */
    public static function vendorLocation($location)
    {
        static::registerNamespace('', $location, true);
    }

    /**
     * Loads a class
     *
     * @param string $class The class
     *
     * @return bool
     */
    public static function load($class)
    {
        $class = $prefix = ltrim($class, '\\');

        // Aliased classes
        if (isset(static::$aliases[$class])) {
            return class_alias(static::$aliases[$class], $class); // This will recurse the autoloader if needed
        }

        do {
            $prefix = substr($class, 0, $pos = strrpos($prefix, '\\'));
            $filename = strtolower(preg_replace(['#[_\\\\]#', '/(?<=[a-z])([A-Z])/'], ['/', '_\\1'], '/'.substr($class, $pos).'.php'));
            if (isset(static::$prefixes[$prefix])) {
                foreach(static::$prefixes[$prefix] as $path) {
                    if (file_exists($path.$filename)) {
                        return require $path.$filename;
                    }
                }
            }
        } while($prefix);
    }
}
