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

use avalon\database;
use avalon\database\pdo\Query;
use avalon\database\pdo\Statement;
use avalon\core\Error;

/**
 * PDO Database wrapper
 *
 * @package Avalon
 * @subpackage Database
 * @since 0.1
 * @author Jack P. <nrx@nirix.net>
 * @copyright Copyright (c) Jack P.
 */
class PDO implements Driver
{
    private $connection;
    private $connection_name;
    public $query_count = 0;
    public $last_query;

    public $prefix;
    public $type;

    /**
     * PDO wrapper constructor.
     *
     * @param array $config Database config array
     */
    public function __construct(array $config, $name)
    {
        // Lowercase the database type
        $this->connection_name = $name;
        $this->prefix = isset($config['prefix']) ? $config['prefix'] : '';
        $this->type = strtolower($config['type']);

        // Check if a DSN is already specified
        if (isset($config['dsn'])) {
            $dsn = $config['dsn'];
        }
        // SQLite
        elseif ($this->type == 'sqlite') {
            $dsn = "sqlite:{$config['path']}";
        }
        // Something else...
        else {
            $dsn = $this->type . ':dbname=' . $config['database'] . ';host=' . $config['host'];
            if (isset($config['port'])) {
                $dsn = "{$dsn};port={$config['port']}";
            }
        }

        $this->connection = new \PDO(
            $dsn,
            isset($config['username']) ? $config['username'] : null,
            isset($config['password']) ? $config['password'] : null,
            (isset($config['options']) ? $config['options'] : []) + [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
    }

    /**
     * Quotes a string for use in a query.
     *
     * @param string $string
     * @param int $type Paramater type
     */
    public function quote($string, $type = \PDO::PARAM_STR)
    {
        return $this->connection->quote($string, $type);
    }

    /**
     * Executes an SQL statement, returning a result set as a PDOStatement object.
     *
     * @param string $query
     *
     * @return mixed
     */
    public function exec($query)
    {
        $this->query_count++;
        $this->last_query = $query;

        return $this->connection->exec($query);
    }

    /**
     * Prepares a statement for execution and returns a statement object.
     *
     * @param string $query
     *
     * @return object
     */
    public function prepare($query)
    {
        $this->query_count++;
        $this->last_query = $query;
        return new Statement($this->connection->prepare($query), $this);
    }

    public function query($query)
    {
        return $this->prepare($query)->exec();
    }

    /**
     * Returns a select query builder object.
     *
     * @param array $cols Columns to select
     *
     * @return object
     */
    public function select($cols = ['*'])
    {
        if (!is_array($cols)) {
            $cols = func_get_args();
        }
        return new Query("SELECT", $cols, $this);
    }

    /**
     * Returns an update query builder object.
     *
     * @param string $table Table name
     *
     * @return object
     */
    public function update($table)
    {
        return new Query("UPDATE", $table, $this);
    }

    /**
     * Returns a delete query builder object.
     *
     * @return object
     */
    public function delete()
    {
        return new Query("DELETE", null, $this);
    }

    /**
     * Returns an insert query builder object.
     *
     * @param array $data Data to insert
     *
     * @return object
     */
    public function insert(array $data)
    {
        return new Query("INSERT", $data, $this);
    }

    /**
     * Returns a replace query builder object.
     *
     * @param array $data Data to insert/replace
     *
     * @return object
     */
    public function replace(array $data)
    {
        return new Query("REPLACE", $data, $this);
    }

    /**
     * Returns the ID of the last inserted row.
     *
     * @return integer
     */
    public function last_insert_id()
    {
        return $this->connection->lastInsertId();
    }

    public function halt($error = 'unknown')
    {

    }
}
