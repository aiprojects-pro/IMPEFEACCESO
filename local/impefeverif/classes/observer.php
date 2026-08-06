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

namespace local_impefeverif;

use local_impefeverif\cupo;

/**
 * Observadores de eventos.
 *
 * El aviso de acceso se dispara al entrar en la cohorte de verificados, que es
 * el momento en que el catalogo queda realmente accesible, y no al consignar la
 * calificacion. Asi vale igual para las vias automaticas y para el
 * empadronamiento, que revisa IMPEFE a mano.
 *
 * @package    local_impefeverif
 * @copyright  2026 CGD E-Learning Center, S.L.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /** @var string Preferencia que evita reenviar el aviso. */
    const PREF_AVISADO = 'local_impefeverif_avisoacceso';

    /**
     * Encola el aviso de acceso cuando el usuario entra en la cohorte de verificados.
     *
     * No se envia el mensaje aqui: el evento puede producirse dentro de una
     * transaccion de base de datos, y message_send no admite eso. Se delega en
     * una tarea ad hoc que corre en el siguiente cron.
     *
     * @param \core\event\cohort_member_added $event
     * @return void
     */
    public static function cohort_member_added(\core\event\cohort_member_added $event): void {
        $cohorteid = (int) helper::ajuste('cohorteid', 0);
        if ($cohorteid <= 0) {
            return;
        }

        // objectid es la cohorte, relateduserid el usuario incorporado.
        if ((int) $event->objectid !== $cohorteid) {
            return;
        }

        $userid = (int) $event->relateduserid;
        if ($userid <= 0) {
            return;
        }

        // No repetir el aviso si el usuario ya lo recibio en su dia.
        if (get_user_preferences(self::PREF_AVISADO, 0, $userid)) {
            return;
        }

        $tarea = new \local_impefeverif\task\avisar_acceso();
        $tarea->set_custom_data(['userid' => $userid]);
        $tarea->set_component('local_impefeverif');

        \core\task\manager::queue_adhoc_task($tarea, true);
    }

    /**
     * Aprueba el acceso en el momento en que una tarea configurada se califica como Apto.
     *
     * Esto cubre especialmente la via de empadronamiento, que revisa IMPEFE a mano:
     * no debe depender de esperar al siguiente cron para que desaparezca la solicitud
     * de acceso y se habilite el catalogo.
     *
     * @param \mod_assign\event\submission_graded $event
     * @return void
     */
    public static function submission_graded(\mod_assign\event\submission_graded $event): void {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/cohort/lib.php');
        require_once($CFG->dirroot . '/user/profile/lib.php');
        require_once($CFG->dirroot . '/user/profile/definelib.php');
        require_once($CFG->libdir . '/accesslib.php');

        $userid = (int) $event->relateduserid;
        $cmid = (int) $event->contextinstanceid;
        if ($userid <= 0 || $cmid <= 0) {
            return;
        }

        $cmids = array_unique(array_merge(
            helper::lista_ids('cmidsauto'),
            helper::lista_ids('cmidsmanual')
        ));
        if (!in_array($cmid, $cmids, true)) {
            return;
        }

        $grade = $DB->get_record('assign_grades', ['id' => $event->objectid], 'id, userid, grade');
        if (!$grade || (int) $grade->userid !== $userid) {
            return;
        }

        $nota = (float) helper::ajuste('notaapto', 2);
        if ((float) $grade->grade < $nota) {
            $usuario = \core_user::get_user($userid);

            $a = new \stdClass();
            $a->nombre = fullname($usuario);

            helper::notificar(
                $userid,
                get_string('avisoincompleto_asunto', 'local_impefeverif'),
                get_string('avisoincompleto_cuerpo', 'local_impefeverif', $a)
            );
            return;
        }

        self::aprobar_usuario($userid);
    }

    /**
     * Anota la matricula en el libro. Consume plaza aunque luego se abandone.
     *
     * @param \core\event\user_enrolment_created $event
     * @return void
     */
    public static function user_enrolment_created(\core\event\user_enrolment_created $event): void {
        global $DB;

        $userid = (int) $event->relateduserid;
        $courseid = (int) $event->courseid;

        if ($userid <= 0 || !cupo::es_del_catalogo($courseid)) {
            return;
        }

        // Evitar duplicar si el evento se repite sobre una matricula ya abierta.
        $abierta = $DB->record_exists_select(
            'local_impefeverif_matr',
            'userid = :userid AND courseid = :courseid AND timeunenrolled = 0',
            ['userid' => $userid, 'courseid' => $courseid]
        );
        if ($abierta) {
            return;
        }

        $fila = new \stdClass();
        $fila->userid = $userid;
        $fila->courseid = $courseid;
        $fila->timeenrolled = time();
        $fila->timecompleted = 0;
        $fila->timeunenrolled = 0;

        $DB->insert_record('local_impefeverif_matr', $fila);
        self::sincronizar_cupo_usuario($userid);
    }

    /**
     * Anota la baja en el libro. La fila no se borra: la plaza anual sigue consumida.
     *
     * @param \core\event\user_enrolment_deleted $event
     * @return void
     */
    public static function user_enrolment_deleted(\core\event\user_enrolment_deleted $event): void {
        global $DB;

        $userid = (int) $event->relateduserid;
        $courseid = (int) $event->courseid;

        if ($userid <= 0) {
            return;
        }

        $fila = $DB->get_record_select(
            'local_impefeverif_matr',
            'userid = :userid AND courseid = :courseid AND timeunenrolled = 0',
            ['userid' => $userid, 'courseid' => $courseid],
            '*',
            IGNORE_MULTIPLE
        );

        if (!$fila) {
            return;
        }

        $fila->timeunenrolled = time();
        $DB->update_record('local_impefeverif_matr', $fila);
        self::sincronizar_cupo_usuario($userid);
    }

    /**
     * Anota la finalizacion. Es lo que libera una de las dos plazas simultaneas.
     *
     * @param \core\event\course_completed $event
     * @return void
     */
    public static function course_completed(\core\event\course_completed $event): void {
        global $DB;

        $userid = (int) $event->relateduserid;
        $courseid = (int) $event->courseid;

        if ($userid <= 0 || !cupo::es_del_catalogo($courseid)) {
            return;
        }

        $fila = $DB->get_record_select(
            'local_impefeverif_matr',
            'userid = :userid AND courseid = :courseid AND timecompleted = 0',
            ['userid' => $userid, 'courseid' => $courseid],
            '*',
            IGNORE_MULTIPLE
        );

        if (!$fila) {
            return;
        }

        $fila->timecompleted = time();
        $DB->update_record('local_impefeverif_matr', $fila);
        self::sincronizar_cupo_usuario($userid);
    }

    /**
     * Sincroniza inmediatamente la cohorte de cupo para una sola persona.
     *
     * La tarea programada mantiene la coherencia global, pero el evento de
     * matricula debe cerrar el cupo en el momento para evitar matriculas extra
     * entre dos ejecuciones del cron.
     *
     * @param int $userid
     * @return void
     */
    protected static function sincronizar_cupo_usuario(int $userid): void {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/cohort/lib.php');

        $concupo = (int) helper::ajuste('cohortecupoid', 0);
        if ($userid <= 0 || $concupo <= 0) {
            return;
        }

        $evaluacion = cupo::evaluar_usuario($userid);
        $existe = $DB->record_exists('cohort_members', [
            'cohortid' => $concupo,
            'userid' => $userid,
        ]);

        if ($evaluacion->puedematricularse && !$existe) {
            cohort_add_member($concupo, $userid);
        } else if (!$evaluacion->puedematricularse && $existe) {
            cohort_remove_member($concupo, $userid);
            helper::notificar_cupo_agotado($userid, $evaluacion);
        }
    }

    /**
     * Completa el circuito de acceso para un usuario aprobado.
     *
     * @param int $userid
     * @return void
     */
    protected static function aprobar_usuario(int $userid): void {
        global $DB;

        $verificados = (int) helper::ajuste('cohorteid', 0);
        $concupo = (int) helper::ajuste('cohortecupoid', 0);

        if ($verificados > 0 && !$DB->record_exists('cohort_members', ['cohortid' => $verificados, 'userid' => $userid])) {
            cohort_add_member($verificados, $userid);
        }

        self::marcar_perfil_aprobado($userid);
        self::asignar_rol_verificado($userid);

        if ($concupo > 0 && $concupo !== $verificados) {
            $evaluacion = cupo::evaluar_usuario($userid);
            $existe = $DB->record_exists('cohort_members', ['cohortid' => $concupo, 'userid' => $userid]);
            if ($evaluacion->puedematricularse && !$existe) {
                cohort_add_member($concupo, $userid);
            } else if (!$evaluacion->puedematricularse && $existe) {
                cohort_remove_member($concupo, $userid);
            }
        }
    }

    /**
     * Mantiene el campo de perfil heredado en Aprobado.
     *
     * @param int $userid
     * @return void
     */
    protected static function marcar_perfil_aprobado(int $userid): void {
        global $DB;

        $fieldid = (int) $DB->get_field('user_info_field', 'id', ['shortname' => 'verification']);
        if ($fieldid <= 0) {
            return;
        }

        $current = $DB->get_record('user_info_data', ['userid' => $userid, 'fieldid' => $fieldid]);
        if ($current) {
            if ((string) $current->data !== 'Aprobado') {
                $current->data = 'Aprobado';
                $current->dataformat = FORMAT_PLAIN;
                $DB->update_record('user_info_data', $current);
            }
        } else {
            $DB->insert_record('user_info_data', (object) [
                'userid' => $userid,
                'fieldid' => $fieldid,
                'data' => 'Aprobado',
                'dataformat' => FORMAT_PLAIN,
            ]);
        }

        \profile_purge_user_fields_cache();
    }

    /**
     * Asigna el rol global de usuario verificado si existe.
     *
     * @param int $userid
     * @return void
     */
    protected static function asignar_rol_verificado(int $userid): void {
        global $DB;

        $roleid = (int) $DB->get_field('role', 'id', ['shortname' => 'usuarioverificado']);
        if ($roleid <= 0) {
            return;
        }

        $context = \context_system::instance();
        if (!$DB->record_exists('role_assignments', [
            'roleid' => $roleid,
            'contextid' => $context->id,
            'userid' => $userid,
        ])) {
            role_assign($roleid, $userid, $context->id, 'local_impefeverif');
        }
    }
}
