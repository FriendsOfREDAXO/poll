<?php

namespace Poll\Vote;

class Answer extends \rex_yform_manager_dataset
{
    public function getText()
    {
        return $this->text;
    }
}
