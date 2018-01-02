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

namespace traq\libraries;

use avalon\output\Response;

/**
 * Atom feed generator.
 *
 */
class AtomResponse extends Response
{
    public $title;
    public $link;
    public $feed_link;
    public $updated;
    public $entries;

    public function __construct($status, $options = [])
    {
        $options['format'] = 'application/atom+xml';
        parent::__construct($status, $options);
    }

    /**
     * Builds the feed.
     *
     * @return string
     */
    public function body()
    {
        $feed = array();

        $feed[] = "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
        $feed[] = "<feed xmlns=\"http://www.w3.org/2005/Atom\">";
        $feed[] = "  <title>{$this->title}</title>";
        $feed[] = "  <link href=\"{$this->link}\" />";
        $feed[] = "  <link href=\"{$this->feed_link}\" rel=\"self\" />";
        $feed[] = "  <updated>{$this->updated}</updated>";


        foreach ($this->entries as $entry) {
            $feed[] = "  <entry>";
            $feed[] = "    <title>{$entry['title']}</title>";
            $feed[] = "    <id>{$entry['id']}</id>";
            $feed[] = "    <updated>{$entry['updated']}</updated>";

            // Link
            if (isset($entry['link'])) {
                $feed[] = "    <link href=\"{$entry['link']}\" />";
            }

            // Summary
            if (isset($entry['summary'])) {
                $feed[] = "    <summary>";
                $feed[] = "        " . htmlspecialchars($entry['summary']);
                $feed[] = "    </summary>";
            }

            // Author
            if (isset($entry['author'])) {
                $feed[] = "    <author>";
                $feed[] = "        <name>{$entry['author']['name']}</name>";
                $feed[] = "    </author>";
            }

            // Content
            if (isset($entry['content'])) {
                $feed[] = "    <content" . (array_key_exists('type', $entry['content']) ? " type=\"{$entry['content']['type']}\"" :'' ) . ">";
                $feed[] = "        " . htmlspecialchars($entry['content']['data']);
                $feed[] = "    </content>";
            }

            $feed[] = "  </entry>";
        }

        $feed[] = "</feed>";

        return implode(PHP_EOL, $feed);
    }
}
