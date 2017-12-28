<?php
/*!
 * Traq
 * Copyright (C) 2009-2013 Traq.io
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

use traq\models\CustomField;

/**
 * Custom fields controller
 *
 * @author Jack P.
 * @since 3.0
 * @package Traq
 * @subpackage Controllers
 */
class CustomFields extends AppController
{
    public function __construct()
    {
        parent::__construct();
        $this->title(l('custom_fields'));
    }

    public function action_index()
    {
        View::set('custom_fields', CustomField::select()->where('project_id', $this->project->id)->exec()->fetch_all());
    }

    /**
     * New field page.
     */
    public function action_new()
    {
        // Create field
        $field = new CustomField(array(
            'type'  => 'text',
            'regex' => '^(.*)$'
        ));

        // Check if the form has been submitted
        if (Request::method() == 'post') {
            $data = array();

            // Loop over properties
            foreach (CustomField::properties() as $property) {
                // Check if it's set and not empty
                if (Request::post($property) !== null) {
                    $data[$property] = Request::post($property);
                }
            }

            if ($data['min_length'] == '') {
                $data['min_length'] = 0;
            }

            if ($data['max_length'] == '') {
                $data['max_length'] = 255;
            }

            // Is required?
            $data['is_required'] = Request::post('is_required') ? 1 : 0;

            // Project ID
            $data['project_id'] = $this->project->id;

            // Set field properties
            $field->set($data);

            // Save and redirect
            if ($this->response['status'] = $field->save()) {
                $this->response['redirect'] = $this->project->href('settings/custom_fields');
            }
        }

        $this->response['field'] = $field;
        $this->response['errors'] = $field->errors;
    }

    /**
     * Edit field page.
     *
     * @param integer $id
     */
    public function action_edit($id)
    {
        // Get field
        $field = CustomField::find($id);

        // Verify project
        if ($field->project_id != $this->project->id) {
            return $this->show_no_permission();
        }

        // Check if the form has been submitted
        if (Request::method() == 'post') {
            $data = array();

            // Loop over properties
            foreach (CustomField::properties() as $property) {
                // Check if it's set and not empty
                if (Request::post($property) !== null) {
                    $data[$property] = Request::post($property);
                }
            }

            if ($data['min_length'] == '') {
                $data['min_length'] = 0;
            }

            if ($data['max_length'] == '') {
                $data['max_length'] = 255;
            }

            if ($this->is_api) {
                $data['is_required'] = Request::post('is_required', $field->is_required);
                $data['multiple'] = Request::post('multiple', $field->multiple);
            } else {
                $data['is_required'] = Request::post('is_required', 0);
                $data['multiple'] = Request::post('multiple', 0);
            }

            // Set field properties
            $field->set($data);

            // Save and redirect
            if ($this->response['status'] = $field->save()) {
                $this->response['redirect'] = $this->project->href('settings/custom_fields');
            }
        }

        $this->response['field'] = $field;
        $this->response['errors'] = $field->errors;
    }

    /**
     * Delete field.
     */
    public function action_delete($id)
    {
        // Find field
        $field = CustomField::find($id);

        // Verify project
        if ($field->project_id != $this->project->id) {
            return $this->show_no_permission();
        }

        // Delete and redirect
        if ($this->response['status'] = $field->delete()) {
            $this->response['redirect'] = $this->project->href('settings/custom_fields');
        }
    }
}
