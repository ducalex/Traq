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

namespace traq\helpers;

use avalon\core\Kernel as Avalon;
use avalon\Database;
use traq\models\CustomField;
use traq\models\Status;
use traq\models\User;
use traq\models\Ticket;

/**
 * Ticket filter query builder.
 *
 * @author Jack P.
 * @since 3.0
 * @package Traq
 * @subpackage Helpers
 */
class TicketFilterQuery
{
    private $sql = array();
    private $filters = array();
    private $project;

    public function __construct($cols = ['tickets.*', 'users.name' => 'owner'])
    {
        $this->project = Avalon::app()->project;
        $this->query = Ticket::select($cols)->join('users', 'users.id', '=', 'tickets.user_id');
        $this->query->where('project_id', $this->project->id);
    }

    /**
     * Processes a filter.
     *
     * @param string $filed
     * @param array $values
     */
    public function process($field, $values)
    {
        if ($field === 'search') {
            $values = (array)$values;
        } elseif (!is_array($values)) {
            $values = explode(',', $values);
        }

        if ($values = array_unique($values)) {
            $is_not = $values[0][0] === '!';
            $values[0] = ltrim($values[0], '!');
            
            // Add to filters array
            $this->filters[$field] = ['prefix' => $is_not ? '!' : '', 'values' => $values];

            $this->add($field, $is_not, array_filter($values));
        }
    }

    /**
     * Checks the values and constructs the query.
     *
     * @param string $field
     * @param string $is_not
     * @param array $values
     */
    private function add($field, $is_not, array $values)
    {
        $query_values = array();

        if (empty($values)) {
            return;
        }

        // Milestone, version, status, type and component
        if (in_array($field, array('milestone', 'status', 'type', 'version', 'component', 'priority', 'severity'))) {
            $class = "\\traq\\models\\" . ucfirst($field === 'version' ? 'milestone' : $field);
            foreach ($values as $value) {
                // What column to use when looking up row.
                $find = ($field === 'milestone' || $field === 'version') ? 'slug' : 'name';

                // Status, type, priority and severity
                if (in_array($field, array('status', 'type', 'priority', 'severity'))) {
                    // Find row and add ID to query values if it exists
                    if ($field === 'status' and ($value === 'allopen' or $value === 'allclosed')) {
                        foreach (Status::select('id')->where('status', ($value === 'allopen' ? 1 : 0))->exec()->fetch_all() as $status) {
                            $query_values[] = $status->id;
                        }
                    } elseif ($row = $class::find($find, $value)) {
                        $query_values[] = $row->id;
                    }
                }
                // Everything else
                else {
                    if ($row = $class::select()->where('project_id', $this->project->id)->where($find, $value)->exec()->fetch()) {
                        $query_values[] = $row->id;
                    }
                }
            }

            // Sort values low to high
            asort($query_values);

            // Add to query if there's any values
            if ($query_values) {
                $this->query->where("{$field}_id", $query_values, $is_not ? 'NOT IN' : 'IN');
            }

            $this->filters[$field]['values'] = $query_values;
        }
        // Summary and description
        elseif (in_array($field, array('summary', 'description'))) {
            foreach ($values as $value) {
                $query_values[] = [($field === 'summary' ? 'summary' : 'body'), strtr("%$value%", '*', '%')];
            }

            if ($is_not) {
                $this->query->where($query_values, null, 'NOT LIKE', 'AND');
            } else {
                $this->query->where($query_values, null, 'LIKE', 'OR');
            }
        }
        // Owner and Assigned to
        elseif (in_array($field, array('owner', 'assigned_to'))) {
            $column = ($field === 'owner') ? 'user' : $field;

            $user_ids = User::select('id')->where('username', $values, 'IN')->fetch_all();

            foreach ($user_ids as $user) {
                $query_values[] = $user->id;
            }

            // If no valid user was found but the query wasn't empty
            $this->query->where([["{$column}_id", $query_values ?: [0], $is_not ? 'NOT IN' : 'IN']]);
        }
        // Search
        elseif ($field === 'search') {
            $value = str_replace('*', '%', implode('%', $values));
            if ($is_not) {
                $this->query->where([['summary', "%$value%"], ['body', "%$value%"]], null, 'NOT LIKE', 'AND');
            } else {
                $this->query->where([['summary', "%$value%"], ['body', "%$value%"]], null, 'LIKE', 'OR');
            }
        }
        // Custom fields
        elseif (in_array($field, array_keys(custom_field_filters_for($this->project)))) {
            $custom_field = CustomField::find('slug', $field);
            $this->filters[$field]['label'] = $custom_field->name;

            // Sort values low to high
            asort($values);

            $custom_field_where = [
                ["c_$field.custom_field_id", $custom_field->id, '='],
                ["c_$field.value", '%'.json_encode($values[0]).'%', $is_not ? 'NOT LIKE' : 'LIKE']
            ];

            $this->query->join("custom_field_values as c_$field", "c_$field.ticket_id", '=', 'tickets.id', '', $custom_field_where);
        }
    }

    /**
     * Returns filters.
     *
     * @return array
     */
    public function filters()
    {
        return $this->filters;
    }

    /**
     * Returns the query.
     *
     * @return object
     */
    public function query()
    {
        return $this->query;
    }
}
