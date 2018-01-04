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
use avalon\http\Session;
use avalon\http\Cookie;
use avalon\output\View;
use avalon\core\Load;

use traq\models\Project;
use traq\models\Ticket;
use traq\models\TicketRelationship;
use traq\models\Milestone;
use traq\models\Status;
use traq\models\Type;
use traq\models\Component;
use traq\models\User;
use traq\models\Subscription;
use traq\models\CustomField;
use traq\models\Timeline;
use traq\helpers\TicketFilterQuery;
use traq\helpers\Pagination;

/**
 * Ticket controller.
 *
 * @author Jack P.
 * @since 3.0
 * @package Traq
 * @subpackage Controllers
 */
class Tickets extends AppController
{
    // Before filters
    public $before = array(
        'view' => array('_check_permission'),
        'new' => array('_check_permission'),
        'edit' => array('_check_permission'),
        'update' => array('_check_permission'),
        'delete' => array('_check_permission'),
        'template' => array('_check_permission'),
    );

    /**
     * Custom constructor, we need to do extra stuff.
     */
    public function __construct()
    {
        parent::__construct();

        // Set the title
        $this->title(l('tickets'));

        // Custom fields
        $this->custom_fields = CustomField::for_project($this->project->id);
        View::set('custom_fields', $this->custom_fields);
    }

    /**
     * Handles the ticket listing index page.
     */
    public function action_index()
    {
        // Atom feed
        $this->feeds[] = array(Request::requestUri() . ".atom?order_by=created_at.desc", l('x_ticket_feed', $this->project->name));

        // Valid columns
        $allowed_columns = ticketlist_allowed_columns();

        // Create ticket filter query
        $filter_query = new TicketFilterQuery();

        $filters = Request::req() ?: Session::get("ticket_filters.{$this->project->id}");
        $filters = $filters ? array_intersect_key($filters, ticket_filters_for($this->project)) : array();

        foreach($filters as $filter => $value) {
            $filter_query->process($filter, $value);
        }

        // Check query's order_by, then use project's default
        list($column, $direction) = explode('.', Request::req('order_by', $this->project->default_ticket_sorting));

        $foreign_keys = ['user', 'milestone', 'version', 'component', 'type', 'status', 'priority', 'severity', 'assigned_to'];

        if (in_array($column, $foreign_keys)) {
            $column = "{$column}_id";
        } elseif (in_array($column, $allowed_columns)) {
            $column = $column;
        } else {
            $column = 'ticket_id';
        }

        $direction = (strtolower($direction) === 'asc') ? 'asc' : 'desc';
        $column = ($column === 'ticket_id') ? 'id' : $column; // It is better to use the primary key
        $order = "$column.$direction";

        $page = Request::req('page') ?: 1;
        $per_page = settings('tickets_per_page');

        // Fetch tickets
        $tickets = $filter_query
            ->query()
            ->offset(($page - 1) * $per_page)
            ->limit($per_page + 1) // We get one more record than we need to see if there's a next page.
            ->order_by($column, $direction)
            ->exec()->fetch_all();

        $more_pages = array_splice($tickets, $per_page) ? 1 : 0;

        // Paginate tickets
        $pagination = new Pagination($page, $per_page, $total = ($page * $per_page) + $more_pages);

        $filters = $filter_query->filters();

        // Add custom fields
        foreach ($this->custom_fields as $field) {
            $allowed_columns[] = $field->id;
        }

        // Set columns from form
        if (Request::post('update_columns')) {
            Session::set('columns', $columns = array_values(Request::post('columns', array())));
        } elseif(Request::req('columns')) {
            $columns = explode(',', Request::req('columns'));
        } elseif(Session::get('columns')) {
            $columns = Session::get('columns');
        } else {
            $columns = $this->project->default_ticket_columns;
        }

        $columns = array_intersect($columns, $allowed_columns);

        if (empty($columns)) {
            $columns = ticket_columns();
        }

        // Send the tickets array to the response object..
        $this->response->objects = compact('tickets', 'filters', 'order', 'pagination', 'columns');
    }

    /**
     * Handles the view ticket page.
     *
     * @param integer $ticket_id
     */
    public function action_view($ticket_id)
    {
        // Does ticket exist?
        if (!$ticket = $this->_get_ticket($ticket_id)) {
            return $this->show_404();
        }

        // Set the title
        $this->title($ticket->summary);

        // Atom feed
        $this->feeds[] = array(Request::requestUri() . ".atom", l('x_x_history_feed', $this->project->name, $ticket->summary));

        // Ticket history
        switch(settings('ticket_history_sorting')) {
            case 'oldest_first':
                $ticket->history->order_by('created_at', 'ASC');
                break;

            case 'newest_first':
                $ticket->history->order_by('created_at', 'DESC');
                break;
        }

        $this->response['ticket'] = $ticket;
        $this->response['ticket_history'] = $ticket->history->exec()->fetch_all();
    }

    /**
     * Handles the add vote page.
     *
     * @param integer $ticket_id
     */
    public function action_vote($ticket_id)
    {
        // Does ticket exist?
        if (!$ticket = $this->_get_ticket($ticket_id)) {
            return $this->show_404();
        }

        // Don't let the owner vote on their own ticket
        if ($this->user->id == $ticket->user_id) {
            View::set('error', l('errors.already_voted'));
            return;
        }

        // Does the user have permission to vote on tickets?
        if (!$this->user->permission($this->project->id, 'vote_on_tickets')) {
            View::set('error', l('errors.must_be_logged_in'));
        }
        // Cast the vote
        elseif ($ticket->add_vote($this->user->id)) {
            $ticket->save();
            View::set('ticket', $ticket);
            View::set('error', false);
        }
        // They've already voted...
        else {
            View::set('error', l('errors.already_voted'));
        }
    }

    /**
     * Handles the tasks page.
     *
     * @param integer $ticket_id
     */
    public function action_manage_tasks($ticket_id)
    {
        if (!$this->user->permission($this->project->id, 'ticket_properties_set_tasks')) {
            return $this->show_no_permission();
        }

        $ticket = Ticket::select()->where("ticket_id", $ticket_id)->where("project_id", $this->project->id)->exec()->fetch();
        $this->response['tasks'] = $ticket->tasks ?: array();
        $this->render['view'] = 'tickets/tasks';
    }

  /**
     * Toggles the state of a task.
     *
     * @param integer $ticket_id
     * @param integer $task_id
     */
    public function action_toggle_task($ticket_id, $task_id)
    {
        $this->render['layout'] = false;

        if ($this->user->permission($this->project->id, 'ticket_properties_complete_tasks')) {
            // Get ticket, update task and save
            $ticket = Ticket::select()->where('project_id', $this->project->id)->where('ticket_id', $ticket_id)->exec()->fetch();
            $ticket->toggle_task($task_id, Request::req('completed') === 'true');
            return $ticket->save();
        }
    }

    /**
     * Handles the voters page.
     *
     * @param integer $ticket_id
     */
    public function action_voters($ticket_id)
    {
        // Does ticket exist?
        if (!$ticket = $this->_get_ticket($ticket_id)) {
            return $this->show_404();
        }

        // Have there been any votes?
        if (empty($ticket->extra['voted'])) {
            $voters = array();
        } else {
            $voters = array_map('User::find', $ticket->extra['voted']);
        }

        View::set('voters', $voters);
    }

    /**
     * Handles the new ticket page and ticket creation.
     */
    public function action_new()
    {
        // Set the title
        $this->title(l('new_ticket'));

        // Create a new ticket object
        $ticket = new Ticket(array(
            'severity_id' => 4,
            'priority_id' => 3,
            'status_id'   => 1,
            'type_id'     => $this->project->default_ticket_type_id
        ));

        // Check if the form has been submitted
        if (Request::method() == 'post') {
            // Set the ticket data
            $data = array(
                'summary'      => Request::post('summary'),
                'body'         => Request::post('description'),
                'user_id'      => $this->user->id,
                'project_id'   => $this->project->id,
                'milestone_id' => 0,
                'version_id'   => 0,
                'component_id' => 0,
                'type_id'      => Request::post('type', 1),
                'severity_id'  => 4,
                'tasks'        => array()
            );

            // Milestone
            if ($this->user->permission($this->project->id, 'ticket_properties_set_milestone')) {
                $data['milestone_id'] = Request::post('milestone');
            }

            // Version
            if ($this->user->permission($this->project->id, 'ticket_properties_set_version')) {
                $data['version_id'] = Request::post('version');
            }

            // Component
            if ($this->user->permission($this->project->id, 'ticket_properties_set_component')) {
                $data['component_id'] = Request::post('component');
            }

            // Severity
            if ($this->user->permission($this->project->id, 'ticket_properties_set_severity')) {
                $data['severity_id'] = Request::post('severity');
            }

            // Priority
            if ($this->user->permission($this->project->id, 'ticket_properties_set_priority')) {
                $data['priority_id'] = Request::post('priority');
            }

            // Status
            if ($this->user->permission($this->project->id, 'ticket_properties_set_status')) {
                $data['status_id'] = Request::post('status');
            }

            // Assigned to
            if ($this->user->permission($this->project->id, 'ticket_properties_set_assigned_to')) {
                $data['assigned_to_id'] = Request::post('assigned_to');
            }

            // Ticket tasks
            if ($this->user->permission($this->project->id, 'ticket_properties_set_tasks') and Request::post('tasks') != null) {
                $data['tasks'] = array_values(@json_decode(Request::post('tasks'), true) ?: array());
            }

            // Time proposed
            if ($this->user->permission($this->project->id, 'ticket_properties_set_time_worked')) {
                $data['time_proposed'] = Request::post('time_proposed');
            }

            // Time worked
            if ($this->user->permission($this->project->id, 'ticket_properties_set_time_proposed')) {
                $data['time_worked'] = Request::post('time_worked');
            }

            // Set the ticket data
            $ticket->set($data);

            // Custom fields, FUN!
            if (Request::post('custom_fields')) {
                $this->process_custom_fields($ticket, Request::post('custom_fields'));
            }

            // Check if the ticket data is valid...
            // if it is, save the ticket to the DB and
            // redirect to the ticket page.
            if (check_ticket_creation_delay($ticket) and $ticket->is_valid()) {
                // Set last ticket creation time
                Session::set('last_ticket_creation', time());

                $ticket->save();

                // Related tickets
                if ($this->user->permission($this->project->id, 'ticket_properties_set_related_tickets')) {
                    foreach (explode(',', Request::post('related_tickets')) as $ticket_id) {
                        $related = Ticket::select('id')
                            ->where('project_id', $this->project->id)
                            ->where('ticket_id', trim($ticket_id))
                            ->limit(1)->exec()->fetch();

                        if ($related) {
                            $relation = new TicketRelationship(array(
                                'ticket_id'         => $ticket->id,
                                'related_ticket_id' => $related->id
                            ));

                            $relation->save();
                        }
                    }
                }

                // Create subscription
                if ($this->user->option('watch_created_tickets')) {
                    $sub = new Subscription(array(
                        'type'       => 'ticket',
                        'user_id'    => $this->user->id,
                        'project_id' => $this->project->id,
                        'object_id'  => $ticket->id
                    ));
                    $sub->save();
                }

                $this->response->redirect = $ticket->href();
            }
        }

        $this->response['ticket'] = $ticket;
        $this->response->errors = $ticket->errors;
    }

    /**
     * Handles the updating of the ticket.
     */
    public function action_update($ticket_id)
    {
        // Does ticket exist?
        if (!$ticket = $this->_get_ticket($ticket_id)) {
            return $this->show_404();
        }

        // Set the title
        $this->title($ticket->summary);
        $this->title(l('update_ticket'));

        // Collect the new data
        $data = array(
            'summary'      => $ticket->summary,
            'milestone_id' => $ticket->milestone_id,
            'version_id'   => $ticket->version_id,
            'component_id' => $ticket->component_id,
            'type_id'      => $ticket->type_id,
            'severity_id'  => $ticket->severity_id,
            'priority_id'  => $ticket->priority_id,
            'status_id'    => $ticket->status_id,
            'tasks'        => $ticket->tasks
        );

        // Summary
        if ($this->user->permission($this->project->id, 'ticket_properties_change_summary')) {
            $data['summary'] = Request::post('summary', $ticket->summary);
        }

        // Type
        if ($this->user->permission($this->project->id, 'ticket_properties_change_type')) {
            $data['type_id'] = Request::post('type', $ticket->type->id);
        }

        // Milestone
        if ($this->user->permission($this->project->id, 'ticket_properties_change_milestone')) {
            $data['milestone_id'] = Request::post('milestone', $ticket->milestone_id);
        }

        // Version
        if ($this->user->permission($this->project->id, 'ticket_properties_change_version')) {
            $data['version_id'] = Request::post('version', $ticket->version_id);
        }

        // Component
        if ($this->user->permission($this->project->id, 'ticket_properties_change_component')) {
            $data['component_id'] = Request::post('component', $ticket->component_id);
        }

        // Severity
        if ($this->user->permission($this->project->id, 'ticket_properties_change_severity')) {
            $data['severity_id'] = Request::post('severity', $ticket->severity_id);
        }

        // Priority
        if ($this->user->permission($this->project->id, 'ticket_properties_change_priority')) {
            $data['priority_id'] = Request::post('priority', $ticket->priority_id);
        }

        // Status
        if ($this->user->permission($this->project->id, 'ticket_properties_change_status')) {
            $data['status_id'] = Request::post('status', $ticket->status_id);
        }

        // Assigned to
        if ($this->user->permission($this->project->id, 'ticket_properties_change_assigned_to')) {
            $data['assigned_to_id'] = Request::post('assigned_to', $ticket->assigned_to_id);
        }

        // Ticket tasks
        if ($this->user->permission($this->project->id, 'ticket_properties_change_tasks') and Request::post('tasks') != null) {
            $data['tasks'] = array_values(@json_decode(Request::post('tasks'), true) ?: array());
        }

        // Time proposed
        if ($this->user->permission($this->project->id, 'ticket_properties_change_time_worked')) {
            $data['time_proposed'] = Request::post('time_proposed');
        }

        // Time worked
        if ($this->user->permission($this->project->id, 'ticket_properties_change_time_proposed')) {
            $data['time_worked'] = Request::post('time_worked');
        }

        // Related tickets
        if ($this->user->permission($this->project->id, 'ticket_properties_change_related_tickets')) {
            $posted_related_tickets = preg_split('/[-\s,]+/', Request::post('related_tickets'));
            $new_relations = array_diff($posted_related_tickets, $ticket->related_ticket_tids());

            // New relations
            foreach ($new_relations as $related_tid) {
                // Fetch ticket info
                $related_ticket = Ticket::select('id')
                    ->where('project_id', $this->project->id)
                    ->where('ticket_id', $related_tid)
                    ->exec()->fetch();

                // Make sure the ticket exists
                if ($related_ticket) {
                    $relation = new TicketRelationship(array(
                        'ticket_id' => $ticket->id,
                        'related_ticket_id' => $related_ticket->id
                    ));
                    $relation->save();
                }
            }

            // Delete relations
            foreach ($ticket->ticket_relationships->exec()->fetch_all() as $relation) {
                if (!in_array($relation->related_ticket->ticket_id, $posted_related_tickets)) {
                    $relation->delete();
                }
            }
        }

        // Check if we're adding an attachment and that the user has permission to do so
        if ($this->user->permission($this->project->id, 'add_attachments') and $file = Request::files('attachment')) {
            $data['attachment'] = $file['name'];
        }

        // Custom fields, FUN!
        if (Request::post('custom_fields')) {
            $this->process_custom_fields($ticket, Request::post('custom_fields'));
        }

        // Update the ticket
        if ($this->response->status = $ticket->update_data($data)) {
            $this->response->redirect = $ticket->href();
        }

        $this->response['ticket'] = $ticket;
        $this->response['ticket_history'] = $ticket->history->exec()->fetch_all();
        $this->response->errors = $ticket->errors;

        $this->render['view'] = 'tickets/view';
    }

    /**
     * Processes the custom fields
     *
     * @param object $ticket
     * @param array  $custom_fields
     */
    private function process_custom_fields(&$ticket, $fields)
    {
        foreach ($this->custom_fields as $field) {
            if (in_array($ticket->type_id, $field->ticket_type_ids) or $field->ticket_type_ids[0] == 0) {
                if (isset($fields[$field->id])) {
                    if ($field->validate($fields[$field->id])) {
                        $ticket->set_custom_field($field->id, $field->name, $fields[$field->id]);
                    } else {
                        $ticket->_add_error($field->id, l("errors.custom_fields.x_is_not_valid", $field->name, $field->type));
                    }
                }

                // Check if field is required
                if ($field->is_required and empty($fields[$field->id])) {
                    $ticket->_add_error($field->id, l('errors.custom_fields.x_required', $field->name));
                }
            }
        }
    }

    /**
     * Handles the editing of the ticket description.
     */
    public function action_edit($ticket_id)
    {
        // Does ticket exist?
        if (!$ticket = $this->_get_ticket($ticket_id)) {
            return $this->show_404();
        }

        // Set the title
        $this->title($ticket->summary);
        $this->title(l('edit_ticket'));

        // Has the form been submitted?
        if (Request::method() == 'post') {
            // Set the ticket body
            $ticket->body = Request::post('body');

            // Save and redirect
            if ($this->response->status = $ticket->save()) {
                $this->response->redirect = $ticket->href();
            }
        }

        $this->response['ticket'] = $ticket;
        $this->response->errors = $ticket->errors;
    }

    /**
     * Move ticket.
     *
     * @param integer $ticket_id
     */
    public function action_move($ticket_id)
    {
        // Does ticket exist?
        if (!$ticket = $this->_get_ticket($ticket_id)) {
            return $this->show_404();
        }

        $next_step = 2;

        // Step 2
        if (Request::post('step') == 2) {
            $next_step = 3;
            $new_project = Project::find(Request::post('project_id'));
            View::set('new_project', $new_project);
        }
        // Step 3
        elseif (Request::post('step') == 3 or $this->is_api) {
            $next_step = 2;
            $new_project = Project::find(Request::post('project_id'));

            // Update ticket data
            $data = array(
                'project_id'     => Request::post('project_id'),
                'milestone_id'   => Request::post('milestone_id'),
                'version_id'     => Request::post('version_id', 0),
                'component_id'   => Request::post('component_id', 0),
                'assigned_to_id' => Request::post('assigned_to_id', 0)
            );

            // Set new ticket ID
            $ticket->ticket_id = $new_project->next_tid;
            $new_project->next_tid++;

            // Update ticket
            if ($ticket->update_data($data)) {
                $new_project->save();

                // Insert timeline event for old project
                $timeline = new Timeline(array(
                    'project_id' => $this->project->id,
                    'owner_id' => $ticket->id,
                    'action' => 'ticket_moved_to',
                    'data' => $new_project->id,
                    'user_id' => $this->user->id
                ));
                $timeline->save();

                // Insert timeline event for new project
                $timeline = new Timeline(array(
                    'project_id' => $new_project->id,
                    'owner_id' => $ticket->id,
                    'action' => 'ticket_moved_from',
                    'data' => $this->project->id,
                    'user_id' => $this->user->id
                ));
                $timeline->save();

                Request::redirectTo($new_project->href("tickets/{$ticket->ticket_id}"));
            }
        }

        View::set(compact('ticket', 'next_step'));
    }

    /**
     * Delete ticket.
     */
    public function action_delete($ticket_id)
    {
        // Does ticket exist?
        if (!$ticket = $this->_get_ticket($ticket_id)) {
            return $this->show_404();
        }
        $ticket->delete();
        Request::redirectTo($this->project->href('tickets'));
    }

    /**
     * Get ticket template
     */
    public function action_template($type_id)
    {
        $this->render['layout'] = false;
        return $template = Type::find($type_id) ? $template->template : '';
    }

    /**
     * Mass Actions.
     */
    public function action_mass_actions()
    {
        // Check permission
        if (!$this->user->permission($this->project->id, 'perform_mass_actions')) {
            return $this->show_no_permission();
        }

        // Decode tickets array
        $tickets = json_decode(Request::post('tickets'), true);

        // Make sure there are some tickets
        if (empty($tickets)) {
            Request::redirectTo($this->project->href('tickets'));
        }

        $valid_fields = array('type', 'milestone', 'version', 'component', 'severity', 'priority', 'status', 'assigned_to');

        // Loop over tickets and process actions
        foreach ($tickets as $ticket_id) {
            $ticket = Ticket::select('*')->where('project_id', $this->project->id)->where('ticket_id', $ticket_id)->exec()->fetch();

            $data = array();

            foreach ($valid_fields as $field) {
                if ($this->user->permission($this->project->id, "ticket_properties_change_$field")
                and Request::post($field, -1) != -1) {
                    $data["{$field}_id"] = Request::post($field);
                }
            }

            if (count($data) or Request::post('comment')) {
                $ticket->update_data($data);
                $ticket->save();
            }
        }

        // Clear selected tickets
        Cookie::delete('selected_tickets');

        Request::redirectTo($this->project->href('tickets'));
    }

    /**
     * Processes the ticket filters form and
     * builds the query string.
     */
    public function action_update_filters()
    {
        $query = array();
        $filters = Request::post('filters', array());

        // Add filter
        if ($new_filter = Request::post('new_filter')) {
            // Add the blank value
            $filters[$new_filter] = array(
                'prefix' => '',
                'values' => array()
            );
        }

        // Remove invalid filters
        $filters = array_intersect_key($filters, ticket_filters_for($this->project));

        foreach ($filters as $name => $filter) {
            if (!empty($filter['values'])) {
                $filter['values'] = array_filter($filter['values'], 'strlen');
            } else {
                $filter['values'] = array();
            }

            // Process filters
            switch ($name) {
                // Summary, description,
                // owner and assigned to
                case 'summary':
                case 'description':
                case 'owner':
                case 'assigned_to':
                case 'search':
                    $filter['values'][] = '';
                    $query[$name] = $filter['prefix'] . implode(',', $filter['values']);
                    break;

                // Milestone, version, type,
                // status and component
                case 'milestone':
                case 'version':
                case 'type':
                case 'status':
                case 'component':
                case 'priority':
                case 'severity':
                    // Class name
                    $class = '\\traq\\models\\' . ucfirst($name == 'version' ? 'milestone' : $name);
                    $field = ($name === 'milestone' || $name === 'version') ? 'slug' : 'name';

                    // Values
                    $values = array();
                    foreach ($filter['values'] as $value) {
                        $values[] = $class::find($value)->{$field};
                    }

                    $query[$name] = $filter['prefix'] . implode(',', $values);
                    break;
            }

            // Process custom field filters
            if ($field = CustomField::find('slug', $name)) {
                $query[$field->slug] = $filter['prefix'] . implode(',', $filter['values']);
            }
        }

        // Save to session and redirect
        Session::set("ticket_filters.{$this->project->id}", $query);
        Request::redirect(Request::url($this->project->href('tickets'), $query));
    }


    /**
     * Get the ticket
     */

    public function _get_ticket($ticket_id)
    {
        return Ticket::select()->where("ticket_id", $ticket_id)->where("project_id", $this->project->id)->exec()->fetch();
    }

    /**
     * Used to check the permission for the requested action.
     */
    public function _check_permission($method)
    {
        // Set the proper action depending on the method
        $actions = [
            'view'     => 'view_tickets',
            'template' => 'create_tickets',
            'new'      => 'create_tickets',
            'edit'     => 'edit_ticket_description',
            'update'   => 'update_tickets',
            'delete'   => 'delete_tickets',
        ];

        // Check if the user has permission
        if (!isset($actions[$method]) || !$this->user->permission($this->project->id, $actions[$method])) {
            // oh noes! display the no permission page.
            return $this->show_no_permission();
        }
    }
}
