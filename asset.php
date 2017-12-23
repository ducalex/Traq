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

$types = [
    'js' => ['mime' => 'text/javascript', 'ext' => 'js', 'minimize' => false],
    'css' => ['mime' => 'text/css', 'ext' => 'css', 'minimize' => true],
    'img' => ['mime' => 'image/png', 'ext' => '{png,jpg,gif}', 'minimize' => false],
];

if ($request = array_intersect_key($_GET, $types)) {
    $req_files = reset($request);
    $type = key($request);
} else {
    exit;
}

// Set content type and charset.
header('Content-Type: '.$types[$type]['mime'].'; charset: UTF-8;');
header('Expires: '.gmdate("D, d M Y H:i:s e", time() + 1800));
header('Pragma: cache');
header('Cache-Control: max-age=1800');

// Check if we can gzip the page or not/
if (extension_loaded('zlib')) {
    // We can!
    ob_end_clean();
    ob_start('ob_gzhandler');
}

$files = $search = [];

if (!empty($_GET['plugin'])) {
    $plugin = basename($_GET['plugin']);
    $search[] = __DIR__ . "/vendor/traq/plugins/$plugin/assets/$type/";
} else {
    $search[] = __DIR__ . "/assets/$type/";
}

if (!empty($_GET['theme'])) {
    $theme = basename($_GET['theme']);
    $search[] = __DIR__ . "/vendor/traq/views/$theme/$type/";
}

if ($req_files === 'all') {
    $glob = '*.' . $types[$type]['ext'];
} else {
    $req_files = strtr($req_files, ['../' => '/', '{' => '', '}' => '']); // avoid going outside the base
    $glob = '{' . $req_files . '}.' . $types[$type]['ext'];
}


foreach($search as $base) {
    $files = array_merge($files, glob($base . $glob, GLOB_BRACE));
}

$output = array_map('file_get_contents', $files);

if ($types[$type]['minimize']) {
    $output = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $output);
    $output = preg_replace('/\s*(,|;|:|{|})\s*/', '$1', $output);
    $output = preg_replace('/\s+/', ' ', $output);
}

// Display all the files.
echo implode("\n/* -------------------- */\n", $output);
