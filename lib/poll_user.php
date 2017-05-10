<?php

class rex_poll_user extends \rex_yform_manager_dataset
{
    public static function hasVoted(rex_poll $poll)
    {
        $votes = rex_poll_vote::query()
            ->where('poll_id', $poll->id)
            ->where('user_hash', self::getHash())
            ->find();

        if (count($votes) > 0) {
            return true;
        }

        return false;
    }

    public static function getHash()
    {
        // TODO: hash verfeinern
        $key = $_SERVER['HTTP_USER_AGENT'].$_SERVER['REMOTE_ADDR'];
        return sha1($key);
    }

    public static function setVoted($poll, $voted = true)
    {
        return $voted;
    }
}
