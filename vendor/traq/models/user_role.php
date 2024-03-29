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
 * Users<>Roles model.
 *
 * @package Traq
 * @subpackage Models
 * @author Jack P.
 * @copyright (c) Jack P.
 */
class UserRole extends Model
{
    protected static $_name = 'user_roles';
    protected static $_properties = array(
        'id',
        'user_id',
        'project_id',
        'project_role_id'
    );

    // Allow easy access to the project and role models
    protected static $_belongs_to = array(
        'project', 'user',

        'role' => array('model' => 'ProjectRole', 'column' => 'project_role_id')
    );

    /**
     * Returns an array of the project members.
     *
     * @return array
     */
    public static function project_members($project_id)
    {
        return array_get_column(static::find('project_id', $project_id, INF), 'user');
    }

    public function is_valid()
    {
        return true;
    }

    public function toArray($fields = array('user', 'role'), $exclude = array())
    {
        return parent::toArray($fields, $exclude);
    }
}
