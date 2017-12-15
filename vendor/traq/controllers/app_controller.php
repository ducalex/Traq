<?php
/*!
 * Traq
 * Copyright (C) 2009-2014 Jack Polgar
 * Copyright (C) 2012-2014 Traq.io
 * https://github.com/nirix
 * http://traq.io
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

namespace traq\controllers;

use avalon\core\Kernel as Avalon;
use avalon\core\Controller;
use avalon\core\Load;
use avalon\Database;
use avalon\http\Request;
use avalon\http\Router;
use avalon\output\View;

use traq\models\User;
use traq\models\Project;
use traq\libraries\Locale;
use traq\helpers\API;

/**
 * App controller
 *
 * @author Jack P.
 * @since 3.0
 * @package Traq
 * @subpackage Controllers
 */
class AppController extends Controller
{
    public $project;
    public $projects = array();
    public $user;
    public $locale;
    public $is_api = false;
    public $title = array();
    public $feeds = array();

    public function __construct()
    {
        // Set DB connection
        $this->db = Database::connection();

        // Set locale
        $this->locale = Locale::load(settings('locale'));

        // Set the theme
        View::$theme = settings('theme');
        View::$inherit_from = APPPATH . "/views/default";

        // Call the controller class constructor
        parent::__construct();

        // Set the title
        $this->title(settings('title'));

        // Load helpers
        Load::helper('html', 'errors', 'form', 'formats', 'time_ago', 'uri', 'string',
            'subscriptions', 'timeline', 'formatting', 'tickets', 'compat');

        // Get the user info
        $this->user = $this->_get_user();

        // Set user locale if needed
        if ($user->locale !== settings('locale') && $locale = Locale::load($user->locale)) {
            $this->locale = $locale;
        }

        // Check if we're on a project page and get the project info
        if (isset(Router::$params['project_slug'])) {
            $this->project = Project::find('slug', Router::$params['project_slug']);

            if ($this->project == false) {
                $this->show_404();
            } elseif ($this->user->permission($this->project->id, 'view')) {
                // Add project name to page title
                $this->title($this->project->name);
                View::set('project', $this->project);
            } else {
                $this->show_no_permission();
            }
        }

        // Fetch all projects and make sure the user has permission
        // to access the project then pass them to the view.
        foreach (Project::select()->order_by('displayorder', 'ASC')->exec()->fetch_all() as $project) {
            // Check if the user has access to view the project...
            if ($this->user->permission($project->id, 'view')) {
                $this->projects[] = $project;
            }
        }

        View::set(array(
            'current_user' => $this->user,
            'projects' => $this->projects,
            'traq' => $this,
            'app' => $this,
        ));
    }

    /**
     * Adds to or returns the page title array.
     *
     * @param mixed $add
     *
     * @return mixed
     */
    public function title($add = null)
    {
        // Check if we're adding or returning
        if ($add === null) {
            // We're returning
            return $this->title;
        }

        // Add the title
        $this->title[] = $add;
    }

    /**
     * Used to display the no permission page.
     */
    public function show_no_permission($http_auth = false)
    {
        header("HTTP/1.0 401 Unauthorized");
        if (!LOGGEDIN && $http_auth) {
            header('WWW-Authenticate: Basic realm="Traq"');
        }
        $this->render['view'] = 'error/no_permission';
        $this->render['action'] = false;
    }

    /**
     * Used to display the login page.
     */
    public function show_login()
    {
        $this->render['action'] = false;
        $this->render['view'] = 'users/login' . ($this->is_api ? '.api' :'');
    }

    /**
     * Used to display the 404 page.
     */
    public function show_404()
    {
        header("HTTP/1.0 404 Not Found");
        $this->render['view'] = 'error/404';
        $this->render['action'] = false;
        View::set('request', $_SERVER['REQUEST_URI']);
    }

    /**
     * Used to display the generic error page.
     */
    public function show_error($title, $message, $code = null)
    {
        $this->render['view'] = 'error/generic';
        $this->render['action'] = false;
        View::set(compact('title', 'message', 'code'));
    }

    /**
     * Does the checking for the session cookie and fetches the users info.
     *
     * @author Jack P.
     * @since 3.0
     * @access private
     */
    private function _get_user()
    {
        // Check if the session cookie is set, if so, check if it matches a user
        // and set set the user info.
        if (isset($_COOKIE['_traq'])) {
            $user = User::find('login_hash', $_COOKIE['_traq']);
        }
        // Check if the API key is set
        elseif ($api_key = API::get_key()) {
            $user = User::find('api_key', $api_key);
            // Set is_api and JSON view extension
            $this->is_api = true;
            Router::$extension = '.json';
        }
        elseif(isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
            $user = User::find('username', $_SERVER['PHP_AUTH_USER']);
            if ($user == false or !$user->verify_password($_SERVER['PHP_AUTH_PW']) or !$user->is_activated()) {
                $user = null;
            }
        }

        // Guest
        if (empty($user)) {
            $user = new User(array(
                'id' => settings('anonymous_user_id'),
                'locale' => settings('locale'),
                'username' => l('guest'),
                'group_id' => 3,
            ));
            define("LOGGEDIN", false);
        } else {
            define("LOGGEDIN", true);
        }

        return $user;
    }

    /**
     * Display a bad API request.
     *
     * @param string $error Error message
     */
    protected function bad_api_request($message)
    {
        $this->render = array_merge(
            $this->render,
            array(
                'action' => false,
                'view'   => "api/bad_request.json",
                'layout' => "plain"
            )
        );

        View::set(compact('message'));
    }

    public function __shutdown()
    {
        // Plain layout for JSON and API requests and Atom feed
        if (Router::$extension or $this->is_api) {
            $this->render['layout'] = 'plain';
        }
        // Bad API request?
        if (API::get_key() === false) {
            $this->bad_api_request('invalid_api_key');
        }

        // Was the page requested via ajax?
        if ($this->render['view'] and Request::isAjax() and Router::$extension == null) {
            // Is this page being used as an overlay?
            if (Request::req('overlay')) {
                $extension = '.overlay';
            }
            // a popover?
            elseif (Request::post('popover')) {
                $extension = '.popover';
            }
            // Neither, just regular javascript
            else {
                $extension = '.js';
            }

            // Set the layout and view extension
            $this->render['layout'] = 'plain';
            $this->render['view'] .= $extension;
        }

        if (Router::$extension) {
            if ($mime = mime_type_for(Router::$extension)) {
                header('Content-type: ' . $mime);
            }
            if (!empty($this->render['view']) and strpos($this->render['view'], Router::$extension) === false) {
                $this->render['view'] .= Router::$extension;
            }
        }

        // Call the controllers shutdown method.
        parent::__shutdown();
    }
}
