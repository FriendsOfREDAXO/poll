<?php

/**
 * Sample Data Installation Page.
 *
 * @package poll
 */

use FriendsOfRedaxo\Poll\Poll;
use FriendsOfRedaxo\Poll\Question;
use FriendsOfRedaxo\Poll\Question\Choice;
use FriendsOfRedaxo\Poll\Vote;
use FriendsOfRedaxo\Poll\Vote\Answer;

// Title of the page
echo rex_view::title($this->i18n('poll') . ' - ' . $this->i18n('poll_sample_data'));

$content = '';
$success = '';
$error = '';

// Handle sample data installation
if (rex_post('install_sample', 'boolean') && rex_csrf_token::factory('poll-sample')->isValid()) {
    try {
        // Load sample poll data
        $sampleData = json_decode(rex_file::get(rex_path::addon('poll', 'install/sample_poll.json')), true);

        if (!$sampleData) {
            throw new Exception('Could not load sample data');
        }

        // Check if sample poll already exists
        $existingPoll = Poll::query()->where('title', $sampleData['title'])->findOne();
        if ($existingPoll) {
            $error = rex_i18n::msg('poll_sample_already_exists');
        } else {
            // Create the poll
            $poll = Poll::create();
            $poll->title = $sampleData['title'];
            $poll->description = $sampleData['description'];
            $poll->type = $sampleData['type'];
            $poll->status = $sampleData['status'];
            $poll->comment = $sampleData['comment'];
            $poll->showresult = $sampleData['showresult'];

            if (!$poll->save()) {
                throw new Exception('Could not save poll');
            }

            // Create questions and choices
            $choiceIds = [];
            foreach ($sampleData['questions'] as $questionData) {
                $question = Question::create();
                $question->poll_id = $poll->getId();
                $question->title = $questionData['title'];
                $question->description = $questionData['description'];
                $question->media = $questionData['media'];
                $question->url = $questionData['url'];

                if (!$question->save()) {
                    throw new Exception('Could not save question');
                }

                // Create choices and store their IDs
                foreach ($questionData['choices'] as $index => $choiceData) {
                    $choice = Choice::create();
                    $choice->question_id = $question->getId();
                    $choice->title = $choiceData['title'];

                    if (!$choice->save()) {
                        throw new Exception('Could not save choice');
                    }

                    $choiceIds[$index] = $choice->getId();
                }

                // Create sample votes if provided
                if (isset($sampleData['sample_votes']) && !empty($sampleData['sample_votes'])) {
                    foreach ($sampleData['sample_votes'] as $voteData) {
                        // Create vote
                        $vote = Vote::create();
                        $vote->status = 1;
                        $vote->poll_id = $poll->getId();
                        $vote->create_datetime = $voteData['create_datetime'];
                        $vote->user_hash = $voteData['user_hash'];
                        $vote->comment = $voteData['comment'];

                        if (!$vote->save()) {
                            throw new Exception('Could not save vote');
                        }

                        // Create answer for this vote
                        $answer = Answer::create();
                        $answer->vote_id = $vote->getId();
                        $answer->question_id = $question->getId();
                        $answer->question_choice_id = $choiceIds[$voteData['choice_index']];
                        $answer->text = $voteData['comment'];
                        $answer->create_datetime = $voteData['create_datetime'];

                        if (!$answer->save()) {
                            throw new Exception('Could not save answer');
                        }
                    }
                }
            }

            $success = rex_i18n::msg('poll_sample_installed_success');
        }
    } catch (Exception $e) {
        $error = rex_i18n::msg('poll_sample_install_error') . ': ' . $e->getMessage();
    }
}

// Check if sample data already exists
$sampleData = json_decode(rex_file::get(rex_path::addon('poll', 'install/sample_poll.json')), true);
$existingPoll = null;
if ($sampleData) {
    $existingPoll = Poll::query()->where('title', $sampleData['title'])->findOne();
}

// Display messages
if ($success) {
    $content .= rex_view::success($success);
}
if ($error) {
    $content .= rex_view::error($error);
}

// Main content
$content .= '<div class="poll-sample-data">';

if ($existingPoll) {
    $content .= '<div class="alert alert-info">';
    $content .= '<h4>' . rex_i18n::msg('poll_sample_already_installed') . '</h4>';
    $content .= '<p>' . rex_i18n::msg('poll_sample_already_installed_desc') . '</p>';
    $content .= '<p><strong>' . rex_i18n::msg('poll_title') . ':</strong> ' . rex_escape($existingPoll->getTitle()) . '</p>';
    $content .= '<p><strong>' . rex_i18n::msg('poll_status') . ':</strong> ' . ($existingPoll->isOnline() ? rex_i18n::msg('poll_status_active') : rex_i18n::msg('poll_status_inactive')) . '</p>';
    $content .= '</div>';
} else {
    $content .= '<div class="alert alert-info">';
    $content .= '<h4>' . rex_i18n::msg('poll_sample_data_info') . '</h4>';
    $content .= '<p>' . rex_i18n::msg('poll_sample_data_description') . '</p>';
    $content .= '</div>';

    if ($sampleData) {
        $content .= '<div class="panel panel-default">';
        $content .= '<div class="panel-heading"><h4>' . rex_i18n::msg('poll_sample_preview') . '</h4></div>';
        $content .= '<div class="panel-body">';
        $content .= '<p><strong>' . rex_i18n::msg('poll_title') . ':</strong> ' . rex_escape($sampleData['title']) . '</p>';
        $content .= '<p><strong>' . rex_i18n::msg('poll_question') . ':</strong> ' . rex_escape($sampleData['questions'][0]['title']) . '</p>';
        $content .= '<p><strong>' . rex_i18n::msg('poll_question_choices') . ':</strong></p>';
        $content .= '<ul>';
        foreach ($sampleData['questions'][0]['choices'] as $choice) {
            $content .= '<li>' . rex_escape($choice['title']) . '</li>';
        }
        $content .= '</ul>';

        // Show sample votes info
        if (isset($sampleData['sample_votes']) && !empty($sampleData['sample_votes'])) {
            $content .= '<p><strong>' . rex_i18n::msg('poll_sample_votes') . ':</strong> ' . count($sampleData['sample_votes']) . ' ' . rex_i18n::msg('poll_sample_votes_count') . '</p>';

            // Count votes per choice
            $voteCounts = [];
            foreach ($sampleData['sample_votes'] as $vote) {
                $choiceIndex = $vote['choice_index'];
                if (!isset($voteCounts[$choiceIndex])) {
                    $voteCounts[$choiceIndex] = 0;
                }
                ++$voteCounts[$choiceIndex];
            }

            $content .= '<div style="margin-top: 10px;"><small>';
            foreach ($sampleData['questions'][0]['choices'] as $index => $choice) {
                $count = $voteCounts[$index] ?? 0;
                $content .= '<div>' . rex_escape($choice['title']) . ': ' . $count . ' ' . rex_i18n::msg('poll_votes') . '</div>';
            }
            $content .= '</small></div>';
        }

        $content .= '</div>';
        $content .= '</div>';
    }

    $content .= '<div class="panel panel-default">';
    $content .= '<div class="panel-body">';

    $content .= '<form method="post">';
    $content .= rex_csrf_token::factory('poll-sample')->getHiddenField();
    $content .= '<input type="hidden" name="install_sample" value="1">';
    $content .= '<div class="row">';
    $content .= '<div class="col-sm-12">';
    $content .= '<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> ' . rex_i18n::msg('poll_sample_install_button') . '</button>';
    $content .= '</div>';
    $content .= '</div>';
    $content .= '</form>';

    $content .= '</div>';
    $content .= '</div>';
}

$content .= '</div>';

// Output the page
$fragment = new rex_fragment();
$fragment->setVar('title', rex_i18n::msg('poll_sample_data'), false);
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');
