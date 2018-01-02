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

use avalon\http\Request;
use avalon\http\Router;
use traq\models\Attachment;

/**
 * Attachments controller
 *
 * @author Jack P.
 * @since 3.0
 * @package Traq
 * @subpackage Controllers
 */
class Attachments extends AppController
{
    /**
     * Used to check the permission for the requested action.
     */
    public function __construct()
    {
        parent::__construct();

        // Get the attachment
        $this->attachment = Attachment::find(Router::$params[0]);

        if (!$this->attachment) {
            $this->show_404();
        }
        // Check if the user has permission
        elseif (!$this->user->permission($this->attachment->ticket->project_id, Router::$params[1]."_attachments")) {
            $this->show_no_permission();
        }
    }

    /**
     * View attachment page
     *
     * @param integer $attachment_id
     */
    public function action_view($attachment_id)
    {
        // Check what type of file we're dealing with.
        if (preg_match('/^(image|text)/i', $this->attachment->type)) {
            header("Content-Disposition: filename=\"{$this->attachment->name}\"");
        }
        // Anything else should be downloaded
        else {
            header("Content-Disposition: attachment; filename=\"{$this->attachment->name}\"");
        }

        // No template rendering
        $this->render['layout'] = $this->render['view'] = false;
        // If the mime-type is text, we can just display it as plain text.
        $this->response->format = preg_replace('#^text/.+$#i', 'text/plain', $this->attachment->type);
        // Attachment Content
        $this->response->body = base64_decode($this->attachment->contents);
    }

    /**
     * Delete attachment
     *
     * @param integer $attachment_id
     */
    public function action_delete($attachment_id)
    {
        // Delete and redirect
        $this->attachment->delete();
        Request::redirectTo($this->attachment->ticket->href());
    }
}
