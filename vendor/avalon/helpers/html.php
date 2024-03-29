<?php
/*!
 * Avalon
 * Copyright (C) 2011-2013 Jack Polgar
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

/**
 * HTML Helper
 *
 * @author Jack P.
 * @package Avalon
 * @subpackage Helpers
 */
class HTML
{
    /**
     * Returns the code to include a CSS file.
     *
     * @param string $file The path to the CSS file.
     *
     * @return string
     */
    public static function css_link($path, $media = 'screen')
    {
        $options = static::build_attributes(['rel' => 'stylesheet', 'href' => $path, 'media' => $media]);
        return "<link $options>\n";
    }

    /**
     * Returns the code to include a JavaScript file.
     *
     * @param string $file The path to the JavaScript file.
     *
     * @return string
     */
    public static function js_inc($path)
    {
        $options = static::build_attributes(['src' => $path]);
        return "<script $options></script>\n";
    }

    /**
     * Returns the code to include a JavaScript file.
     *
     * @param string $file The path to the JavaScript file.
     *
     * @return string
     */
    public static function feed_link($path)
    {
        $options = static::build_attributes(['rel' => 'alternate', 'href' => $path, 'type' => 'application/atom+xml']);
        return "<link $options>\n";
    }

    /**
     * Returns the code for a link.
     *
     * @param string $label The label.
     * @param string $url The URL.
     * @param array $attributes Options for the URL code (class, title, etc).
     * @param bool $prepend_base Add the site's base URL to $url
     *
     * @return string
     */
    public static function link($label, $url = null, array $attributes = [], $prepend_base = true)
    {
        $url = $url === null ? $label : $url;
        $label = htmlentities($label);
        $attributes['href'] = $prepend_base ? Request::base(ltrim($url, '/')) : $url;
        $options = static::build_attributes($attributes);

        return "<a {$options}>{$label}</a>";
    }

    /**
     * Builds the attributes for HTML elements.
     *
     * @param array $attributes An array of attributes and their values.
     *
     * @return string
     */
    public static function build_attributes($attributes)
    {
        $options = [];
        foreach ($attributes as $attr => $val) {
            if (in_array($attr, ['id', 'checked', 'disabled']) and $val === false) {
                continue;
            }
            $options[] = $attr.'="'.htmlentities($val).'"';
        }
        return implode(' ', $options);
    }
}
