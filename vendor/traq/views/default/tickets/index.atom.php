<?php
use traq\libraries\AtomResponse;

// Entries
$entries = array();
foreach ($tickets as $ticket) {
    $entries[] = array(
        'title' => $ticket->summary,
        'id' => "tickets:{$ticket->ticket_id}",
        'updated' => Time::date("c", $ticket->created_at),
        'link' => Request::base($ticket->href(), true),
        'author' => array(
            'name' => $ticket->user->name
        ),
        'content' => array(
            'type' => "html",
            'data' => $ticket->body
        ),
    );
}

// Make feed
$app->response = new AtomResponse(200, array(
    'title' => l('x_ticket_feed', $app->project->name),
    'link' => Request::base('', true),
    'feed_link' => Request::base(Request::uri(), true),
    'updated' => $entries[0]['updated'],
    'entries' => $entries,
));

// Output feed
print($app->response->body());