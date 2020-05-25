<?php

/*
 * module:poll_basic_output v1.2
 */

$poll_id = (int) 'REX_VALUE[1]';

$poll = rex_poll::get($poll_id);
if ($poll) {
    $fragment = new rex_fragment();
    $fragment->setVar('poll', $poll);
    echo $fragment->parse('addons/poll/poll.php');
}
