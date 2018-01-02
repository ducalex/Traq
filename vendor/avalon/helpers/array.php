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

/**
 * Removes the specified keys from the array.
 *
 * @param array $array
 * @param array $keys Keys to remove
 * @param bool  $recursive Search for $keys recursively
 *
 * @return array
 *
 * @author Jack P.
 * @package Avalon
 * @subpackage Helpers
 */
function array_remove_keys(array $array, array $keys, $recursive = true)
{
    $array = array_diff_key($array, array_flip($keys));

    // Loop over the array in case we need to recurse
    if ($recursive) {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $value = array_remove_keys($value, $keys);
            }
        }
    }
    
    return $array;
}

/**
 * Get values identified by keys
 *
 * @param array $array
 * @param array $keys Keys to get
 * @param bool  $merge Merge values
 *
 * @return array
 */
function array_get_keys(array $array, array $keys, $merge = true)
{
    $array = array_intersect_key($array, array_flip($keys));

    if ($merge && $array) {
        $array = call_user_func_array('array_merge', $array);
    }

    return $array;
}

/**
 * Merges two arrays recursively.
 * Unlike the standard array_merge_recursive which converts values with duplicate keys to arrays
 * this one overwrites them.
 *
 * @param array $first
 * @param array $second
 *
 * @return array
 */
function array_merge_recursive2(array &$first, array &$second)
{
    $merged = $first;

    foreach ($second as $key => &$value) {
        if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
            $merged[$key] = array_merge_recursive2($merged[$key], $value);
        } else {
            $merged[$key] = $value;
        }
    }

    return $merged;
}
