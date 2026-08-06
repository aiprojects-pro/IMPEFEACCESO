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

use local_impefeverif\cupo;
use local_impefeverif\helper;

/**
 * Sincroniza la cohorte de personas con cupo disponible.
 *
 * La automatricula de cada curso del catalogo esta restringida a los miembros
 * de esa cohorte, de modo que al agotarse el cupo el boton de matricularse
 * desaparece en todos los cursos a la vez, y vuelve al liberarse plaza.
 *
 * @package    local_impefeverif
 * @copyright  2026 CGD E-Learning Center, S.L.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class recalcular_cupos extends \core\task\scheduled_task {

    /**
     * Nombre visible de la tarea.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_recalcularcupos', 'local_impefeverif');
    }

    /**
     * Recalcula la pertenencia a la cohorte de cupo.
     *
     * @return void
     */
    public function execute() {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/cohort/lib.php');
        require_once($CFG->dirroot . '/user/profile/lib.php');
        require_once($CFG->dirroot . '/user/profile/definelib.php');
        require_once($CFG->libdir . '/accesslib.php');

        $verificados = (int) helper::ajuste('cohorteid', 0);
        $concupo = (int) helper::ajuste('cohortecupoid', 0);

        if ($verificados <= 0 || $concupo <= 0) {
            mtrace('local_impefeverif: cohortes de verificados o de cupo sin configurar. No se recalcula nada.');
            return;
        }

        if ($verificados === $concupo) {
            mtrace('local_impefeverif: [error] las dos cohortes son la misma. Se aborta.');
            return;
        }

        $this->sincronizar_verificados($verificados);

        // Solo se evalua a quien ya esta verificado: el resto no puede matricularse igualmente.
        $candidatos = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', [$verificados]);
        $candidatos = array_map('intval', $candidatos);

        if (empty($candidatos)) {
            mtrace('local_impefeverif: la cohorte de verificados esta vacia.');
            return;
        }

        $actuales = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', [$concupo]);
        $actuales = array_map('intval', $actuales);

        $libro = cupo::libro($candidatos);

        $deben = [];
        $agotados = [];

        foreach ($candidatos as $userid) {
            $evaluacion = cupo::evaluar($libro[$userid] ?? []);
            if ($evaluacion->puedematricularse) {
                $deben[] = $userid;
            } else {
                $agotados[$userid] = $evaluacion;
            }
        }

        $anadir = array_diff($deben, $actuales);
        $quitar = array_diff($actuales, $deben);

        foreach ($anadir as $userid) {
            cohort_add_member($concupo, $userid);
        }

        foreach ($quitar as $userid) {
            cohort_remove_member($concupo, $userid);
            if (isset($agotados[$userid])) {
                $this->avisar($userid, $agotados[$userid]);
            }
        }

        mtrace('local_impefeverif: cupos recalculados. '
            . count($anadir) . ' incorporacion(es), ' . count($quitar) . ' baja(s), '
            . count($deben) . ' persona(s) con cupo disponible.');
    }

    /**
     * Incorpora a la cohorte Verificados a quienes ya constan como aprobados.
     *
     * Cubre dos escenarios del aula:
     *  - el circuito nuevo, basado en calificacion Apto en las tareas configuradas;
     *  - el circuito anterior, basado en el campo de perfil verification = Aprobado.
     *
     * @param int $cohorteid
     * @return void
     */
    protected function sincronizar_verificados(int $cohorteid): void {
        global $DB;

        $userids = [];

        $cmids = array_unique(array_merge(
            helper::lista_ids('cmidsauto'),
            helper::lista_ids('cmidsmanual')
        ));
        $nota = (float) helper::ajuste('notaapto', 2);

        if (!empty($cmids)) {
            [$cmsql, $cmparams] = $DB->get_in_or_equal($cmids, SQL_PARAMS_NAMED, 'cm');
            $params = $cmparams + ['nota' => $nota];
            $sql = "SELECT DISTINCT g.userid
                      FROM {course_modules} cm
                      JOIN {assign} a ON a.id = cm.instance
                      JOIN {assign_grades} g ON g.assignment = a.id
                      JOIN {user} u ON u.id = g.userid AND u.deleted = 0
                     WHERE cm.id {$cmsql}
                       AND g.grade >= :nota";
            $userids = array_merge($userids, array_map('intval', $DB->get_fieldset_sql($sql, $params)));
        }

        $fieldid = (int) $DB->get_field('user_info_field', 'id', ['shortname' => 'verification']);
        if ($fieldid > 0) {
            $sql = "SELECT d.userid
                      FROM {user_info_data} d
                      JOIN {user} u ON u.id = d.userid AND u.deleted = 0
                     WHERE d.fieldid = :fieldid
                       AND d.data = :estado";
            $userids = array_merge($userids, array_map('intval', $DB->get_fieldset_sql($sql, [
                'fieldid' => $fieldid,
                'estado' => 'Aprobado',
            ])));
        }

        $userids = array_values(array_unique(array_filter($userids)));
        $anadidos = 0;

        foreach ($userids as $userid) {
            if (!$DB->record_exists('cohort_members', ['cohortid' => $cohorteid, 'userid' => $userid])) {
                cohort_add_member($cohorteid, $userid);
                $anadidos++;
            }
        }

        $this->marcar_perfil_aprobado($userids);

        mtrace("local_impefeverif: verificados sincronizados. {$anadidos} incorporacion(es).");
    }

    /**
     * Mantiene el campo de perfil heredado en Aprobado.
     *
     * La portada ya usa este campo para ocultar la seccion Solicitud de acceso
     * a quienes tienen la solicitud aprobada.
     *
     * @param int[] $userids
     * @return void
     */
    protected function marcar_perfil_aprobado(array $userids): void {
        global $DB;

        $fieldid = (int) $DB->get_field('user_info_field', 'id', ['shortname' => 'verification']);
        if ($fieldid <= 0 || empty($userids)) {
            return;
        }

        $now = time();
        foreach ($userids as $userid) {
            if (!$DB->record_exists('user', ['id' => $userid, 'deleted' => 0])) {
                continue;
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

            $this->asignar_rol_verificado((int) $userid);
        }

        \profile_purge_user_fields_cache();
    }

    /**
     * Asigna el rol global de usuario verificado si existe.
     *
     * @param int $userid
     * @return void
     */
    protected function asignar_rol_verificado(int $userid): void {
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

    /**
     * Avisa a la persona de que se ha quedado sin cupo, una vez por motivo.
     *
     * @param int $userid
     * @param \stdClass $evaluacion
     * @return void
     */
    protected function avisar(int $userid, \stdClass $evaluacion): void {
        helper::notificar_cupo_agotado($userid, $evaluacion);
    }
}
