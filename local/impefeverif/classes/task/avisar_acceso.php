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
use local_impefeverif\observer;

/**
 * Envia el aviso de acceso una vez la cuenta esta verificada.
 *
 * @package    local_impefeverif
 * @copyright  2026 CGD E-Learning Center, S.L.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class avisar_acceso extends \core\task\adhoc_task {

    /**
     * Nombre visible de la tarea.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_avisaracceso', 'local_impefeverif');
    }

    /**
     * Envia el aviso.
     *
     * @return void
     */
    public function execute() {
        $datos = $this->get_custom_data();
        $userid = isset($datos->userid) ? (int) $datos->userid : 0;

        if ($userid <= 0) {
            mtrace('local_impefeverif: aviso de acceso sin usuario. Se descarta.');
            return;
        }

        if (get_user_preferences(observer::PREF_AVISADO, 0, $userid)) {
            mtrace("local_impefeverif: el usuario {$userid} ya fue avisado. Se omite.");
            return;
        }

        $usuario = \core_user::get_user($userid);
        if (!$usuario || !empty($usuario->deleted) || !empty($usuario->suspended)) {
            return;
        }

        $a = new \stdClass();
        $a->nombre = fullname($usuario);
        $a->url = helper::url_catalogo();
        $a->sitio = format_string(get_site()->fullname);

        helper::notificar(
            $userid,
            get_string('avisoacceso_asunto', 'local_impefeverif'),
            get_string('avisoacceso_cuerpo', 'local_impefeverif', $a),
            'acceso'
        );

        set_user_preference(observer::PREF_AVISADO, time(), $userid);

        mtrace("local_impefeverif: aviso de acceso enviado al usuario {$userid}.");
    }
}
