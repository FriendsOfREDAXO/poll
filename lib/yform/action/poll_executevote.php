<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_action_poll_executevote extends rex_yform_action_abstract
{
    public function executeAction()
    {
        $pollId = $this->params['value_pool']['sql'][$this->getElement(2)];
        $optionId = $this->params['value_pool']['sql'][$this->getElement(3)];
        $email = isset($this->params['value_pool']['sql'][$this->getElement(4)]) ? $this->params['value_pool']['sql'][$this->getElement(4)] : '';
        $comment = isset($this->params['value_pool']['sql'][$this->getElement(5)]) ? $this->params['value_pool']['sql'][$this->getElement(5)] : '';

        $templateId = $this->getElement(5);

        $poll = rex_poll::get($pollId);
        if ($poll) {

            $hash = rex_poll_user::getHash();
            if ($email != '') {
                $hash = rex_poll_user::getHash($email . $poll->getId() . rex::getProperty('instname'));
            }

            if ($poll->executeVote($optionId, $hash, $comment)) {
                if ($poll->type == 'direct') {
                    $_REQUEST['vote_success'] = true;
                }
                if ($poll->type == 'email') {
                    $this->params['value_pool']['email']['poll-link'] = rtrim(rex::getServer(), "/") . rex_getUrl(rex_article::getCurrentid(), rex_clang::getCurrentid(), ['hash' => $hash]);


                    $etpl = $poll->getEmailTemplateById($templateId);
                    if($etpl){
                        $etpl = rex_yform_email_template::replaceVars($etpl, $this->params['value_pool']['email']);

                        $etpl['mail_to'] = $email;
                        $etpl['mail_to_name'] = $email;

                        if (!rex_yform_email_template::sendMail($etpl)) {
                            return false;
                        }
                    }

                    return false;
                }
            }
        }


    }

    public function getDescription()
    {
        return 'action|poll_executevote|label poll id|label option|label email|email template|comment';
    }
}
