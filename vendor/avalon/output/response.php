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

namespace avalon\output;

class Response
{
    private $status = 200;
    private $format = 'text/html';
    private $redirect = null;
    private $errors = [];
    private $objects = [];
    private $body = '';

    public function __set($var, $val)
    {
        if (isset($this->$var)) {
            $this->$var = $val;
        } else {
            $this->objects[$var] = $val;
        }
    }

    public static function __get($var)
    {
        if (isset($this->$var)) {
            return $this->$var;
        } elseif (isset($this->objects[$var])) {
            return $this->objects[$var];
        }
    }

    public static function body()
    {
        if ($format === 'application/json') {
            return json_encode($this, JSON_UNESCAPED_SLASHES);
        } else {
            return $this->body;
        }
    }
}
