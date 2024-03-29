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

namespace traq\controllers\admin;

use avalon\http\Request;
use avalon\output\View;

use traq\models\User;
use traq\models\Setting;

/**
 * Admin Users controller
 *
 * @author Jack P.
 * @since 3.0
 * @package Traq
 * @subpackage Controllers
 */
class Users extends AppController
{
    public function __construct()
    {
        parent::__construct();
        $this->title(l('users'));
    }

    public function action_index()
    {
        $this->response['users'] = User::fetch_all();
    }

    /**
     * Create user page.
     */
    public function action_new()
    {
        $this->title(l('new'));

        // Create a new user object
        $user = new User(array('group_id' => 2));

        // Check if the form has been submitted
        if (Request::method() == 'post') {
            // Set the users information
            $user->set(array(
                'username' => Request::post('username'),
                'name'     => Request::post('name') ?: Request::post('username'),
                'password' => Request::post('password'),
                'email'    => Request::post('email'),
                'group_id' => Request::post('group_id', 2)
            ));

            // Check if the data is valid
            if ($user->is_valid()) {
                // Save the users data and redirect
                // to the user listing page.
                $this->response->status = $user->save();
                $this->response->redirect = '/admin/users';
            }
        }
        
        $this->response['user'] = $user;
    }

    /**
     * Edit user page
     *
     * @param integer $id Users ID.
     */
    public function action_edit($id)
    {
        $this->title(l('edit'));

        // Fetch the user from the DB.
        $user = User::find($id);

        // Check if the form has been submitted.
        if (Request::method() == 'post') {
            // Update the users information.
            $user->set(array(
                'username' => Request::post('username', $user->username),
                'name'     => Request::post('name', $user->name),
                'email'    => Request::post('email', $user->email),
                'group_id' => Request::post('group_id', $user->group_id)
            ));

            // Check if we're changing their password.
            if (Request::post('password')) {
                // Update their password.
                $user->set('password', Request::post('password'));
            }

            // Check if the users data is valid.
            if ($user->is_valid()) {
                // Save and redirect to user listing.
                $this->response->status = $user->save();
                $this->response->redirect = '/admin/users';
            }
        }

        $this->response['user'] = $user;
    }

    /**
     * Delete user
     *
     * @param integer $id Users ID.
     */
    public function action_delete($id)
    {
        // Find and delete the user then
        // redirect to the user listing page.
        $this->response->status = User::find($id)->delete();
        $this->response->redirect = '/admin/users';
    }

    /**
     * Mass Action processing.
     */
    public function action_mass_actions()
    {
        // Make sure there are some users...
        if (!Request::post('users')) {
            Request::redirectTo('/admin/users');
        }

        // Get anonymous user ID
        $anon_id = Setting::find('setting', 'anonymous_user_id')->value;

        // What are we deleting?
        $delete_user     = Request::post('delete_user') == 1 ? true : false;
        $delete_tickets  = Request::post('delete_tickets') == 1 ? true : false;
        $delete_comments = Request::post('delete_comments') == 1 ? true : false;

        // Loop over users
        foreach (Request::post('users') as $user_id) {
            $user = User::find($user_id);

            // Delete tickets?
            if ($delete_tickets) {
                foreach ($user->tickets->exec()->fetch_all() as $ticket) {
                    $ticket->delete();
                }
            }

            // Delete comments
            if ($delete_user || $delete_comments) {
                foreach ($user->ticket_updates->exec()->fetch_all() as $update) {
                    if ($delete_comments) {
                        $update->delete();
                    } elseif ($delete_user) {
                        $update->set('user_id', $anon_id);
                        $update->save();
                    }
                }
            }

            // Delete user
            if ($delete_user) {
                $user->delete();
            }
        }

        $this->response->redirect = '/admin/users';
    }
}
