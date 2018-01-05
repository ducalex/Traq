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

namespace traq\controllers\admin;

use avalon\http\Request;
use avalon\output\View;

use traq\models\Plugin;

/**
 * Admin Plugins controller
 *
 * @author Jack P.
 * @since 3.0
 * @package Traq
 * @subpackage Controllers
 */
class Plugins extends AppController
{
    public function __construct()
    {
        parent::__construct();

        // Register all namespaces
        foreach (glob(APPPATH . '/plugins/*') as $file) {
            \avalon\Autoloader::registerNamespace('traq\\plugins', $file);
        }
    }


    public function action_index()
    {
        $this->title(l('plugins'));

        $plugins = array(
            'enabled' => array(),
            'disabled' => array()
        );

        $installed = array();

        foreach (Plugin::fetch_all() as $plugin) {
            $installed[$plugin->file] = $plugin->enabled;
        }

        // Scan the plugin directory
        foreach (glob(APPPATH . '/plugins/*') as $file) {
            $plugin = new Plugin(array('file' => basename($file)));

            if ($plugin->is_valid()) {
                $key = !empty($installed[$plugin->file]) ? 'enabled' : 'disabled';
                $class_name = $plugin->get_class();
                $plugins[$key][$plugin->file] = array_merge($class_name::info(), array(
                    'installed' => isset($installed[$plugin->file]),
                    'enabled' => !empty($installed[$plugin->file]),
                    'file' => $plugin->file
                ));
            }
        }

        View::set('plugins', $plugins);
    }

    /**
     * Enables the specified plugin.
     *
     * @param string $file The plugin filename (without .plugin.php)
     */
    public function action_enable($file)
    {
        if ($plugin = Plugin::find('file', $file)) {
            $plugin->set('enabled', 1);

            if ($plugin->save()) {
                $class_name = $plugin->get_class();
                $class_name::__enable();
            }
        }

        Request::redirectTo('/admin/plugins');
    }

    /**
     * Disables the specified plugin.
     *
     * @param string $file The plugin filename (without .plugin.php)
     */
    public function action_disable($file)
    {
        if ($plugin = Plugin::find('file', $file)) {
            $plugin->set('enabled', 0);

            if ($plugin->save()) {
                $class_name = $plugin->get_class();
                $class_name::__disable();
            }
        }
        Request::redirectTo('/admin/plugins');
    }

    /**
     * Installs the specified plugin.
     *
     * @param string $file The plugin filename
     */
    public function action_install($file)
    {
        if ($plugin = new Plugin(array('file' => $file))) {
            $plugin->set('enabled', 1);

            if ($plugin->save()) {
                $class_name = $plugin->get_class();
                $class_name::__install();
            }
        }
        Request::redirectTo('/admin/plugins');
    }

    /**
     * Uninstalls the specified plugin.
     *
     * @param string $file The plugin filename
     */
    public function action_uninstall($file)
    {
        $plugin = Plugin::find('file', $file);
        $class_name = $plugin->get_class();

        // Check if the class exists
        if (class_exists($class_name)) {
            $class_name::__uninstall();
        }

        $plugin->delete();

        Request::redirectTo('/admin/plugins');
    }
}
