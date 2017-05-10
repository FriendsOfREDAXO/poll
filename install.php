<?php

$content = rex_file::get(rex_path::addon('poll', 'install/tablesets/poll_tables.json'));
rex_yform_manager_table_api::importTablesets($content);

$content = '';
$searchtext = 'module:poll_basic_output';

$gm = rex_sql::factory();
$gm->setQuery('select * from rex_module where output LIKE "%' . $searchtext . '%"');

$module_id = 0;
$module_name = '';
foreach ($gm->getArray() as $module) {
    $module_id = $module['id'];
    $module_name = $module['name'];
}

$yform_module_name = 'translate:poll_module';

$input = rex_file::get(rex_path::addon('poll', 'module/module_input.inc'));
$output = rex_file::get(rex_path::addon('poll', 'module/module_output.inc'));

$mi = rex_sql::factory();
$mi->setTable('rex_module');
$mi->setValue('input', $input);
$mi->setValue('output', $output);

if ($module_id == rex_request('module_id', 'integer', -1)) {
    $mi->setWhere('id="' . $module_id . '"');
    $mi->update();
} else {
    $mi->setValue('name', $yform_module_name);
    $mi->insert();
    $module_id = (int) $mi->getLastId();
    $module_name = $yform_module_name;
}
