<?php

class rex_poll extends \rex_yform_manager_dataset
{
    // translate:poll_result_always=0,translate:poll_result_ifvoted=1,translate:poll_result_never=2,poll_result_ifended=3

    public function showResult()
    {
        $hash = rex_request('hash', 'string') != '' ? rex_request('hash', 'string') : rex_poll_user::getHash();

        //always=0,ifvoted=1,never=2,ifended=3
        if ($this->showresult == 0) {
            return true;
        } elseif ($this->showresult == 2) {
            return false;
        } elseif ($this->showresult == 3 && $this->status == 0) {
            return true;
        } elseif ($this->showresult == 1) {
            if($this->type == 'direct' && rex_request('vote_success','string') == '1'){
                return true;
            }
            else if($this->type == 'hash' && rex_poll_user::getVote($this, $hash)){
                return true;
            }
            else if($this->type == 'email' && rex_poll_user::getVote($this, $hash)){
                return true;
            }
        }
        return false;
    }

    public function getHits()
    {
        $hits = 0;
        foreach ($this->getOptions() as $option) {
            $hits = $hits + $option->getHits();
        };
        return $hits;
    }

    public function executeVote($option_id, $hash)
    {
        if ($this->status == 0) {
            return false;
        }
        switch ($this->type) {

            case "hash":
                if (rex_poll_user::getVote($this, $hash)) {
                    return false;
                }

                if (!empty($option_id)) {
                    if ($this->checkOptionById($option_id)) {
                        $vote = rex_poll_vote::create();
                        $vote->poll_id = $this->id;
                        $vote->status = 1;
                        $vote->option_id = $option_id;
                        $vote->user_hash = $hash;

                        if ($vote->save()) {
                        } else {
                            dump(implode('<br>', $vote->getMessages()));
                            return false;
                        }
                    }
                }
                break;

            case "email":
                if (rex_poll_user::getVote($this, $hash)) {
                    return false;
                }

                if (!empty($option_id)) {
                    if ($this->checkOptionById($option_id)) {
                        $vote = rex_poll_vote::create();
                        $vote->poll_id = $this->id;
                        $vote->status = 0;
                        $vote->option_id = $option_id;
                        $vote->user_hash = $hash;

                        if ($vote->save()) {
                        } else {
                            dump(implode('<br>', $vote->getMessages()));
                            return false;
                        }
                    }
                }
                break;

            default:
                if (!empty($option_id)) {
                    if ($this->checkOptionById($option_id)) {
                        $vote = rex_poll_vote::create();
                        $vote->poll_id = $this->id;
                        $vote->status = 1;
                        $vote->option_id = $option_id;

                        if ($vote->save()) {
                        } else {
                            dump(implode('<br>', $vote->getMessages()));
                            return false;
                        }
                    }
                }
        }

        return true;
    }

    public
    function checkOptionById($id)
    {
        $id = (int)$id;
        $option = rex_poll_option::get($id);
        if ($option && $option->poll_id == $this->id) {
            return true;
        }

        return false;
    }

    public
    function getOptions($sortedby = 'hits')
    {
        return $this->getRelatedCollection('options');
    }

    public
    function getOptionsSorted($sortedby = 'hits')
    {
        $options = $this->getOptions();
        // TODO: $sortedby // hits / alphanum /
        return $options;
    }

    public
    function getFormByType()
    {
        $options = [];
        foreach ($this->getRelatedCollection('options') as $option) {
            $options[] = $option->title . '=' . $option->id;
        }

        switch ($this->type) {
            case "hash":
                $form_data = '
                    hidden|poll-id|' . $this->id . '
                        
                    html|poll-question|<h2>' . $this->description . '</h2>
                    radio|poll-option||' . implode(',', $options) . '
                    
                    validate|empty|poll-option|' . rex_i18n::msg('poll_validate_option') . '
                    
                    action|poll_executevote|poll-id|poll-option
                    action|showtext|<p>' . rex_i18n::msg('poll_vote_success') . '</p>|||1
                ';
                break;
            case "email":
                $form_data = '
                    hidden|poll-id|' . $this->id . '
                    hidden|poll-title|' . $this->title . '|no-db
                    hidden|poll-link||no-db

                    html|poll-question|<h2>' . $this->description . '</h2>
                    
                    radio|poll-option||' . implode(',', $options) . '
                    
                    html|email_note|<p> ' . rex_i18n::msg('poll_email_note') . '</p>
                    text|poll-email|' . rex_i18n::msg('poll_email_label') . '
                    
                    validate|empty|poll-option|' . rex_i18n::msg('poll_validate_option') . '
                    validate|empty|poll-email|' . rex_i18n::msg('poll_validate_email') . '
                    validate|email|poll-email|' . rex_i18n::msg('poll_validate_email') . '
                    
                    checkbox|Datenschutz|<p>Ich habe die <a target="_blank" rel="noopener" href="'.rex_getUrl(120).'">Datenschutzerklärung</a> zur Kenntnis genommen.</p>|0,1|0|no_db
                    validate|empty|Datenschutz|Bitte bestätigen Sie, dass Sie die Datenschutzerklärung zur Kenntnis genommen haben und stimmen Sie der elektronischen Verwendung Ihrer Daten zur Abstimmung zu.

                    action|poll_executevote|poll-id|poll-option|poll-email|poll_user
                    action|showtext|<p>' . rex_i18n::msg('poll_vote_confirm') . '</p>|||1
                ';
                break;
            default:
                $form_data = '
                    hidden|poll-id|' . $this->id . '
                        
                    html|poll-question|<h2>' . $this->description . '</h2>
                    radio|poll-option||' . implode(',', $options) . '
                    
                    validate|empty|poll-option|' . rex_i18n::msg('poll_validate_option') . '
                    
                    action|poll_executevote|poll-id|poll-option
                    action|showtext|<p>' . rex_i18n::msg('poll_vote_success') . '</p>|||1
                ';
        }

        $yform = new rex_yform();
//        $yform->setDebug(TRUE);
        $form_data = trim(str_replace("<br />", "", rex_yform::unhtmlentities($form_data)));
        $yform->setFormData($form_data);
        $yform->SetObjectparams("form_action", rex_getUrl(rex_article::getCurrentId(), rex_clang::getCurrentId()));
        $yform->SetObjectparams("form_class", 'form-voting');
        $yform->SetObjectparams("real_field_names", true);

        return $yform->getForm();
    }

    public
    function getOutput()
    {
        $out = '';

        switch ($this->type) {

            case "hash":

                if ($this->status == 1) {
                    if (!rex::isBackend()) {
                        $vote = rex_poll_user::getVote($this, rex_poll_user::getHash());
                        if ($vote) {
                            $out = rex_i18n::msg('poll_vote_exists');
                        } else {
                            $out = '<div class="rex-poll-voting"> ' . $this->getFormByType() . '</div> ';
                        }
                    }
                } else {
                    $out = '<p>' . rex_i18n::msg('poll_finished') . '</p>';
                }

                return $out;

            case "email":

                $hash = rex_request('hash', 'string') ? rex_request('hash', 'string') : '';

                if ($this->status == 1) {
                    if(!rex::isBackend()){
                        if ($hash != '') {
                            $vote = rex_poll_user::getVote($this, $hash);
                            if ($vote) {
                                if ($vote->status == 0) {
                                    if ($vote->activate()) {
                                        $out = rex_i18n::msg('poll_vote_success');
                                    } else {
                                        $out = rex_i18n::msg('poll_vote_fail');
                                    }
                                } else {
                                    $out = rex_i18n::msg('poll_vote_exists');
                                }
                            } else {
                                $out = rex_i18n::msg('poll_vote_fail');
                            }
                        } else {
                            $out = '<div class="rex-poll-voting"> ' . $this->getFormByType() . '</div> ';
                        }
                    }
                } else {
                    $out = '<p>' . rex_i18n::msg('poll_finished') . '</p>';
                }

                return $out;

            default:

                if ($this->status == 1) {
                    if (!rex::isBackend()) {
                        $out = '<div class="rex-poll-voting"> ' . $this->getFormByType() . '</div> ';
                    }
                } else {
                    $out = '<p>' . rex_i18n::msg('poll_finished') . '</p>';
                }

                return $out;
        }


    }
}
