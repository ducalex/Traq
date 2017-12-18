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

namespace avalon\http;

/**
 * Avalon's HTTP request class.
 *
 * @since 0.1
 * @package Avalon
 * @subpackage HTTP
 * @author Jack P.
 * @copyright (C) Jack P.
 */
class Request
{
    private static $server;
    private static $request_uri;
    private static $uri;
    private static $base;
    private static $base_full;
    private static $segments;
    private static $method;
    private static $query;
    private static $files;
    private static $headers;
    private static $cookies;
    private static $post;
    private static $get;
    private static $scheme;
    private static $host;
    private static $remote_addr;
    
    /**
     * Initialize the class to get request
     * information statically.
     */
    public static function fromGlobals()
    {
        return new static($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
    }

    public function __construct(array $server, array $get = [], array $post = [], array $cookies = [], array $files = [])
    {
        static::$server = $server;
        static::$get = $get;
        static::$post = $post;
        static::$cookies = $cookies;
        static::$files = $files;

        // Set query string
        static::$query = (isset($server['QUERY_STRING']) ? $server['QUERY_STRING'] : null);

        // Set request scheme
        static::$scheme = (empty($server['HTTPS']) || $server['HTTPS'] == 'off') ? 'http' : 'https';

        // Set host
        static::$host = strtolower(preg_replace('/:\d+$/', '', trim($_SERVER['SERVER_NAME'])));

        // Set base url
        static::$base = static::baseUrl();
        static::$base_full = static::$scheme . '://' . static::$host . static::$base;

        // Set the request path
        static::$request_uri = static::requestPath();

        // Set relative uri without query string
        static::$uri = preg_replace(array('#^'.preg_quote(static::$base).'#', '/\?.*$/'), '', static::$request_uri);

        // Request segments
        static::$segments = explode('/', trim(static::$uri, '/'));

        // Set the request method
        static::$method = strtolower($server['REQUEST_METHOD']);

        // Set the request method
        static::$remote_addr = $server['REMOTE_ADDR'];

        foreach ($server as $key => $value) {
            if (substr($key, 0, 5) === 'HTTP_') {
                static::$headers[substr($key, 5)] = $value;
            }
        }
    }

    /**
     * Returns the relative requested URI.
     *
     * @return string
     */
    public function getUri()
    {
        return static::$uri;
    }

    /**
     * Static method for returning relative the URI.
     *
     * @return string
     */
    public static function uri()
    {
        return static::$uri;
    }

    /**
     * Returns the request method if nothing
     * is passed, otherwise returns true/false
     * if the passed string matches the method.
     *
     * @param string $matches
     *
     * @return string
     */
    public static function method($matches = false)
    {
        // Return the request method
        if (!$matches) {
            return static::$method;
        }
        // Match the request method
        else {
            return static::$method == $matches;
        }
    }

    /**
     * Returns the full requested URI.
     *
     * @return string
     */
    public static function requestUri()
    {
        return static::$request_uri;
    }

    /**
     * Returns the server host
     *
     * @return string
     */
    public static function host()
    {
        return static::$host;
    }

    /**
     * Returns the client IP
     *
     * @return string
     */
    public static function remoteIP()
    {
        return static::$remote_addr;
    }

    /**
     *
     * Returns a parsed authorization header containing each of "scheme", "username", "password",
     * "digest" elements if applicable. $key can be specified if you want only one element
     *
     * @return mixed
     */
    public static function auth($key = null)
    {
        if (preg_match('/^(?<scheme>Basic|Digest)\s+(?<data>.+)$/i', static::header('AUTHORIZATION'), $data)) {
            $data['scheme'] = strtolower($data['scheme']);

            if ($data['scheme'] === 'basic') {
                list($data['username'], $data['password']) = @explode(':', base64_decode($data['data']), 2);
            }

            if ($key === null) {
                unset($data[0], $data[1], $data[2]);
                return $data;
            }

            return isset($data[$key]) ? $data[$key] : false;
        }
        return false;
    }

    /**
     *
     * @param string $key     Header name
     * @param mixed  $not_set Value to return if not set
     *
     * @return mixed
     */
    public static function header($key = null, $not_set = null)
    {
        if ($key === null) {
            return static::$headers;
        }

        $key = strtoupper($key);

        return isset(static::$headers[$key]) ? static::$headers[$key] : $not_set;
    }

    /**
     * Returns the value of the key from the FILES array.
     * If no key is given, return all files.
     *
     * @param string $key     File field
     *
     * @return mixed
     */
    public static function files($key = null)
    {
        if ($key === null) {
            return static::$files;
        }

        return isset(static::$files[$key]['name']) ? static::$files[$key] : null;
    }

    /**
     * Returns the value of the key from the COOKIE array,
     * if it's not set, returns null by default.
     * If no key is given, return all cookies.
     *
     * @param string $key     Cookie name
     * @param mixed  $not_set Value to return if not set
     *
     * @return mixed
     */
    public static function cookie($key = null, $not_set = null)
    {
        if ($key === null) {
            return static::$cookies;
        }

        return isset(static::$cookies[$key]) ? static::$cookies[$key] : $not_set;
    }

    /**
     * Returns the value of the key from the POST array,
     * if it's not set, returns null by default.
     * If no key is given, return the full array.
     *
     * @param string $key     Key to get from POST array
     * @param mixed  $not_set Value to return if not set
     *
     * @return mixed
     */
    public static function post($key = null, $not_set = null)
    {
        if ($key === null) {
            return static::$post;
        }

        return isset(static::$post[$key]) ? static::$post[$key] : $not_set;
    }

    /**
     * Returns the value of the key from the GET+POST array,
     * if it's not set, returns null by default.
     * If no key is given, return the full array.
     *
     * This is replacing $_REQUEST because variable_order can be problematic
     *
     * @param string $key     Key to get from GET+POST array
     * @param mixed  $not_set Value to return if not set
     *
     * @return mixed
     */
    public static function req($key = null, $not_set = null)
    {
        return static::get($key, static::post($key, $not_set));
    }

    /**
     * Returns the value of the key from the GET array,
     * if it's not set, returns null by default.
     * If no key is given, return the full array.
     *
     * @param string $key     Key to get from GET array
     * @param mixed  $not_set Value to return if not set
     *
     * @return mixed
     */
    public static function get($key = null, $not_set = null)
    {
        if ($key === null) {
            return static::$get;
        }

        return isset(static::$get[$key]) ? static::$get[$key] : $not_set;
    }

    /**
     * Gets the URI segment.
     *
     * @param integer $segment Segment index
     *
     * @return mixed
     */
    public static function seg($segment)
    {
        return (isset(static::$segments[$segment]) ? static::$segments[$segment] : false);
    }

    /**
     * Redirects to the specified URL.
     *
     * @param string $url
     */
    public static function redirect($url)
    {
        header("Location: " . $url);
        exit;
    }

    /**
     * Redirects to the specified path relative to the
     * entry file.
     *
     * @param string $path
     */
    public static function redirectTo($path = '')
    {
        static::redirect(static::base($path));
    }

    /**
     * Checks if the request was made via Ajax.
     *
     * @return bool
     */
    public static function isAjax()
    {
        return strtolower(static::header('X_REQUESTED_WITH')) == 'xmlhttprequest';
    }

    /**
     * Determines if the request is secure.
     *
     * @return boolean
     */
    public static function isSecure()
    {
        return static::$scheme === 'https';
    }

    /**
     * Gets the base URL
     *
     * @return string
     */
    public static function base($path = '', $full = false)
    {
        return ($full ? static::$base_full : static::$base) . '/' . trim($path, '/');
    }

    /**
     * Builds a full Traq URL to be used in links
     *
     * @param string $path    If path is empty then the current uri will be used
     * @param array $query
     *
     * @return string
     */
    public static function url($path = null, array $query = [], $full = false)
    {
        $url = static::base($path ?: static::$uri, $full);

        if ($query && $query = http_build_query($query)) {
            $url .= '?' . $query;
        }

        return $url;
    }


    private function baseUrl()
    {
        $filename = basename(static::$server['SCRIPT_FILENAME']);
        $try = array('SCRIPT_NAME', 'PHP_SELF', 'ORIG_SCRIPT_NAME');

        foreach ($try as $key) {
            if (basename(static::$server[$key]) === $filename) {
                return dirname(static::$server[$key]);
            }
        }

        return false;
    }

    private function requestPath()
    {
        $try = array('HTTP_X_ORIGINAL_URL', 'HTTP_X_REWRITE_URL', 'UNENCODED_URL', 'REQUEST_URI', 'ORIG_PATH_INFO');

        foreach ($try as $key) {
            if (!empty(static::$server[$key])) {
                return preg_replace('#^'.preg_quote(static::$scheme.'://'.static::$host).'#', '', static::$server[$key]);
            }
        }
    }
}
