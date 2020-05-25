<?php

$poll = $this->poll;

$hits_all = $poll->getHits();

$results = '';
if ($hits_all > 0) {
    foreach ($poll->getOptions() as $option) {
        $hits = $option->getHits();

        $percent = (int) ($hits / $hits_all * 100);
        $results .= '
<div class="progress bb-progress-thin">
    <div class="progress-bar bb-blue-bg" role="progressbar" aria-valuenow="' . $percent . '" aria-valuemin="0" aria-valuemax="100" data-percent="' . $percent . '">
                    <span class="poll-vote-value">' . $percent . ' % [ ' . $option->getHits() .
                    ' Stimmen ]</span> ' . $option->title . '</div>
</div>
';
    }
} else {
    $results .= '<p>Es liegen keine Abstimmungen vor.</p>';
}

echo '
<div class="poll">
    <p class="poll-title"><h3>Umfrage: ' . $poll->title . '</h3></p>
    <p class="poll-info">Status: ' . ('1' == $poll->status ? '<span class="poll-status-online" title="online"></span>' : '<span class="poll-status-offline" title="offline"></span>') . ' Antworten: ' . $hits_all . '</p>
    <div class="poll-results">' . $results . '</div>
</div>
';
