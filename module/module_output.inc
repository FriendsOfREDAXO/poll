<?php
/*
 * module:poll_basic_output v2.0.0
 */

use Poll\Poll;

$pollId = (int) 'REX_VALUE[1]';

$poll = Poll::get($pollId);
if ($poll) {
    $fragment = new rex_fragment();
    $fragment->setVar('poll', $poll);
    echo $fragment->parse('addons/poll/poll.php');
}
