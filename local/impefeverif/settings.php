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
 * Ajustes de administracion.
 *
 * @package    local_impefeverif
 * @copyright  2026 CGD E-Learning Center, S.L.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_impefeverif',
        get_string('pluginname', 'local_impefeverif')
    );
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configcheckbox(
        'local_impefeverif/activo',
        get_string('activo', 'local_impefeverif'),
        get_string('activo_desc', 'local_impefeverif'),
        0
    ));

    $settings->add(new admin_setting_configtext(
        'local_impefeverif/cmidsauto',
        get_string('cmidsauto', 'local_impefeverif'),
        get_string('cmidsauto_desc', 'local_impefeverif'),
        '',
        PARAM_SEQUENCE
    ));

    $settings->add(new admin_setting_configtext(
        'local_impefeverif/cmidsmanual',
        get_string('cmidsmanual', 'local_impefeverif'),
        get_string('cmidsmanual_desc', 'local_impefeverif'),
        '',
        PARAM_SEQUENCE
    ));

    $settings->add(new admin_setting_configtext(
        'local_impefeverif/cohorteid',
        get_string('cohorteid', 'local_impefeverif'),
        get_string('cohorteid_desc', 'local_impefeverif'),
        0,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_impefeverif/urlcatalogo',
        get_string('urlcatalogo', 'local_impefeverif'),
        get_string('urlcatalogo_desc', 'local_impefeverif'),
        '',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configtext(
        'local_impefeverif/cohortecupoid',
        get_string('cohortecupoid', 'local_impefeverif'),
        get_string('cohortecupoid_desc', 'local_impefeverif'),
        0,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_impefeverif/categoriacatalogo',
        get_string('categoriacatalogo', 'local_impefeverif'),
        get_string('categoriacatalogo_desc', 'local_impefeverif'),
        '',
        PARAM_SEQUENCE
    ));

    $settings->add(new admin_setting_configtext(
        'local_impefeverif/maxanuales',
        get_string('maxanuales', 'local_impefeverif'),
        get_string('maxanuales_desc', 'local_impefeverif'),
        5,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_impefeverif/maxsimultaneos',
        get_string('maxsimultaneos', 'local_impefeverif'),
        get_string('maxsimultaneos_desc', 'local_impefeverif'),
        2,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_impefeverif/liberarconbaja',
        get_string('liberarconbaja', 'local_impefeverif'),
        get_string('liberarconbaja_desc', 'local_impefeverif'),
        0
    ));

    $settings->add(new admin_setting_configtext(
        'local_impefeverif/caducidadencurso',
        get_string('caducidadencurso', 'local_impefeverif'),
        get_string('caducidadencurso_desc', 'local_impefeverif'),
        0,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_impefeverif/horasespera',
        get_string('horasespera', 'local_impefeverif'),
        get_string('horasespera_desc', 'local_impefeverif'),
        24,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_impefeverif/horainicio',
        get_string('horainicio', 'local_impefeverif'),
        get_string('horainicio_desc', 'local_impefeverif'),
        8,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_impefeverif/horafin',
        get_string('horafin', 'local_impefeverif'),
        get_string('horafin_desc', 'local_impefeverif'),
        15,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_impefeverif/diassemana',
        get_string('diassemana', 'local_impefeverif'),
        get_string('diassemana_desc', 'local_impefeverif'),
        '1,2,3,4,5',
        PARAM_SEQUENCE
    ));

    $settings->add(new admin_setting_configtextarea(
        'local_impefeverif/festivos',
        get_string('festivos', 'local_impefeverif'),
        get_string('festivos_desc', 'local_impefeverif'),
        '',
        PARAM_RAW
    ));

    $settings->add(new admin_setting_configtext(
        'local_impefeverif/zonahoraria',
        get_string('zonahoraria', 'local_impefeverif'),
        get_string('zonahoraria_desc', 'local_impefeverif'),
        'Europe/Madrid',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'local_impefeverif/notaapto',
        get_string('notaapto', 'local_impefeverif'),
        get_string('notaapto_desc', 'local_impefeverif'),
        2,
        PARAM_FLOAT
    ));

    $settings->add(new admin_setting_configtext(
        'local_impefeverif/graderid',
        get_string('graderid', 'local_impefeverif'),
        get_string('graderid_desc', 'local_impefeverif'),
        0,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_impefeverif/extensiones',
        get_string('extensiones', 'local_impefeverif'),
        get_string('extensiones_desc', 'local_impefeverif'),
        'pdf,jpg,jpeg,png',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'local_impefeverif/diasretencion',
        get_string('diasretencion', 'local_impefeverif'),
        get_string('diasretencion_desc', 'local_impefeverif'),
        30,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_impefeverif/purgaactiva',
        get_string('purgaactiva', 'local_impefeverif'),
        get_string('purgaactiva_desc', 'local_impefeverif'),
        0
    ));
}
