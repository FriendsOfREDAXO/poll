<?php

class rex_poll_option extends \rex_yform_manager_dataset
{
    public function getHits()
    {
        $hits = rex_poll_vote::query()
            ->where('option_id', $this->id)
            ->where('status', 1)
            ->find();

        return count($hits);
    }
}
