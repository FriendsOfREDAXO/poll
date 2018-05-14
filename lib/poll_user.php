<?php

class rex_poll_user extends \rex_yform_manager_dataset
{
    public static function getVote(rex_poll $poll, $hash)
    {
        return rex_poll_vote::query()
            ->where('poll_id', $poll->id)
            ->where('user_hash', $hash)
            ->findOne();
    }

    public static function hasVoted(rex_poll $poll, $hash)
    {
        return self::getVote($poll, $hash) ? true : false;
    }

    public static function getHash($salt = '')
    {
        if($salt != ''){
            return sha1($salt);
        }
        return sha1($_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);
    }
}
