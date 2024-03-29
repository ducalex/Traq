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

namespace traq\libraries;

/**
 * SCM Base class.
 * Copyright (C) Jack Polgar
 *
 * @author Jack P.
 * @copyright (C) Jack P.
 * @package Traq
 * @package SCM
 * @version 0.1
 */
class SCM
{
    /**
     * Used to load an SCM class.
     */
    public static function factory($name, &$info = array())
    {
        $class = "\\traq\\libraries\\scm\\adapters\\" . ucfirst($name);

        if (class_exists($class)) {
            return new $class($info);
        }

        return false;
    }

    /**
     * Used to list supported SCM adapters.
     */
    public static function adapters()
    {
        $adapters = array();
        foreach(glob(APPPATH . "/libraries/scm/adapters/*.php") as $file) {
            $name = basename($file, '.php');
            $class = "\\traq\\libraries\\scm\\adapters\\" . ucfirst($name);
            $adapters[$name] = $class::name;
        }

        \FishHook::run('function:scm_types', array(&$adapters));
        return $adapters;
    }

    /**
     * Used when saving repository information.
     *
     * @param array $info Repository model object.
     * @param bool $is_new
     *
     * @return object
     */
    public function _before_save_info(&$repo, $is_new = false)
    {
        throw new Exception("Method " . __CLASS__ . "::" . __FUNCTION__ . "() not implemented");
    }

    /*!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
     *
     *  This is the base class for all SCM types.
     *  It is essentially the API for the repository browser,
     *  none of this class is finalised and anything can change.
     *
     *  Join in on the discussion on the forum to help build the best
     *  API for the repository browser.
     *
     *  You can also join the IRC channel and discuss the API, but
     *  please post all discussion to the forum topic about it.
     *
     *!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
     */

    protected $info;
    protected $last_error;

    /**
     * Class constructor.
     *
     * @param object $info Repository model object.
     */
    public function __construct(&$info)
    {
        $this->info = $info;
    }

    /**
     * Returns the name of the SCM.
     *
     * @return string
     */
    public function name()
    {
        return static::name;
    }

    /**
     * Returns the last error from a command
     *
     * @return string
     */
    public function last_error()
    {
        return $this->last_error;
    }

    /**
     * Checks if the current repository exists on disk
     *
     * @return boolean
     */
    public function exists()
    {
        throw new Exception("Method " . __CLASS__ . "::" . __FUNCTION__ . "() not implemented");
    }

    /**
     * Create the current repository on disk
     *
     * @return boolean
     */
    public function create($overwrite = false)
    {
        throw new Exception("Method " . __CLASS__ . "::" . __FUNCTION__ . "() not implemented");
    }

    /**
     * Returns the default/main branch of the repository.
     *
     * @return string
     */
    public function default_branch()
    {
        throw new Exception("Method " . __CLASS__ . "::" . __FUNCTION__ . "() not implemented");
    }

    /**
     * Returns an array of branches.
     *
     * @return array
     */
    public function branches()
    {
        throw new Exception("Method " . __CLASS__ . "::" . __FUNCTION__ . "() not implemented");
    }

    /**
     * Returns an array of tags.
     *
     * @return array
     */
    public function tags()
    {
        throw new Exception("Method " . __CLASS__ . "::" . __FUNCTION__ . "() not implemented");
    }

    /**
     * Returns the information about the given file path.
     *
     * @param string $path File/directory path
     * @param string $revision Revision identifier
     *
     * @return File
     */
    public function file_info($path, $revision = null)
    {
        throw new Exception("Method " . __CLASS__ . "::" . __FUNCTION__ . "() not implemented");
    }

    /**
     * Fetches the revision info.
     *
     * @param string $revision Revision identifier.
     * @param string $path Directory path for the revision.
     *
     * @return Revision
     */
    public function revision($revision, $path = null)
    {
        throw new Exception("Method " . __CLASS__ . "::" . __FUNCTION__ . "() not implemented");
    }

    /**
     * Check if the revision exists.
     *
     * @param string $revision Revision identifier.
     *
     * @return bool
     */
    public function revision_exists($revision)
    {
        throw new Exception("Method " . __CLASS__ . "::" . __FUNCTION__ . "() not implemented");
    }

    /**
     * Fetches the revision count.
     *
     * @param string $revision Revision identifier.
     * @param string $path Directory path for the revision.
     *
     * @return integer
     */
    public function revision_count($revision, $path = null)
    {
        throw new Exception("Method " . __CLASS__ . "::" . __FUNCTION__ . "() not implemented");
    }

    /**
     * Client backend (for git clone or svn checkout)
     *
     */
    public function http_serve_backend($path)
    {
        throw new Exception("Method " . __CLASS__ . "::" . __FUNCTION__ . "() not implemented");
    }

    /**
     * Fetches all the revisions.
     *
     * @param string $path Directory path.
     *
     * @return array
     */
    public function revisions($branch = null, $path = null)
    {
        throw new Exception("Method " . __CLASS__ . "::" . __FUNCTION__ . "() not implemented");
    }

    /**
     * Lists the directory for the path and revision.
     *
     * @param string $path Directory path.
     * @param string $revision Revision identifier.
     *
     * @return array
     */
    public function list_dir($path, $revision = null)
    {
        throw new Exception("Method " . __CLASS__ . "::" . __FUNCTION__ . "() not implemented");
    }

    /**
     * Read a file for the path and revision.
     *
     * @param string $path Directory path.
     * @param string $revision Revision identifier.
     *
     * @return blob
     */
    public function read_file($path, $revision = null)
    {
        throw new Exception("Method " . __CLASS__ . "::" . __FUNCTION__ . "() not implemented");
    }

    /**
     * Return a patch in unified diff format of the specified revision
     *
     * @param string $revision Revision identifier.
     *
     * @return string
     */
    public function patch($revision)
    {
        throw new Exception("Method " . __CLASS__ . "::" . __FUNCTION__ . "() not implemented");
    }

    /**
     * Return a diff between two revisions
     *
     * @param string $revision Revision identifier.
     */
    public function diff($revision1, $revision2)
    {
        throw new Exception("Method " . __CLASS__ . "::" . __FUNCTION__ . "() not implemented");
    }

    /**
     * Return an archive/checkout of the specified revision
     *
     * @param string $revision Revision identifier.
     * @param string $format archive format
     *
     * @return blob
     */
    public function archive($revision, $format = 'zip')
    {
        throw new Exception("Method " . __CLASS__ . "::" . __FUNCTION__ . "() not implemented");
    }
}
