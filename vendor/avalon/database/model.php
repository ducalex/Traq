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
    protected static $_properties = []; // Table columns
    protected static $_has_many = []; // Has many relationship array
    protected static $_belongs_to = []; // Belongs to relationship array
    protected static $_filters_before = []; // Before filters
    protected static $_filters_after = []; // After filters
    protected static $_connection_name = 'main'; // Name of the connection to use
    protected static $_serialize = []; // Columns to serialize to json when reading/writing database
    protected static $_escape = []; // Columns to escape when reading from database
    protected static $_timestamp = ['created_at', 'updated_at', 'published_at']; // Columns to convert to GMT timestamp

    // Information different per table row
    protected $_original = []; // The original data coming from the database
    protected $_data = []; // The working dataset containing both old and new data
    protected $_is_new = true; // Used to determine if this is a new row or not.
    protected $errors = [];

    /**
     * Used to build to assign the row data to the class as variables.
     *
     * @param array $data The row data
     */
    public function __construct(array $data = [], $is_new = true) {
        // Is there any data?
        // If so get the columns and add them to
        // the properties array
        static::$_properties = array_merge(static::$_properties, array_keys($data));

        // Some special cases
        foreach ($data as $column => $value) {
            if (!$is_new and in_array($column, static::$_serialize)) { // Unserialize only if it comes from the database
                $data[$column] = json_decode($value, true);
            } elseif (in_array($column, static::$_escape)) {
                $data[$column] = htmlspecialchars($value);
            } elseif (!$is_new and in_array($column, static::$_timestamp)) {
                $data[$column] = Time::gmt_to_local($data[$column]);
            }
        }

        $this->_data = $data;
        $this->_original = $is_new ? [] : $data;
        $this->_is_new = (bool)$is_new;

        // Create filter arrays if they aren't already
        foreach (['construct', 'create', 'save', 'delete'] as $filter) {
            // Before filters
            if (empty(static::$_filters_before[$filter])) {
                static::$_filters_before[$filter] = [];
            }

            // After filters
            if (empty(static::$_filters_after[$filter])) {
                static::$_filters_after[$filter] = [];
            }
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
        $action = $this->_is_new ? 'create' : 'save';
        $data = [];
 
        // Before save filters
        foreach (static::$_filters_before[$action] as $filter) {
            $this->$filter();
        }
        
        // Created at field
        if ($this->_is_new and in_array('created_at', static::$_properties) and !isset($data['created_at'])) {
            $this->_data['created_at'] = Time::date('Y-m-d H:i:s');
        }
        // Updated at field
        if (in_array('updated_at', static::$_properties) and !isset($data['updated_at'])) {
            $this->_data['updated_at'] = Time::date('Y-m-d H:i:s');
        }
        
        foreach ($this->_data as $column => $value) {
            if (!array_key_exists($column, $this->_original) || $this->_original[$column] !== $value) {
                if (in_array($column, static::$_escape)) {
                    $data[$column] = htmlspecialchars_decode($this->_data[$column]);
                } elseif (in_array($column, static::$_serialize)) {
                    $data[$column] = json_encode($this->_data[$column]);
                } elseif (in_array($column, static::$_timestamp)) {
                    $data[$column] = Time::gmt('Y-m-d H:i:s', $this->_data[$column]);
                } else {
                    $data[$column] = $this->_data[$column];
                }
            }
        }
        
        FishHook::run('model::save/'.$action, [static::class, &$data]);

        // Create
        if ($this->_is_new) {
            static::db()->insert($data)->into(static::$_name)->exec();
            if (0 < $id = static::db()->last_insert_id()) {
                $this->_data[$primary_key] = $id;
            }
        }
        // Update if there is something to update
        elseif ($data) {
            static::db()->update(static::$_name)->set($data)->where($primary_key, $this->_original[$primary_key])->exec();
        }
        
        // Sync our original data reference
        $this->_original = $this->_data;
        
        return true;
    }

    /**
     * Deletes the row.
     */
    public function delete() {
        if ($this->_is_new) {
            return false;
        }
        // Before delete filters
        foreach (static::$_filters_before['delete'] as $filter) {
            $this->$filter();
        }
        return static::db()->delete()->from(static::$_name)->where(static::$_primary_key, $this->_data[static::$_primary_key])->exec();
    }

    /**
     * Checks if the row is new or not.
     *
     * @return bool
     */
    public function is_new($is_new = null) {
        if ($is_new !== null) {
            if ($this->_is_new = (bool)$is_new) {
                $this->_original = [];
            }
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
            // Plugin hook
            FishHook::run('model::set', [static::class, $col, $this->_data, &$val]);

            $this->_data[$col] = $val;

            if (!in_array($val, static::$_properties)) {
                static::$_properties[] = $val;
            }
        }
    }

    /**
     * Checks if a property is dirty (isn't saved)
     *
     * @param string $property
     * 
     * @return bool
     */
    public function is_dirty($property = null) {
        if ($property === null) {
            return $this->_original !== $this->_data;
        } else {
            return (!isset($this->_original[$property], $this->_data[$property])
                    || $this->_original[$property] !== $this->_data[$property]);
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
            $val = array_key_exists($var, $this->_data) ? $this->_data[$var] : '';
        }
        // Has many
        elseif (in_array($var, static::$_has_many) or isset(static::$_has_many[$var])) {
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
        else if (in_array($var, static::$_belongs_to) or isset(static::$_belongs_to[$var])) {
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
        FishHook::run('model::get', [static::class, $var, $this->_data, &$val]);

        return $val;
    }

    /**
     * Magical set function to check if the property exists or not.
     */
    public function __set($var, $val) {
        if (in_array($var, static::$_properties)) {
            $this->set($var, $val);
        } else {
            $this->$var = $val;
        }
    }

    public function __sleep() {
        return array_keys($this->toArray());
    }

    public function jsonSerialize() {
        return $this->toArray();
    }

    /**
     * Returns the models data as an array.
     *
     * @return array
     */
    public function toArray($include = [], $exclude = []) {
        $fields = array_diff($include ?: static::$_properties, $exclude);
        // This is necessary because some data isn't in _data (belongs_to and has_many)
        $data = [];
        foreach($fields as $field) {
            $data[$field] = $this->$field;
            if ($data[$field] instanceOf self) {
                $data[$field] = $data[$field]->toArray();
            }
        }

        // Plugin hook
        FishHook::run('model::serialize', [static::class, $include, $exclude, $this->_data, &$data]);

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
     * Private function to get the database connection.
     *
     * @return object
     */
    protected static function db() {
        return Database::connection(static::$_connection_name);
    }
}
