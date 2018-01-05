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

class Response implements \ArrayAccess
{
    public $status = 200;
    public $format = 'text/html';
    public $redirect;
    public $errors = [];
    public $objects = [];
    public $body = '';


    public function __construct($status = 200, array $options = [])
    {
        $this->status = $status;
        foreach($options as $key => $val) {
            $this->$key = $val;
        }
    }

    public function body()
    {
        if ($this->format === 'application/json' && $this->body === '') {
            return json_encode($this->objects, JSON_UNESCAPED_SLASHES);
        } else {
            return $this->body;
        }
    }

    public function status()
    {
        if (in_array($this->status, [200, 400, 401, 402, 403, 404, 500])) {
            return $this->status;
        }

        return $this->status ? 200 : 400;
    }

    public function offsetExists($offset)
    {
        return isset($this->objects[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->objects[$offset]) ? $this->objects[$offset] : null;
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->body .= $value; // $this->response[] = 'text...';
        } else {
            $this->objects[$offset] = $value; // $this->response['object'] = 'value...';
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->objects[$offset]);
    }

    public function from(Response $response)
    {
        return new static($response->status, (array)$response);
    }
}
