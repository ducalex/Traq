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

namespace traq\models;

use avalon\database\Model;

/**
 * Ticket history model.
 *
 * @package Traq
 * @subpackage Models
 * @author Jack P.
 * @copyright (c) Jack P.
 */
class TicketHistory extends Model
{
    protected static $_name = 'ticket_history';
    protected static $_serialize = array('changes');
    protected static $_properties = array(
        'id',
        'user_id',
        'ticket_id',
        'changes',
        'comment',
        'created_at'
    );

    // Relations
    protected static $_belongs_to = array('ticket', 'user');

    /**
     * Checks that the data is valid.
     */
    public function is_valid()
    {
        // Just return true.
        return true;
    }
}
