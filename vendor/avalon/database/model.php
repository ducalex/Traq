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

namespace avalon\database;

use avalon\Database;
use avalon\helpers\Time;
use \FishHook;

/**
 * Database Model class
 *
 * @package Avalon
 * @subpackage Database
 * @since 0.1
 * @author Jack P. <nrx@nirix.net>
 * @copyright Copyright (c) Jack P.
 */
class Model implements \JsonSerializable
{
    // Static information
    protected static $_name; // Table name
    protected static $_primary_key = 'id'; // Primary key
    protected static $_has_many; // Has many relationship array
    protected static $_properties = ['*']; // Table columns
    protected static $_belongs_to; // Belongs to relationship array
    protected static $_filters_before = []; // Before filters
    protected static $_filters_after = []; // After filters
    protected static $_connection_name = 'main'; // Name of the connection to use
    protected static $_serialize = []; // Fields to serialize to json when reading/writing database
    protected static $_escape = []; // Fields to escape when reading from database

    // Information different per table row
    protected $_changed_properties = []; // Properties that have been changed
    protected $_data = [];
    protected $_is_new = true; // Used to determine if this is a new row or not.
    protected $errors = [];

    /**
     * Used to build to assign the row data to the class as variables.
     *
     * @param array $data The row data
     */
    public function __construct(array $data = [], $is_new = true) {
        $this->_data = $data;
        $this->_is_new = $is_new;

        // Is there any data?
        // If so get the columns and add them to
        // the properties array
        foreach ($data as $column => $value) {
            if (!in_array($column, static::$_properties)) {
                static::$_properties[] = $column;
            }

            // Unserialize only if it comes from the database
            if ($is_new == false and in_array($column, static::$_serialize)) {
                $this->_data[$column] = json_decode($value, true);
            } elseif (in_array($column, static::$_escape)) {
                $this->_data[$column] = htmlspecialchars($value);
            }
        }

        // Create filter arrays if they aren't already
        foreach (['construct', 'create', 'save'] as $filter) {
            // Before filters
            if (!isset(static::$_filters_before[$filter])) {
                static::$_filters_before[$filter] = [];
            }

            // After filters
            if (!isset(static::$_filters_after[$filter])) {
                static::$_filters_after[$filter] = [];
            }
        }

        if (!in_array('_date_time_convert', static::$_filters_after['construct'])) {
            static::$_filters_after['construct'][] = '_date_time_convert';
        }

        if (!in_array('_timestamps', static::$_filters_before['create'])) {
            static::$_filters_before['create'][] = '_timestamps';
        }

        if (!in_array('_timestamps', static::$_filters_before['save'])) {
            static::$_filters_before['save'][] = '_timestamps';
        }

        // And run the after construct filter array...
        foreach (static::$_filters_after['construct'] as $filter) {
            $this->$filter();
        }

        // Plugin hook
        FishHook::run('model::__construct', [static::class, $this, &static::$_properties, &static::$_escape]);
    }

    /**
     * Find the first matching row and returns it.
     *
     * @param string $find Either the value of the primary key, or the field name.
     * @param value $value The value of the field to find if the $find param is the field name.
     * @param int $count if specified returns an array of all matches up to count
     *
     * @return object|array
     */
    public static function find($find, $value = null, $count = 1) {
        if ($value === null) {
            list($find, $value) = [static::$_primary_key, $find];
        }

        $data = static::select()->where($find, $value)->limit($count)->exec()->fetch_all();

        if (func_num_args() === 3) {
            return $data ? $data : [];
        } else {
            return $data ? $data[0] : false;    
        }
    }

    /**
     * Creates a new row or saves the changed properties.
     */
    public function save() {
        // Make sure the data is valid..
        if (!$this->is_valid()) {
            return false;
        }

        $primary_key = static::$_primary_key;
        $action = $this->_is_new() ? 'create' : 'save';
        $data = [];

        // Before save filters
        if (!empty(static::$_filters_before[$action])) {
            foreach (static::$_filters_before[$action] as $filter) {
                $this->$filter();
            }
        }

        foreach (static::$_properties as $column) {
            if (is_scalar($column) && isset($this->_data[$column])) {
                if (in_array($column, static::$_escape)) {
                    $data[$column] = htmlspecialchars_decode($this->_data[$column]);
                } elseif (in_array($column, static::$_serialize)) {
                    $data[$column] = json_encode($this->_data[$column]);
                } else {
                    $data[$column] = $this->_data[$column];
                }
            }
        }

        FishHook::run('model::save/'.$action, [static::class, &$data]);

        // Save
        if ($this->_is_new() === false) {
            unset($data[$primary_key]);
            static::db()->update(static::$_name)->set($data)->where($primary_key, $this->_data[$primary_key])->exec();
        }
        // Create
        else {
            static::db()->insert($data)->into(static::$_name)->exec();
            $this->_data[$primary_key] = static::db()->last_insert_id();
        }
        return true;
    }

    /**
     * Deletes the row.
     */
    public function delete() {
        if ($this->_is_new() === true) {
            return false;
        }
        // Before delete filters
        if (!empty(static::$_filters_before['delete'])) {
            foreach (static::$_filters_before['delete'] as $filter) {
                $this->$filter();
            }
        }
        return static::db()->delete()->from(static::$_name)->where(static::$_primary_key, $this->_data[static::$_primary_key])->exec();
    }

    /**
     * Checks if the row is new or not.
     *
     * @return bool
     */
    public function _is_new($is_new = null) {
        if ($is_new !== null) {
            $this->_is_new =  $is_new;
        }
        return $this->_is_new;
    }

    /**
     * Sets the value of the column(s) to the value(s).
     *
     * @param mixed $col Either the column or an array to update multiple columns.
     * @param mixed $val The value of the column if only updating one column.
     *
     * @example $model->set(['col1'=>'val1', 'col2'=>'val2']);
     *          $model->set('col1', 'val1');
     */
    public function set($col, $val = null) {
        if (is_array($col)) {
            foreach ($col as $var => $val) {
                $this->set($var, $val);
            }
        } else {
            $this->_data[$col] = $val;
            $this->_set_changed($col);

            if (!in_array($val, static::$_properties)) {
                static::$_properties[] = $val;
            }

            // Plugin hook
            FishHook::run('model::set', [static::class, $col, $val]);
        }
    }

    /**
     * Adds the property to the changed properties array.
     *
     * @param string $property
     */
    protected function _set_changed($property) {
        if (in_array($property, static::$_properties) and !in_array($property, $this->_changed_properties)) {
            $this->_changed_properties[] = $property;
        }
    }

    /**
     * Shortcut of the select() function for the database.
     *
     * @param mixed $cols The columns to select.
     *
     * @return object
     */
    public static function select($cols = null) {
        return static::db()->select($cols ?: static::$_properties)->from(static::$_name)->_model(static::class);
    }

    /**
     * Aliases the database's update() method for the current row.
     */
    public function update() {
        return static::db()->update(static::$_name)->where(static::$_primary_key, $this->data[static::$_primary_key]);
    }

    /**
     * Fetches all the rows for the table.
     *
     * @return array
     */
    public static function fetch_all() {
        return static::select(static::$_properties)->exec()->fetch_all();
    }

    public function is_valid() {
        // Until the validation stuff is done we will return false,
        // to work around this each model will have to create its own
        // is_valid method.
        return false;
    }

    /**
     * Magical function to load the relationships.
     */
    public function __get($var) {
        // Model data
        if (in_array($var, static::$_properties)) {
            $val = isset($this->_data[$var]) ? $this->_data[$var] : '';
        }
        // Has many
        elseif (is_array(static::$_has_many) and (in_array($var, static::$_has_many) or isset(static::$_has_many[$var]))) {
            $has_many = [ // Defaults
                'model' => rtrim($var, 's'),
                'foreign_key' => substr(static::$_name, 0, -1) . '_id',
                'column' => static::$_primary_key
            ];

            if (isset(static::$_has_many[$var])) {
                $has_many = array_merge($has_many, static::$_has_many[$var]);
            }

            $model = preg_replace('/[^\\\\]+$/', ucfirst($has_many['model']), static::class);
            $val = $this->$var = $model::select()->where($has_many['foreign_key'], $this->{$has_many['column']});
        }
        // Belongs to
        else if (is_array(static::$_belongs_to) and (in_array($var, static::$_belongs_to) or isset(static::$_belongs_to[$var]))) {
            $belongs_to = [ // Defaults
                'model' => $var,
                'column' => $var . '_id'
            ];

            if (isset(static::$_belongs_to[$var])) {
                $belongs_to = array_merge($belongs_to, static::$_belongs_to[$var]);
            }

            $model = preg_replace('/[^\\\\]+$/', ucfirst($belongs_to['model']), static::class);
            $belongs_to += ['foreign_key' => $model::$_primary_key];

            $val = $this->$var = $model::find($belongs_to['foreign_key'], $this->{$belongs_to['column']});
        } else {
            $val = $this->$var;
        }

        // Plugin hook
        FishHook::run('model::__get', [static::class, $var, $this->_data, &$val]);

        return $val;
    }

    /**
     * Magical set function to check if the property exists or not.
     */
    public function __set($var, $val) {
        if (in_array($var, static::$_properties)) {
            FishHook::run('model::__set', [static::class, $var, &$val]);
            $this->_data[$var] = $val;
            $this->_set_changed($var);
        } else {
            $this->$var = $val;
        }
    }

    public function __sleep() {
        return array_keys($this->__toArray());
    }

    public function jsonSerialize() {
        return $this->__toArray();
    }

    /**
     * Returns the models data as an array.
     *
     * @return array
     */
    public function __toArray($include = [], $exclude = []) {
        $fields = array_diff($include ?: static::$_properties, $exclude);
        // This is necessary because some data isn't in _data (belongs_to and has_many)
        $data = [];
        foreach($fields as $field) {
            $data[$field] = $this->$field;
        }
        return $data;
    }

    /**
     * Used to add errors to the models error array.
     *
     * @param string $field
     * @param string $message
     */
    public function _add_error($field, $message) {
        $this->errors[$field] = $message;
    }

    /**
     * Adds a data property to the model.
     *
     * @param string $name
     */
    public static function _add_property($name)
    {
        if (!in_array($name, static::$_properties)) {
            static::$_properties[] = $name;
        }
    }

    /**
     * Sets the created_at and updated_at fields when saving.
     */
    private function _timestamps() {
        // Created at field
        if ($this->_is_new() and in_array('created_at', static::$_properties) and !isset($this->_data['created_at'])) {
            $this->_data['created_at'] = Time::gmt();
        }

        // Updated at field
        if (!$this->_is_new() and in_array('updated_at', static::$_properties)) {
            $this->updated_at = Time::gmt();
        }
    }

    /**
     * Converts the created_at, updated_at and published_at properties
     * to local time from gmt time.
     */
    private function _date_time_convert() {
        foreach (['created_at', 'updated_at', 'published_at'] as $var) {
            if (!$this->_is_new() and isset($this->_data[$var])) {
                $this->_data[$var] = Time::gmt_to_local($this->_data[$var]);
            }
        }
    }

    /**
     * Private function to get the database connection.
     *
     * @return object
     */
    protected static function db() {
        return Database::connection(static::$_connection_name);
    }
}
