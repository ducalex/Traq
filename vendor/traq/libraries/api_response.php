<?php
/*!
 * Traq
 * Copyright (C) 2009-2013 Traq.io
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

namespace traq\libraries;

use avalon\output\Response;

class ApiResponse extends Response
{
    public function __construct($status, $options = [])
    {
        $options['format'] = 'application/json';
        parent::__construct($status, $options);
    }

    public function body()
    {
        return json_encode([
            'status' => $this->status,
            'version' => TRAQ_API_VER,
            'errors' => $this->errors,
        ] + $this->objects, JSON_UNESCAPED_SLASHES);
    }
}