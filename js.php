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

// Set content type and charset.
header('Content-Type: text/javascript; charset: UTF-8;');
header('Expires: '.gmdate("D, d M Y H:i:s", time() + 1800).' GMT');
header('Pragma: cache');
header('Cache-Control: max-age=1800');

// Make sure there are files to load.
if (empty($_REQUEST['js'])) {
    exit;
}

// Check if we can gzip the page or not/
if (extension_loaded('zlib')) {
    // We can!
    ob_end_clean();
    ob_start('ob_gzhandler');
}

$files = [];

if (empty($_REQUEST['plugin'])) {
    $base = __DIR__ . '/assets/js/';
} else {
    $plugin = basename($_REQUEST['plugin']);
    $base = __DIR__ . "/vendor/traq/plugins/$plugin/assets/js/";
}

if ($_REQUEST['js'] === 'all') {
    $files = glob("$base/*.js");
} else {
    foreach(explode(',', $_REQUEST['js']) as $file) {
        $files[] = "$base/$file.js";
    }
}

$files = array_filter($files, function($path) {
    // or realpath($path) === $path ?
    return strpos($path, '../') === false && file_exists($path);
});

$output = array_map('file_get_contents', $files);

// Display all the files.
echo implode("\n/* -------------------- */\n", $output);
