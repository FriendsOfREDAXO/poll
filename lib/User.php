<?php

namespace Poll;

class User extends \rex_yform_manager_dataset
{
    public static function getVote(Poll $poll, $hash)
    {
        return Vote::query()
            ->where('poll_id', $poll->getId())
            ->where('user_hash', $hash)
            ->findOne();
    }

    public static function hasVoted(Poll $poll, $hash): bool
    {
        return self::getVote($poll, $hash) ? true : false;
    }

    public static function getHash($salt = ''): string
    {
        if ('' != $salt) {
            return sha1($salt);
        }
        return sha1($_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);
    }
}
