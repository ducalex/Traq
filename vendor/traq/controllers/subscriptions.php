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

namespace traq\controllers;

use avalon\http\Request;

use traq\models\Subscription;
use traq\models\Milestone;
use traq\models\Ticket;

/**
 * Subscription controller.
 *
 * @author Jack P.
 * @since 3.0
 * @package Traq
 * @subpackage Controllers
 */
class Subscriptions extends AppController
{
    public function __construct()
    {
        parent::__construct();

        // Although no permission is required, we do need a valid user!
        if (!LOGGEDIN) {
            return $this->show_no_permission();
        }
    }

    /**
     * Toggles the subscription.
     *
     * @param string  $type Subscription type (Project, Milestone, Ticket)
     * @param integer $id   Subscribed object ID
     */
    public function action_toggle($type, $id)
    {
        switch ($type) {
            // Project
            case 'project':
                $object = $this->project;
                break;

            // Milestone
            case 'milestone':
                // Get milestone
                $object = Milestone::select()->where(['project_id' => $this->project->id, 'slug' => $id])->fetch();
                break;

            // Milestone
            case 'ticket':
                // Get ticket
                $object = Ticket::select()->where(['project_id' => $this->project->id, 'ticket_id' => $id])->fetch();
                break;
        }

        if ($object) {
            $sub = Subscription::find_sub($this->user, $object, true);
            // Toggle
            $sub->delete() || $sub->save();
            Request::redirectTo($object->href());
        }
    }
}
