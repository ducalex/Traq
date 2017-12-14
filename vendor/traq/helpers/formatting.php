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
use \traq\models\Repository;
use \traq\libraries\SCM;

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

    // SCM links
    $text = scm_links($text, $project);

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

/**
 * Links to the corresponding commit (pattern from default repository. for git it will be 7 chars hex, for svn it is r123)
 *
 * @param string $text
 *
 * @return string
 */
function scm_links($text, $project)
{
    $repository = Repository::select()->where('project_id', $project->id)->where('is_default', 1)->exec()->fetch();

    if ($repository && $scm = SCM::factory($repository->type, $repository)) {
        return preg_replace_callback(
            '!\b'.$scm::link_pattern.'\b!',
            function($match) use($repository, $scm) {
                //if ($revision = $scm->revision($match[2])) {
                //    $match[2] = HTML::link($match[2], $repository->href("commit/{$match[2]}"), ['title' => $revision->subject]);
                // This is best until we have caching allowing us to get content fast
                if ($scm->revision_exists($match[1])) {
                    return HTML::link($match[1], $repository->href("commit/{$match[1]}"));
                }
                return $match[1];
            },
            $text
        );
    }
    return $text;
}
