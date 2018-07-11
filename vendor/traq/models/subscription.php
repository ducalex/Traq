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

use traq\models\Project;
use traq\models\Milestone;
use traq\models\Ticket;

/**
 * Subscription model.
 *
 * @package Traq
 * @subpackage Models
 * @author Jack P.
 * @copyright (c) Jack P.
 */
class Subscription extends Model
{
    protected static $_name = 'subscriptions';
    protected static $_properties = array(
        'id',
        'type',
        'user_id',
        'project_id',
        'object_id'
    );

    protected static $_belongs_to = array('user');

    private $object;

    /**
     * Fetch subscriptions for specified project.
     *
     * @param integer $project_id
     *
     * @return array
     */
    public static function fetch_all_for($project_id)
    {
        return static::select()->where('project_id', $project_id)->exec()->fetch_all();
    }

    /**
     * Checks if the user is subscribed to the
     * passed object and return the subscription.
     * $make_new will return a new (unsaved) one if none exists
     *
     * @param object $user
     * @param object $object
     *
     * @return self
     */
    public static function find_sub(User $user, Model $object, $make_new = false)
    {
        $type = strtolower(preg_replace('/^.*\\\\([^\\\\]+)$/', '$1', get_class($object)));

        $subscription = [
            'project_id' => ($type == 'project') ? $object->id : $object->project_id,
            'user_id' => $user->id,
            'type' => $type,
            'object_id' => $object->id,
        ];

        $sub = static::select()->where($subscription)->exec()->fetch();

        if ($sub === false and $make_new) {
            return new static($subscription);
        }

        return $sub;
    }

    /**
     * Returns the subscribed object.
     *
     * @return object
     */
    public function object() {
        if ($this->object) {
            return $this->object;
        } elseif (class_exists($class = "traq\\models\\{$this->type}")) {
            return $class::find($this->object_id);
        } else {
            return false;
        }
    }

    /**
     * Checks if the groups data is valid.
     *
     * @return bool
     */
    public function is_valid()
    {
        return true;
    }
}
