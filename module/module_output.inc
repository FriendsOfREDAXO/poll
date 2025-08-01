<?php
/*
 * module:poll_basic_output v2.0.0
 */

use FriendsOfRedaxo\Poll\Poll;

$pollId = (int) 'REX_VALUE[1]';

if ($pollId > 0 && $poll = Poll::get($pollId)) 
 {
    $fragment = new rex_fragment();
    $fragment->setVar('poll', $poll);
    echo $fragment->parse('addons/poll/poll.php');
}
