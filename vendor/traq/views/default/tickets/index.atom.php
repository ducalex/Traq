<?php
use traq\helpers\Atom;

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
            'type' => "XHTML",
            'data' => $ticket->body
        ),
    );
}

// Make feed
$feed = new Atom(array(
    'title' => l('x_ticket_feed', $app->project->name),
    'link' => Request::base('', true),
    'feed_link' => Request::base(Request::requestUri()),
    'updated' => $entries[0]['updated'],
    'entries' => $entries,
));

// Output feed
print($feed->build());
