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
 * Simulacion sin efectos: muestra que haria cada tarea, sin calificar ni borrar.
 *
 * Uso:
 *   php local/impefeverif/cli/simular.php
 *   php local/impefeverif/cli/simular.php --purga
 *
 * @package    local_impefeverif
 * @copyright  2026 CGD E-Learning Center, S.L.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

use local_impefeverif\cupo;
use local_impefeverif\helper;

[$opciones, $sinreconocer] = cli_get_params(
    ['help' => false, 'purga' => false, 'cupos' => false],
    ['h' => 'help', 'p' => 'purga', 'c' => 'cupos']
);

if ($opciones['help']) {
    cli_writeln("Simulacion de la verificacion de requisitos (no modifica nada).\n");
    cli_writeln("  --purga, -p   Simula la destruccion de documentacion vencida.");
    cli_writeln("  --cupos, -c   Muestra el cupo de cada persona verificada.");
    cli_writeln("  --help,  -h   Esta ayuda.");
    exit(0);
}

$ahora = time();
$zona = (string) helper::ajuste('zonahoraria', 'Europe/Madrid');
$laboral = helper::en_horario_laboral($ahora) ? 'SI' : 'NO';

cli_heading('Configuracion vigente');
cli_writeln('Validacion automatica activa .. ' . (helper::ajuste('activo', 0) ? 'SI' : 'NO'));
cli_writeln('Purga activa .................. ' . (helper::ajuste('purgaactiva', 0) ? 'SI' : 'NO'));
cli_writeln('Zona horaria .................. ' . $zona);
cli_writeln('En horario laboral ahora ...... ' . $laboral);
cli_writeln('Horas de espera ............... ' . helper::ajuste('horasespera', 24));
cli_writeln('Dias de conservacion .......... ' . helper::ajuste('diasretencion', 30));
cli_writeln('Tareas automaticas (cmid) ..... ' . implode(', ', helper::lista_ids('cmidsauto')));
cli_writeln('Tareas manuales (cmid) ........ ' . implode(', ', helper::lista_ids('cmidsmanual')));
cli_writeln('Cuenta calificadora ........... ' . helper::ajuste('graderid', 0));
cli_writeln('Cohorte de verificados ........ ' . helper::ajuste('cohorteid', 0));
cli_writeln('Cohorte con cupo .............. ' . helper::ajuste('cohortecupoid', 0));
cli_writeln('Categoria del catalogo ........ ' . helper::ajuste('categoriacatalogo', 0));
cli_writeln('Maximo anual / simultaneos .... ' . helper::ajuste('maxanuales', 5)
    . ' / ' . helper::ajuste('maxsimultaneos', 2));
cli_writeln('');

if ($opciones['cupos']) {
    $verificados = (int) helper::ajuste('cohorteid', 0);
    if ($verificados <= 0) {
        cli_error('No hay cohorte de verificados configurada.');
    }

    $userids = array_map('intval', $DB->get_fieldset_select(
        'cohort_members', 'userid', 'cohortid = ?', [$verificados]));

    cli_heading('Cupo por persona verificada (' . count($userids) . ')');

    if (empty($userids)) {
        cli_writeln('La cohorte de verificados esta vacia.');
        exit(0);
    }

    $libro = cupo::libro($userids);
    $condisponible = 0;

    foreach ($userids as $userid) {
        $r = cupo::evaluar($libro[$userid] ?? []);
        $usuario = \core_user::get_user($userid, 'id, firstname, lastname');
        $nombre = $usuario ? fullname($usuario) : "usuario {$userid}";
        $renueva = $r->ventanafin ? userdate($r->ventanafin, '%d/%m/%Y') : '-';

        cli_writeln(sprintf(
            '  %-32s consumidas %d/%d  en curso %d/%d  renueva %s  %s',
            \core_text::substr($nombre, 0, 32),
            $r->consumidas, $r->maxanuales,
            $r->encurso, $r->maxsimultaneos,
            $renueva,
            $r->puedematricularse ? 'CON CUPO' : 'SIN CUPO (' . $r->motivo . ')'
        ));

        if ($r->puedematricularse) {
            $condisponible++;
        }
    }

    cli_writeln("\nTotal con cupo disponible: {$condisponible} de " . count($userids) . '.');
    exit(0);
}

if ($opciones['purga']) {
    $dias = (int) helper::ajuste('diasretencion', 30);
    $corte = $ahora - ($dias * DAYSECS);
    $cmids = array_unique(array_merge(helper::lista_ids('cmidsauto'), helper::lista_ids('cmidsmanual')));

    cli_heading('Documentacion que se destruiria');
    $total = 0;

    foreach ($cmids as $cmid) {
        $cargado = helper::cargar_tarea((int) $cmid);
        if ($cargado === null) {
            continue;
        }
        [$cm, $course, $context] = $cargado;
        $assign = new \assign($context, $cm, $course);
        $instancia = $assign->get_instance();

        $sql = "SELECT s.id AS submissionid, s.userid, g.timemodified AS timegraded
                  FROM {assign_submission} s
                  JOIN {assign_grades} g
                    ON g.assignment = s.assignment
                   AND g.userid = s.userid
                   AND g.attemptnumber = s.attemptnumber
                 WHERE s.assignment = :assignid
                   AND s.latest = 1
                   AND g.grade IS NOT NULL
                   AND g.grade >= 0
                   AND g.timemodified <= :corte";

        $filas = $DB->get_records_sql($sql, ['assignid' => $instancia->id, 'corte' => $corte]);

        cli_writeln("Tarea {$cmid} ({$instancia->name}): " . count($filas) . ' candidato(s).');

        foreach ($filas as $fila) {
            $info = helper::inspeccionar_ficheros($context->id, (int) $fila->submissionid);
            if ($info->numfiles === 0) {
                continue;
            }
            $fecha = userdate((int) $fila->timegraded, '%d/%m/%Y');
            cli_writeln("  usuario {$fila->userid}: {$info->numfiles} fichero(s) "
                . "[{$info->fileext}] validado el {$fecha}");
            $total++;
        }
    }

    cli_writeln("\nTotal: {$total} envio(s) se purgarian.");
    exit(0);
}

$horas = (int) helper::ajuste('horasespera', 24);
$corte = $ahora - ($horas * HOURSECS);

cli_heading('Acreditaciones que se validarian');
$validables = 0;
$incompletas = 0;

foreach (helper::lista_ids('cmidsauto') as $cmid) {
    $cargado = helper::cargar_tarea((int) $cmid);
    if ($cargado === null) {
        continue;
    }
    [$cm, $course, $context] = $cargado;
    $assign = new \assign($context, $cm, $course);
    $instancia = $assign->get_instance();

    $envios = $DB->get_records_select(
        'assign_submission',
        'assignment = :assignid AND latest = 1 AND status = :estado AND timemodified <= :corte',
        ['assignid' => $instancia->id, 'estado' => ASSIGN_SUBMISSION_STATUS_SUBMITTED, 'corte' => $corte],
        'timemodified ASC'
    );

    cli_writeln("Tarea {$cmid} ({$instancia->name}): " . count($envios) . ' envio(s) en plazo.');

    foreach ($envios as $envio) {
        if (empty($envio->userid)) {
            continue;
        }
        $previa = $DB->get_record('assign_grades', [
            'assignment' => $instancia->id,
            'userid' => $envio->userid,
            'attemptnumber' => $envio->attemptnumber,
        ]);
        if ($previa && $previa->grade !== null && (float) $previa->grade >= 0) {
            continue;
        }

        $info = helper::inspeccionar_ficheros($context->id, (int) $envio->id);
        $via = helper::via_declarada((int) $envio->userid);

        if ($info->numfiles === 0 || !$info->admitido) {
            cli_writeln("  usuario {$envio->userid} [{$via}]: INCOMPLETA "
                . "({$info->numfiles} fichero(s), {$info->fileext})");
            $incompletas++;
            continue;
        }

        cli_writeln("  usuario {$envio->userid} [{$via}]: se validaria "
            . "({$info->fileext}, {$info->filesize} bytes)");
        $validables++;
    }
}

cli_writeln("\nTotal: {$validables} se validarian, {$incompletas} quedarian incompletas.");
if ($laboral === 'NO') {
    cli_writeln('Aviso: ahora mismo esta fuera del horario laboral, la tarea real no actuaria.');
}
