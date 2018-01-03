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
 * PDO Database wrapper statement class
 *
 * @package Avalon
 * @subpackage Database
 * @since 0.1
 * @author Jack P. <nrx@nirix.net>
 * @copyright Copyright (c) Jack P.
 */
class Statement implements \Countable
{
    private $connection;
    private $statement;
    private $_model;
    private $results = null;
    private $count = 0;
    private $cursor = 0;

    /**
     * PDO Statement constructor.
     *
     * @param $statement
     *
     * @return void
     */
    public function __construct($statement, $connection)
    {
        $this->statement = $statement;
        $this->connection = $connection;
    }

    /**
     * Sets the model for the rows to use.
     *
     * @param string $model
     *
     * @return object
     */
    public function _model($model)
    {
        $this->_model = $model;
        return $this;
    }

    /**
     * Fetches all the rows.
     *
     * @return array
     */
    public function fetch_all()
    {
        $this->cursor = 0;
        $rows = [];

        while($row = $this->fetch()) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Fetches the next row from a result set.
     *
     * @param int $offset
     *
     * @return object|array
     */
    public function fetch($offset = null)
    {
        if ($offset === null) {
            $offset = &$this->cursor;
        }

        if (!isset($this->results[$offset])) {
            return false;
        }

        $result = $this->results[$offset++];

        if ($this->_model === null) {
            return $result;
        } else {
            $model = $this->_model;
            return new $model($result, false);
        }
    }

    /**
     * Binds a parameter to the specified variable name.
     *
     * @param mixed $param Parameter
     * @param mixed &$value Variable
     * @param integer $type Data type
     * @param integer $length Length
     * @param mixed $options Driver options
     *
     * @return object
     */
    public function bind_param($param, &$value, $type = \PDO::PARAM_STR, $length = 0, $options = [])
    {
        $this->statement->bindParam($param, $value, $type, $length, $options);
        return $this;
    }

    /**
     * Binds a value to a parameter.
     *
     * @param mixed $param Parameter
     * @param mixed $value Value
     * @param integer $type Data type
     *
     * @return object
     */
    public function bind_value($param, $value, $type = \PDO::PARAM_STR)
    {
        $this->statement->bindValue($param, $value, $type);
        return $this;
    }

    /**
     * Executes a prepared statement.
     *
     * @return object
     */
    public function exec()
    {
        $this->statement->execute();
        if ($this->statement->columnCount() > 0) {
            $this->results = $this->statement->fetchAll(\PDO::FETCH_ASSOC);
            $this->count = count($this->results);
        } else {
            $this->count = $this->statement->rowCount();
        }
        return $this;
    }

    /**
     * Returns the number of rows affected by the last SQL statement.
     *
     * @return integer
     */
    public function count()
    {
        return $this->count;
    }
}
