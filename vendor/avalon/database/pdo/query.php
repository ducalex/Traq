<?php
/*!
 * Avalon
 * Copyright (C) 2011-2012 Jack Polgar
 *
 * This file is part of Avalon.
 *
 * Avalon is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation; version 3 only.
 *
 * Avalon is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with Avalon. If not, see <http://www.gnu.org/licenses/>.
 */

namespace avalon\database\pdo;

use avalon\Database;
use avalon\database\PDO;

/**
 * PDO Database wrapper query builder
 *
 * @package Avalon
 * @subpackage Database
 * @since 0.1
 * @author Jack P. <nrx@nirix.net>
 * @copyright Copyright (c) Jack P.
 */
class Query
{
    private $connection;
    private $type;
    private $distinct;
    private $cols;
    private $table;
    private $limit;
    private $group_by = [];
    private $where = [];
    private $joins = [];
    private $order_by = [];
    private $custom_sql = [];
    private $bind = [];
    private $_model;

    /**
     * PDO Query builder constructor.
     *
     * @param string $type
     * @param mixed $data
     *
     * @return object
     */
    public function __construct($type, $data = null, $connection = null)
    {
        if ($type === 'SELECT') {
            $this->cols = (array)($data ?: '*');
        } else if ($type === 'INSERT' || $type === 'REPLACE') {
            $this->data = $data;
        } else if ($type === 'UPDATE') {
            $this->table = $data;
        }

        $this->connection = $connection;
        $this->prefix = $this->connection->prefix;
        $this->type = $type;
    }

    /**
     * Enable use of the model object for table rows.
     *
     * @param string $model The model class.
     *
     * @return object
     */
    public function _model($model)
    {
        $this->_model = $model;
        return $this;
    }

    /**
     * Append DISTINCT to query type.
     *
     * @return object
     */
    public function distinct($distinct = true)
    {
        $this->distinct = $distinct;
        return $this;
    }

    /**
     * Set the table to select/delete from.
     *
     * @param string $table
     *
     * @return object
     */
    public function from($table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Set the table to insert data into.
     *
     * @param string $table
     *
     * @return object
     */
    public function into($table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * SQL JOIN
     * Constraint will be built with USING if $operator is null otherwise ON $col1 $op $col2
     *
     * @param string $table
     * @param string $one first column for ON clause
     * @param string $one operator for ON clause
     * @param string $two second column for ON clause
     * @param string $type LEFT|RIGHT|INNER
     *
     * @return object
     */
    public function join($table, $one, $operator = null, $two = null, $type = 'LEFT', $where = null)
    {
        $this->joins[] = [$table, $one, $operator, $two, $type, $where];
        return $this;
    }

    /**
     * Sets the column => value data.
     *
     * @param array $data
     *
     * @return object
     */
    public function set(array $data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Bind value
     *
     * @param string $parameter
     * @param mixed $value
     *
     * @return object
     */
    public function bind($parameter, $value)
    {
        $this->bind[':'.ltrim($parameter, ':')] = $value;
        return $this;
    }

    /**
     * Orders the query rows.
     *
     * @param string $col Column
     * @param string $dir Direction
     *
     * @return object
     */
    public function order_by($col, $dir = 'ASC')
    {
        $this->order_by = [$col, $dir];
        return $this;
    }

    /**
     * Limits the query rows.
     *
     * @param integer $from
     * @param integer $to
     *
     * @return object
     */
    public function limit($from, $to = null)
    {
        $this->limit = implode(',', func_get_args());
        return $this;
    }

    /**
     * Insert custom SQL into the query.
     *
     * @param string $sql
     *
     * @return object
     */
    public function custom_sql($sql)
    {
        $this->custom_sql[] = $sql;
        return $this;
    }

    /**
     * Easily add a "table = something" to the query.
     *
     * @example
     *    1: where("count", 5, ">=")
     *    or
     *    2: where([['count', 5, '>='], ['other', 123, '>=']]);
     *    or
     *    3: where([['count', 5], ['other', 123]], '>=');
     *    or
     *    4: where(['count' => 5, 'other' => 123], '>=');
     *
     * @param string $column Column
     * @param mixed $value Column value
     * @param string $cond Condition/Comparator (=, !=, >=, <=, !=, etc)
     *
     * @return object
     */
    public function where($column, $value = null, $cond = '=')
    {
        // Check if this is a mass add
        if (is_array($column)) {
            $cond = $value ?: $cond;
            foreach($column as $column => $value) {
                if (is_int($column) && is_array($value)) { // (Example 2-3)
                    $this->where[] = $value + [null, null, $cond];
                }
                else { // (Example 4)
                    $this->where[] = [$column, $value, $cond];
                }
            }
        }
        // Just one, add it. (Example 1)
        else {
            $this->where[] = [$column, $value, $cond];
        }

        return $this;
    }

    /**
     * Executes the query and return the statement.
     *
     * @return object
     */
    public function exec()
    {
        $result = $this->connection->prepare($this->_assemble());

        foreach ($this->bind as $key => $value) {
            if (is_int($value)) {
                $type = \PDO::PARAM_INT;
            } elseif (is_bool($value)) {
                $type = \PDO::PARAM_BOOL;
            } elseif (is_null($value)) {
                $type = \PDO::PARAM_NULL;
            } else {
                $type = \PDO::PARAM_STR; // Default
            }
            $result->bind_value($key, $value, $type);
        }

        return $result->_model($this->_model)->exec();
    }

    /**
     * Shortcut to ->exec()->fetch()
     *
     * @return object
     */
    public function fetch()
    {
        return $this->exec()->fetch();
    }

    /**
     * Shortcut to ->exec()->fetch_all()
     *
     * @return array
     */
    public function fetch_all()
    {
        return $this->exec()->fetch_all();
    }

    /**
     * Private method used to compile the query into a string.
     *
     * @return string
     */
    public function _assemble()
    {
        $query[] = $this->type;

        if ($this->type === 'SELECT') {
            $cols = [];
            foreach ($this->cols as $col => $as) {
                if (is_int($col)) {
                    $cols[] = $this->_parse_field_name($as);
                }
                // This is an "alias"
                else {
                    $cols[] = $this->_parse_field_name($col) . ' AS ' . $this->_parse_field_name($as);
                }
            }
            if ($this->distinct) {
                $query[] = 'DISTINCT';
            }
            $query[] = implode(', ', $cols);
        }

        // Select or Delete query
        if ($this->type === 'SELECT' || $this->type === 'DELETE') {
            $query[] = 'FROM ' . $this->_parse_field_name($this->prefix.$this->table);

            // Joins
            foreach($this->joins as list($table, $one, $operator, $two, $type, $where)) {
                $one = $this->_parse_field_name($one);
                $two = $this->_parse_field_name($two);
                $table = $this->_parse_field_name($this->prefix.$table);
                $type = strtoupper($type);

                if ($where) {
                    $where = 'AND ' . $this->_build_where($where);
                }

                if ($operator === null) { // USING
                    $query[] = "$type JOIN $table USING ($one)";
                } else {
                    $query[] = "$type JOIN $table ON ($one $operator $two $where)";
                }
            }

            // Where
            if ($this->where) {
                $query[] = 'WHERE ' . $this->_build_where($this->where);
            }

            // Group by
            if (count($this->group_by) > 0) {
                $query[] = "GROUP BY " . implode(', ', $this->group_by);
            }

            // Custom SQL
            $query = array_merge($query, $this->custom_sql);

            // Order by
            if (count($this->order_by) > 0) {
                list($column, $direction) = $this->order_by;
                $column = $this->_parse_field_name($column);
                $query[] = "ORDER BY $column $direction";
            }

            // Limit
            if ($this->limit !== null) {
                $query[] = "LIMIT {$this->limit}";
            }
        }
        // Insert query
        else if($this->type === 'INSERT' || $this->type === 'REPLACE') {
            $columns = $values = [];

            foreach($this->data as $column => $value) {
                $this->bind($values[] = ":i_{$column}", $value);
                $columns[] = "`{$column}`";
            }

            $query[] = 'INTO ' . $this->_parse_field_name($this->prefix.$this->table);
            $query[] = '('.implode(', ', $columns).') VALUES('.implode(', ', $values).')';
        }
        // Update query
        else if($this->type === 'UPDATE') {
            $set = [];

            foreach ($this->data as $column => $value) {
                $this->bind($key = ":u_{$column}", $value);
                $set[] = "`$column` = $key";
            }

            $query[] = $this->_parse_field_name($this->prefix.$this->table);
            $query[] = 'SET '.implode(', ', $set);

            // Where
            if ($this->where) {
                $query[] = 'WHERE ' . $this->_build_where($this->where);
            }
        }

        return implode(' ', $query);
    }


    private function _parse_field_name($field)
    {
        if (preg_match('/^[A-Z_]+\(.*\)$/', $field)) { // This is likely a FUNCTION()
            return $field;
        }

        // Add table prefix to table.column
        $field = strpos($field, '.') ? $this->prefix.$field : $field;

        return preg_replace('/(?!\bas\b )\b\w+/', '`$0`', $field); // try to not break "column as alias"
    }


    public function _build_where(array $where, $union = 'AND')
    {
        $_where = [];

        foreach ($where as $i => list($column, $value, $cond)) {
            $placeholder = preg_replace('/[^a-zA-Z0-9_:]/', '_', ":w{$i}_{$column}");
            $column = $this->_parse_field_name($column);
            if ($cond === 'IN' || $cond === 'NOT IN') {
                foreach((array)$value as $j => $value) {
                    $this->bind($IN[] = "{$placeholder}_in_$j", $value);
                }
                $_where[] = "{$column} {$cond} (" . implode(',', $IN) . ")";
            } else {
                $_where[] = "{$column} {$cond} {$placeholder}";
                $this->bind($placeholder, $value);
            }
        }

        return '('.implode(" $union ", $_where).')';
    }

    /**
     * Magic method that converts the query to a string.
     * And some PHP team members have said PHP is not a magic language,
     * this is why Ruby is better.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->_assemble();
    }
}
