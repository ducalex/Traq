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

use traq\models\Severity;

/**
 * Severities controller
 *
 * @author Jack P.
 * @since 3.0
 * @package Traq
 * @subpackage Controllers
 */
class Severities extends AppController
{
    public function __construct()
    {
        parent::__construct();
        $this->title(l('severities'));
    }

    /**
     * Severity listing.
     */
    public function action_index()
    {
        $this->response['severities'] = Severity::fetch_all();
    }

    /**
     * New severity.
     */
    public function action_new()
    {
        // Create the severity
        $severity = new Severity();

        // Check if the form has been submitted
        if (Request::method() == 'post') {
            // Set the name
            $severity->set('name', Request::post('name'));

            // Save and redirect
            if ($this->response->status = $severity->save()) {
                $this->response->redirect = '/admin/severities';
            }
        }

        $this->response['severity'] = $severity;
    }

    /**
     * Edit severity.
     *
     * @param integer $id
     */
    public function action_edit($id)
    {
        // Get the severity
        $severity = Severity::find($id);

        // Check if the form has been submitted
        if (Request::method() == 'post') {
            // Set the name
            $severity->set('name', Request::post('name', $severity->name));

            // Save and redirect
            if ($this->response->status = $severity->save()) {
                $this->response->redirect = '/admin/severities';
            }
        }

        $this->response['severity'] = $severity;
    }

    /**
     * Delete severity.
     *
     * @param integer $id
     */
    public function action_delete($id)
    {
        // Get the severity and delete
        $this->response->status = Severity::find($id)->delete();
        $this->response->redirect = '/admin/severities';
    }
}
