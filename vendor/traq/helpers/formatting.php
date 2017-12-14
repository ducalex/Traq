<?php
/*!
 * Traq
 * Copyright (C) 2009-2013 Traq.io
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

use \avalon\core\Kernel as Avalon;
use \traq\models\Project;

/**
 * Formats the supplied text.
 *
 * @param string $text
 * @param bool $strip_html Disables HTML, making it safe.
 * @param object $project Project to be used in links
 *
 * @return string
 */
function format_text($text, $strip_html = true, $project = null)
{
    $text = $strip_html ? htmlspecialchars($text) : $text;
    $project = $project ?: Avalon::app()->project;

    FishHook::run('function:format_text', array(&$text, $strip_html));

    // Ticket links
    $text = ticket_links($text, $project);

    // Wiki links
    $text = wiki_links($text, $project);

    return $text;
}

/**
 * Links #123 and project#123 to the corresponding ticket.
 *
 * @param string $text
 *
 * @return string
 */
function ticket_links($text, $project)
{
    return preg_replace_callback(
        "|(?:[\w\d\-_]+)?#([\d]+)|",
        function($matches){
            $match = explode('#', $matches[0]);

            // switch project project#123
            if (isset($match[1])) {
                $project = Project::find('slug', $match[0]) ?: $project;
            }

            return HTML::link($matches[0], $project->href("tickets/{$match[1]}"));
        },
        $text
    );
}

/**
 * Converts the wiki [[page]] and [[text|page]] to HTML links.
 *
 * @param string $text
 *
 * @return string
 */
function wiki_links($text, $project)
{
    return preg_replace_callback(
        "|\[\[(?P<page>[\w\d\-_]+)(\|(?P<text>[\s\w\d\-_]+))?\]\]|",
        function($matches) use($project) {

            if (!isset($matches['text'])) {
                $matches['text'] = $matches['page'];
            }

            return HTML::link($matches['text'], $project->href("wiki/{$matches['page']}"));
        },
        $text
    );
}
