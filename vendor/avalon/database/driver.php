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
use avalon\core\Error;

/**
 * Database driver base.
 *
 * @package Avalon
 * @subpackage Database
 * @since 0.1
 * @author Jack P. <nrx@nirix.net>
 * @copyright Copyright (c) Jack P.
 */
abstract class Driver
{
    public abstract function halt($error = 'Unknown error');
    public abstract function quote($string, $type = \PDO::PARAM_STR);
    public abstract function exec($query);
    public abstract function prepare($query);
    public abstract function query($query);
    public abstract function select($cols = ['*']);
    public abstract function update($table);
    public abstract function delete();
    public abstract function insert(array $data);
    public abstract function replace(array $data);
    public abstract function last_insert_id();
}