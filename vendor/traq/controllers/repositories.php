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

namespace traq\controllers;

use avalon\http\Request;
use avalon\http\Router;
use avalon\output\View;

use traq\models\Repository;
use traq\models\User;

use traq\libraries\SCM;
use traq\helpers\Pagination;

class Repositories extends AppController
{
    public function __construct()
    {
        parent::__construct();

        if (!$this->user->permission($this->project->id, 'scm_browse_repositories')) {
            return $this->show_no_permission(true);
        }

        $repositories = Repository::select()->where('project_id', $this->project->id)->order_by('is_default', 'desc')->exec()->fetch_all();

        if (empty($repositories)) {
            return $this->show_error('Oops', 'This project has no repository');
        }

        if (empty(Router::$params['slug'])) {
            $this->repository = $repositories[0];
        } else {
            foreach($repositories as $repository) {
                if (strcasecmp($repository->slug, Router::$params['slug']) === 0) {
                    $this->repository = $repository;
                }
            }
        }

        if (empty($this->repository)) {
            return $this->show_404();
        }

        $this->title(l('repository') . ': ' . $this->repository->slug);

        $this->scm = SCM::factory($this->repository->type, $this->repository);
        $branches = $this->scm->branches();
        $tags = $this->scm->tags();
        $default_branch = $this->scm->default_branch();

        $nav_tags = $nav_branches = $nav_commits = [];

        foreach($tags as $tag) {
            $nav_tags[] = ['value' => $tag, 'label' => $tag];
        }

        foreach($branches as $branch) {
            $nav_branches[] = ['value' => $branch, 'label' => $branch];
        }

        $this->target = Router::$params['revision'] ?: $default_branch;

        if (!in_array($this->target, $branches) && !in_array($this->target, $tags)) { // Then it's probably a revision
            $nav_commits[] = ['value' => $this->target, 'label' => '@'.$this->target];
        }

        View::set([
            'repositories' => $repositories,
            'repository' => $this->repository,
            'target' => $this->target,
            'default_branch' => $default_branch,

            'nav_branches' => $nav_branches,
            'nav_tags' => $nav_tags,
            'nav_commits' => $nav_commits,
            'nav_title' => null
        ]);
    }


    public function action_commits($branch = null, $path = null)
    {
        $target = $branch ?: $this->target;
        $extra = $this->repository->extra;
        $cache = &$extra['cache'][$target];

        if (empty($cache['rev_count']) || $cache['expires'] < time() || $cache['last_modified'] != $this->scm->last_modified()) {
            $cache = [
                'expires' => time() + 3600,
                'last_modified' => $this->scm->last_modified(),
                'rev_count' => $this->scm->revision_count($target, $path)
            ];
            $this->repository->extra = $extra;
            $this->repository->save();
        }
        //$revision_count = $this->scm->revision_count($branch ?: $this->target, $path);
        $revision_count = $cache['rev_count'];

        $page = (int)Request::req('page', 1);
        $revisions = $this->scm->revisions($branch, $path, ($page - 1) * 50, 50);
        $pagination = new Pagination($page, 50, $revision_count);

        $nav_title = $path . ' ' . (($page - 1) * 50) . '-' . ($page * 50) . ' of '. $revision_count;

        $users = array();

        foreach($revisions as &$commit) {
            if (!isset($users[$commit->email])) {
                $users[$commit->email] = User::find('email', $commit->email);
            }
            $commit->user = $users[$commit->email];
        }

        $this->response->objects = compact('revisions', 'pagination');

        View::set(compact('nav_title'));
    }


    public function action_commit($revision)
    {
        $commit = $this->scm->revision($revision);

        if (empty($commit)) {
            return $this->show_404();
        }

        $this->response['commit'] = $commit;

        $this->title($commit->subject);

        View::set(['commit' => $commit, 'nav_title' => $commit->subject, 'commit_diff' => $commit->diff]);
	}


    public function action_compare()
    {
		list($rev2, $rev1) = Request::req('rev', ['NX', 'NX']);
        $commit1 = $this->scm->revision($rev1);
        $commit2 = $this->scm->revision($rev2);
		$commit_diff = $this->scm->diff($rev1, $rev2);
		$nav_title = 'Comparing ' . $rev1 . ' to '.$rev2;

        if (empty($commit1) || empty($commit2)) {
            return $this->show_404();
        }

        View::set(compact('commit_diff', 'commit1', 'commit2', 'nav_title'));
    }


    public function action_browse($target = null, $path = null)
    {
        $target = $target ?: $this->target;

        $files = $this->scm->list_dir($path.'/', $target);

        $nav_title = $path;

		View::set(compact('target', 'files', 'path', 'nav_title'));

        if ($files !== false) { // We found a folder, we can quit now.
            $this->response['files'] = $files;
			return;
		}

		if ($files = $this->scm->list_dir($path, $target)) {
			$content = $this->scm->read_file($path, $target);  // maybe it's a file then?
			$mime = preg_replace('#^text/[^\s;]+#', 'text/plain', (new \finfo())->buffer($content, FILEINFO_MIME), -1, $is_text);
			$language = ' language-'.\pathinfo($path, PATHINFO_EXTENSION);

			if ($is_text || empty($content)) {
				View::set(compact('content', 'language', 'files'));
			} else {
				header('Content-Type: '.$mime);
				header('Content-Length: '.strlen($content));
				echo $content;
			}
			return;
		}

		$this->show_404();
    }


    public function action_diff($revision = null)
    {
		$revision = $revision ?: $this->target;
        $this->render['layout'] = 'plain';
        $this->response->format = 'text/plain';
        $data = $this->scm->patch($revision);

        return $data;
    }


    public function action_zip($revision = null)
    {
		$revision = $revision ?: $this->target;
        $this->render['layout'] = 'plain';
        $this->response->format = 'application/zip';
        $data = $this->scm->archive($revision);

        header('Content-Disposition: attachment; filename="'.$this->repository->slug.'-'.$revision.'.zip"');
        header('Content-Type: application/zip');
        header('Content-Length: '.strlen($data));

        return $data;
    }

    public function action_serve($path)
    {
        if (!$this->repository->serve || !$this->user->permission($this->project->id, 'scm_http_client_read')) {
            return $this->show_no_permission(true);
		}

		$this->scm->http_serve_backend($path);
    }
}