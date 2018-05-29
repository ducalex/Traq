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

namespace traq\controllers\ProjectSettings;

use avalon\http\Request;
use avalon\output\View;

use traq\models\Repository;

use traq\libraries\SCM;

/**
 * Project repository settings controller
 *
 * @author Jack P.
 * @since 3.0
 * @package Traq
 * @subpackage Controllers
 */
class Repositories extends AppController
{
    public function __construct()
    {
        parent::__construct();
        View::set('scm_types', SCM::adapters());
        View::set('scm_base_directory', settings('scm_base_directory'));
        $this->title(l('repositories'));

        if (!$this->user->permission($this->project->id, 'scm_manage_repositories')) {
            return $this->show_no_permission();
        }
    }

    /**
     * Lists the projects repositories.
     */
    public function action_index()
    {
        $repos = Repository::select()->where('project_id', $this->project->id);
        View::set('repos', $repos);
    }

    /**
     * New repository page.
     */
    public function action_new()
    {
        $repo = new Repository(array('type' => 'git'));

        if (Request::method() == 'post') {
            $this->_save($repo);
        }

        // Pass the repo info to the view.
        View::set('repo', $repo);
    }

    /**
     * delete repository page.
     */
    public function action_delete($id)
    {
        $this->title(l('delete'));

        // Fetch the milestone
        $repo = Repository::find($id);

        if ($repo->project_id !== $this->project->id) {
            return $this->show_no_permission();
        }

        // Delete milestone
        $this->response->status = $repo->delete();
        $this->response->redirect = $this->project->href("settings/repositories");
    }

    /**
     * Edit repository page.
     */
    public function action_edit($id)
    {
        $this->title(l('edit'));

        $repo = Repository::find($id);

        if ($repo->project_id !== $this->project->id) {
            return $this->show_no_permission();
        }

        if (Request::method() == 'post') {
            $this->response->status = $this->_save($repo);
        }

        // Pass the repo info to the view.
        View::set('repo', $repo);
    }

    private function _save($repo)
    {
        // Set the information
        $repo->set(array(
            'slug'       => Request::post('slug', $repo->slug),
            'type'       => Request::post('type', $repo->type),
            'location'   => Request::post('location', $repo->location),
            'is_default' => Request::post('is_default', 0),
            'project_id' => $this->project->id,
            //'extra'      => Request::post('extra', $repo->extra),
            'serve'      => Request::post('serve', $repo->serve),
        ));

        // Get the SCM class
        $scm = SCM::factory($repo->type, $repo);
        $target = settings('scm_base_directory');

        if (!$repo->location && $target) {
            if (!is_dir($target) || !is_writable($target)) {
                throw new Exception('Repository base directory invalid, check Traq\'s settings');
            }

            $repo->location = settings('scm_base_directory') . '/' . $this->project->slug . '/' . basename($repo->slug);

            if ($repo->is_valid() && !$scm->create()) {
                $repo->_add_error('creation', l('errors.scm.unable_to_create_directory'));
                $repo->location = '';
            }
        }

        if (!$scm->exists()) {
            $repo->_add_error('location', l('errors.scm.location_not_a_repository'));
            $repo->_add_error('cmd', $scm->last_error());
        }

        // Check if data is good
        if (!$repo->is_valid() || !$repo->save()) {
            View::set('errors', $repo->errors);
        } else {
            Request::redirectTo($this->project->href('settings/repositories'));
        }
    }
}
