<?php

class rex_poll extends \rex_yform_manager_dataset
{
    // translate:poll_result_always=0,translate:poll_result_ifvoted=1,translate:poll_result_never=2,poll_result_ifended=3

    public function showResult()
    {
        $hash = '' != rex_request('hash', 'string') ? rex_request('hash', 'string') : rex_poll_user::getHash();

        //always=0,ifvoted=1,never=2,ifended=3
        if (0 == $this->showresult) {
            return true;
        }
        if (2 == $this->showresult) {
            return false;
        }
        if (3 == $this->showresult && 0 == $this->status) {
            return true;
        }
        if (1 == $this->showresult) {
            if ('direct' == $this->type && '1' == rex_request('vote_success', 'string')) {
                return true;
            }
            if ('hash' == $this->type && rex_poll_user::getVote($this, $hash)) {
                return true;
            }
            if ('email' == $this->type && rex_poll_user::getVote($this, $hash)) {
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
        }
        return $hits;
    }

    public function executeVote($option_id, $hash, $comment = '')
    {
        if (0 == $this->status) {
            return false;
        }

        switch ($this->type) {
            case 'hash':
                if (rex_poll_user::getVote($this, $hash)) {
                    return false;
                }

                if (!empty($option_id)) {
                    if ($this->checkOptionById($option_id)) {
                        $vote = rex_poll_vote::create();
                        $vote->poll_id = $this->getId();
                        $vote->status = 1;
                        $vote->option_id = $option_id;
                        $vote->user_hash = $hash;
                        $vote->comment = $comment;

                        if (!$vote->save()) {
                            dump(implode('<br>', $vote->getMessages()));
                            return false;
                        }
                    }
                }
                break;

            case 'email':

                if (rex_poll_user::getVote($this, $hash)) {
                    return false;
                }

                if (!empty($option_id)) {
                    if ($this->checkOptionById($option_id)) {
                        $vote = rex_poll_vote::create();
                        $vote->poll_id = $this->getId();
                        $vote->status = 0;
                        $vote->option_id = $option_id;
                        $vote->user_hash = $hash;
                        $vote->comment = $comment;

                        if (!$vote->save()) {
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
                        $vote->poll_id = $this->getId();
                        $vote->status = 1;
                        $vote->option_id = $option_id;
                        $vote->comment = $comment;

                        if (!$vote->save()) {
                            dump(implode('<br>', $vote->getMessages()));
                            return false;
                        }
                    }
                }
        }

        return true;
    }

    public function checkOptionById($id)
    {
        $id = (int) $id;
        $option = rex_poll_option::get($id);
        if ($option && $option->poll_id == $this->getId()) {
            return true;
        }

        return false;
    }

    public function getOptions($sortedby = 'hits')
    {
        return $this->getRelatedCollection('options');
    }

    public function getOptionsSorted($sortedby = 'hits')
    {
        $options = $this->getOptions();
        // TODO: $sortedby // hits / alphanum /
        return $options;
    }

    public function getEmailTemplateById($id)
    {
        $gt = rex_sql::factory();
        $gt->setQuery('select * from ' . rex::getTablePrefix() . 'yform_email_template where id=:id', [':id' => $id]);
        if (1 == $gt->getRows()) {
            $b = $gt->getArray();
            return current($b);
        }
        return false;
    }

    public function getFormByType()
    {
        $options = [];
        foreach ($this->getRelatedCollection('options') as $option) {
            $fragment = new rex_fragment();
            $fragment->setVar('option', $option);
            $options[] = $fragment->parse('addons/poll/option.php');
        }

        $comment = '';
        if (1 == $this->getValue('comment')) {
            $comment = 'textarea|poll-comment|{{ comment }}';
        }

        switch ($this->type) {
            case 'hash':
                $form_data = '
                    objparams|submit_btn_label|{{ poll_submit_poll }}
                    hidden|poll-id|' . $this->getId() . '

                    html|poll-question|<h2>' . $this->description . '</h2>
                    radio|poll-option||' . implode(',', $options) . '

                    validate|empty|poll-option|{{ poll_validate_option }}

                    ' . $comment . '

                    action|poll_executevote|poll-id|poll-option||poll-comment
                    action|showtext|<p>{{ poll_vote_success }}</p>|||1
                ';
                break;
            case 'email':
                $form_data = '
                    objparams|submit_btn_label|{{ poll_submit_poll }}
                    hidden|poll-id|' . $this->getId() . '
                    hidden|poll-title|' . $this->title . '|no-db
                    hidden|poll-link||no-db

                    html|poll-question|<h2>' . $this->description . '</h2>

                    radio|poll-option||' . implode(',', $options) . '

                    html|email_note|<p>{{ poll_email_note }}</p>
                    text|poll-email|{{ poll_email_label }}

                    validate|empty|poll-option|{{ poll_validate_option }}
                    validate|empty|poll-email|{{ poll_validate_email }}
                    validate|email|poll-email|{{ poll_validate_email }}

                    ' . $comment . '

                    checkbox|ds|{{ poll_datenschutz_checkbox }}|0,1|0|no_db
                    validate|empty|ds|{{ poll_datenschutz_checkbox_error }}

                    action|poll_executevote|poll-id|poll-option|poll-email|' . $this->getValue('emailtemplate') . '|poll-comment
                    action|showtext|<p>{{ poll_vote_confirm }}</p>|||1
                ';
                break;
            default:
                $form_data = '
                    objparams|submit_btn_label|{{ poll_submit_poll }}
                    hidden|poll-id|' . $this->getId() . '

                    html|poll-question|<h2>' . $this->description . '</h2>
                    radio|poll-option||' . implode(',', $options) . '

                    validate|empty|poll-option|{{ poll_validate_option }}

                    ' . $comment . '

                    action|poll_executevote|poll-id|poll-option||poll-comment
                    action|showtext|<p>{{ poll_vote_success }}</p>|||1
                ';
        }

        $yform = new rex_yform();
        $form_data = trim(str_replace('<br />', '', rex_yform::unhtmlentities($form_data)));
        $yform->setFormData($form_data);
        $yform->SetObjectparams('form_action', rex_getUrl(rex_article::getCurrentId(), rex_clang::getCurrentId()));
        $yform->SetObjectparams('form_class', 'form-voting');
        $yform->SetObjectparams('real_field_names', true);

        return $yform->getForm();
    }

    public function getOutput()
    {
        $out = '';

        switch ($this->type) {
            case 'hash':

                if (1 == $this->status) {
                    if (!rex::isBackend()) {
                        $vote = rex_poll_user::getVote($this, rex_poll_user::getHash());
                        if ($vote) {
                            $out = '{{ poll_vote_exists }}';
                        } else {
                            $out = '<div class="rex-poll-voting"> ' . $this->getFormByType() . '</div> ';
                        }
                    }
                } else {
                    $out = '<p>{{ poll_finished }}</p>';
                }

                return $out;

            case 'email':

                $hash = rex_request('hash', 'string') ? rex_request('hash', 'string') : '';

                if (1 == $this->status) {
                    if (!rex::isBackend()) {
                        if ('' != $hash) {
                            $vote = rex_poll_user::getVote($this, $hash);
                            if ($vote) {
                                if (0 == $vote->status) {
                                    if ($vote->activate()) {
                                        $out = ' {{ poll_vote_success }}';
                                    } else {
                                        $out = '{{ poll_vote_fail }}';
                                    }
                                } else {
                                    $out = '{{ poll_vote_exists }}';
                                }
                            } else {
                                $out = '{{ poll_vote_fail }}';
                            }
                        } else {
                            $out = '<div class="rex-poll-voting"> ' . $this->getFormByType() . '</div> ';
                        }
                    }
                } else {
                    $out = '<p>{{ poll_finished }}</p>';
                }

                return $out;

            default:

                if (1 == $this->status) {
                    if (!rex::isBackend()) {
                        $out = '<div class="rex-poll-voting"> ' . $this->getFormByType() . '</div> ';
                    }
                } else {
                    $out = '<p>{{ poll_finished }}</p>';
                }

                return $out;
        }
    }
}
