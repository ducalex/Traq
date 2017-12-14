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

use avalon\core\Load;

Load::helper('array');

/**
 * Returns the json encoded version of the passed data.
 *
 * @param mixed $data
 * @param array $options
 *
 * @author Jack P.
 * @copyright Copyright (c) Jack P.
 * @package Traq
 * @subpackage Helpers
 */
function to_json($data, $options = array())
{
    // Merge options with defaults
    $defaults = array('hide' => array('password', 'login_hash', 'api_key', 'private_key'));
    $options = array_merge($defaults, $options);

    // Convert the data to an array, if possible..
    $data = to_array($data);

    // Remove the parts we don't want...
    if (!empty($options['hide'])) {
        $data = array_remove_keys($data, $options['hide']);
    }

    return json_encode($data);
}

/**
 * Returns the mime type for the specified extension.
 *
 * @param string $extension
 *
 * @author Jack P.
 * @copyright Copyright (c) Jack P.
 * @package Traq
 * @subpackage Helpers
 */
function mime_type_for($extension)
{
    // Remove the first dot from the extension
    $extension = ltrim($extension, '.');

    // Mime Types, because, you know....
    $mime_types = array(
        'json' => 'application/json',
        'css'  => 'text/css',
        'js'   => 'text/javascript',
        'rss'  => 'application/rss+xml',
        'atom' => 'application/atom+xml',
        'xml'  => 'application/xml',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'gif'  => 'image/gif',
        'zip'  => 'application/zip',
        'gz'   => 'application/gzip',
    );

    // Check if it's in the allowed mime types array
    if (isset($mime_types[$extension]))
    {
        return $mime_types[$extension];
    }

    // These are the files we want to force to be
    // plain text, we don't want them running on the server.
    $plain_text = array(
        'txt', // Do I really need to explain this?
        'rb',  // Ruby, my favorite, so it's at the top
        'php', // PHP, not my favorite, should be at the bottom
        'pl',  // Perl
        'py',  // Python
        'h',   // Header file
        'c',   // C file
        'cpp',  // C++ file
        'diff',  // Diff file
    );

    // Check if its in the plain text array
    if (isset($plain_text[$extension])) {
        return 'text/plain';
    }

    // Unknown extension, at least at this time,
    // or as Leonard McCoy would say, "Damn it Jim, I'm a Doctor not a File extension!".
    return false;
}
