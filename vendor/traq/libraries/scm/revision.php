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

namespace traq\libraries\scm;

class Revision
{
    // This class should store the following information
    public $id;       // string  Revision ID/hash
    public $full_id;  // string  Long form of revision ID/hash
    public $parents;  // array   Array of $id of parents
    public $author;   // string  Name of author
    public $email;    // string  Email of author
    public $date;     // integer Unix timestamp
    public $subject;  // string  Summary of commit
    public $message;  // string  Full commit message
    public $diff;     // string  Diff text (if any)
    public $is_first; // bool    Is it the first commit
    public $is_merge; // bool    Is it a merge


    public function __construct(array $properties)
    {
        foreach($properties as $key => $value) {
            $this->$key = $value;
        }
    }
}
