<?php
/*!
 * Traq
 * Copyright (C) 2009-2014 Jack Polgar
 * Copyright (C) 2012-2014 Traq.io
 * https://github.com/nirix
 * http://traq.io
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

namespace traq\controllers;

use avalon\core\Controller;
use avalon\http\Request;
use avalon\output\View;
use avalon\core\Load;

use traq\models\Type;
use traq\models\User;
use traq\models\Project;

/**
 * Misc controller
 *
 * @author Jack P.
 * @since 3.0
 * @package Traq
 * @subpackage Controllers
 */
class Misc extends AppController
{
    public $render = [
        'view' => false,
        'action' => true,
        'layout' => 'plain',
    ];

    /**
     * Used to autocomplete usernames
     */
    public function action_autocomplete_username()
    {
        $users = User::select('username')->where('username', str_replace('*', '%', Request::req('term')) . "%", 'LIKE')->exec()->fetch_all();
        die(json_encode(array_get_column($users, 'username')));
    }

    public function action_preview_text()
    {
        $this->title(l('preview'));
        $this->render['layout'] = 'overlay';

        $project = Project::find('slug', Request::post('project'));

        if ($project && !$this->user->permission($project->id, 'view_tickets')) {
            return $this->show_no_permission();
        }

        return format_text(Request::post('data'), true, $project);
    }

    public function action_traq_news()
    {
        if ($data = file_get_contents('http://traq.io/news.json')) {
            $news = json_decode($data);
            foreach($news as $item) {
                $item->content = format_text($item->content);
            }
            return json_encode($news);
        }
        return '[]';
    }
}
