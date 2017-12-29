<?php
/*!
 * Avalon
 * Copyright (C) 2011-2012 Jack Polgar
 *
 * This file is part of Avalon.
 *
 * Avalon is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation; version 3 only.
 *
 * Avalon is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with Avalon. If not, see <http://www.gnu.org/licenses/>.
 */

namespace avalon\core;

/**
 * Error class
 *
 * @since 0.1
 * @package Avalon
 * @subpackage Core
 * @author Jack P.
 * @copyright (C) Jack P.
 */
class Error
{
    public static function register()
    {
        set_exception_handler(function($e) {
            $message = preg_replace('/(\(\d+\))\:.+$/m', '$1', $e); // Remove argument display for safety
            $message = str_replace(DOCROOT, '', $message); // Remove full path for safety
            \avalon\core\Error::halt(get_class($e), $message);
        });
    }

    public static function halt($title, $message = '')
    {
        @ob_end_clean();
        
        $app = Kernel::app();

        if ($app && $app->response['format'] === 'application/json') {
            $body = json_encode(array('error' => $title, 'description' => $message));
        } else {
            $message = nl2br(htmlentities($message));

            $body  = '<html><body>';
            $body .= '<blockquote style="font-family:\'Helvetica Neue\', Arial, Helvetica, sans-serif;background:#fbe3e4;color:#8a1f11;padding:0.8em;margin-bottom:1em;border:2px solid #fbc2c4;">';

            if ($title !== null) {
                $body .= '<h1 style="margin: 0;">'.htmlentities($title).'</h1>';
            }

            $body .= $message;
            $body .= '<div style="margin-top:8px;"><small>Powered by Avalon</small></div>';
            $body .= '</blockquote>';
            $body .= '</body></html>';

        }
        die($body);
    }
}
