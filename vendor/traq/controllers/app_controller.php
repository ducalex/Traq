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
use avalon\http\Session;
use avalon\http\Cookie;
use avalon\output\View;

use traq\models\User;
use traq\models\Project;
use traq\libraries\Locale;
use traq\libraries\ApiResponse;
use traq\libraries\AtomResponse;

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
        Load::helper('html', 'form', 'formats', 'time_ago', 'uri', 'string',
            'subscriptions', 'formatting', 'tickets', 'compat');

        // Get the user info
        $this->user = $this->_get_user();

        // Set response format if it's API call
        if ($this->is_api || Router::$extension === '.json') { // Allowing JSON outside API might be a security issue...
            $this->response = ApiResponse::from($this->response);
        }

        // Set user locale if needed
        if ($this->user->locale !== settings('locale') && $locale = Locale::load($this->user->locale)) {
            $this->locale = $locale;
        }

        // Fetch all projects and make sure the user has permission
        // to access the project then pass them to the view.
        foreach (Project::select()->order_by('displayorder', 'ASC')->exec()->fetch_all() as $project) {
            // Check if the user has access to view the project...
            if ($this->user->permission($project->id, 'view')) {
                $this->projects[] = $project;
            }
        }

        // Check if we're on a project page and get the project info
        if (isset(Router::$params['project_slug'])) {
            $this->project = Project::find('slug', Router::$params['project_slug']);

            if ($this->project == false) {
                $this->show_404();
            } elseif ($this->user->permission($this->project->id, 'view')) {
                // Add project name to page title
                $this->title($this->project->name);
            } else {
                $this->show_no_permission();
            }
        }

        View::set('app', $this, true);
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
        if (!LOGGEDIN && $http_auth) {
            header('WWW-Authenticate: Basic realm="Traq"');
        }

        $this->response->status = 401;
        $this->response->errors = [l('errors.no_permission.title')];

        if (LOGGEDIN) {
            $this->render['view'] = 'error/no_permission';
            $this->render['action'] = false;
        } else {
            $this->title[] = l('errors.must_be_logged_in');
            $this->show_login();
        }
    }

    /**
     * Used to display the login page.
     */
    public function show_login()
    {
        $this->render['view'] = 'users/login';
        $this->render['action'] = false;
    }

    /**
     * Used to display the 404 page.
     */
    public function show_404()
    {
        $this->response->status = 404;
        $this->response->errors = [l('errors.404.message', Request::requestUri())];
        $this->render['view'] = 'error/404';
        $this->render['action'] = false;
    }

    /**
     * Used to display the generic error page.
     */
    public function show_error($title, $message, $status = 0)
    {
        $this->response->status = $status;
        $this->response->errors = [$message];
        $this->render['view'] = 'error/generic';
        $this->render['action'] = false;
        View::set(compact('title', 'message', 'status'));
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
        // Check if the session is set
        if ($user_id = Session::get('user_id')) {
            $user = User::find($user_id);
        }
        // Cookie login for backwards compatibility, but it also serves as a "remember me" feature
        elseif ($login_hash = Cookie::get('_traq')) {
            $user = User::find('login_hash', $login_hash);
        }
        // Check if the API key is set
        elseif ($api_key = Request::req('access_token', Request::header('ACCESS_TOKEN'))) {
            if ($user = User::find('api_key', $api_key)) {
                $this->is_api = true;
            } else {
                $this->show_error('api', 'invalid_api_key');
            }
        }
        // Check if there's an HTTP Basic Auth header going on
        elseif ($username = Request::auth('username')) {
            $user = User::find('username', $username);

            if (!$user or !$user->verify_password(Request::auth('password')) or !$user->is_activated()) {
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
            Session::set('user_id', $user->id);
            define("LOGGEDIN", true);
        }

        return $user;
    }

    public function __shutdown()
    {
        // Build the API response if format is json
        if ($this->response instanceOf ApiResponse) {
            $this->render['layout'] = false;
            $this->render['view'] = false;
        }

        // Was the page requested via ajax?
        if ($this->render['view'] and Request::isAjax() and Router::$extension == null) {
            // Is this page being used as an overlay?
            if (Request::req('overlay')) {
                $this->render['view'] .= '.overlay';
            }
            // a popover?
            elseif (Request::post('popover')) {
                $this->render['view'] .= '.popover';
            }

            $this->render['layout'] = 'plain';
        }

        if (Router::$extension) {
            if ($mime = mime_type_for(Router::$extension)) {
                $this->response->format = $mime;
            }
            if (!empty($this->render['view']) and strpos($this->render['view'], Router::$extension) === false) {
                $this->render['view'] .= Router::$extension;
            }
            // Don't render the layout for json or xml content
            $this->render['layout'] = false;
        }

        // Call the controllers shutdown method.
        parent::__shutdown();
    }
}
