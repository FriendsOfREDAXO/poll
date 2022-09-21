<?php
namespace Poll;

use Poll\Question\Choice;
use Poll\Vote\Answer;

class Poll extends \rex_yform_manager_dataset
{
    /**
     * @param \rex_yform_manager_collection $items
     *
     * @return \rex_yform_manager_collection
     */
    public static function populateQuestions(\rex_yform_manager_collection $items)
    {
        return $items->populateRelation('questions');
    }

    /**
     * @return \rex_yform_manager_collection|Question[]
     */
    public function getQuestions()
    {
        return $this->getRelatedCollection('questions');
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isOnline(): bool
    {
        return 1 == $this->status;
    }


    // translate:poll_result_always=0,translate:poll_result_ifvoted=1,translate:poll_result_never=2,poll_result_ifended=3

    public function showResult(): bool
    {
        $hash = '' != rex_request('hash', 'string') ? rex_request('hash', 'string') : User::getHash();

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
            if ('hash' == $this->type && User::getVote($this, $hash)) {
                return true;
            }
            if ('email' == $this->type && User::getVote($this, $hash)) {
                return true;
            }
        }
        return false;
    }

    public function getHits(): int
    {
        //$hits = Vote::query()
        //    ->where('poll_id', $this->getId())
        //    ->where('status', 1)
        //    ->find();

        //return count($hits);

        $hits = Answer::query()
            ->alias('a')
            ->joinRelation('vote_id', 'v')
            ->where('a.question_choice_id', 0, '!=')
            ->where('v.poll_id', $this->getId())
            ->where('v.status', 1)
            ->groupBy('a.vote_id')
            ->find();

        return count($hits);
    }

    public function executeVote($answers, $hash, $comment = '')
    {
        if (!$this->isOnline()) {
            return false;
        }

        $cleanAnswers = [];
        foreach ($answers as $questionId => $answer) {
            if (isset($answer['choice_id'])) {
                if ($this->checkChoiceByQuestionId($answer['choice_id'], $questionId)) {
                    $cleanAnswers[$questionId]['choice_id'] = $answer['choice_id'];
                }
            }
            if (isset($answer['text'])) {
                if ($this->checkQuestion($questionId)) {
                    $cleanAnswers[$questionId]['text'] = $answer['text'];
                }
            }
        }

        switch ($this->getType()) {
            case 'hash':
                if (User::getVote($this, $hash)) {
                    return false;
                }

                if (!empty($cleanAnswers)) {
                    $vote = Vote::create();
                    $vote->poll_id = $this->getId();
                    $vote->status = 1;
                    $vote->user_hash = $hash;
                    $vote->comment = $comment;

                    if (!$vote->save()) {
                        dump(implode('<br>', $vote->getMessages()));
                        return false;
                    } else {
                        foreach ($cleanAnswers as $questionId => $cleanAnswer) {
                            $this->executeAnswer($vote, $questionId, $cleanAnswer);
                        }
                    }
                }
                break;

            case 'email':
                if (User::getVote($this, $hash)) {
                    return false;
                }

                if (!empty($cleanAnswers)) {
                    $vote = Vote::create();
                    $vote->poll_id = $this->getId();
                    $vote->status = 0;
                    $vote->user_hash = $hash;
                    $vote->comment = $comment;

                    if (!$vote->save()) {
                        dump(implode('<br>', $vote->getMessages()));
                        return false;
                    } else {
                        foreach ($cleanAnswers as $questionId => $cleanAnswer) {
                            $this->executeAnswer($vote, $questionId, $cleanAnswer);
                        }
                    }
                }
                break;

            default:
                if (!empty($cleanAnswers)) {
                    $vote = Vote::create();
                    $vote->poll_id = $this->getId();
                    $vote->status = 1;
                    $vote->comment = $comment;

                    if (!$vote->save()) {
                        dump(implode('<br>', $vote->getMessages()));
                        return false;
                    } else {
                        foreach ($cleanAnswers as $questionId => $cleanAnswer) {
                            $this->executeAnswer($vote, $questionId, $cleanAnswer);
                        }
                    }
                }
        }

        return true;
    }

    private function executeAnswer($vote, $questionId, $cleanAnswer): void
    {
        $answer = Answer::create();
        $answer->vote_id = $vote->getId();
        $answer->question_id = $questionId;
        if (isset($cleanAnswer['choice_id'])) {
            $answer->question_choice_id = $cleanAnswer['choice_id'];
        }
        if (isset($cleanAnswer['text'])) {
            $answer->text = $cleanAnswer['text'];
        }

        if (!$answer->save()) {
            dump(implode('<br>', $answer->getMessages()));
            $vote->delete();
            return;
        }
    }

    public function checkChoiceByQuestionId($choiceId, $questionId): bool
    {
        $choiceId = (int) $choiceId;
        $choice = Choice::get($choiceId);
        if ($choice && $choice->getQuestion() && $choice->getQuestion()->getPoll() &&
            $choice->getQuestion()->getPoll()->getId() == $this->getId() && $choice->getQuestion()->getId() == (int)$questionId) {
            return true;
        }

        return false;
    }
    public function checkQuestion($questionId): bool
    {
        $questionId = (int) $questionId;
        $question = Question::get($questionId);
        if ($question && $question->poll_id == $this->getId()) {
            return true;
        }

        return false;
    }

    public function getEmailTemplateById($id)
    {
        $gt = \rex_sql::factory();
        $gt->setQuery('SELECT * FROM '.\rex::getTable('yform_email_template').' WHERE id=:id', [':id' => $id]);
        if (1 == $gt->getRows()) {
            $b = $gt->getArray();
            return current($b);
        }
        return false;
    }

    public function getFormByType()
    {
        $formDataQuestions = [];
        foreach ($this->getQuestions() as $question) {
            $formDataQuestions[] = 'fieldset|poll-question-'.$question->getId().'|'.$question->getTitle();

            $questionChoices = [];
            $choices = $question->getChoices();
            if ($choices->isEmpty()) {
                $formDataQuestions[] = 'textarea|poll-question-'.$question->getId().'-answer|{{ poll_answer }}';
                $formDataQuestions[] = 'validate|empty|poll-question-'.$question->getId().'-answer|{{ poll_validate_question }}';
            } else {
                foreach ($choices as $choice) {
                    $questionChoices[$choice->getTitle()] = $choice->getId();
                }
                $formDataQuestions[] = 'choice|poll-question-'.$question->getId().'-choice||'.json_encode($questionChoices).'|1|0';
                $formDataQuestions[] = 'validate|empty|poll-question-'.$question->getId().'-choice|{{ poll_validate_question }}';
            }
        }

        $comment = '';
        if (1 == $this->getValue('comment')) {
            $comment .= 'fieldset|poll-comment|{{ poll_comment_legend }}'."\n";
            $comment .= 'textarea|poll-comment|{{ poll_comment_label }}'."\n";
        }

        switch ($this->getType()) {
            case 'hash':
                $form_data = '
                    hidden|poll-id|'.$this->getId().'
                    
                    '.implode("\n", $formDataQuestions).'

                    ' . $comment . '

                    action|poll_executevote|poll-id|||poll-comment
                    action|showtext|<p>{{ poll_vote_success }}</p>|||1
                ';
                break;
            case 'email':
                $form_data = '
                    hidden|poll-id|' . $this->getId() . '
                    hidden|poll-title|'.$this->getTitle().'|no-db
                    hidden|poll-link||no-db

                    '.implode("\n", $formDataQuestions).'

                    html|email_note|<p>{{ poll_email_note }}</p>
                    text|poll-email|{{ poll_email_label }}

                    validate|empty|poll-email|{{ poll_validate_email }}
                    validate|type|poll-email|email|{{ poll_validate_email }}

                    ' . $comment . '

                    checkbox|ds|{{ poll_datenschutz_checkbox }}|0|no_db
                    validate|empty|ds|{{ poll_datenschutz_checkbox_error }}

                    action|poll_executevote|poll-id|poll-email|' . $this->getValue('emailtemplate') . '|poll-comment
                    action|showtext|<p>{{ poll_vote_confirm }}</p>|||1
                ';
                break;
            default:
                $form_data = '
                    objparams|form_name|form-'.$this->getId().'
                    hidden|poll-id|' . $this->getId() . '

                    '.implode("\n", $formDataQuestions).'

                    ' . $comment . '

                    action|poll_executevote|poll-id|||poll-comment
                    action|showtext|<p>{{ poll_vote_success }}</p>|||1
                ';
        }

        $yform = new \rex_yform();
        $form_data = trim(str_replace('<br />', '', \rex_yform::unhtmlentities($form_data)));
        $yform->setFormData($form_data);
        $yform->setObjectparams('form_action', rex_getUrl(\rex_article::getCurrentId(), \rex_clang::getCurrentId()));
        $yform->setObjectparams('form_class', 'form-voting');
        $yform->setObjectparams('real_field_names', false);
        $yform->setObjectparams('submit_btn_label', '{{ poll_submit_poll }}');
        //$yform->setObjectparams('hide_top_warning_messages', true);
        //$yform->setObjectparams('hide_field_warning_messages', false);

        return $yform->getForm();
    }

    public function getOutput(): string
    {
        $out = '';

        switch ($this->getType()) {
            case 'hash':
                if ($this->isOnline()) {
                    if (!\rex::isBackend()) {
                        $vote = User::getVote($this, User::getHash());
                        if ($vote) {
                            $out = '{{ poll_vote_exists }}';
                        } else {
                            $out = '<div class="poll-voting"> ' . $this->getFormByType() . '</div> ';
                        }
                    }
                } else {
                    $out = '<p>{{ poll_finished }}</p>';
                }

                return $out;

            case 'email':

                $hash = rex_request('hash', 'string') ? rex_request('hash', 'string') : '';

                if ($this->isOnline()) {
                    if (!\rex::isBackend()) {
                        if ('' != $hash) {
                            $vote = User::getVote($this, $hash);
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
                            $out = '<div class="poll-voting"> ' . $this->getFormByType() . '</div> ';
                        }
                    }
                } else {
                    $out = '<p>{{ poll_finished }}</p>';
                }

                return $out;

            default:

                if ($this->isOnline()) {
                    if (!\rex::isBackend()) {
                        $out = '<div class="poll-voting"> ' . $this->getFormByType() . '</div> ';
                    }
                } else {
                    $out = '<p>{{ poll_finished }}</p>';
                }

                return $out;
        }
    }
}
