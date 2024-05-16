<?php

use Poll\Poll;
use Poll\User;

/** @var Poll $poll */
$poll = $this->poll;
$hash = rex_request('hash', 'string') != '' ? rex_request('hash', 'string') : User::getHash();

echo '<h1>{{ poll_title }}: ' . rex_escape($poll->getTitle()) . '</h1> ';
if ('' !== trim($poll->getDescription())) {
    echo '<p>' . rex_escape(nl2br($poll->getDescription()), 'html_simplified') . '</p> ';
}

echo $poll->getOutput();

if ($poll->showResult($hash)) {
    $items = [];
    $hitsAll = 0;
    foreach ($poll->getQuestions() as $question) {
        $choices = $question->getChoices();

        if ($choices->isEmpty()) {
            continue;
        }

        $description = '';
        if ('' !== $question->getDescription()) {
            $description = '<div class="poll-description">' . $question->getDescription() . '</div>';
        }

        $picture = '';
        if (rex_media::get($question->media)) {
            $picture = '<div class="poll-picture"><img src="/media/' . $question->media . '"/></div>';
        }

        $link = '';
        if ('' != $question->getUrl()) {
            $link = '<div class="poll-link"><a href="' . $question->getUrl() . '">mehr Informationen</a></div>';
        }


        $progressBar = [];
        $hitsAll = $question->getHits();
        if ($hitsAll != 0) {
            foreach ($choices as $choice) {
                $hits = $choice->getHits();
                $percent = (int)($hits / $hitsAll * 100);

                $progressBar[] =
                    '<div class="poll-progress-item" aria-valuenow="' . $percent . '" aria-valuemin="0" aria-valuemax="100">
                    <span class="poll-progress-label">' . rex_escape($choice->getTitle()) . '</span>
                    <progress class="poll-progress-bar" value="' . $percent . '" max="100">' . $percent . '%</progress>
                    <span class="poll-progress-value">' . $percent . '%<span>[' . $hits . ']</span></span>
                </div>';
            }
            $progressBarOut = '<div class="poll-progress">
                    ' . implode('', $progressBar) . '
                </div>';
        }

        $items[] =
            '<li>
                <div class="poll-title">' . $question->getTitle() . '</div>
                ' . $description . '
                ' . $picture . '
                ' . $link . '
                ' . $progressBarOut . '
            </li>';
    }

    if ($hitsAll != 0) {
        echo
        '<div class="poll">
            <h2>{{ poll_result }}</h2>
            ' . ($poll->getHits() > 0 ? '<p>{{ poll_votes_taken }} ' . $poll->getHits() . '</p>' : '') . '
            <ul class="poll-result-list">' . implode('', $items) . '</ul>
        </div>';
    }
}
