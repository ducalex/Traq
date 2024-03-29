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

namespace traq\models;

use avalon\database\Model;

/**
 * Plugin model.
 *
 * @package Traq
 * @subpackage Models
 * @author Jack P.
 * @copyright (c) Jack P.
 */
class Plugin extends Model
{
    protected static $_name = 'plugins';
    protected static $_properties = array(
        'id',
        'file',
        'enabled'
    );

    public function get_class()
    {
        $bits = explode('_', $this->_data['file']);
        $bits = array_map('ucfirst', $bits);
        return '\\traq\\plugins\\' . implode($bits);
    }

    public function is_valid()
    {
        // Make sure the file field isnt blank
        return !empty($this->_data['file']) && class_exists($this->get_class());
    }
}
