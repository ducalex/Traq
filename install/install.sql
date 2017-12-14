-- 
-- Traq
-- Copyright (C) 2009-2016 Traq.io
-- 
-- This file is part of Traq.
-- 
-- Traq is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; version 3 only.
-- 
-- Traq is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
-- GNU General Public License for more details.
-- 
-- You should have received a copy of the GNU General Public License
-- along with Traq. If not, see <http://www.gnu.org/licenses/>.
-- 

-- Dump of table traq_attachments
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `traq_attachments`;

CREATE TABLE `traq_attachments` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci,
  `contents` LONGTEXT NOT NULL COLLATE utf8_unicode_ci,
  `type` VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci,
  `size` INTEGER NOT NULL,
  `user_id` INTEGER NOT NULL,
  `ticket_id` INTEGER NOT NULL,
  `created_at` DATETIME NOT NULL
);

-- Dump of table traq_components
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `traq_components`;

CREATE TABLE `traq_components` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci,
  `project_id` INTEGER NOT NULL
);

-- Dump of table traq_custom_field_values
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `traq_custom_field_values`;

CREATE TABLE `traq_custom_field_values` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `custom_field_id` INTEGER NOT NULL,
  `ticket_id` INTEGER NOT NULL,
  `value` TEXT
);

-- Dump of table traq_custom_fields
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `traq_custom_fields`;

CREATE TABLE `traq_custom_fields` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `type` VARCHAR(255) NOT NULL DEFAULT 'TEXT',
  `values` TEXT,
  `multiple` INTEGER NOT NULL DEFAULT '0',
  `default_value` VARCHAR(255) DEFAULT NULL,
  `regex` VARCHAR(255) DEFAULT NULL,
  `min_length` INTEGER DEFAULT NULL,
  `max_length` INTEGER DEFAULT NULL,
  `is_required` INTEGER NOT NULL DEFAULT '0',
  `project_id` INTEGER NOT NULL,
  `ticket_type_ids` VARCHAR(255) NOT NULL
);

-- Dump of table traq_milestones
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `traq_milestones`;

CREATE TABLE `traq_milestones` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL DEFAULT '' COLLATE utf8_unicode_ci,
  `slug` VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci,
  `codename` VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci,
  `info` TEXT NOT NULL COLLATE utf8_unicode_ci,
  `changelog` TEXT NULL COLLATE utf8_unicode_ci,
  `due` DATETIME DEFAULT NULL,
  `completed_on` DATETIME DEFAULT NULL,
  `status` INTEGER NOT NULL DEFAULT '1',
  `is_locked` INTEGER NOT NULL DEFAULT '0',
  `project_id` INTEGER NOT NULL,
  `displayorder` INTEGER NOT NULL
);

-- Dump of table traq_permissions
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `traq_permissions`;

CREATE TABLE `traq_permissions` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `project_id` INTEGER NOT NULL DEFAULT '0',
  `type` VARCHAR(255) DEFAULT NULL,
  `type_id` INTEGER NOT NULL DEFAULT '0',
  `action` VARCHAR(255) NOT NULL DEFAULT '',
  `value` INTEGER NOT NULL DEFAULT '0'
);

INSERT INTO `traq_permissions` (`project_id`, `type`, `type_id`, `action`, `value`)
VALUES
  (0,'usergroup',0,'view',1),
  (0,'usergroup',0,'project_settings',0),
  (0,'usergroup',0,'delete_timeline_events',0),
  (0,'usergroup',0,'view_tickets',1),
  (0,'usergroup',0,'create_tickets',1),
  (0,'usergroup',0,'update_tickets',1),
  (0,'usergroup',0,'delete_tickets',0),
  (0,'usergroup',0,'move_tickets',0),
  (0,'usergroup',0,'comment_on_tickets',1),
  (0,'usergroup',0,'edit_ticket_description',0),
  (0,'usergroup',0,'vote_on_tickets',1),
  (0,'usergroup',0,'add_attachments',1),
  (0,'usergroup',0,'view_attachments',1),
  (0,'usergroup',0,'delete_attachments',0),
  (0,'usergroup',0,'perform_mass_actions',0),
  (0,'usergroup',0,'ticket_properties_set_assigned_to',0),
  (0,'usergroup',0,'ticket_properties_set_milestone',0),
  (0,'usergroup',0,'ticket_properties_set_version',0),
  (0,'usergroup',0,'ticket_properties_set_component',0),
  (0,'usergroup',0,'ticket_properties_set_severity',0),
  (0,'usergroup',0,'ticket_properties_set_priority',0),
  (0,'usergroup',0,'ticket_properties_set_status',0),
  (0,'usergroup',0,'ticket_properties_set_tasks',0),
  (0,'usergroup',0,'ticket_properties_set_related_tickets',0),
  (0,'usergroup',0,'ticket_properties_set_time_proposed',0),
  (0,'usergroup',0,'ticket_properties_set_time_worked',0),
  (0,'usergroup',0,'ticket_properties_change_type',0),
  (0,'usergroup',0,'ticket_properties_change_assigned_to',0),
  (0,'usergroup',0,'ticket_properties_change_milestone',0),
  (0,'usergroup',0,'ticket_properties_change_version',0),
  (0,'usergroup',0,'ticket_properties_change_component',1),
  (0,'usergroup',0,'ticket_properties_change_severity',0),
  (0,'usergroup',0,'ticket_properties_change_priority',0),
  (0,'usergroup',0,'ticket_properties_change_status',0),
  (0,'usergroup',0,'ticket_properties_change_summary',0),
  (0,'usergroup',0,'ticket_properties_change_tasks',0),
  (0,'usergroup',0,'ticket_properties_change_related_tickets',0),
  (0,'usergroup',0,'ticket_properties_change_time_proposed',0),
  (0,'usergroup',0,'ticket_properties_change_time_worked',0),
  (0,'usergroup',0,'ticket_properties_complete_tasks',0),
  (0,'usergroup',0,'edit_ticket_history',0),
  (0,'usergroup',0,'delete_ticket_history',0),
  (0,'usergroup',0,'create_wiki_page',0),
  (0,'usergroup',0,'edit_wiki_page',0),
  (0,'usergroup',0,'delete_wiki_page',0),
  (0,'usergroup',3,'create_tickets',0),
  (0,'usergroup',3,'comment_on_tickets',0),
  (0,'usergroup',3,'update_tickets',0),
  (0,'usergroup',3,'vote_on_tickets',0),
  (0,'usergroup',3,'add_attachments',0),
  (0,'role',0,'view',1),
  (0,'role',0,'project_settings',0),
  (0,'role',0,'delete_timeline_events',0),
  (0,'role',0,'view_tickets',1),
  (0,'role',0,'create_tickets',1),
  (0,'role',0,'update_tickets',1),
  (0,'role',0,'delete_tickets',0),
  (0,'role',0,'move_tickets',0),
  (0,'role',0,'comment_on_tickets',1),
  (0,'role',0,'edit_ticket_description',0),
  (0,'role',0,'vote_on_tickets',1),
  (0,'role',0,'add_attachments',1),
  (0,'role',0,'view_attachments',1),
  (0,'role',0,'delete_attachments',0),
  (0,'role',0,'perform_mass_actions',0),
  (0,'role',0,'ticket_properties_set_assigned_to',1),
  (0,'role',0,'ticket_properties_set_milestone',1),
  (0,'role',0,'ticket_properties_set_version',1),
  (0,'role',0,'ticket_properties_set_component',1),
  (0,'role',0,'ticket_properties_set_severity',1),
  (0,'role',0,'ticket_properties_set_priority',1),
  (0,'role',0,'ticket_properties_set_status',1),
  (0,'role',0,'ticket_properties_set_tasks',1),
  (0,'role',0,'ticket_properties_set_related_tickets',1),
  (0,'role',0,'ticket_properties_set_time_proposed',1),
  (0,'role',0,'ticket_properties_set_time_worked',1),
  (0,'role',0,'ticket_properties_change_type',1),
  (0,'role',0,'ticket_properties_change_assigned_to',1),
  (0,'role',0,'ticket_properties_change_milestone',1),
  (0,'role',0,'ticket_properties_change_version',1),
  (0,'role',0,'ticket_properties_change_component',1),
  (0,'role',0,'ticket_properties_change_severity',1),
  (0,'role',0,'ticket_properties_change_priority',1),
  (0,'role',0,'ticket_properties_change_status',1),
  (0,'role',0,'ticket_properties_change_summary',1),
  (0,'role',0,'ticket_properties_change_tasks',1),
  (0,'role',0,'ticket_properties_change_related_tickets',1),
  (0,'role',0,'ticket_properties_change_time_proposed',1),
  (0,'role',0,'ticket_properties_change_time_worked',1),
  (0,'role',0,'ticket_properties_complete_tasks',1),
  (0,'role',0,'edit_ticket_history',0),
  (0,'role',0,'delete_ticket_history',0),
  (0,'role',0,'create_wiki_page',0),
  (0,'role',0,'edit_wiki_page',0),
  (0,'role',0,'delete_wiki_page',0),
  (0,'role',1,'project_settings',1),
  (0,'role',1,'delete_timeline_events',1),
  (0,'role',1,'delete_tickets',1),
  (0,'role',1,'move_tickets',1),
  (0,'role',1,'edit_ticket_description',1),
  (0,'role',1,'delete_attachments',1),
  (0,'role',1,'edit_ticket_history',1),
  (0,'role',1,'delete_ticket_history',1),
  (0,'role',1,'perform_mass_actions',1),
  (0,'role',1,'create_wiki_page',1),
  (0,'role',1,'edit_wiki_page',1),
  (0,'role',1,'delete_wiki_page',1);


-- Dump of table traq_plugins
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `traq_plugins`;

CREATE TABLE `traq_plugins` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `file` VARCHAR(255) NOT NULL DEFAULT '' COLLATE utf8_unicode_ci,
  `enabled` INTEGER NOT NULL DEFAULT '1'
);


INSERT INTO `traq_plugins` (`id`, `file`, `enabled`)
VALUES
	(1,'markdown',1);

-- Dump of table traq_priorities
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `traq_priorities`;

CREATE TABLE `traq_priorities` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci,
  
);

INSERT INTO `traq_priorities` (`id`, `name`)
VALUES
	(1,'Highest'),
	(2,'High'),
	(3,'Normal'),
	(4,'Low'),
	(5,'Lowest');

-- Dump of table traq_project_roles
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `traq_project_roles`;

CREATE TABLE `traq_project_roles` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) DEFAULT NULL,
  `assignable` VARCHAR(255) NOT NULL DEFAULT '1',
  `project_id` INTEGER DEFAULT '0'
);

INSERT INTO `traq_project_roles` (`id`, `name`, `assignable`, `project_id`)
VALUES
	(1,'Manager',1,0),
	(2,'Developer',1,0),
	(3,'Tester',0,0);


-- Dump of table traq_projects
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `traq_projects`;

CREATE TABLE `traq_projects` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci,
  `slug` VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci,
  `codename` VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci,
  `info` TEXT NOT NULL COLLATE utf8_unicode_ci,
  `next_tid` INTEGER NOT NULL DEFAULT '1',
  `enable_wiki` INTEGER NOT NULL DEFAULT '0',
  `default_ticket_type_id` INTEGER DEFAULT NULL,
  `default_ticket_sorting` VARCHAR(255) NOT NULL DEFAULT 'priority.asc',
  `default_ticket_columns` TEXT NOT NULL DEFAULT '',
  `displayorder` INTEGER NOT NULL DEFAULT '0',
  `extra` TEXT NOT NULL DEFAULT '',
  `private_key` VARCHAR(255) NOT NULL DEFAULT ''
);

-- Dump of table traq_repo_changes
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `traq_repo_changes`;

CREATE TABLE `traq_repo_changes` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `changeset_id` INTEGER NOT NULL,
  `action` varchar(1) NOT NULL DEFAULT '',
  `path` TEXT NOT NULL,
  `from_path` TEXT,
  `revision` VARCHAR(255) DEFAULT NULL,
  `branch` VARCHAR(255) DEFAULT NULL
);

-- Dump of table traq_repo_changeset_parents
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `traq_repo_changeset_parents`;

CREATE TABLE `traq_repo_changeset_parents` (
  `changeset_id` INTEGER PRIMARY KEY NOT NULL,
  `parent_id` INTEGER NOT NULL
);

-- Dump of table traq_repo_changesets
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `traq_repo_changesets`;

CREATE TABLE `traq_repo_changesets` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `repository_id` INTEGER NOT NULL,
  `revision` VARCHAR(255) NOT NULL DEFAULT '',
  `commiter` VARCHAR(255) DEFAULT '',
  `committed_on` DATETIME NOT NULL,
  `comment` TEXT,
  `user_id` INTEGER DEFAULT NULL
);

-- Dump of table traq_repositories
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `traq_repositories`;

CREATE TABLE `traq_repositories` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `project_id` INTEGER NOT NULL,
  `slug` VARCHAR(255) NOT NULL DEFAULT '',
  `type` VARCHAR(255) NOT NULL,
  `location` VARCHAR(255) DEFAULT NULL,
  `username` VARCHAR(255) DEFAULT NULL,
  `password` VARCHAR(255) DEFAULT NULL,
  `extra` TEXT,
  `is_default` INTEGER DEFAULT NULL
);

-- Dump of table traq_settings
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `traq_settings`;

CREATE TABLE `traq_settings` (
  `setting` VARCHAR(255) NOT NULL PRIMARY KEY COLLATE utf8_unicode_ci,
  `value` TEXT NOT NULL COLLATE utf8_unicode_ci
);


INSERT INTO `traq_settings` (`setting`, `value`)
VALUES
	('allow_registration','1'),
  ('email_validation','0'),
	('check_for_update','1'),
	('date_time_format','g:iA d/m/Y'),
  ('date_format','d/m/Y'),
	('locale','enus'),
	('theme','default'),
  ('ticket_creation_delay', '30'),
  ('ticket_history_sorting', 'oldest_first'),
  ('tickets_per_page', '25'),
	('timeline_day_format','l, jS F Y'),
  ('timeline_days_per_page','7'),
	('timeline_time_format','h:iA'),
	('title','Traq'),
  ('site_name', ''),
  ('site_url', '');


-- Dump of table traq_ticket_relationships
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `traq_ticket_relationships` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `ticket_id` INTEGER NOT NULL,
  `related_ticket_id` INTEGER NOT NULL
);

-- Dump of table traq_severities
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `traq_severities`;

CREATE TABLE `traq_severities` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci
);


INSERT INTO `traq_severities` (`id`, `name`)
VALUES
	(1,'Blocker'),
	(2,'Critical'),
	(3,'Major'),
	(4,'Normal'),
	(5,'Minor'),
	(6,'Trivial');


-- Dump of table traq_subscriptions
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `traq_subscriptions`;

CREATE TABLE `traq_subscriptions` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `type` VARCHAR(255) NOT NULL,
  `user_id` INTEGER NOT NULL,
  `project_id` INTEGER NOT NULL,
  `object_id` INTEGER NOT NULL
);

-- Dump of table traq_ticket_history
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `traq_ticket_history`;

CREATE TABLE `traq_ticket_history` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `user_id` INTEGER NOT NULL,
  `ticket_id` INTEGER NOT NULL,
  `changes` TEXT NOT NULL COLLATE utf8_unicode_ci,
  `comment` TEXT NOT NULL COLLATE utf8_unicode_ci,
  `created_at` DATETIME NOT NULL
);

-- Dump of table traq_statuses
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `traq_statuses`;

CREATE TABLE `traq_statuses` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci,
  `status` INTEGER NOT NULL,
  `changelog` INTEGER NOT NULL DEFAULT '1'
);


INSERT INTO `traq_statuses` (`id`, `name`, `status`, `changelog`)
VALUES
	(1,'New',1,0),
	(2,'Accepted',1,0),
	(3,'Closed',0,1),
	(4,'Completed',0,1);


-- Dump of table traq_types
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `traq_types`;

CREATE TABLE `traq_types` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci,
  `bullet` VARCHAR(16) NOT NULL COLLATE utf8_unicode_ci,
  `changelog` INTEGER NOT NULL DEFAULT '1',
  `template` TEXT NOT NULL COLLATE utf8_unicode_ci
);


INSERT INTO `traq_types` (`id`, `name`, `bullet`, `changelog`, `template`)
VALUES
	(1,'Defect','-',1,''),
	(2,'Feature Request','+',1,''),
	(3,'Enhancement','*',1,''),
	(4,'Task','*',1,'');


-- Dump of table traq_tickets
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `traq_tickets`;

CREATE TABLE `traq_tickets` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `ticket_id` INTEGER NOT NULL,
  `summary` VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci,
  `body` LONGTEXT NOT NULL COLLATE utf8_unicode_ci,
  `user_id` INTEGER NOT NULL,
  `project_id` INTEGER NOT NULL,
  `milestone_id` INTEGER NOT NULL default '0',
  `version_id` INTEGER NOT NULL,
  `component_id` INTEGER NOT NULL,
  `type_id` INTEGER NOT NULL,
  `status_id` INTEGER NOT NULL DEFAULT '1',
  `priority_id` INTEGER NOT NULL DEFAULT '3',
  `severity_id` INTEGER NOT NULL,
  `assigned_to_id` INTEGER NOT NULL,
  `is_closed` INTEGER NOT NULL DEFAULT '0',
  `is_private` INTEGER NOT NULL DEFAULT '0',
  `votes` INTEGER DEFAULT '0',
  `tasks` LONGTEXT NULL COLLATE utf8_unicode_ci,
  `extra` LONGTEXT NOT NULL COLLATE utf8_unicode_ci,
  `time_proposed` VARCHAR(255),
  `time_worked` VARCHAR(255),
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL
);

-- Dump of table traq_timeline
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `traq_timeline`;

CREATE TABLE `traq_timeline` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `project_id` INTEGER NOT NULL,
  `owner_id` INTEGER NOT NULL,
  `action` VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci,
  `data` TEXT NULL COLLATE utf8_unicode_ci,
  `user_id` INTEGER NOT NULL,
  `created_at` DATETIME NOT NULL
);

-- Dump of table traq_user_roles
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `traq_user_roles`;

CREATE TABLE `traq_user_roles` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `user_id` INTEGER DEFAULT NULL,
  `project_id` INTEGER DEFAULT NULL,
  `project_role_id` INTEGER DEFAULT NULL
);

-- Dump of table traq_usergroups
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `traq_usergroups`;

CREATE TABLE `traq_usergroups` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci,
  `is_admin` INTEGER NOT NULL DEFAULT '0'
);


INSERT INTO `traq_usergroups` (`id`, `name`, `is_admin`)
VALUES
	(1,'Administrators',1),
	(2,'Members',0),
	(3,'Guests',0);


-- Dump of table traq_users
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `traq_users`;

CREATE TABLE `traq_users` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci,
  `password` VARCHAR(255) NOT NULL,
  `password_ver` VARCHAR(255) DEFAULT 'crypt',
  `name` VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci,
  `email` VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci,
  `group_id` INTEGER NOT NULL DEFAULT '2',
  `locale` VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci,
  `options` TEXT COLLATE utf8_unicode_ci COLLATE utf8_unicode_ci,
  `login_hash` VARCHAR(255) NOT NULL DEFAULT '0',
  `api_key` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL
);

-- Dump of table traq_wiki
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `traq_wiki`;

CREATE TABLE `traq_wiki` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `project_id` INTEGER NOT NULL,
  `title` VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci,
  `slug` VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci,
  `main` INTEGER NOT NULL DEFAULT '0',
  `revision_id` INTEGER NULL
);

-- Dump of table traq_wiki_revisions
-- ------------------------------------------------------------

DROP TABLE IF EXISTS `traq_wiki_revisions`;

CREATE TABLE `traq_wiki_revisions` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `wiki_page_id` INTEGER NOT NULL,
  `revision` INTEGER NOT NULL DEFAULT '1',
  `content` LONGTEXT NOT NULL COLLATE utf8_unicode_ci,
  `user_id` INTEGER NOT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL
);