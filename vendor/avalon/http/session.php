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

class Session
{
    private static $session = [];
    private static $last_activity = 0;


    public static function start($name = APPNAME, $lifetime = 0, $path = '/')
    {
        session_start([
            'name' => $name,
            'cookie_lifetime' => $lifetime,
            'gc_maxlifetime' => $lifetime ?: 1440,
            'cookie_path' => $path,
        ]);

        static::$session = &$_SESSION;
        static::$last_activity = isset(static::$session['__activity']) ? static::$session['__activity'] : time();
        static::$session['__activity'] = time();

        if (!isset(static::$session['__created'])) {
            static::$session['__created'] = time();
        }
    }

    
    public static function stop()
    {
        session_write_close();
    }


    public static function clear()
    {
        static::$session = [];
    }


    public static function reset()
    {
        return session_regenerate_id(true);
    }


    public static function get($key)
    {
        return isset(static::$session[$key]) ? static::$session[$key] : null;
    }


    public static function set($key, $value)
    {
        static::$session[$key] = $value;
    }


    public static function age()
    {
        return time() - static::$session['__created'];
    }


    public static function idle()
    {
        return time() - static::$last_activity;
    }
}
