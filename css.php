<?php
/*
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

// Set the content type and charset.
header('Content-Type: text/css; charset: UTF-8;');
header('Expires: '.gmdate("D, d M Y H:i:s e", time() + 1800));
header('Pragma: cache');
header('Cache-Control: max-age=1800');

// Check for the CSS index in the request array..
if (empty($_REQUEST['css']) && empty($_REQUEST['theme'])) {
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
    $base = __DIR__ . '/assets/css/';
} else {
    $plugin = basename($_REQUEST['plugin']);
    $base = __DIR__ . "/vendor/traq/plugins/$plugin/assets/css/";
}

if ($_REQUEST['css'] === 'all') {
    $files = glob("$base/*.css");
} else {
    foreach(explode(',', $_REQUEST['css']) as $file) {
        $files[] = "$base/$file.css";
    }
}

// Theme CSS files
if (isset($_REQUEST['theme'])) {
    $theme = htmlspecialchars($_REQUEST['theme']);
    $theme_files = isset($_REQUEST['theme_files']) ? $_REQUEST['theme_files'] : 'default';

    foreach(explode(',', $theme_files) as $file) {
        $files[] = __DIR__ . "/vendor/traq/views/$theme/css/$file.css";
    }
}

$files = array_filter($files, function($path) {
    return strpos($path, '../') === false && file_exists($path);
});

$output = array_map('file_get_contents', $files);

// Remove comments and such from the output.
$output = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $output);
$output = preg_replace('/\s*(,|;|:|{|})\s*/', '$1', $output);
$output = preg_replace('/\s+/', ' ', $output);

echo implode($output);
