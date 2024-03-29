<?php
use traq\libraries\AtomResponse;

// Get entries
$entries = array();
foreach ($days as $day) {
    foreach ($day['activity'] as $row) {
        $entry = array();
        // Ticket created, closed and reopened
        if (in_array($row->action, array('ticket_created','ticket_closed','ticket_reopened','ticket_updated'))) {
            $entry['title'] = l("timeline.{$row->action}",
                array(
                    'ticket_summary'     => $row->ticket()->summary,
                    'ticket_id'          => $row->ticket()->ticket_id,
                    'ticket_type_name'   => $row->ticket()->type->name,
                    'ticket_status_name' => ($row->action == 'ticket_updated' ? null : $row->ticket_status()->name)
                )
            );
            $entry['id'] = "timeline:{$row->id}:ticket:{$row->ticket()->ticket_id}:{$row->action}";
            $entry['author'] = array(
                'name' => $row->user->name
            );
            $entry['link'] = Request::base($row->ticket()->href(), true);
        }
        // Milestones
        elseif (in_array($row->action, array('milestone_completed', 'milestone_cancelled'))) {
            $entry['title'] = l("timeline.{$row->action}", $row->milestone()->name);
            $entry['id'] = "timeline:{$row->id}:milestone:{$row->milestone()->slug}";
            $entry['link'] = Request::base($row->milestone()->href(), true);
        }
        // Ticket comments
        elseif ($row->action == 'ticket_comment') {
            $entry['title'] = l('timeline.ticket_comment', $row->ticket()->summary, $row->ticket()->ticket_id);
            $entry['id'] = "timeline:{$row->id}:ticket:{$row->ticket()->ticket_id}:commented";
            $entry['link'] = Request::base($row->ticket()->href(), true);
        }
        // Moved tickets
        elseif ($row->action == 'ticket_moved_from' or $row->action == 'ticket_moved_to') {
            $entry['title'] = l("timeline.{$row->action}", array('ticket' => $row->ticket()->summary, 'project' => $row->other_project()->name));
            $entry['id'] = "timeline:{$row->id}:ticket:{$row->ticket()->ticket_id}:moved";
            $entry['link'] = Request::base($row->ticket()->href(), true);
        }
        // Wiki new/edite page
        elseif ($row->action == 'wiki_page_created' or $row->action == 'wiki_page_edited') {
            $entry['title'] = l("timeline.{$row->action}", array('title' => $row->wiki_page()->title, 'slug' => $row->wiki_page()->slug));
            $entry['id'] = "timeline:{$row->id}:wiki:{$row->owner_id}" . ($row->action == 'wiki_page_created' ? 'created' : 'edited');
            $entry['link'] = Request::base($row->wiki_page()->href(), true);
        }

        $entry['updated'] = Time::date("c", $row->created_at);
        $entries[] = $entry;
    }
}

// Make feed
$app->response = new AtomResponse(200, array(
    'title' => l('x_timeline_feed', $app->project->name),
    'link' => Request::base('', true),
    'feed_link' => Request::base(Request::uri(), true),
    'updated' => $entries[0]['updated'],
    'entries' => $entries,
));

// Output feed
print($app->response->body());
