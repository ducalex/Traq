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
    private static $request_uri;
    private static $uri;
    private static $base;
    private static $base_full;
    private static $segments = array();
    private static $method;
    private static $requested_with;
    public static $query;
    public static $headers = array();
    public static $cookies = array();
    public static $post = array();
    public static $get = array();
    public static $scheme;
    public static $host;

    /**
     * Initialize the class to get request
     * information statically.
     */
    public static function init()
    {
        return new static;
    }

    public function __construct()
    {
        // Set query string
        static::$query = (isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : null);

        // Set request scheme
        static::$scheme = static::isSecure() ? 'https' : 'http';

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
        static::$method = strtolower($_SERVER['REQUEST_METHOD']);

        // Requested with
        static::$requested_with = @$_SERVER['HTTP_X_REQUESTED_WITH'];

        // _GET
        static::$get = $_GET;

        // _POST
        static::$post = $_POST;

        // _COOKIE
        static::$cookies = $_COOKIE;

        foreach($_SERVER as $key => $value) {
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
        return strtolower(static::$requested_with) == 'xmlhttprequest';
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

    /**
     * Determines if the request is secure.
     *
     * @return boolean
     */
    public static function isSecure()
    {
        if (empty($_SERVER['HTTPS'])) {
            return false;
        }

        return $_SERVER['HTTPS'] == 'on' or $_SERVER['HTTPS'] == 1;
    }

    private function baseUrl()
    {
        $filename = basename($_SERVER['SCRIPT_FILENAME']);

        if (basename($_SERVER['SCRIPT_NAME']) === $filename) {
            $baseUrl = $_SERVER['SCRIPT_NAME'];
        } elseif (basename($_SERVER['PHP_SELF']) === $filename) {
            $baseUrl = $_SERVER['PHP_SELF'];
        } elseif (basename($_SERVER['ORIG_SCRIPT_NAME']) === $filename) {
            $baseUrl = $_SERVER['ORIG_SCRIPT_NAME'];
        }

        $baseUrl = dirname($baseUrl);

        return $baseUrl;
    }

    private function requestPath()
    {
        $requestPath = '';

        if (isset($_SERVER['HTTP_X_ORIGINAL_URL'])) {
            $requestPath = $_SERVER['HTTP_X_ORIGINAL_URL'];
        } elseif (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
            $requestPath = $_SERVER['HTTP_X_REWRITE_URL'];
        } elseif (isset($_SERVER['IIS_WasUrlRewritten'])
                  and $_SERVER['IIS_WasUrlRewritten'] = 1
                  and isset($_SERVER['UNENCODED_URL'])
                  and $_SERVER['UNENCODED_URL'] != '')
        {
            $requestPath = $_SERVER['UNENCODED_URL'];
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $requestPath = $_SERVER['REQUEST_URI'];

            $schemeAndHost = static::$scheme . '://' . static::$host;
            if (strpos($requestPath, $schemeAndHost) === 0) {
                $requestPath = substr($requestPath, strlen($schemeAndHost));
            }
        } elseif (isset($_SERVER['ORIG_PATH_INFO'])) {
            $requestPath = $_SERVER['ORIG_PATH_INFO'];
        }

        return $requestPath;
    }
}
