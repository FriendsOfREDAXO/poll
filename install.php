<?php

rex_yform_manager_table_api::importTablesets(rex_file::get(rex_path::addon('poll', 'install/tablesets/poll_tables.json')));

$searchtext = 'module:poll_basic_output';
$yform_module_name = 'translate:poll_module';

$gm = rex_sql::factory();
$gm->setDebug();
$gm->setQuery('select * from rex_module where output LIKE ?', ['%' . $searchtext . '%']);
$modules = $gm->getArray();

if (0 < count($modules)) {
    $gm->setQuery('update rex_module set input = :input, output = :output, updatedate = :updatedate, updateuser = :updateuser where id = :module_id', [
        ':input' => rex_file::get(rex_path::addon('poll', 'module/module_input.inc')),
        ':output' => rex_file::get(rex_path::addon('poll', 'module/module_output.inc')),
        ':updatedate' => date('Y-m-d H:i:s'),
        ':updateuser' => 'poll-addon',
        ':module_id' => $modules[0]['id'],
    ]);

} else {
    $gm->setQuery('insert into rex_module set name = :name, input = :input, output = :output, updatedate = :updatedate, updateuser = :updateuser where id = :module_id', [
        ':name' => $yform_module_name,
        ':input' => rex_file::get(rex_path::addon('poll', 'module/module_input.inc')),
        ':output' => rex_file::get(rex_path::addon('poll', 'module/module_output.inc')),
        ':updatedate' => date('Y-m-d H:i:s'),
        ':updateuser' => 'poll-addon',
        ':module_id' => $modules[0]['id'],
    ]);

}

$name = 'poll_user';

$templates = $gm->getArray('select * from rex_yform_email_template where name = ?', [$name]);
$subject = 'Bestätigung für Umfrage "REX_YFORM_DATA[field="poll-title"]"';
$body = 'Hallo,

vielen Dank für deine Beteiligung an der Umfrage. Bitte bestätige deine Wahl unter folgendem Link: REX_YFORM_DATA[field="poll-link"]

Vielen Dank,
Das Poll-System';

if (0 < count($templates)) {
    $gm->setQuery('update rex_yform_email_template set subject = :subject, body = :body where id = :template_id', [
        ':subject' => $subject,
        ':body' => $body,
        ':template_id' => $templates[0]['id'],
    ]);

} else {

    $gm->setQuery('insert into rex_yform_email_template set subject = :subject, body = :body, name = :name', [
        ':name' => $name,
        ':subject' => $subject,
        ':body' => $body,
    ]);

}
