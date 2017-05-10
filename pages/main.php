<?php

echo rex_view::title($this->i18n('poll'));

$content = '';

$fragment = new rex_fragment();
$fragment->setVar('title', $this->i18n('description'), false);
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');