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
    'js'  => ['mime' => 'text/javascript', 'ext' => 'js', 'dir' => 'js','minimize' => false],
    'css' => ['mime' => 'text/css', 'ext' => 'css', 'dir' => 'css', 'minimize' => true],
    'img' => ['mime' => 'image/png', 'ext' => '{png,jpg,gif}', 'dir' => '{images,img}', 'minimize' => false],
];

if ($request = array_intersect_key($_GET, $types)) {
    $req_files = reset($request);
    extract($types[key($request)]);

    if ($req_files === 'all') {
        $req_files = '*';
    }
} else {
    header("HTTP/1.0 404 Not Found");
    exit;
}


header('Content-Type: '.$mime.'; charset: UTF-8;');
header('Expires: '.gmdate("D, d M Y H:i:s e", time() + 1800));
header('Pragma: cache');
header('Cache-Control: max-age=1800');

ini_set('zlib.output_compression', 'on');

$files = $search = [];

if (!empty($_GET['plugin'])) {
    $search[] = __DIR__ . "/vendor/traq/plugins/{$_GET['plugin']}/assets/{$dir}/{{$req_files}}.{$ext}";
} else {
    $search[] = __DIR__ . "/assets/{$dir}/{{$req_files}}.{$ext}";
}

if (!empty($_GET['theme'])) {
    $theme_files = isset($_GET['theme_files']) ? $_GET['theme_files'] : 'default';
    $search[] = __DIR__ . "/vendor/traq/views/{$_GET['theme']}/{$dir}/{{$theme_files}}.{$ext}";
}

foreach($search as $glob) {
    if (strpos($glob, '../') === false) { // avoid going outside the base if someone tries to inject a path
        $files = array_merge($files, glob($glob, GLOB_BRACE));
    }
}

$output = array_map('file_get_contents', $files);

if ($minimize) {
    $output = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $output);
    $output = preg_replace('/\s*(,|;|:|{|})\s*/', '$1', $output);
    $output = preg_replace('/\s+/', ' ', $output);
}

// Display all the files.
echo implode("\n/* -------------------- */\n", $output);
