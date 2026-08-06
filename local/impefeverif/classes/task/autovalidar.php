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
 * Valida automaticamente las acreditaciones de estudiante y trabajador.
 *
 * Solo actua dentro del horario laboral configurado y cuando ha transcurrido
 * el plazo de espera desde el envio. No lee el contenido del documento:
 * comprueba que existe, que el formato es admisible, y guarda su huella.
 *
 * @package    local_impefeverif
 * @copyright  2026 CGD E-Learning Center, S.L.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class autovalidar extends \core\task\scheduled_task {

    /**
     * Nombre visible de la tarea.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_autovalidar', 'local_impefeverif');
    }

    /**
     * Ejecuta la validacion automatica.
     *
     * @return void
     */
    public function execute() {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/assign/locallib.php');

        if (!helper::ajuste('activo', 0)) {
            mtrace('local_impefeverif: desactivado en los ajustes. No se hace nada.');
            return;
        }

        if (!helper::en_horario_laboral()) {
            mtrace('local_impefeverif: fuera del horario laboral configurado. No se valida nada.');
            return;
        }

        $cmids = helper::lista_ids('cmidsauto');
        if (empty($cmids)) {
            mtrace('local_impefeverif: no hay tareas de validacion automatica configuradas.');
            return;
        }

        $horas = (int) helper::ajuste('horasespera', 24);
        $corte = time() - ($horas * HOURSECS);
        $nota = (float) helper::ajuste('notaapto', 2);
        $graderid = (int) helper::ajuste('graderid', 0);

        if ($graderid <= 0 || !$DB->record_exists('user', ['id' => $graderid, 'deleted' => 0])) {
            mtrace('local_impefeverif: [error] la cuenta calificadora configurada no existe. Se aborta.');
            return;
        }

        $validadas = 0;
        $rechazadas = 0;

        foreach ($cmids as $cmid) {
            $cargado = helper::cargar_tarea($cmid);
            if ($cargado === null) {
                continue;
            }
            [$cm, $course, $context] = $cargado;

            $assign = new \assign($context, $cm, $course);
            $instancia = $assign->get_instance();

            mtrace("local_impefeverif: revisando tarea {$cmid} ({$instancia->name})...");

            $envios = $DB->get_records_select(
                'assign_submission',
                'assignment = :assignid AND latest = 1 AND status = :estado AND timemodified <= :corte',
                [
                    'assignid' => $instancia->id,
                    'estado' => ASSIGN_SUBMISSION_STATUS_SUBMITTED,
                    'corte' => $corte,
                ],
                'timemodified ASC'
            );

            foreach ($envios as $envio) {
                if (empty($envio->userid)) {
                    continue; // Envio de grupo: no contemplado en este circuito.
                }

                // No recalificar lo ya calificado, ni por persona ni por el propio automatismo.
                $previa = $DB->get_record('assign_grades', [
                    'assignment' => $instancia->id,
                    'userid' => $envio->userid,
                    'attemptnumber' => $envio->attemptnumber,
                ]);
                if ($previa && $previa->grade !== null && (float) $previa->grade >= 0) {
                    continue;
                }

                $info = helper::inspeccionar_ficheros($context->id, $envio->id);

                $registro = new \stdClass();
                $registro->userid = (int) $envio->userid;
                $registro->cmid = (int) $cmid;
                $registro->submissionid = (int) $envio->id;
                $registro->via = helper::via_declarada((int) $envio->userid) ?: helper::via_por_tarea((int) $cmid);
                $registro->modo = 'auto';
                $registro->timesubmitted = (int) $envio->timemodified;
                $registro->numfiles = $info->numfiles;
                $registro->fileext = $info->fileext;
                $registro->filesize = $info->filesize;
                $registro->filehash = $info->filehash;
                $registro->graderid = $graderid;
                $registro->timevalidated = 0;
                $registro->timepurged = 0;
                $registro->notificado = 0;

                // Comprobacion objetiva: hay documento y el formato es admisible.
                if ($info->numfiles === 0 || !$info->admitido) {
                    $registro->estado = ($info->numfiles === 0) ? 'sin_archivo' : 'formato_no_admitido';

                    $yaavisado = $DB->get_field('local_impefeverif_reg', 'notificado', [
                        'userid' => $registro->userid,
                        'cmid' => $registro->cmid,
                    ]);

                    if (empty($yaavisado)) {
                        $usuario = \core_user::get_user($userid);

                        $a = new \stdClass();
                        $a->nombre = fullname($usuario);

                        helper::notificar(
                            $registro->userid,
                            get_string('avisoincompleto_asunto', 'local_impefeverif'),
                            get_string('avisoincompleto_cuerpo', 'local_impefeverif', $a)
                        );
                        $registro->notificado = 1;
                    } else {
                        $registro->notificado = 1;
                    }

                    helper::registrar($registro);
                    $rechazadas++;
                    mtrace("  usuario {$registro->userid}: {$registro->estado}. No se valida.");
                    continue;
                }

                // Consignar la calificacion con la cuenta de validacion automatica.
                $calificacion = $assign->get_user_grade((int) $envio->userid, true, (int) $envio->attemptnumber);
                $calificacion->grade = $nota;
                $calificacion->grader = $graderid;
                $calificacion->timemodified = time();
                $assign->update_grade($calificacion);

                // Recalcular la finalizacion de la actividad para este usuario.
                $modinfo = get_fast_modinfo($course);
                $cminfo = $modinfo->get_cm($cm->id);
                $completion = new \completion_info($course);
                if ($completion->is_enabled($cminfo)) {
                    $completion->update_state($cminfo, COMPLETION_UNKNOWN, (int) $envio->userid);
                }

                $registro->estado = 'validada';
                $registro->timevalidated = time();
                $registro->notificado = 0;
                helper::registrar($registro);

                // No se avisa aqui. El aviso lo dispara la entrada en la cohorte de
                // verificados, que es cuando el catalogo queda realmente accesible.
                $validadas++;
                mtrace("  usuario {$registro->userid}: validada (hash {$info->filehash}).");
            }
        }

        mtrace("local_impefeverif: {$validadas} acreditaciones validadas, {$rechazadas} incompletas.");
    }
}
