<?php
/*!
 * Avalon
 * Copyright (C) 2011-2014 Jack Polgar
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

namespace avalon\helpers;

/**
 * Time Helper
 *
 * @author Jack P.
 * @package Avalon
 * @subpackage Helpers
 */
class Time
{
    /**
     * Formats the date.
     *
     * @param string $format Date format
     * @param mixed $time Date in unix time or date-time format.
     *
     * @return string
     */
    public static function date($format = "Y-m-d H:i:s", $time = 'now')
    {
        return date($format, static::to_unix($time));
    }

    /**
     * Formats the GMT date.
     *
     * @param string $format Date format
     * @param mixed $time Date in unix time or date-time format.
     *
     * @return string
     */
    public static function gmt($format = "Y-m-d H:i:s", $time = 'now')
    {
        return gmdate($format, static::to_unix($time));
    }

    /**
     * Converts the given GMT time to local time.
     *
     * @param string $datetime
     *
     * @return string
     */
    public static function gmt_to_local($datetime)
    {
        return date("Y-m-d H:i:s", static::to_unix($datetime) + idate('Z'));
    }

    /**
     * Converts a datetime timestamp into a unix timestamp.
     *
     * @param datetime $original
     *
     * @return mixed
     */
    public static function to_unix($original)
    {
        return ctype_digit($original) ? $original : strtotime($original);
    }

    /**
     * Returns time ago in words of the given date.
     *
     * @param string $original
     * @param bool $detailed
     *
     * @return string
     */
    public static function ago_in_words($original, $detailed = true)
    {
        return static::difference_in_words($original, $detailed);
    }

    /**
     * Returns time difference in words for the given date.
     *
     * @param string $original
     * @param bool $detailed
     *
     * @return string
     */
    public static function difference_in_words($original, $detailed = true)
    {
        $original = Time::to_unix($original);
        $now = time(); // Get the time right now...
    
        // Time chunks...
        $chunks = array(
            array(60 * 60 * 24 * 365, 'year', 'years'),
            array(60 * 60 * 24 * 30, 'month', 'months'),
            array(60 * 60 * 24 * 7, 'week', 'weeks'),
            array(60 * 60 * 24, 'day', 'days'),
            array(60 * 60, 'hour', 'hours'),
            array(60, 'minute', 'minutes'),
            array(1, 'second', 'seconds'),
        );
    
        // Get the difference
        $difference = abs($original - $now);
    
        // Loop around, get the time from
        foreach($chunks as $i => list($seconds, $name, $names)) {
            if ($count = floor($difference / $seconds)) break;
        }

        // Format the time from
        $from = $count . " " . (1 == $count ? $name : $names);

        // Get the detailed time from if the detailed variable is true
        if ($detailed && isset($chunks[++$i])) {
            list($seconds2, $name2, $names2) = $chunks[$i];
            if ($count2 = floor(($difference - $seconds * $count) / $seconds2)) {
                $from = $from . " and " . $count2 . " " . (1 == $count2 ? $name2 : $names2);
            }
        }

        // Return the time from
        return $from;
    }
}
