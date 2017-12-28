<?php
/*!
 * Avalon
 * Copyright (C) 2011-2014 Jack Polgar
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

use avalon\http\Router;
use avalon\http\Request;
use avalon\http\Session;
use avalon\output\Body;
use avalon\output\View;

/**
 * Avalon's Kernel.
 *
 * @since 0.1
 * @package Avalon
 * @subpackage Core
 * @author Jack P.
 * @copyright (C) Jack P.
 */
class Kernel
{
    private static $version = '0.7';
    private static $app;
    private static $request;

    /**
     * Initializes the the kernel and routes the request.
     */
    public static function init()
    {
        static::$request = Request::fromGlobals();

        Session::start(APPNAME, 0, Request::base());

        // Route the request
        Router::route(static::$request);

        // Check if the routed controller and method exists
        if (!is_callable(array(Router::$controller, 'action_' . Router::$method))) {
            Router::set404();
        }
    }

    /**
     * Executes the routed request.
     */
    public static function run()
    {
        // Start the app
        static::$app = new Router::$controller;

        // Before filters
        $filters = array_merge(
            isset(static::$app->before['*']) ? static::$app->before['*'] : array(),
            isset(static::$app->before[Router::$method]) ? static::$app->before[Router::$method] : array()
        );
        foreach ($filters as $filter) {
            static::$app->{$filter}(Router::$method);
        }

        // Call the method
        if (static::$app->render['action']) {
            $output = call_user_func_array(array(static::$app, 'action_' . Router::$method), Router::$vars);
        }

        // After filters
        $filters = array_merge(
            isset(static::$app->after['*']) ? static::$app->after['*'] : array(),
            isset(static::$app->after[Router::$method]) ? static::$app->after[Router::$method] : array()
        );
        foreach ($filters as $filter) {
            static::$app->{$filter}(Router::$method);
        }
        
        // If an object is returned, use the `response` variable if it's set.
        if (isset($output->response)) {
            $output = $output->response;
        }

        // Check if we have any content
        if (static::$app->render['action'] and isset($output)) {
            static::$app->render['view'] = false;
            // Get the content, clear the body
            // and append content to a clean slate.
            static::$app->response['content'] = $output;
        }

        static::$app->__shutdown();
    }

    /**
     * Returns the app object.
     *
     * @return object
     */
    public static function app()
    {
        return static::$app;
    }

    /**
     * Returns the request object.
     *
     * @return object
     */
    public static function request()
    {
        return static::$request;
    }

    /**
     * Returns the version of Avalon.
     *
     * @return string
     */
    public static function version() {
        return static::$version;
    }
}
