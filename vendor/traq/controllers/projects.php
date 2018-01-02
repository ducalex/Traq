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

namespace traq\controllers;

use avalon\http\Request;
use avalon\http\Session;
use avalon\output\View;
use avalon\Database;

use traq\models\Ticket;
use traq\models\Timeline;
use traq\models\Milestone;
use traq\models\Type;
use traq\models\Status;
use traq\helpers\Pagination;

/**
 * Project controller.
 *
 * @author Jack P.
 * @since 3.0
 * @package Traq
 * @subpackage Controllers
 */
class Projects extends AppController
{
    /**
     * Project listing page.
     */
    public function action_index()
    {
        $this->response['projects'] = $this->projects;
    }

    /**
     * Handles the project info page.
     */
    public function action_view()
    {
        // Make sure this is a project
        if (!$this->project) {
            return $this->show_404();
        }

        $this->response['project'] = $this->project;

        // Get open and closed ticket counts.
        View::set('ticket_count', array(
            'open' => Ticket::select()->where('project_id', $this->project->id)->where('is_closed', 0)->exec()->row_count(),
            'closed' => Ticket::select()->where('project_id', $this->project->id)->where('is_closed', 1)->exec()->row_count()
        ));
    }

    /**
     * Handles the roadmap page.
     */
    public function action_roadmap($which = 'active')
    {
        // Get the projects milestones and send them to the view.
        $milestones = Milestone::select()->where('project_id', $this->project->id);

        // Are we displaying all milestones?
        if ($which == 'all') {
            // We do NOTHING!
        }
        // Just the completed ones?
        elseif ($which == 'completed') {
            $milestones = $milestones->where('status', 2);
        }
        // Just the cancelled ones?
        elseif ($which == 'cancelled') {
            $milestones = $milestones->where('status', 0);
        }
        // Looks like just the active ones
        else {
            $milestones = $milestones->where('status', 1);
        }

        // Get the milestones and send them to the response
        $this->response['milestones'] = $milestones->order_by('displayorder', 'ASC')->exec()->fetch_all();
    }

    /**
     * Handles the milestone page.
     */
    public function action_milestone($milestone_slug)
    {
        // Get the milestone
        $milestone = Milestone::select()->where(array(
            array('project_id', $this->project->id),
            array('slug', $milestone_slug)
        ))->exec()->fetch();

        // Make sure milestone exists
        if (!$milestone) {
            return $this->show_404();
        }

        // Get the milestone and send it to the response
        $this->response['milestone'] = $milestone;
    }

    /**
     * Handles the changelog page.
     */
    public function action_changelog()
    {
        // Atom feed
        $this->feeds[] = array(Request::requestUri() . ".atom", l('x_changelog_feed', $this->project->name));

        // Fetch ticket types
        $types = array();
        foreach (Type::fetch_all() as $type) {
            $types[$type->id] = $type;
        }

        $this->response['milestones'] = $this->project->milestones->where('status', 2)->order_by('displayorder', 'DESC')->exec()->fetch_all();
        $this->response['types'] = $types;
    }

    /**
     * Handles the timeline page.
     */
    public function action_timeline()
    {
        $timeline_filters = array(
            'new_tickets'           => array('ticket_created'),
            'tickets_opened_closed' => array('ticket_closed', 'ticket_reopened'),
            'ticket_updates'        => array('ticket_updated'),
            'ticket_comments'       => array('ticket_comment'),
            'ticket_moves'          => array('ticket_moved_from', 'ticket_moved_to'),
            'milestones'            => array('milestone_completed', 'milestone_cancelled'),
            'wiki_pages'            => array('wiki_page_created', 'wiki_page_edited')
        );

        $days = $events = array();

        // Check if filters are set
        if ($req_filters = Request::post('filters', Session::get('timeline_filters'))) {
            // Fetch filters
            $filters = array_intersect_key($timeline_filters, $req_filters);
            // Save filters to session
            Session::set('timeline_filters', $req_filters);
        } 
        // Otherwise use them all
        else {
            $filters = $timeline_filters;
        }

        $events = call_user_func_array('array_merge', $filters); // Merge all selected event categories
        $filters = array_merge(
            array_fill_keys(array_keys($timeline_filters), false), // Set all filters to false
            array_fill_keys(array_keys($filters), true) // Then set enabled filters to true
        );
        
        // Atom feed
        $this->feeds[] = array(Request::requestUri() . ".atom", l('x_timeline_feed', $this->project->name));

        $days_query = $this->db->prepare("
            SELECT DISTINCT DATE(`created_at`) as `date`
            FROM {$this->db->prefix}timeline
            WHERE project_id = {$this->project->id} AND `action` IN ('" . implode("','", $events) . "')
            ORDER BY created_at DESC
        ")->exec()->fetch_all();

        // Pagination
        $pagination = new Pagination(Request::req('page', 1), settings('timeline_days_per_page'), count($days_query));

        // Limit?
        if ($pagination->paginate) {
            $days_query = array_slice($days_query, $pagination->limit, $pagination->per_page);
        }

        // Loop through the days and get their activity
        foreach ($days_query as $info) {
            // Fetch the activity for this day
            $fetch_activity = Timeline::select()
                ->where('project_id', $this->project->id)
                ->where('created_at', "{$info['date']} %", "LIKE")
                ->where('action', $events, "IN")
                ->order_by('created_at', 'DESC');

            // Push the days data to the
            // rows array,
            $days[] = array(
                'created_at' => $info['date'],
                'activity' => $fetch_activity->exec()->fetch_all()
            );
        }

        $this->response->objects = compact('days', 'filters', 'pagination');
    }

    /**
     * Delete timeline event.
     *
     * @param integer $event_id
     */
    public function action_delete_timeline_event($event_id)
    {
        if (!$this->user->permission($this->project->id, 'delete_timeline_events')) {
            return $this->show_no_permission();
        }

        $event = Timeline::find($event_id);
        $event->delete();

        if (!Request::isAjax()) {
            Request::redirectTo($this->project->href('timeline'));
        }

        View::set(compact('event'));
    }
}
