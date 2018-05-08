<?php

$polls = rex_poll::query()->find();

$select = new rex_select();
$select->setName('REX_INPUT_VALUE[1]');

foreach ($polls as $poll) {
    $select->addOption($poll->title, $poll->id);
}

$select->setSelected('REX_VALUE[1]');

echo $select->get();
