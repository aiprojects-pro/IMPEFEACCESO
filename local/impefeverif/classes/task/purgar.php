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

namespace local_impefeverif\task;

use local_impefeverif\helper;

/**
 * Destruye la documentacion acreditativa transcurrido el plazo de conservacion.
 *
 * Cubre las tres vias, tanto las validadas automaticamente como las revisadas
 * por persona. Elimina el fichero y conserva la calificacion y la huella, de
 * modo que sigue acreditandose que la comprobacion se hizo.
 *
 * @package    local_impefeverif
 * @copyright  2026 CGD E-Learning Center, S.L.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class purgar extends \core\task\scheduled_task {

    /**
     * Nombre visible de la tarea.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_purgar', 'local_impefeverif');
    }

    /**
     * Ejecuta la purga.
     *
     * @return void
     */
    public function execute() {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/assign/locallib.php');

        if (!helper::ajuste('purgaactiva', 0)) {
            mtrace('local_impefeverif: purga desactivada en los ajustes. No se destruye nada.');
            return;
        }

        $dias = (int) helper::ajuste('diasretencion', 30);
        if ($dias < 1) {
            mtrace('local_impefeverif: [error] plazo de retencion no valido. Se aborta.');
            return;
        }
        $corte = time() - ($dias * DAYSECS);

        $cmids = array_unique(array_merge(
            helper::lista_ids('cmidsauto'),
            helper::lista_ids('cmidsmanual')
        ));

        if (empty($cmids)) {
            mtrace('local_impefeverif: no hay tareas configuradas. Nada que purgar.');
            return;
        }

        $automaticas = helper::lista_ids('cmidsauto');
        $totalficheros = 0;
        $totalusuarios = 0;

        foreach ($cmids as $cmid) {
            $cargado = helper::cargar_tarea($cmid);
            if ($cargado === null) {
                continue;
            }
            [$cm, $course, $context] = $cargado;

            $assign = new \assign($context, $cm, $course);
            $instancia = $assign->get_instance();

            mtrace("local_impefeverif: purgando tarea {$cmid} ({$instancia->name})...");

            // Envios ya calificados hace mas del plazo de retencion.
            $sql = "SELECT s.id AS submissionid,
                           s.userid,
                           s.timemodified AS timesubmitted,
                           g.grade,
                           g.grader,
                           g.timemodified AS timegraded
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

            $candidatos = $DB->get_records_sql($sql, [
                'assignid' => $instancia->id,
                'corte' => $corte,
            ]);

            foreach ($candidatos as $candidato) {
                $info = helper::inspeccionar_ficheros($context->id, (int) $candidato->submissionid);

                if ($info->numfiles === 0) {
                    continue; // Ya estaba purgado o nunca hubo fichero.
                }

                $registro = new \stdClass();
                $registro->userid = (int) $candidato->userid;
                $registro->cmid = (int) $cmid;
                $registro->submissionid = (int) $candidato->submissionid;
                $registro->via = helper::via_declarada((int) $candidato->userid);
                $registro->modo = in_array((int) $cmid, $automaticas, true) ? 'auto' : 'manual';
                $registro->estado = 'purgada';
                $registro->timesubmitted = (int) $candidato->timesubmitted;
                $registro->timevalidated = (int) $candidato->timegraded;
                $registro->graderid = (int) $candidato->grader;
                $registro->numfiles = $info->numfiles;
                $registro->fileext = $info->fileext;
                $registro->filesize = $info->filesize;
                $registro->filehash = $info->filehash;
                $registro->timepurged = time();
                $registro->notificado = 1;

                helper::registrar($registro);

                $borrados = helper::destruir_ficheros($context->id, (int) $candidato->submissionid);
                $totalficheros += $borrados;
                $totalusuarios++;

                mtrace("  usuario {$registro->userid}: {$borrados} fichero(s) destruido(s), "
                    . "huella {$info->filehash} conservada.");
            }
        }

        mtrace("local_impefeverif: purga completada. {$totalficheros} fichero(s) de "
            . "{$totalusuarios} usuario(s) destruidos.");
    }
}
