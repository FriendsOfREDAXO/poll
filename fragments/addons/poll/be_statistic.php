<?php

use Poll\Poll;
use Poll\Vote\Answer;

/** @var Poll $poll */
$poll = $this->poll;

$hits_all = $poll->getHits();

$items = [];
if ($hits_all > 0) {
    foreach ($poll->getQuestions() as $question) {
        $result = [];
        $choices = $question->getChoices();
        if ($choices->isEmpty()) {
            /** @var Answer[] $answers */
            $answers = $question->getAnswers();

            $answerItems = [];
            foreach ($answers as $answer) {
                $answerItems[] = '<li><blockquote>'.rex_escape($answer->getText()).'</blockquote></li>';
            }

            $result[] = '<h4 class="poll-list-title">'.rex_i18n::msg('poll_answers').'</h4><ul class="poll-answer-list">'.implode('', $answerItems).'</ul>';
        } else {
            $hitsAll = $question->getHits();
            foreach ($choices as $choice) {
                $hits = $choice->getHits();
                $percent = (int)($hits / $hitsAll * 100);

                $result[] =
                    '<div class="poll-progress" aria-valuenow="'.$percent.'" aria-valuemin="0" aria-valuemax="100">
                        <span class="poll-progress-label">'.rex_escape($choice->getTitle()).'</span>
                        <progress class="poll-progress-bar" value="'.$percent.'" max="100">'.$percent.'%</progress>
                        <span class="poll-progress-value">'.$percent.'%<span>['.$hits.']</span></span>
                    </div>';
            }
        }

        $items[] =
            '<li>
                <h3 class="poll-title">'.$question->getTitle().'</h3>
                '.implode('', $result).'
            </li>';
    }
} else {
    $items[] = '<li>Es liegen keine Abstimmungen vor.</li>';
}

echo
    '<div class="poll">
        <h2 class="poll-heading">'.rex_i18n::msg('poll').': '.$poll->getTitle().'</h2>
        <dl class="poll-info-list">
            <dt>'.rex_i18n::msg('poll_status').'</dt>
            <dd>'.($poll->isOnline() ? '<span class="rex-online" title="online">'.rex_i18n::msg('poll_online').'</span>' : '<span class="rex-offline" title="offline">'.rex_i18n::msg('poll_offline').'</span>').'</dd>
            <dt>'.rex_i18n::msg('poll_answers').'</dt>
            <dd>'.$hits_all.'</dd>
        </dl>
        <ul class="poll-result-list">'.implode('', $items).'</ul>
    </div>';
