<?php
use traq\helpers\Atom;

// Entries
$entries = array();
$updates = $ticket->history->order_by('id', 'DESC')->exec()->fetch_all();
foreach (array_reverse($updates) as $update) {
    $content = array();

    if (is_array($update->changes)) {
        $content[] = "<ul>";
        foreach ($update->changes as $change) {
            $content[] = "    <li>" . View::get('tickets/_history_change_bit', array('change' => $change)) . "</li>";
        }
        $content[] = "</ul>";
    }

    if ($update->comment != '') {
        $content[] = "<hr />";
        $content[] = format_text($update->comment);
    }

    $entries[] = array(
        'title' => l('update_x', count($entries)+1),
        'id' => "tickets:{$ticket->ticket_id}:update:{$update->id}",
        'updated' => Time::date("c", $update->created_at),
        'link' => Request::base($ticket->href(), true),
        'author' => array(
            'name' => $update->user->name
        ),
        'content' => array(
            'type' => "html",
            'data' => implode(PHP_EOL, $content)
        ),
    );
}

// Make feed
$entries = array_reverse($entries);
$feed = new Atom(array(
    'title' => l('x_x_history_feed', $app->project->name, $ticket->summary),
    'link' => Request::base('', true),
    'feed_link' => Request::base(Request::uri(), true),
    'updated' => $entries[0]['updated'],
    'entries' => $entries,
));

// Output feed
print($feed->build());
