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

namespace traq\libraries\scm\adapters;
use avalon\core\Load;
use avalon\core\Kernel as Avalon;
use traq\libraries\scm\Revision;
use traq\libraries\scm\File;

Load::helper('diff');


class Git extends \traq\libraries\SCM
{
    const name = 'Git';
    const link_pattern = '([a-f0-9]{7}|[a-f0-9]{40})';

    private $_binary = 'git'; // In the future this might be configurable

    /**
     * Used when saving repository information.
     *
     * @param array $info Repository model object.
     * @param bool $is_new
     *
     * @return object
     */
    public function _before_save_info(&$info, $is_new = false)
    {
        $info->location = realpath($info->location);
        // Check if the location is a repository or not...
        if ($this->_shell('branch') === false) {
            $info->_add_error('location', l('errors.scm.location_not_a_repository'));
            $info->_add_error('cmd', $this->last_error);
        }
    }

    /**
     * Returns the default/main branch of the repository.
     *
     * @return string
     */
    public function default_branch()
    {
        preg_match('/^\*\s*(.+)$/m', $this->_shell('branch'), $match);
        return $match ? $match[1] : false;
    }

    /**
     * Returns an array of branches.
     *
     * @return array
     */
    public function branches()
    {
        preg_match_all('/^[\*\s]*(.+)$/m', $this->_shell('branch'), $matches);
        return $matches ? $matches[1] : false;
    }

    /**
     * Returns an array of tags.
     *
     * @return array
     */
    public function tags()
    {
        preg_match_all('/^[\*\s]*(.+)$/m', $this->_shell('tag'), $matches);
        return $matches ? $matches[1] : false;
    }

    /**
     * Undocumented function
     *
     * @param string $revision
     * @param string $path
     * @param integer $skip
     * @param integer $count
     * @return array
     */
    public function revisions($revision = null, $path = null, $skip = 0, $count = 50)
    {
        $arg_filters = ' --skip='.intval($skip).' --max-count='.intval($count);

        $output = $this->_shell("log -z $arg_filters --format='Commit:%h %H%x1f%p%x1f%an%x1f%ae%x1f%ad%x1f%s%x1f%B%x1f'", $revision, $path);
        $history = [];

        foreach(explode("\x00", $output) as $revision_string) {
            if ($revision = $this->_parse_log_entry($revision_string)) {
                $history[$revision->id] = $revision;
            }
        }

        return $history;
    }

    /**
     * Undocumented function
     *
     * @param string $revision
     * @param string $path
     * @return object
     */
    public function revision($revision, $path = null)
    {
        $output = $this->_shell("show --format='Commit:%h %H%x1f%p%x1f%an%x1f%ae%x1f%ad%x1f%s%x1f%B%x1f'", $revision, $path);
        $commit = $this->_parse_log_entry($output);

        if ($commit) {
            if (empty($commit->diff) && count($commit->parents) > 1) { // Most likely a merge
                $commit->diff = $this->_shell('diff ' . implode('...', $commit->parents));
            }

            if (!empty($commit->diff)) {
                $commit->raw_diff = $commit->diff;
                $commit->diff = parse_diff($commit->raw_diff);
            }
        }

        return $commit;
    }

    /**
     * Undocumented function
     *
     * @param string $branch
     * @param string $path
     * @return integer
     */
    public function revision_exists($revision)
    {
        return $this->_shell('cat-file -e', $revision) !== false;
    }

    /**
     * Undocumented function
     *
     * @param string $branch
     * @param string $path
     * @return integer
     */
    public function revision_count($branch, $path = null)
    {
        return (int)$this->_shell('rev-list --count', $branch, $path);
    }

    /**
     * Returns an single object matching $path and $revision or false on not found
     *
     * @param string $path
     * @param string $revision
     * @return object
     */
    public function file_info($path, $revision = null)
    {
        $file = $this->list_dir(trim($path, '/'), $revision);
        return $file ? reset($file) : false;
    }

    /**
     * Returns an array of objects/files matching $path and $revision or false on not found
     *
     * @param string $path
     * @param string $revision
     * @return array
     */
    public function list_dir($path, $revision = null)
    {
        $output = $this->_shell("ls-tree --full-tree -l", $revision, $path);
        $files = $dirs = [];

        if (preg_match_all('/^(\d+)\s+([^\s]+)\s+([^\s]+)\s+([^\s]+)\s+(.+)$/m', $output, $matches)) {
            natcasesort($matches[5]); // Sort the paths

            foreach($matches[5] as $i => $path) {
                // This will probably need caching
                $last_revision = $this->revisions($revision, $path, 0, 1);

                $file = new File([
                    'path' => $matches[5][$i],
                    'mode' => $matches[1][$i],
                    'type' => $matches[2][$i] === 'tree' ? 'dir' : 'blob',
                    'id'   => $matches[3][$i],
                    'size' => $matches[4][$i],
                    'revision' => reset($last_revision),
                ]);

                if ($file->is_dir()) {
                    $dirs[$path] = $file;
                } else {
                    $files[$path] = $file;
                }
            }

            return array_merge($dirs, $files);
        }

        return false;
    }

    /**
     * Returns the content of a file matching path and revision or false if not found
     *
     * @param string $path
     * @param string $revision
     * @return string
     */
    public function read_file($path, $revision = 'HEAD')
    {
        return $this->_shell('show '.escapeshellarg($revision).':'.escapeshellarg(ltrim($path, '/')));
    }

    /**
     * Returns a unified diff patch containing the changes introduced by a specific revision
     *
     * @param string $revision
     * @return string
     */
    public function patch($revision)
    {
        return $this->_shell('format-patch --stdout -1 ' . escapeshellarg($revision));
    }

    /**
     * Returns a unified diff
     *
     * @param string $revision1
     * @param string $revision2
     * @return string
     */
    public function diff($revision1, $revision2, $raw_output = false)
    {
        $output = $this->_shell('diff ' . escapeshellarg($revision1) . ' ' . escapeshellarg($revision2));
        return $raw_output ? $output : parse_diff($output);
    }

    /**
     * Returns an archive snapshot of the tree at a specific revision
     *
     * @param string $revision
     * @param string $format
     * @return blob
     */
    public function archive($revision, $format = 'zip')
    {
        return $this->_shell('archive --format='.escapeshellarg($format).' '.escapeshellarg($revision));
    }

    /**
     * Returns the unix timestamp of when the repository was last modified in any way(push/rebase/merge/commit/branch/tag/etc)
     * I haven't found a reliable way for git yet so we have to read the filesystem
     *
     * @return int
     */
    public function last_modified()
    {
        if (file_exists($this->info->location.'/index')) {
            return @filemtime($this->info->location.'/index');
        } else {
            return @filemtime($this->info->location.'/.git/index');
        }
    }

    /**
     * Client backend (for git clone or svn checkout)
     *
     */
    public function http_serve_backend($path)
    {
        ob_end_clean();

        $env = [
            'GIT_PROJECT_ROOT' => $this->info->location,
            'GIT_HTTP_EXPORT_ALL' => '1',
            'PATH_INFO' => $path,
            'REMOTE_USER' => Avalon::app()->user->username,
            'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'],
            'QUERY_STRING' => $_SERVER['QUERY_STRING'],
            'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
            'CONTENT_TYPE' => $_SERVER['CONTENT_TYPE'],
            'CONTENT_LENGTH' => $_SERVER['CONTENT_LENGTH'],
            'HTTP_CONTENT_ENCODING' => $_SERVER['HTTP_CONTENT_ENCODING'],
        ];

        $git = proc_open($this->_binary. ' http-backend', [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes, $this->info->location, $env);
        if ($git === false) die('Failed to execute process');
        list($git_stdin, $git_stdout, $git_stderr) = $pipes;

        stream_copy_to_stream(fopen('php://input', 'r'), $git_stdin);

        fclose($git_stdin);

        while(($line = fgets($git_stdout)) !== "\r\n" && !feof($git_stdout)) {
            if (preg_match('/^Status: ([0-9]+) (.*)$/', $line, $matches)) {
                header($matches[2], true, $matches[1]);
            } else {
                header(rtrim($line), true);
            }
        }

        fpassthru($git_stdout);
        fclose($git_stdout);
        proc_close($git);
        exit;
    }


    private function _shell($cmd, $revision = null, $path = null, &$exit_code = null)
    {
        $arg_revision = $revision ? escapeshellarg($revision) : '';
        $arg_path = $path ? ' -- '.escapeshellarg(ltrim($path, '/')) : '';
        $arg_location = escapeshellarg($this->info->location);

        $git = proc_open("{$this->_binary} -C $arg_location $cmd $arg_revision $arg_path", [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $this->info->location);
        if ($git === false) return false;
        list(, $git_stdout, $git_stderr) = $pipes;

        $stdout = stream_get_contents($git_stdout);
        $this->last_error = stream_get_contents($git_stderr) ?: null;

        return proc_close($git) ? false : $stdout;
    }

    private function _parse_log_entry($revision)
    {
        $regex = "/^Commit:(?<id>[a-f0-9]+) (?<hash>[a-f0-9]+)\x1f(?<parents>[a-f0-9\s]+|)\x1f(?<author>[^\x1f]+)\x1f(?<email>[^\x1f]+)\x1f(?<date>[^\x1f]+)\x1f(?<subject>[^\x1f]+)\x1f(?<message>[^\x1f]+)\x1f\s*(?<diff>.*)$/ms";

        if (!preg_match($regex, $revision, $match)) {
            return false;
        }

        $parents = array_filter(explode(' ', $match['parents']));

        return new Revision([
            'id'        => $match['id'],
            'full_id'   => $match['hash'],
            'parents'   => $parents,
            'author'    => $match['author'],
            'email'     => $match['email'],
            'date'      => strtotime($match['date']),
            'subject'   => $match['subject'],
            'message'   => $match['message'],
            'diff'      => $match['diff'],
            'is_first'  => empty($parents),
            'is_merge'  => count($parents) > 1,
        ]);
    }
}
