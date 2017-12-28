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

namespace traq\controllers\ProjectSettings;

use avalon\http\Request;
use avalon\output\View;

use traq\models\Component;

/**
 * Components controller
 *
 * @author Jack P.
 * @since 3.0
 * @package Traq
 * @subpackage Controllers
 */
class Components extends AppController
{
    public function __construct()
    {
        parent::__construct();
        $this->title(l('components'));
    }

    /**
     * Components listing page.
     */
    public function action_index()
    {
        View::set('components', $this->project->components);
    }

    /**
     * New component page.
     */
    public function action_new()
    {
        $this->title(l('new'));

        $component = new Component();

        // Check if the form has been submitted
        if (Request::method() == 'post') {
            // Set the information
            $component->set(array(
                'name'       => Request::post('name'),
                'project_id' => $this->project->id
            ));

            // Check if the data is valid
            if ($component->is_valid()) {
                // Save and redirect
                if ($this->response['status'] = $component->save()) {
                    $this->response['redirect'] = $this->project->href("settings/components");
                }
            }
        }

        $this->response['component'] = $component;
        $this->response['errors'] = $component->errors;
    }

    /**
     * Edit component page.
     *
     * @param integer $id Component ID
     */
    public function action_edit($id)
    {
        $this->title(l('edit'));

        // Fetch the component
        $component = Component::find($id);

        if ($component->project_id !== $this->project->id) {
            return $this->show_no_permission();
        }

        // Check if the form has been submitted
        if (Request::method() == 'post') {
            // Update the information
            $component->set(array(
                'name' => Request::post('name'),
            ));

            // Check if the data is valid
            if ($component->is_valid()) {
                // Save and redirect
                if ($this->response['status'] = $component->save()) {
                    $this->response['redirect'] = $this->project->href("settings/components");
                }
            }
        }

        $this->response['component'] = $component;
        $this->response['errors'] = $component->errors;
    }

    /**
     * Delete component.
     *
     * @param integer $id Component ID
     */
    public function action_delete($id)
    {
        // Fetch the component
        $component = Component::find($id);

        if ($component->project_id !== $this->project->id) {
            return $this->show_no_permission();
        }

        // Delete component
        if ($this->response['status'] = $component->delete()) {
            $this->response['redirect'] = $this->project->href("settings/components");
        }
    }
}
