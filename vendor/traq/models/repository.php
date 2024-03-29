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

namespace traq\models;

use avalon\database\Model;

/**
 * Repository model.
 *
 * @package Traq
 * @subpackage Models
 * @author Jack P.
 * @copyright (c) Jack P.
 */
class Repository extends Model
{
    protected static $_name = 'repositories';
    protected static $_serialize = array('extra');
    protected static $_properties = array(
        'id',
        'project_id',
        'slug',
        'type',
        'location',
        'username',
        'password',
        'extra',
        'is_default',
        'serve'
    );

    protected static $_belongs_to = array(
        'project',
    );

    protected static $_has_many = array(
        'changesets' => array('model' => 'RepoChangeset')
    );

    protected static $_filters_before = array(
        'save' => array('_before_save'),
        'create' => array('_before_save'),
    );

    protected function _before_save()
    {
        if (!$this->_is_new && $this->_data['is_default']) {
            static::db()->update('repositories')
                        ->set(array('is_default' => 0))
                        ->where('project_id', $this->project->id)
                        ->where('id', $this->id, '!=')
                        ->exec();
        }
    }

    public function href($commit = null)
    {
        return $this->project->href("repository/{$this->slug}/$commit");
    }


    public function is_valid()
    {
        $errors = array();

        // Check if slug is empty
        if (empty($this->_data['slug'])) {
            $errors['slug'] = l('errors.slug_blank');
        }

        // Make sure slug isn't in use
        $repo = Repository::select('id')->where([['id', $this->id, '!='], ['slug', $this->slug], ['project_id', $this->project_id]]);

        if ($repo->exec()->count()) {
            $errors['slug'] = l('errors.slug_in_use');
        }

        // Check if location empty
        if (empty($this->_data['location'])) {
            $errors['location'] = l('errors.scm.location_blank');
        }

        // This is different, here we're merging the local errors
        // with the errors array of the class which may have been
        // added via the SCM::_before_save_info() method.
        $this->errors = array_merge($errors, $this->errors);

        return empty($this->errors);
    }
}
