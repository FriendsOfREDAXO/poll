<?php

namespace FriendsOfRedaxo\Poll;

use rex_request;
use rex_yform_manager_dataset;

class User extends rex_yform_manager_dataset
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
        return sha1(
            rex_request::server('HTTP_USER_AGENT', 'string', '') .
            rex_request::server('REMOTE_ADDR', 'string', ''),
        );
    }
}
