<?php

echo rex_view::title($this->i18n('poll'));

$polls = [];
foreach (rex_poll::getAll() as $poll) {

    $fragment = new rex_fragment();
    $fragment->setVar('poll', $poll);
    $polls[] = $fragment->parse('addons/poll/be_statistic.php');

}

$content = '<div class="rex-poll"><div class="polls">' . implode('', $polls) . '</div></div>';

$fragment = new rex_fragment();
$fragment->setVar('title', $this->i18n('statistics'), false);
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');
