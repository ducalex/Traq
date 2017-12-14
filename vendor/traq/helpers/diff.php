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


/**
 * Parse unified diff format and returns a structured array
 *
 * @param string $diff
 * @return array
 */
function parse_diff($diff)
{
	$chunks = preg_split('/^(diff .+)$/m', $diff, -1, PREG_SPLIT_DELIM_CAPTURE);
	$chunks = array_slice($chunks, 1);

	foreach($chunks as $i => $content) {
		if ($i % 2 === 0) {
			$path = explode(' ', $content);
			$file = [
				'diff' => $content,
				'status' => 'changed',
				'file_b' => array_pop($path),
				'file_a' => array_pop($path),
			];
		} else {
			foreach(preg_split('/\r?\n/', $content) as $line) {
				list($word, $rest) = explode(' ', $line, 2);
				if ($word === '@@' || $word === '@@@') {
					break;
				} elseif ($word === '---') {
					$file['file_a'] = $rest;
				} elseif ($word === '+++') {
					$file['file_b'] = $rest;
				} elseif ($word === 'rename') {
					$file['status'] = $word;
					list($direction, $name) = explode(' ', $rest, 2);
					if ($direction === 'from') $file['file_a'] = $name;
					if ($direction === 'to')   $file['file_b'] = $name;
				} elseif ($word === 'new' || $word === 'delete') {
					$file['status'] = $word;
				}
			}
			$file['file_a'] = preg_replace('#^[ab]/#', '', $file['file_a']);
			$file['file_b'] = preg_replace('#^[ab]/#', '', $file['file_b']);
			$file['content'] = trim($content, "\r\n");
			$files[] = $file;
		}
	}

	return $files;
}
