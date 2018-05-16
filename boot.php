<?php

if (rex::isBackend()) {
    rex_extension::register('PACKAGES_INCLUDED', function ($params) {

        $plugin = rex_plugin::get('yform', 'manager');

        if ($plugin) {
            $pages = $plugin->getProperty('pages');
            $ycom_tables = ['rex_poll', 'rex_poll_option', 'rex_poll_vote'];

            if (isset($pages) && is_array($pages)) {
                foreach ($pages as $page) {
                    if (in_array($page->getKey(), $ycom_tables)) {
                        $page->setBlock('poll');
                    }
                }

            }

        }

    });
}

rex_yform_manager_dataset::setModelClass('rex_poll', rex_poll::class);
rex_yform_manager_dataset::setModelClass('rex_poll_option', rex_poll_option::class);
rex_yform_manager_dataset::setModelClass('rex_poll_vote', rex_poll_vote::class);

if (rex::isBackend() && rex::getUser()) {
    rex_view::addCssFile($this->getAssetsUrl('rex-poll.css'));
    rex_view::addJsFile($this->getAssetsUrl('rex-poll.js'));
}
