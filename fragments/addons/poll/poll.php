<?php

$poll = $this->poll;
$hash = rex_request('hash', 'string') != '' ? rex_request('hash', 'string') : rex_poll_user::getHash();

echo '<h1>{{ poll_title }}: ' . rex_escape($poll->title) . ' </h1> ';

echo $poll->getOutput();

if ($poll->showResult($hash)) {
    $hits_all = $poll->getHits() > 0 ? $poll->getHits() : 1;
    $options = [];
    foreach ($poll->getOptions() as $option) {
        $hits = $option->getHits();
        $percent = (int)($hits / $hits_all * 100);

        $description = '';
        if(rex_media::get($option->media)){
            $description = '<div class="poll-description">' . $option->description . '</div>';
        }

        $picture = '';
        if(rex_media::get($option->media)){
            $picture = '<div class="poll-picture"><img src="/media/'.$option->media.'"/></div>';
        }

        $link = '';
        if(rex_media::get($option->media)){
            $link = '<div class="poll-link"><a href="'.rex_getUrl($option->link).'">mehr Informationen</a></div>';
        }

        $options[] = '
                    <li>
                        <div class="poll-title">' . $option->title . '</div>
                        '.$description.'
                        '.$picture.'
                        '.$link.'
                        <div class="progress bb-progress-thin">
                            <div class="progress-bar bb-blue-bg" role="progressbar" aria-valuenow="' . $percent . '" aria-valuemin="0" aria-valuemax="100" style="width: ' . $percent . '%;">
                                <span class="poll-vote-value"><span>' . $percent . ' %</span > [' . $option->getHits() . ']</span>
                            </div>
                        </div>
                    </li>
                 ';
    }

    echo '    <div class="rex-poll">
                <div class="rex-poll-results">
                    <h2>{{ poll_result }}</h2>
                    ' . ($poll->getHits() > 0 ? '<p> {{ poll_votes_taken }} ' . $poll->getHits() . '</p>' : '') . '
                    <ul> ' . implode('', $options) . '</ul>
                </div>
             </div>
            ';

}
