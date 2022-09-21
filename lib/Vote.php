<?php

namespace Poll;

class Vote extends \rex_yform_manager_dataset
{
    public function activate(): bool
    {
        self::setValue('status', 1);
        if (self::save()) {
            return true;
        }
        return false;
    }
}
