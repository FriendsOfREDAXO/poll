<?php

class rex_poll_vote extends \rex_yform_manager_dataset
{
    public function activate()
    {
        self::setValue('status', 1);
        if (self::save()) {
            return true;
        }
        return false;
    }
}
