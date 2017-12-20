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
				'command' => $content,
				'status' => 'changed',
				'file_b' => array_pop($path),
				'file_a' => array_pop($path),
				'chunks' => [],
			];
		} else {
			$in_block = null;

			foreach(preg_split('/\r?\n/', rtrim($content, "\r\n")) as $line) {
				if (preg_match('/^@@ -(?:(\d+),)?(\d+) \+(?:(\d+),)?(\d+) @@/', $line, $match)) {
					list($in_block, $a_start, $a_length, $b_start, $b_length) = $match;
					$in_block = $line;
					$file['chunks'][$in_block]['details'] = compact('a_start', 'b_start', 'a_length', 'b_length');
					$file['chunks'][$in_block]['file_a'] =
					$file['chunks'][$in_block]['file_b'] = array_fill(0, max($a_length, $b_length), null);
					$pos_a = $pos_b = $prev = 0;
				} elseif ($in_block) {
					switch($line[0]) {
						case ' ':
							if ($prev === '+' || $prev === '-') {
								$pos_a = $pos_b = max($pos_a, $pos_b);
							}
							$file['chunks'][$in_block]['file_a'][$pos_a++] = $line;
							$file['chunks'][$in_block]['file_b'][$pos_b++] = $line;
							break;
						case '+':
							$file['chunks'][$in_block]['file_b'][$pos_b++] = $line;
							break;
						case '-':
							$file['chunks'][$in_block]['file_a'][$pos_a++] = $line;
							break;
					}
					$file['chunks'][$in_block]['unified'][] = $line;
					$prev = $line[0];
				} else {
					if (preg_match('/^(?:rename from|---) (?<file_a>.+)$/i', $line, $match)) {
						$file['file_a'] = $match['file_a'];
					} elseif (preg_match('/^(?:rename to|\+\+\+) (?<file_b>.+)$/i', $line, $match)) {
						$file['file_b'] = $match['file_b'];
					}
					if (preg_match('/^(new|deleted|rename) /', $line, $match)) {
						$file['status'] = $match[1];
					}
					$file['header'][] = $line;
				}
			}
			$file['file_a'] = preg_replace('#^[ab]/#', '', $file['file_a']);
			$file['file_b'] = preg_replace('#^[ab]/#', '', $file['file_b']);
			$files[] = $file;
		}
	}
	return $files;
}
