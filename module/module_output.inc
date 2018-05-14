<?php

/*
 * module:poll_basic_output v1.1
 */

$out = '';
$results = '';

$confirm = rex_request('confirm', 'string') ? rex_request('confirm', 'string') : ''; // Hash des Bestätigungslinks
$userHash = $confirm != '' ? $confirm : rex_poll_user::getHash();

$poll_id = (int)'REX_VALUE[1]';
$poll = null;


if ($poll_id > 0) {
    $poll = rex_poll::get($poll_id);
}

if ($poll) {

    if ($poll->status == 0) {
        $out = '<p>' . rex_i18n::msg('poll_finished') . '</p>';
    } else {

        if ( !rex::isBackend() ) {
            $vote = rex_poll_user::getVote($poll, $userHash);

            if ($vote) {

                if ($confirm != '' && $vote->status == 0) {
                    if ($vote->activate()) {
                        $out = rex_i18n::msg('poll_vote_sucess');
                    } else {
                        $out = rex_i18n::msg('poll_vote_fail');
                    }
                } elseif ($confirm == '' && $vote->status == 0) {
                    $out = rex_i18n::msg('poll_vote_confirm');
                } else {
                    $out = rex_i18n::msg('poll_vote_exists');
                }
            } else if ( $confirm != '') {
                $out = rex_i18n::msg('poll_vote_notexists');
            } else {

                $votings = [];
                foreach ($poll->getRelatedCollection('options') as $option) {
                    $votings[] = $option->title . '=' . $option->id;
                }

                $form = '';
                if ($poll->type === 'email') {

                    $yform = new rex_yform();
//                $yform->setDebug(TRUE);

                    $form_data = '
                    hidden|poll-id|' . $poll->id . '
                    hidden|poll-title|' . $poll->title . '|no-db
                    hidden|poll-link||no-db
                    

                    html|poll-question|<h2>'.$poll->description.'</h2>
                    radio|poll-option||' . implode(',', $votings) . '
                    
                    html|email_note|<div class="email_note"><h3>Bitte abstimmen:</h3><p> ' . rex_i18n::msg('poll_email_note') . '</p></div>
                    text|poll-email|' . rex_i18n::msg('poll_email_label') . '
                    
                    validate|empty|poll-option|' . rex_i18n::msg('poll_validate_option') . '
                    validate|empty|poll-email|' . rex_i18n::msg('poll_validate_email') . '
                    validate|email|poll-email|' . rex_i18n::msg('poll_validate_email') . '
                    
                    action|poll_executevote|poll-id|poll-option|0
                    action|tpl2email|poll_de|poll-email|
                    action|showtext|<p>' . rex_i18n::msg('poll_vote_confirm') . '</p>|||1
                ';
                    $form_data = trim(str_replace("<br />", "", rex_yform::unhtmlentities($form_data)));
                    $yform->setFormData($form_data);
                    $yform->SetObjectparams("form_action", rex_getUrl($this->article_id, $this->clang));
                    $yform->SetObjectparams("form_class", 'form-voting');
                    $yform->SetObjectparams("real_field_names", true);

                    $form = $yform->getForm();
                } else {

                    $yform = new rex_yform();
//                $yform->setDebug(TRUE);
                    $form_data = '
                    hidden|poll-id|' . $poll->id . '
                    
                        
                    html|poll-question|<h2>'.$poll->description.'</h2>
                    radio|poll-option||' . implode(',', $votings) . '
                    
                    validate|empty|poll-option|' . rex_i18n::msg('poll_validate_option') . '
                    
                    action|poll_executevote|poll-id|poll-option|1
                    action|showtext|<p>' . rex_i18n::msg('poll_vote_sucess') . '</p>|||1
                ';
                    $form_data = trim(str_replace("<br />", "", rex_yform::unhtmlentities($form_data)));
                    $yform->setFormData($form_data);
                    $yform->SetObjectparams("form_action", rex_getUrl($this->article_id, $this->clang));
                    $yform->SetObjectparams("form_class", 'form-voting');
                    $yform->SetObjectparams("real_field_names", true);

                    $form = $yform->getForm();
                }

                $out = '<div class="rex-poll-voting"> ' . $form . '</div> ';
            }
        }
    }

    if ($poll->showResult()) {
        $hits_all = $poll->getHits() > 0 ? $poll->getHits() : 1;
        foreach ($poll->getOptions() as $option) {
            $hits = $option->getHits();
            $percent = (int)($hits / $hits_all * 100);
            $results .= '<li><span class="poll-vote-value"><span>' . $percent . ' %</span > [' . $option->getHits() . ']</span >' . $option->title . '</li> ';
            $results .= '
            <div class="progress bb-progress-thin">
                        <div class="progress-bar bb-blue-bg" role="progressbar" aria-valuenow="' . $percent . '" aria-valuemin="0" aria-valuemax="100" style="width: ' . $percent . '%;"></div>
                    </div>';
        }

        $results = '
            <div class="rex-poll-results">
                <h2>'.rex_i18n::msg('poll_result').':</h2>
                <ul> ' . $results . '</ul>
            </div>
        ';
    }
}


echo '
    <div class="rex-poll">
        <h1>'.rex_i18n::msg('poll_title').': ' . $poll->title . ' </h1>
        ' . ($poll->getHits() > 0 ? '<p>'.rex_i18n::msg('poll_votes_taken', $poll->getHits()).'</p>' : '') . '
        ' . $out . '
        ' . $results . '
    </div>
';

