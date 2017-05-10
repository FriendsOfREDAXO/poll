<?php

/*
 * module:poll_basic_output v1.0
 */

$poll_id = (int) 'REX_VALUE[1]';
$poll = null;
if ($poll_id > 0) {
    $poll = rex_poll::get($poll_id);
}

if ($poll) {
    echo '<div class="rex_poll">';
    echo '<h2>Umfrage: '.$poll->title.'</h2>';

    if ($poll->executeVote()) {
        echo '<p>Ihre Abstimmung wurde aufgenommen</p>';
    }

    if ($poll->status == 0) {
        echo '<p>Die Umfrage ist beendet</p>';
    } elseif (!rex_poll_user::hasVoted($poll)) {
        $li = [];
        foreach ($poll->getRelatedCollection('options') as $option) {
            $li[] = '<li><a href="'.rex_getUrl('', '', ['rex_poll['.$poll->id.']' => $option->id]).'">'.$option->title.'</a></li>';
        }
        echo '<h3>Bitte abstimmen:</h2><ul>'.implode('', $li).'</ul>';
    }

    if ($poll->showResult()) {
        $hits_all = $poll->getHits();
        $li = [];
        foreach ($poll->getOptions() as $option) {
            $hits = $option->getHits();
            $showpercent = 1;
            $percent = (int) ($hits / $hits_all * 100);
            if ($percent > 1) $showpercent = $percent;
            $li[] = '<li>'.$option->title.' <span class="poll-balken" style="width:'.$showpercent.'px;background-color:#f90; display:inline-block; height:13px; margin:0px 5px;"></span>'.$percent.'% ['.$option->getHits().']</li>';
        }
        echo '<h3>Ergebnis:</h2><ul>'.implode('', $li).'</ul>';
    }
    echo '</div>';
}
