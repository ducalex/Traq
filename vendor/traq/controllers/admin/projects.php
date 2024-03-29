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

use traq\models\Project;

/**
 * Admin Projects controller
 *
 * @author Jack P.
 * @since 3.0
 * @package Traq
 * @subpackage Controllers
 */
class Projects extends AppController
{
    public function __construct()
    {
        parent::__construct();
        $this->title(l('projects'));
    }

    public function action_index()
    {
        // Fetch all projects and pass them to the response.
        $this->response['projects'] = Project::fetch_all();
    }

    /**
     * Create a new project page.
     */
    public function action_new()
    {
        $this->title(l('new'));

        $project = new Project;

        if (Request::method() == 'post') {
            $project->set(array(
                'name'         => Request::post('name'),
                'slug'         => Request::post('slug'),
                'codename'     => Request::post('codename'),
                'info'         => Request::post('info'),
                'enable_wiki'  => (int)Request::post('enable_wiki', 0),
                'default_ticket_type_id' => Request::post('default_ticket_type_id'),
                'default_ticket_sorting' => Request::post('default_ticket_sorting'),
                'displayorder' => (int)Request::post('displayorder', 0)
            ));

            // Save project
            if ($this->response->status = $project->save()) {
                $this->response->redirect = 'admin/projects';
            }
        }

        $this->response['project'] = $project;
        $this->response->errors = $project->errors;
    }

    /**
     * Delete a project.
     *
     * @param integer $id Project ID.
     */
    public function action_delete($id)
    {
        $project = Project::find('id', $id);

        if ($this->response->status = $project->delete()) {
            $this->response->redirect = 'admin/projects';
        }
    }
}
