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

use avalon\http\Router;

define("RTR_PROJSLUG", '(?P<project_slug>[a-zA-Z0-9\-\_]+)');

// Internal
Router::add('404', 'traq::controllers::Error.404');

// Root
Router::add('/', 'traq::controllers::Projects.index');

Router::add('/(login|logout|register)', 'traq::controllers::Users.$1');
Router::add('/login/resetpassword', 'traq::controllers::Users.reset_password');
Router::add('/login/resetpassword/([a-zA-Z0-9]+)', 'traq::controllers::Users.reset_password/$1');
Router::add('/usercp', 'traq::controllers::Usercp.index');
Router::add('/usercp/(password|subscriptions|create_api_key)', 'traq::controllers::Usercp.$1');
Router::add('/users/([0-9]+)', 'traq::controllers::Users.view/$1');
Router::add('/users/validate/(.*)', 'traq::controllers::Users.validate/$1');

// API
Router::add('/statuses', 'traq::controllers::API.statuses');
Router::add('/priorities', 'traq::controllers::API.priorities');

// Misc
Router::add('/_ajax/autocomplete/(username)', 'traq::controllers::Misc.autocomplete_$1');
Router::add('/_misc/preview_text', 'traq::controllers::Misc.preview_text');
Router::add('/_misc/traq_news', 'traq::controllers::Misc.traq_news');

// Attachment routes
Router::add('/attachments/(?P<attachment_id>[0-9]+)/([a-zA-Z0-9\-_.]+)', 'traq::controllers::Attachments.view/$1');
Router::add('/attachments/(?P<attachment_id>[0-9]+)/([a-zA-Z0-9\-_.\s]+)/delete', 'traq::controllers::Attachments.delete/$1');

// ------------------------------------------------
// Project routes
Router::add('/projects', 'traq::controllers::Projects.index');
Router::add('/' . RTR_PROJSLUG . '/milestone/(?P<milestone_slug>[a-zA-Z0-9\-_.]+?)', 'traq::controllers::Projects.milestone/$2');
Router::add('/' . RTR_PROJSLUG . '/(timeline|roadmap|changelog)', 'traq::controllers::Projects.$2');
Router::add('/' . RTR_PROJSLUG . '/timeline/([0-9]+)/delete', 'traq::controllers::Projects.delete_timeline_event/$2');
Router::add('/' . RTR_PROJSLUG . '/roadmap/(completed|all|cancelled)', 'traq::controllers::Projects.roadmap/$2');
Router::add('/' . RTR_PROJSLUG, 'traq::controllers::Projects.view');

// Ticket routes
Router::add('/' . RTR_PROJSLUG . '/tickets/(?P<ticket_id>[0-9]+)', 'traq::controllers::Tickets.view/$2');
Router::add('/' . RTR_PROJSLUG . '/tickets/(?P<ticket_id>[0-9]+)/(move|delete|update|edit|vote|voters)', 'traq::controllers::Tickets.$3/$2');
Router::add('/' . RTR_PROJSLUG . '/tickets/(?P<ticket_id>[0-9]+)/history/([0-9]+)/(edit|delete)', 'traq::controllers::TicketHistory.$4/$3');
Router::add('/' . RTR_PROJSLUG . '/tickets/(?P<ticket_id>[0-9]+)/tasks/manage', 'traq::controllers::Tickets.manage_tasks/$2');
Router::add('/' . RTR_PROJSLUG . '/tickets/(?P<ticket_id>[0-9]+)/tasks/([0-9]+)', 'traq::controllers::Tickets.toggle_task/$2,$3');
Router::add('/' . RTR_PROJSLUG . '/tickets/(mass_actions|update_filters|new)', 'traq::controllers::Tickets.$2');
Router::add('/' . RTR_PROJSLUG . '/tickets/template/([0-9])', 'traq::controllers::Tickets.template/$2');
Router::add('/' . RTR_PROJSLUG . '/tickets', 'traq::controllers::Tickets.index');

// Wiki routes
Router::add('/' . RTR_PROJSLUG . '/wiki', 'traq::controllers::Wiki.view', array('slug' => 'main'));
Router::add('/' . RTR_PROJSLUG . '/wiki/_(pages|new)', 'traq::controllers::Wiki.$2');
Router::add('/' . RTR_PROJSLUG . '/wiki/(?P<slug>[a-zA-Z0-9\-\_/]+)/_(edit|delete)', 'traq::controllers::Wiki.$3');
Router::add('/' . RTR_PROJSLUG . '/wiki/(?P<slug>[a-zA-Z0-9\-\_/]+)/_revisions', 'traq::controllers::Wiki.revisions/$2');
Router::add('/' . RTR_PROJSLUG . '/wiki/(?P<slug>[a-zA-Z0-9\-\_/]+)/_revisions/(\d+)', 'traq::controllers::Wiki.revision/$2,$3');
Router::add('/' . RTR_PROJSLUG . '/wiki/(?P<slug>[a-zA-Z0-9\-\_/]+)', 'traq::controllers::Wiki.view');

// SCM routes
Router::add('/' . RTR_PROJSLUG . '/repository', 'traq::controllers::Repositories.commits');
Router::add('/' . RTR_PROJSLUG . '/repository/(?P<slug>[a-zA-Z0-9\-\_\.]+)\.(git|svn|repo|hg)(/.+)?', 'traq::controllers::Repositories.serve/$4');
Router::add('/' . RTR_PROJSLUG . '/repository/(?P<slug>[a-zA-Z0-9\-\_\.]+)', 'traq::controllers::Repositories.commits');
Router::add('/' . RTR_PROJSLUG . '/repository/(?P<slug>[a-zA-Z0-9\-\_\.]+)/(browse|diff|zip|commits|compare)', 'traq::controllers::Repositories.$3');
Router::add('/' . RTR_PROJSLUG . '/repository/(?P<slug>[a-zA-Z0-9\-\_\.]+)/(browse|commit|diff|zip|commits)/(?P<revision>[a-zA-Z0-9\-\._]+)(?<path>/.+)?', 'traq::controllers::Repositories.$3/$4,$5');

// Project settings routes
Router::add('/' . RTR_PROJSLUG . '/settings', 'traq::controllers::ProjectSettings::Options.index');
Router::add('/' . RTR_PROJSLUG . '/settings/(milestones|components|members|repositories)', 'traq::controllers::ProjectSettings::$2.index');
Router::add('/' . RTR_PROJSLUG . '/settings/(milestones|components|members|repositories)/new', 'traq::controllers::ProjectSettings::$2.new');
Router::add('/' . RTR_PROJSLUG . '/settings/(milestones|components|members|repositories)/([0-9]+)/(edit|delete)', 'traq::controllers::ProjectSettings::$2.$4/$3');
Router::add('/' . RTR_PROJSLUG . '/settings/custom_fields', 'traq::controllers::ProjectSettings::CustomFields.index');
Router::add('/' . RTR_PROJSLUG . '/settings/custom_fields/new', 'traq::controllers::ProjectSettings::CustomFields.new');
Router::add('/' . RTR_PROJSLUG . '/settings/custom_fields/([0-9]+)/(edit|delete)', 'traq::controllers::ProjectSettings::CustomFields.$3/$2');

Router::add('/' . RTR_PROJSLUG . '/settings/members/save', 'traq::controllers::ProjectSettings::Members.save');

// Project permission routes
Router::add('/' . RTR_PROJSLUG . '/settings/permissions/(groups|roles)', 'traq::controllers::ProjectSettings::Permissions.index/$2');

// Subscription routes
Router::add('/' . RTR_PROJSLUG . '/(?:un)?subscribe', 'traq::controllers::Subscriptions.toggle/project,$1');
Router::add('/' . RTR_PROJSLUG . '/milestone/(?P<milestone_slug>[a-zA-Z0-9\-_.]+?)/(?:un)?subscribe', 'traq::controllers::Subscriptions.toggle/milestone,$2');
Router::add('/' . RTR_PROJSLUG . '/tickets/(?P<ticket_id>[0-9]+)/(?:un)?subscribe', 'traq::controllers::Subscriptions.toggle/ticket,$2');

// ------------------------------------------------
// AdminCP routes
Router::add('/admin', 'traq::controllers::admin::Dashboard.index');
Router::add('/admin/settings', 'traq::controllers::admin::Settings.index');

// Projects
Router::add('/admin/projects', 'traq::controllers::admin::Projects.index');
Router::add('/admin/projects/new', 'traq::controllers::admin::Projects.new');
Router::add('/admin/projects/([0-9]+)/delete', 'traq::controllers::admin::Projects.delete/$1');

// Plugins
Router::add('/admin/plugins', 'traq::controllers::admin::Plugins.index');
Router::add('/admin/plugins/(install|enable|disable|uninstall)/([a-zA-Z0-9\-\_]+)', 'traq::controllers::admin::Plugins.$1/$2');

// Users
Router::add('/admin/users', 'traq::controllers::admin::Users.index');
Router::add('/admin/users/new', 'traq::controllers::admin::Users.new');
Router::add('/admin/users/([0-9]+)/(edit|delete)', 'traq::controllers::admin::Users.$2/$1');
Router::add('/admin/users/mass_actions', 'traq::controllers::admin::Users.mass_actions');

// User groups
Router::add('/admin/groups', 'traq::controllers::admin::Groups.index');
Router::add('/admin/groups/new', 'traq::controllers::admin::Groups.new');
Router::add('/admin/groups/([0-9]+)/(edit|delete)', 'traq::controllers::admin::Groups.$2/$1');

// Project roles
Router::add('/admin/roles', 'traq::controllers::admin::ProjectRoles.index');
Router::add('/admin/roles/new', 'traq::controllers::admin::ProjectRoles.new');
Router::add('/admin/roles/([0-9]+)/(edit|delete)', 'traq::controllers::admin::ProjectRoles.$2/$1');

// Ticket types
Router::add('/admin/tickets/types', 'traq::controllers::admin::Types.index');
Router::add('/admin/tickets/types/new', 'traq::controllers::admin::Types.new');
Router::add('/admin/tickets/types/([0-9]+)/(edit|delete)', 'traq::controllers::admin::Types.$2/$1');

// Ticket statuses
Router::add('/admin/tickets/statuses', 'traq::controllers::admin::Statuses.index');
Router::add('/admin/tickets/statuses/new', 'traq::controllers::admin::Statuses.new');
Router::add('/admin/tickets/statuses/([0-9]+)/(edit|delete)', 'traq::controllers::admin::Statuses.$2/$1');

// Severities
Router::add('/admin/severities', 'traq::controllers::admin::Severities.index');
Router::add('/admin/severities/new', 'traq::controllers::admin::Severities.new');
Router::add('/admin/severities/([0-9]+)/(edit|delete)', 'traq::controllers::admin::Severities.$2/$1');

// Priorities
Router::add('/admin/priorities', 'traq::controllers::admin::Priorities.index');
Router::add('/admin/priorities/new', 'traq::controllers::admin::Priorities.new');
Router::add('/admin/priorities/([0-9]+)/(edit|delete)', 'traq::controllers::admin::Priorities.$2/$1');
