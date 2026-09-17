<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Removes the legacy task-description notice from IMPEFE access activities.
 *
 * @package local_impefeverif
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');

global $DB;

foreach ([11469, 11495, 11496] as $cmid) {
    $assign = $DB->get_record_sql(
        "SELECT a.*
           FROM {assign} a
           JOIN {course_modules} cm ON cm.instance = a.id
           JOIN {modules} m ON m.id = cm.module AND m.name = ?
          WHERE cm.id = ?",
        ['assign', $cmid],
        MUST_EXIST
    );

    $intro = preg_replace(
        '#\s*<div class="alert alert-info impefe-verification-notice" role="status">.*?</div>#s',
        '',
        $assign->intro
    );
    if ($intro === $assign->intro) {
        mtrace("Actividad {$cmid}: no contiene aviso heredado.");
        continue;
    }

    $assign->intro = $intro;
    $assign->timemodified = time();
    $DB->update_record('assign', $assign);
    mtrace("Actividad {$cmid}: aviso heredado retirado.");
}

purge_all_caches();
mtrace('Cachés purgadas.');
