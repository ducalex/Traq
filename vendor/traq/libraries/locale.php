<?php
/*!
 * Traq
 * Copyright (C) 2009-2012 Traq.io
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

namespace traq\libraries;

use \avalon\core\Load;
use \avalon\helpers\Time;

Load::helper('array');

/**
 * Traq Localization System
 * Copyright (C) Jack Polgar
 *
 * @author Jack P.
 * @copyright (C) Jack P.
 * @package Traq
 * @subpackage Locale
 */
class Locale
{
    protected static $info = array();
    protected $locale = array();

    public static $locales = array();

    /**
     * Constructor!
     */
    public function __construct()
    {
        // If localization strings are stored in
        // the Locale_x->locale() method, push them
        // to the Locale_x->$locale array.
        if (method_exists($this, 'locale')) {
            $this->locale = $this->locale();
        }

        if (!empty(static::$info['system'])) {
            setlocale(LC_TIME, static::$info['system'].'.UTF-8', static::$info['system'].'.utf8', static::$info['system']);
        }
    }

    /**
     * Loads the specified locale.
     *
     * @param string $locale
     *
     * @return object
     */
    public static function load($locale)
    {
        if (!empty(self::$locales[$locale])) {
            return self::$locales[$locale];
        }

        $class = "\\traq\locale\\{$locale}";
        // Check if the file exists..
        if (class_exists($class)) {
            $localization = new $class();

            if ($files = Load::find("locale/$locale.php")) {
                $files = array_diff($files, array(APPPATH."/locale/$locale.php"));
                foreach($files as $locale_file) {
                    $localization->add(include $locale_file);
                }
            }

            self::$locales[$locale] = $localization;

            return $localization;
        }

        return false;
    }

    /**
     * Returns the locale information.
     *
     * @return array
     */
    public static function info()
    {
        return static::$info;
    }

    /**
     * Translates the specified string.
     *
     * @return string
     */
    public function translate($string, $vars = array())
    {
        $string = func_get_arg(0);

        if (!is_array($vars)) {
            $vars = array_slice(func_get_args(), 1);
        }

        return $this->_compile_string($this->get_string($string), $vars);
    }

    /**
     * Date localization method
     */
    public function date($format, $timestamp = null)
    {
        $defaults = array('date.short' => '%x', 'date.long' => '%a, %d %B %Y', 'date.full' => '%c', 'time' => '%X');

        $locale_format = $this->get_strings("date_format.$format") ?: (isset($defaults[$format]) ? $defaults[$format] : $format);

        return strftime($locale_format, Time::to_unix($timestamp));
    }

    /**
     * Fetches the translation for the specified string.
     *
     * @param string $string
     *
     * @return string
     */
    public function get_string($string)
    {
        $strings = $this->get_strings($string);

        if (is_array($strings) && isset($strings[0])) {
            return $strings[0];
        } elseif(is_string($strings)) {
            return $strings;
        }

        return $string;
    }

    /**
     * Return all strings for a given key
     *
     * @param string $string
     *
     * @return array|string
     */
    public function get_strings($string)
    {
        $locale = &$this->locale;
        $indexes = explode('.', $string);

        // Exact match?
        if (isset($locale[$string])) {
            return $locale[$string];
        }

        // Loop over the indexes and find the string
        foreach($indexes as $index) {
            if (isset($locale[$index])) {
                $locale = &$locale[$index];
            } else {
                return false;
            }
        }

        return $locale;
    }

    /**
     * Determines which replacement to use for plurals.
     *
     * @param integer $numeral
     *
     * @return integer
     */
    public function calculate_numeral($numeral)
    {
        return ($numeral > 1 or $numeral < -1 or $numeral == 0) ? 1 : 0;
    }

    /**
     * Adds extra locale strings
     * If collisions occur, the new string will overwrite the old one.
     *
     * @param array $vars
     */
    public function add($vars)
    {
        $this->locale = array_merge_recursive2($this->locale, $vars);
    }

    /**
     * Compiles the translated string with the variables.
     *
     * @example
     *     _compile_string('{plural:$1, {$1 post|$1 posts}}', array(1));
     *     will become "1 post"
     *
     * @param string $string
     * @param array $vars
     *
     * @return string
     */
    protected function _compile_string($string, $vars)
    {
        $translation = $string;

        // If $vars isn't an array, get everything after
        // the string and use whatever we get.
        if (!is_array($vars)) {
            $vars = array_slice(func_get_args(), 1);
        }

        // Loop through and replace the placeholders
        // with the values from the $vars array.
        $count = 0;
        foreach ($vars as $key => $val) {
            $count++;

            // If array key is an integer,
            // use the counter to avoid clashes
            // with numbered placeholders.
            if (is_integer($key)) {
                $key = $count;
            }

            // Replace placeholder with value
            $translation = str_replace(array("{{$key}}"), $val, $translation);
        }

        // Match plural:n,{x, y}
        if (preg_match_all("/{plural:(?<value>-{0,1}\d+)(,|, ){(?<replacements>.*?)}}/i", $translation, $matches)) {
            foreach($matches[0] as $id => $match) {
                // Split the replacements into an array.
                // There's an extra | at the start to allow for better matching
                // with values.
                $replacements = explode('|', $matches['replacements'][$id]);

                // Get the value
                $value = $matches['value'][$id];

                // Check what replacement to use...
                $replacement_id = $this->calculate_numeral($value);
                if ($replacement_id !== false) {
                    $translation = str_replace($match, $replacements[$replacement_id], $translation);
                }
                // Get the last value then
                else {
                    $translation = str_replace($match, end($replacements), $translation);
                }
            }
        }

        // We're done here.
        return $translation;
    }
}
