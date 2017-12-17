<?php
/*!
* Session
* Copyright (C) 2009-2012 Jack P.
* https://github.com/nirix
*
* FishHook is free software: you can redistribute it and/or modify
* it under the terms of the GNU Lesser General Public License as published by
* the Free Software Foundation; version 3 only.
*
* FishHook is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU Lesser General Public License for more details.
*
* You should have received a copy of the GNU Lesser General Public License
* along with FishHook. If not, see <http://www.gnu.org/licenses/>.
*/

namespace avalon\http;

class Cookie
{
    public static function get($key)
    {
        return isset($_COOKIE[$key]) ? $_COOKIE[$key] : null;
    }

    public static function set($name, $value, $expire, $path, $domain, $secure)
    {
        if (\call_user_func_array('setcookie', \func_get_args())) {
            $_COOKIE[$name] = $value;
            return true;
        }
        return false;
    }

    public static function delete($name)
    {
        if (setcookie($name, '', time() - 3600, '/')) {
            unset($_COOKIE[$name]);
            return true;
        }
        return false;
    }
}
