<?php

use Poll\Poll;

/** @var Poll[] $polls */
$polls = Poll::getAll();

$select = new rex_select();
$select->setName('REX_INPUT_VALUE[1]');

foreach ($polls as $poll) {
    $select->addOption($poll->getTitle(), $poll->getId());
}

$select->setSelected('REX_VALUE[1]');

echo $select->get();
