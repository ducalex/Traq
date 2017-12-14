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

function time_from_now_ago($original, $detailed = true, $include_from_now_ago = true)
{
    if (Time::to_unix($original) > time()) {
        return time_from_now($original, $detailed, $include_from_now_ago);
    } else {
        return time_ago($original, $detailed, $include_from_now_ago);
    }
}

/**
 * Returns the time ago in words wrapped in a span
 * with the full date as the hover title.
 *
 * @param mixed $original
 * @param bool $detailed Include "and seconds/minutes/etc"
 * @param bool $include_ago Appends the word "ago" to the end
 *
 * @return string
 */
function time_ago($original, $detailed = true, $include_ago = true)
{
    $original = Time::to_unix($original);

    $datetime = ldate("l, jS F Y @ g:ia P", $original); //Time::date('Y-m-d H:i:s', $original);
    $time_ago = $include_ago ? l('time.ago', time_difference_in_words($original, $detailed)) : time_difference_in_words($original, $detailed);
    return "<span title=\"{$datetime}\">{$time_ago}</span>";
}

/**
 * Returns the time from now in words wrapped in a span
 * with the full date as the hover title.
 *
 * @param mixed $original
 * @param bool $detailed Include "and seconds/minutes/etc"
 * @param bool $include_ago Appends the word "ago" to the end
 *
 * @return string
 */
function time_from_now($original, $detailed = true, $include_from_now = true)
{
    $original = Time::to_unix($original);

    $datetime = ldate('l, jS F Y @ g:ia P', $original);
    $time_ago = $include_from_now ? l('time.from_now', time_difference_in_words($original, $detailed)) : time_difference_in_words($original, $detailed);
    return "<span title=\"{$datetime}\">{$time_ago}</span>";
}

/**
 * Returns the time ago in words.
 *
 * @param mixed $original
 * @param bool $detailed Include "and seconds/minutes/etc"
 *
 * @return string
 */
function time_difference_in_words($original, $detailed = true)
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
    $from = l("time.x_{$name}", $count);

    // Get the detailed time from if the detaile variable is true
    if ($detailed && isset($chunks[++$i])) {
        list($seconds2, $name2, $names2) = $chunks[$i];
        if ($count2 = floor(($difference - $seconds * $count) / $seconds2)) {
            $from = l('time.x_and_x', $from, l("time.x_{$name2}", $count2));
        }
    }

    // Return the time from
    return $from;
}
