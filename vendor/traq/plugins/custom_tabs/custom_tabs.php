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

namespace traq\plugins;

use \FishHook;
use \HTML;
use avalon\Autoloader;
use avalon\Database;
use avalon\http\Router;
use avalon\http\Request;
use avalon\output\View;

use CustomTabs\models\CustomTab;

/**
 * Custom tabs plugin.
 *
 * @since 3.0.7
 * @package Traq
 * @subpackage Plugins
 * @author Jack P.
 * @copyright (c) Jack P.
 */
class CustomTabs extends \traq\libraries\Plugin
{
    protected static $info = array(
        'name'    => 'Custom Tabs',
        'version' => '1.0',
        'author'  => 'Jack P.'
    );

    private static $tabs = array();

    public static function init()
    {
        // Register namespace
        Autoloader::registerNamespace('CustomTabs', __DIR__);

        // Add routes
        Router::add('/admin/custom_tabs', 'CustomTabs::controllers::admin::CustomTabs.index');
        Router::add('/admin/custom_tabs/new', 'CustomTabs::controllers::admin::CustomTabs.new');
        Router::add('/admin/custom_tabs/([0-9]+)/(edit|delete)', 'CustomTabs::controllers::admin::CustomTabs.$2/$1');

        // Hook into the admin navbar
        FishHook::add('template:layouts/admin/main_nav', array(static::class, 'admin_nav'));

        // Get tabs
        static::$tabs = CustomTab::fetch_all();
        View::set('custom_tabs', static::$tabs);

        // Hook into navbar
        FishHook::add('template:layouts/default/main_nav', array(static::class, 'display_tabs'));
    }

    /**
     * Display tabs
     */
    public static function display_tabs()
    {
        echo View::render('custom_tabs/tabs');
    }

    /**
     * Add link to AdminCP nav.
     */
    public static function admin_nav()
    {
        echo '<li' . active_nav('/admin/custom_tabs', ' class="active"') . '>' . HTML::link(l('custom_tabs'), "/admin/custom_tabs") . '</li>';
    }

    /**
     * Create the tabs table
     */
    public static function __install()
    {
        $conn = Database::connection();

        $auto_increment = $conn->type === 'sqlite' ? '' : 'AUTO_INCREMENT';

        $conn->exec("DROP TABLE IF EXISTS `{$conn->prefix}custom_tabs`");
        $conn->exec("
            CREATE TABLE `{$conn->prefix}custom_tabs` (
              `id` INTEGER NOT NULL $auto_increment,
              `label` varchar(255) NOT NULL DEFAULT '',
              `url` varchar(255) NOT NULL DEFAULT '',
              `project_id` INTEGER NOT NULL,
              `groups` varchar(255) NOT NULL DEFAULT '',
              `display_order` INTEGER NOT NULL,
              PRIMARY KEY (`id`)
            )
        ");
    }

    /**
     * Delete the tabs table
     */
    public static function __uninstall()
    {
        Database::connection()->exec("DROP TABLE IF EXISTS `" . Database::connection()->prefix . "custom_tabs`;");
    }
}
