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
 * A drawer based layout for the Eskada theme.
 *
 * @package    theme_moove
 * @copyright  2025 Willian Mano - willianmanoaraujo@gmail.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/behat/lib.php');
require_once($CFG->dirroot . '/course/lib.php');

// Add block button in editing mode.
$addblockbutton = $OUTPUT->addblockbutton();

if (isloggedin()) {
    $courseindexopen = (get_user_preferences('drawer-open-index', true) == true);
    $blockdraweropen = (get_user_preferences('drawer-open-block') == true);
} else {
    $courseindexopen = false;
    $blockdraweropen = false;
}

if (defined('BEHAT_SITE_RUNNING') && get_user_preferences('behat_keep_drawer_closed') != 1) {
    $blockdraweropen = true;
}

$extraclasses = ['uses-drawers'];
if ($courseindexopen) {
    $extraclasses[] = 'drawer-open-index';
}

$blockshtml = $OUTPUT->blocks('side-pre');
$hasblocks = (strpos($blockshtml, 'data-block=') !== false || !empty($addblockbutton));
if (!$hasblocks) {
    $blockdraweropen = false;
}
$courseindex = core_course_drawer();
if (!$courseindex) {
    $courseindexopen = false;
}

$forceblockdraweropen = $OUTPUT->firstview_fakeblocks();

$secondarynavigation = false;
$overflow = '';
if ($PAGE->has_secondary_navigation()) {
    $secondary = $PAGE->secondarynav;

    if ($secondary->get_children_key_list()) {
        $tablistnav = $PAGE->has_tablist_secondary_navigation();
        $moremenu = new \core\navigation\output\more_menu($PAGE->secondarynav, 'nav-tabs', true, $tablistnav);
        $secondarynavigation = $moremenu->export_for_template($OUTPUT);
        $extraclasses[] = 'has-secondarynavigation';
    }

    $overflowdata = $PAGE->secondarynav->get_overflow_menu_data();
    if (!is_null($overflowdata)) {
        $overflow = $overflowdata->export_for_template($OUTPUT);
    }
}

$primary = new core\navigation\output\primary($PAGE);
$renderer = $PAGE->get_renderer('core');
$primarymenu = $primary->export_for_template($renderer);
$buildregionmainsettings = !$PAGE->include_region_main_settings_in_header_actions() && !$PAGE->has_secondary_navigation();
// If the settings menu will be included in the header then don't add it here.
$regionmainsettingsmenu = $buildregionmainsettings ? $OUTPUT->region_main_settings_menu() : false;

$header = $PAGE->activityheader;
$headercontent = $header->export_for_template($renderer);

$bodyattributes = $OUTPUT->body_attributes($extraclasses);
$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => \core\context\course::instance(SITEID), "escape" => false]),
    'output' => $OUTPUT,
    'sidepreblocks' => $blockshtml,
    'hasblocks' => $hasblocks,
    'bodyattributes' => $bodyattributes,
    'courseindexopen' => $courseindexopen,
    'blockdraweropen' => $blockdraweropen,
    'courseindex' => $courseindex,
    'primarymoremenu' => $primarymenu['moremenu'],
    'secondarymoremenu' => $secondarynavigation ?: false,
    'mobileprimarynav' => $primarymenu['mobileprimarynav'],
    'usermenu' => $primarymenu['user'],
    'langmenu' => $primarymenu['lang'],
    'forceblockdraweropen' => $forceblockdraweropen,
    'regionmainsettingsmenu' => $regionmainsettingsmenu,
    'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
    'overflow' => $overflow,
    'headercontent' => $headercontent,
    'addblockbutton' => $addblockbutton,
];

$themesettings = new \theme_moove\util\settings();

$templatecontext = array_merge($templatecontext, $themesettings->footer());

global $DB;

$categoryicons = [
    46 => '01_actividad-fisica-y-deportiva.png',
    47 => '02_administracion-y-gestion.png',
    48 => '03_agraria.png',
    49 => '04_artes-graficas.png',
    50 => '05_comercio-y-marketing.png',
    51 => '06_edificacion-y-obra-civil.png',
    52 => '07_fabricacion-mecanica.png',
    53 => '08_finanzas-y-criptomonedas.png',
    54 => '09_habilidades-personales.png',
    66 => '10_hosteleria-y-turismo.png',
    55 => '11_idiomas.png',
    56 => '12_igualdad.png',
    57 => '13_industria-alimentaria.png',
    58 => '14_informatica-y-comunicacion.png',
    59 => '15_instalacion-y-mantenimiento.png',
    60 => '16_inteligencia-artificial.png',
    61 => '17_oficios.png',
    62 => '18_prevencion-riesgos-laborales.png',
    63 => '19_sanidad.png',
    64 => '20_seguridad-y-medio-ambiente.png',
    65 => '21_servicios-socioculturales.png',
];

$categories = [];
list($insql, $inparams) = $DB->get_in_or_equal(array_keys($categoryicons), SQL_PARAMS_NAMED);
$sql = "SELECT id, name
          FROM {course_categories}
         WHERE visible = 1 AND id {$insql}
      ORDER BY sortorder";
$coursecategories = $DB->get_records_sql($sql, $inparams);

foreach ($coursecategories as $category) {
    $categories[] = [
        'name' => format_string($category->name, true),
        'url' => (new moodle_url('/course/index.php', ['categoryid' => $category->id]))->out(false),
        'image' => (new moodle_url('/theme/moove/pix/categoryicons/' . $categoryicons[$category->id]))->out(false),
    ];
}

$templatecontext['categorycardsenabled'] = !empty($categories);
$templatecontext['categorycards'] = $categories;

$template = 'theme_moove/drawers';
if (!isloggedin()) {
    $templatecontext = array_merge($templatecontext, $themesettings->frontpage());
    $template = 'theme_moove/frontpage';
}

echo $OUTPUT->render_from_template($template, $templatecontext);
