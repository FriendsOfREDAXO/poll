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
        $error = true;
        $poll_id = $this->params['value_pool']['sql'][$this->getElement(2)];
        $option_id = $this->params['value_pool']['sql'][$this->getElement(3)];
        $status = $this->getElement(4);

        if ($poll_id > 0 && rex_poll::get($poll_id)->executeVote($option_id,$status)) {

            $this->params['value_pool']['email']['poll-link'] = rex::getServer().rex_getUrl(rex_article::getCurrentid(), rex_clang::getCurrentid(), ['confirm' => rex_poll_user::getHash()]);

            $error = false;
        }

        if ($error) {
            $this->params['form_show'] = true;
            $this->params['hasWarnings'] = true;
            $this->params['warning_messages'][] = $this->params['Error-Code-InsertQueryError'];
            return false;
        }
    }

    public function getDescription()
    {
        return 'action|poll_executevote|label poll id|label option|label email|status';
    }
}
