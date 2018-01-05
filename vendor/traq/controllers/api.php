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

namespace traq\controllers;

use traq\models\Status;
use traq\models\Priority;
use traq\models\Type;

/**
 * API controller.
 *
 * @author Jack P.
 * @since 3.1
 * @package Traq
 * @subpackage Controllers
 */
class API extends AppController
{
    public $is_api = true;

    /**
     * Ticket statuses.
     */
    public function action_statuses()
    {
        $this->response['statuses'] = Status::fetch_all();
    }

    /**
     * Ticket priorities.
     */
    public function action_priorities()
    {
        $this->response['priorities'] = Priority::fetch_all();
    }

    /**
     * Ticket types.
     */
    public function action_types()
    {
        $this->response['types'] = Type::fetch_all();
    }
}
