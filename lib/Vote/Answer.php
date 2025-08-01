<?php

namespace FriendsOfRedaxo\Poll\Vote;

use rex_yform_manager_dataset;

class Answer extends rex_yform_manager_dataset
{
    public function getText()
    {
        return $this->text;
    }
}
