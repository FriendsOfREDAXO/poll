<?php

/*
 * module:poll_basic_output v1.1
 */

$out = '';
$results = '';

$poll_id = (int)'REX_VALUE[1]';
$poll = null;

if ($poll_id > 0) {
    $poll = rex_poll::get($poll_id);
    if ($poll) {
        $out = '<h1>' . rex_i18n::msg('poll_title') . ': ' . $poll->title . ' </h1> ' . $poll->getOutput();

        if ($poll->showResult()) {
            $hits_all = $poll->getHits() > 0 ? $poll->getHits() : 1;
            foreach ($poll->getOptions() as $option) {
                $hits = $option->getHits();
                $percent = (int)($hits / $hits_all * 100);
                $results .= '<li><span class="poll-vote-value"><span>' . $percent . ' %</span > [' . $option->getHits() . ']</span >' . $option->title . '</li> ';
                $results .= '
                    <div class="progress bb-progress-thin">
                       <div class="progress-bar bb-blue-bg" role="progressbar" aria-valuenow="' . $percent . '" aria-valuemin="0" aria-valuemax="100" style="width: ' . $percent . '%;"></div>
                    </div>
                 ';
            }

            $out .= '
                <div class="rex-poll-results">
                    <h2>' . rex_i18n::msg('poll_result') . ':</h2>
                    ' . ($poll->getHits() > 0 ? '<p>' . rex_i18n::msg('poll_votes_taken', $poll->getHits()) . '</p>' : '') . '
                    <ul> ' . $results . '</ul>
                </div>
            ';
        }
    }
}

echo '
    <div class="rex-poll">
        ' . $out . '
    </div>
';

