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

namespace traq\helpers;

use avalon\http\Request;

/**
 * Notification helper.
 *
 * @author Jack P.
 * @since 3.0
 * @package Traq
 * @subpackage Helpers
 */
class Pagination implements \JsonSerializable
{
    public $paginate = false;
    public $total_rows = 0;
    public $per_page = 25;
    public $next_page;
    public $prev_page;
    public $next_page_url;
    public $prev_page_url;
    public $limit;
    public $pages;

    /**
     * Generates pagination information.
     *
     * @param integer $page     Current page
     * @param integer $per_page Rows per page
     * @param integer $rows     Rows in the database
     */
    public function __construct($page, $per_page, $total_rows)
    {
        // Set information
        $this->total_rows = (int)$total_rows;
        $this->per_page = (int)$per_page;
        $this->total_pages = ceil($total_rows / $per_page);
        $this->page = min(max($page, 1), $this->total_pages);

        // More than per-page limit?
        if ($total_rows > $per_page) {
            $this->paginate = true;

            // Next/prev pages
            $this->next_page = ($this->page + 1);
            $this->prev_page = ($this->page - 1);

            // Limit pages
            $this->limit = max($this->page-1 > 0, 0) * $per_page;

            $this->first_page_url = Request::url('', ['page' => 1] + Request::get());
            $this->last_page_url = Request::url('', ['page' => $this->total_pages] + Request::get());

            // Next page URL
            if ($this->next_page <= $this->total_pages) {
                $this->next_page_url = Request::url('', ['page' => $this->next_page] + Request::get());
            }

            // Previous page URL
            if ($this->prev_page > 0) {
                $this->prev_page_url = Request::url('', ['page' => $this->prev_page] + Request::get());
            }

            $range_from = min(max($this->page - 5, 1), $this->total_pages);
            $range_to = min($this->page + 5, $this->total_pages);

            foreach(range($range_from, $range_to) as $page) {
                $this->pages[$page] = Request::url('', ['page' => $page] + Request::get());
            }
        }
    }
    
    public function jsonSerialize()
    {
        return [
            'rowsTotalCount' => $this->total_rows,
            'rowsPerPage' => $this->per_page,
            'pagesTotalCount' => $this->total_pages,
            'pagesCurrent' => $this->page,
        ];
    }
}
