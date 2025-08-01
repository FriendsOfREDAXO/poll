<?php

use FriendsOfRedaxo\Poll\Poll;
use FriendsOfRedaxo\Poll\Question;
use FriendsOfRedaxo\Poll\Question\Choice;
use FriendsOfRedaxo\Poll\Vote;
use FriendsOfRedaxo\Poll\Vote\Answer;

if (rex::isBackend()) {
    rex_extension::register('PACKAGES_INCLUDED', static function ($params) {
        $addon = rex_addon::get('yform');

        if ($addon) {
            $pages = $addon->getProperty('pages');
            $ycom_tables = ['rex_poll', 'rex_poll_question', 'rex_poll_question_choice', 'rex_poll_vote', 'rex_poll_vote_answer'];

            if (isset($pages) && is_array($pages)) {
                foreach ($pages as $page) {
                    if (in_array($page->getKey(), $ycom_tables)) {
                        $page->setBlock('poll');
                    }
                }
            }
        }
    });
}

rex_yform_manager_dataset::setModelClass('rex_poll', Poll::class);
rex_yform_manager_dataset::setModelClass('rex_poll_question', Question::class);
rex_yform_manager_dataset::setModelClass('rex_poll_question_choice', Choice::class);
rex_yform_manager_dataset::setModelClass('rex_poll_vote', Vote::class);
rex_yform_manager_dataset::setModelClass('rex_poll_vote_answer', Answer::class);

if (rex::isBackend() && rex::getUser()) {
    rex_view::addCssFile($this->getAssetsUrl('rex-poll.css'));
    rex_view::addJsFile($this->getAssetsUrl('rex-poll.js'));
    rex_view::addCssFile($this->getAssetsUrl('poll-dashboard.css'));
    rex_view::addJsFile($this->getAssetsUrl('poll-dashboard.js'));
}
