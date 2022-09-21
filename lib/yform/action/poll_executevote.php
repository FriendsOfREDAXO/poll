<?php

use Poll\Poll;
use Poll\User;

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_action_poll_executevote extends rex_yform_action_abstract
{
    public function executeAction(): void
    {
        $pollId = $this->params['value_pool']['sql'][$this->getElement(2)];
        $email = isset($this->params['value_pool']['sql'][$this->getElement(3)]) ? $this->params['value_pool']['sql'][$this->getElement(3)] : '';
        $templateId = $this->getElement(4);
        $comment = isset($this->params['value_pool']['sql'][$this->getElement(5)]) ? $this->params['value_pool']['sql'][$this->getElement(5)] : '';


        $poll = Poll::get($pollId);
        if ($poll) {
            $hash = User::getHash();
            if ($email != '') {
                $hash = User::getHash($email.$poll->getId().rex::getProperty('instname'));
            }

            $answers = [];
            foreach ($poll->getQuestions() as $question) {
                $choices = $question->getChoices();
                if ($choices->isEmpty()) {
                    if (isset($this->params['value_pool']['sql']['poll-question-'.$question->getId().'-answer'])) {
                        $answers[$question->getId()]['text'] = $this->params['value_pool']['sql']['poll-question-'.$question->getId().'-answer'];
                    }
                } else {
                    foreach ($question->getChoices() as $choice) {
                        if (isset($this->params['value_pool']['sql']['poll-question-'.$question->getId().'-choice'])) {
                            $answers[$question->getId()]['choice_id'] = $this->params['value_pool']['sql']['poll-question-'.$question->getId().'-choice'];
                        }
                    }
                }
            }

            if ($poll->executeVote($answers, $hash, $comment)) {
                if ($poll->getType() == 'direct') {
                    $_REQUEST['vote_success'] = true;
                }
                if ($poll->getType() == 'email') {
                    $this->params['value_pool']['email']['poll-link'] = rtrim(rex::getServer(), "/") . rex_getUrl(rex_article::getCurrentid(), rex_clang::getCurrentid(), ['hash' => $hash]);

                    $etpl = $poll->getEmailTemplateById($templateId);
                    if($etpl){
                        $etpl = rex_yform_email_template::replaceVars($etpl, $this->params['value_pool']['email']);

                        $etpl['mail_to'] = $email;
                        $etpl['mail_to_name'] = $email;

                        if (!rex_yform_email_template::sendMail($etpl)) {
                            return;
                        }
                    }

                    return;
                }
            }
        }


    }

    public function getDescription(): string
    {
        return 'action|poll_executevote|label poll id|label email|email template|comment';
    }
}
