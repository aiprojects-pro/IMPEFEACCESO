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
 * Reapplies IMPEFE access setup stored in Moodle database.
 *
 * @package    local_impefeverif
 * @copyright  2026 CGD E-Learning Center, S.L.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/course/lib.php');

global $DB;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
    ],
    [
        'h' => 'help',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error("Opciones no reconocidas:\n  {$unrecognized}");
}

if ($options['help']) {
    cli_writeln('Reaplica la configuración IMPEFE de acceso: política, portada, escala y tareas.');
    cli_writeln('');
    cli_writeln('Uso: php local/impefeverif/cli/apply_access_setup.php');
    exit(0);
}

$transaction = $DB->start_delegated_transaction();

set_config('sitepolicyhandler', 'local_impefeverif');
cli_writeln('sitepolicyhandler = local_impefeverif');

$supportemail = get_config('core', 'supportemail') ?: 'soporte@cgdformacion.com';

$policycontent = <<<'HTML'
<p>Para acceder a los cursos del aula virtual, la persona debe acreditar una de las siguientes situaciones:</p>
<ul>
  <li>Empadronamiento en el municipio de Ciudad Real, mediante certificado o volante de empadronamiento.</li>
  <li>Trabajo en una empresa del municipio, mediante certificado de empresa o informe de vida laboral.</li>
  <li>Estudiante mayor de 16 años de un centro educativo ubicado en Ciudad Real, mediante matrícula o certificado del centro.</li>
</ul>

<h4>Declaración responsable</h4>
<p>La persona declara que los datos aportados son ciertos y que la documentación presentada corresponde a la vía de acreditación seleccionada. La inexactitud, falsedad u omisión de carácter esencial podrá determinar la imposibilidad de continuar usando el servicio, sin perjuicio de las responsabilidades que procedan.</p>

<h4>Verificación de la documentación</h4>
<p>La documentación presentada será revisada para comprobar la vía de acceso seleccionada.</p>

<h4>Límites de uso</h4>
<p>El acceso a la plataforma está sujeto a un máximo de cinco acciones formativas por persona en una ventana de doce meses contada desde la primera matrícula. No se podrán realizar más de dos acciones formativas simultáneamente. Los cursos no finalizados dejarán de ocupar plaza simultánea transcurridos noventa días desde la matrícula.</p>

<h4>Conservación de documentos</h4>
<p>La documentación acreditativa se conserva durante un mes contado desde la fecha de validación. Transcurrido ese plazo, finalizará su conservación. Se conservará un registro técnico de la comprobación: persona acreditada, vía, modo de validación, fechas de aportación y validación, extensión, tamaño y huella criptográfica del documento.</p>
HTML;

$policy = $DB->get_record('tool_policy_versions', ['id' => 1]);
if ($policy) {
    $policy->name = 'Condiciones de acceso';
    $policy->content = $policycontent;
    $policy->contentformat = FORMAT_HTML;
    $policy->timemodified = time();
    $DB->update_record('tool_policy_versions', $policy);
    cli_writeln('Política id 1 actualizada.');
} else {
    cli_writeln('[aviso] No existe tool_policy_versions.id=1; no se ha creado política nueva.');
}

$sectionhtml = <<<HTML
<div class="impefe-access-intro">
  <h3>Cómo acceder a los cursos</h3>
  <p>El acceso a los cursos está reservado a las personas empadronadas, estudiantes o trabajadoras en Ciudad Real. Para obtenerlo deberá acreditar su situación mediante el siguiente procedimiento.</p>

  <h4>Formas de acreditación</h4>
  <p>Acredite su situación según su caso:</p>
  <ul>
    <li><strong>Empadronado/a:</strong> declaración responsable de estar empadronado/a en Ciudad Real (no hay que aportar ningún documento).</li>
    <li><strong>Estudiante:</strong> matrícula, carné o certificado de un centro educativo de Ciudad Real.</li>
    <li><strong>Trabajador/a:</strong> contrato, nómina reciente o certificado de empresa que acredite el centro de trabajo en Ciudad Real.</li>
  </ul>

  <h4>Procedimiento</h4>
  <h5>Para Empadronados/as</h5>
  <ol>
    <li><strong>Rellene la declaración responsable.</strong> Acceda a la actividad "Declaración responsable de empadronamiento" e introduzca sus datos personales.</li>
    <li><strong>Firma electrónica.</strong> Firme electrónicamente la declaración responsable confirmando que está empadronado en Ciudad Real.</li>
    <li><strong>Revisión y validación.</strong> El personal responsable revisará la declaración de forma manual. El plazo de resolución es de hasta 3 días hábiles. Este trámite no constituye una calificación ni una evaluación.</li>
    <li><strong>Comunicación y acceso.</strong> Una vez validada la declaración, recibirá un correo electrónico de confirmación, momento a partir del cual los cursos quedarán disponibles en su cuenta.</li>
  </ol>

  <h5>Para Estudiantes y Trabajadores/as</h5>
  <ol>
    <li><strong>Aporte su documentación.</strong> Acceda a la actividad "Subir documentación" y suba el documento correspondiente (PDF, JPG o PNG).</li>
    <li><strong>Revisión y validación.</strong> El personal responsable revisará la documentación de forma manual. El plazo de resolución es de hasta 3 días hábiles. Este trámite no constituye una calificación ni una evaluación.</li>
    <li><strong>Comunicación y acceso.</strong> Una vez validada la documentación, recibirá un correo electrónico de confirmación con el enlace de acceso, momento a partir del cual los cursos quedarán disponibles en su cuenta.</li>
  </ol>

  <p><strong>¿Su declaración o documentación no resulta válida?</strong> Se le comunicará el motivo por correo electrónico y podrá presentarla de nuevo desde la misma actividad, sin trámites adicionales.</p>
  <p><strong>¿Necesita ayuda?</strong> Puede escribirnos a través de nuestro <a href="https://impefe.campusenlinea.es/mod/feedback/view.php?id=3399">formulario de contacto</a>.</p>
</div>
HTML;

$section = $DB->get_record('course_sections', ['id' => 3403]);
if (!$section) {
    $section = $DB->get_record('course_sections', ['course' => SITEID, 'name' => 'Solicitud de Acceso']);
}
if ($section) {
    $section->name = 'Solicitud de Acceso';
    $section->summary = $sectionhtml;
    $section->summaryformat = FORMAT_HTML;
    $DB->update_record('course_sections', $section);
    rebuild_course_cache((int) $section->course, true);
    cli_writeln("Sección Solicitud de Acceso actualizada: {$section->id}.");
} else {
    cli_writeln('[aviso] No se encontró la sección Solicitud de Acceso.');
}

$scale = $DB->get_record('scale', ['id' => 4]);
if (!$scale) {
    $scale = (object) [
        'courseid' => 0,
        'userid' => get_admin()->id,
        'name' => 'No válido / Válido',
        'scale' => 'No válido,Válido',
        'description' => '',
        'descriptionformat' => FORMAT_HTML,
        'timemodified' => time(),
    ];
    $scale->id = $DB->insert_record('scale', $scale);
    cli_writeln("Escala creada: {$scale->id}.");
} else {
    $scale->name = 'No válido / Válido';
    $scale->scale = 'No válido,Válido';
    $scale->timemodified = time();
    $DB->update_record('scale', $scale);
    cli_writeln("Escala actualizada: {$scale->id}.");
}

$cmids = [11469, 11495, 11496];
foreach ($cmids as $cmid) {
    try {
        $cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
    } catch (moodle_exception $e) {
        cli_writeln("[aviso] No existe la tarea cmid={$cmid}; se omite.");
        continue;
    }

    $assign = $DB->get_record('assign', ['id' => $cm->instance], '*', MUST_EXIST);
    $assign->grade = -((int) $scale->id);
    $assign->timemodified = time();
    $DB->update_record('assign', $assign);

    $gradeitem = $DB->get_record('grade_items', [
        'courseid' => $cm->course,
        'itemmodule' => 'assign',
        'iteminstance' => $assign->id,
    ]);

    if ($gradeitem) {
        $gradeitem->scaleid = (int) $scale->id;
        $gradeitem->grademin = 1;
        $gradeitem->grademax = 2;
        $gradeitem->needsupdate = 0;
        $DB->update_record('grade_items', $gradeitem);
        $DB->set_field('grade_grades', 'rawscaleid', (int) $scale->id, ['itemid' => $gradeitem->id]);
    }

    cli_writeln("Tarea {$cmid} usa la escala {$scale->id}.");
}

$transaction->allow_commit();

purge_all_caches();
cli_writeln('Configuración IMPEFE aplicada y cachés purgadas.');
