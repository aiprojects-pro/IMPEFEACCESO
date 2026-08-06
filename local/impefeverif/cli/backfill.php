<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Carga inicial del libro de matriculas a partir de las matriculas existentes.
 *
 * Los observadores solo capturan lo que ocurre a partir de su instalacion. Este
 * script recupera el historico. Solo cuenta las matriculas vivas: las bajas
 * anteriores a la instalacion son irrecuperables, Moodle ya borro la fila.
 *
 * Uso:
 *   php local/impefeverif/cli/backfill.php            (simulacion)
 *   php local/impefeverif/cli/backfill.php --ejecutar
 *
 * @package    local_impefeverif
 * @copyright  2026 CGD E-Learning Center, S.L.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_impefeverif\cupo;
use local_impefeverif\helper;

[$opciones, $sinreconocer] = cli_get_params(
    ['help' => false, 'ejecutar' => false],
    ['h' => 'help', 'e' => 'ejecutar']
);

if ($opciones['help']) {
    cli_writeln("Carga inicial del libro de matriculas.\n");
    cli_writeln("  --ejecutar, -e   Escribe en la base de datos. Sin esta opcion solo simula.");
    cli_writeln("  --help,     -h   Esta ayuda.");
    exit(0);
}

$categorias = helper::lista_ids('categoriacatalogo');
if (empty($categorias)) {
    cli_error('No hay categorias de catalogo configuradas. Configuralas antes de la carga inicial.');
}

$sql = "SELECT ue.id AS ueid, ue.userid, e.courseid, ue.timecreated, ue.timestart
          FROM {user_enrolments} ue
          JOIN {enrol} e ON e.id = ue.enrolid
          JOIN {user} u ON u.id = ue.userid AND u.deleted = 0
      ORDER BY ue.userid ASC, ue.timecreated ASC";

$matriculas = $DB->get_recordset_sql($sql);

$insertadas = 0;
$omitidas = 0;
$completadas = 0;

foreach ($matriculas as $m) {
    if (!cupo::es_del_catalogo((int) $m->courseid)) {
        continue;
    }

    $existe = $DB->record_exists('local_impefeverif_matr', [
        'userid' => (int) $m->userid,
        'courseid' => (int) $m->courseid,
    ]);

    if ($existe) {
        $omitidas++;
        continue;
    }

    // Fecha de finalizacion, si consta.
    $timecompleted = (int) $DB->get_field('course_completions', 'timecompleted', [
        'userid' => (int) $m->userid,
        'course' => (int) $m->courseid,
    ]);

    $fila = new \stdClass();
    $fila->userid = (int) $m->userid;
    $fila->courseid = (int) $m->courseid;
    $fila->timeenrolled = (int) ($m->timecreated ?: $m->timestart);
    $fila->timecompleted = $timecompleted ?: 0;
    $fila->timeunenrolled = 0;

    if ($opciones['ejecutar']) {
        $DB->insert_record('local_impefeverif_matr', $fila);
    }

    $insertadas++;
    if ($fila->timecompleted) {
        $completadas++;
    }
}

$matriculas->close();

cli_heading($opciones['ejecutar'] ? 'Carga inicial ejecutada' : 'Simulacion de la carga inicial');
cli_writeln("Matriculas a anotar ....... {$insertadas}");
cli_writeln("De ellas, ya finalizadas .. {$completadas}");
cli_writeln("Ya existentes, omitidas ... {$omitidas}");

if (!$opciones['ejecutar']) {
    cli_writeln("\nNo se ha escrito nada. Repite con --ejecutar cuando el resultado te cuadre.");
} else {
    cli_writeln("\nHecho. Ejecuta ahora la tarea de recalculo de cupos.");
}
