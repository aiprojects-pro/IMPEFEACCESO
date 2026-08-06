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
 * Language strings.
 *
 * @package    local_impefeverif
 * @copyright  2026 CGD E-Learning Center, S.L.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Access requirement verification';
$string['privacy:metadata'] = 'For each verified accreditation the plugin keeps the user, the declared route, the submission and validation timestamps, and a cryptographic digest of the document. The document itself is not kept beyond the retention period.';

$string['task_autovalidar'] = 'Review student and worker accreditations';
$string['task_purgar'] = 'Review expired accreditation documents';
$string['task_avisaracceso'] = 'Notify the user that their account is verified';

$string['cohorteid'] = 'Verified users cohort';
$string['cohorteid_desc'] = 'Id of the "Verified" cohort. Joining this cohort triggers the access notification, for both student/worker and human-reviewed routes.';

$string['urlcatalogo'] = 'Course catalogue URL';
$string['urlcatalogo_desc'] = 'Address offered to the user in the access notification. Leave empty to use the site course index.';

$string['activo'] = 'Enable accreditation review';
$string['activo_desc'] = 'While disabled the scheduled task runs but grades nothing. Keep it off until the whole flow has been tested on staging.';

$string['cmidsauto'] = 'Student and worker review assignments';
$string['cmidsauto_desc'] = 'Comma-separated course module ids of the student and worker assignments.';

$string['cmidsmanual'] = 'Human-reviewed assignments';
$string['cmidsmanual_desc'] = 'Comma-separated course module ids of the assignments reviewed by a person. Not auto-validated, but still purged.';

$string['horasespera'] = 'Hours to wait before reviewing';
$string['horasespera_desc'] = 'Hours that must elapse after submission before the review is recorded.';

$string['horainicio'] = 'Working hours start';
$string['horainicio_desc'] = 'Hour (24h format) from which reviews may be recorded.';

$string['horafin'] = 'Working hours end';
$string['horafin_desc'] = 'Hour (24h format) after which reviews stop being recorded.';

$string['diassemana'] = 'Working days';
$string['diassemana_desc'] = 'Comma-separated weekdays on which reviews may be recorded. 1 is Monday, 7 is Sunday.';

$string['festivos'] = 'Public holidays';
$string['festivos_desc'] = 'One date per line in YYYY-MM-DD format.';

$string['zonahoraria'] = 'Reference time zone';
$string['zonahoraria_desc'] = 'Time zone used to interpret working hours, regardless of the server one.';

$string['notaapto'] = 'Pass grade value';
$string['notaapto_desc'] = 'With a scale, the position of the pass item. With points, the grade to record.';

$string['graderid'] = 'Grading account';
$string['graderid_desc'] = 'User id of the account recording grades. Use a dedicated account for student and worker reviews.';

$string['extensiones'] = 'Accepted extensions';
$string['extensiones_desc'] = 'Comma-separated list of accepted file extensions.';

$string['diasretencion'] = 'Retention days';
$string['diasretencion_desc'] = 'Days the document is kept after validation. Afterwards only the verification record remains.';

$string['purgaactiva'] = 'Enable retention period management';
$string['purgaactiva_desc'] = 'While disabled the scheduled task runs without changing documents.';

$string['messageprovider:validacion'] = 'Access requirement verification issues';
$string['messageprovider:acceso'] = 'Account verified and catalogue access granted';

$string['avisoacceso_asunto'] = 'Your account is verified: you can now enrol in courses';
$string['avisoacceso_cuerpo'] = 'Hello {$a->nombre}:

We have checked the documentation you provided and your account on {$a->sitio} is now verified. You can browse the catalogue and enrol yourself in the courses you are interested in.

Catalogue: {$a->url}

Please note two platform conditions:
- You may not take two training actions at the same time.
- The maximum is five training actions per person per year.

If you detect any error in your data, reply to this message or contact us before enrolling.';

$string['avisoincompleto_asunto'] = 'Documentation missing for your access verification';
$string['avisoincompleto_cuerpo'] = 'We could not verify your accreditation in "{$a}" because no file was found or the format is not accepted. Please go back to the activity and provide the document as PDF or image.';

$string['task_recalcularcupos'] = 'Recalculate training action quota';

$string['cohortecupoid'] = 'Cohort of users with quota available';
$string['cohortecupoid_desc'] = 'Id of the "quota available" cohort. Must differ from the verified one. Restrict each catalogue course self-enrolment to this cohort.';

$string['categoriacatalogo'] = 'Catalogue category';
$string['categoriacatalogo_desc'] = 'Comma-separated ids of the categories holding the courses subject to limits, including subcategories. Enrolments outside them do not consume quota.';

$string['maxanuales'] = 'Maximum training actions per window';
$string['maxanuales_desc'] = 'Maximum enrolments within the 12-month window, counted from the user first enrolment and renewed on its anniversary.';

$string['maxsimultaneos'] = 'Maximum simultaneous courses';
$string['maxsimultaneos_desc'] = 'Maximum number of courses a user may have in progress at the same time.';

$string['liberarconbaja'] = 'Unenrolment frees a simultaneous slot';
$string['liberarconbaja_desc'] = 'When off, only course completion frees a simultaneous slot. Turning it on lets unenrolment free the slot, as an escape valve for abandoned courses. It never returns the annual slot.';

$string['caducidadencurso'] = 'Expiry of unfinished courses (days)';
$string['caducidadencurso_desc'] = 'Zero disables expiry. Above zero, a course enrolled longer ago than that stops occupying a simultaneous slot even if unfinished.';

$string['avisocupoanual_asunto'] = 'You have used your {$a->maxanuales} training actions';
$string['avisocupoanual_cuerpo'] = 'You have reached the maximum of {$a->maxanuales} training actions for your current period, so you cannot enrol in new courses for now.

Your quota renews on {$a->renovacion}.

The courses you are already enrolled in remain available as usual.';

$string['avisosimultaneos_asunto'] = 'You already have {$a->maxsimultaneos} courses in progress';
$string['avisosimultaneos_cuerpo'] = 'You currently have {$a->encurso} unfinished course(s), and the maximum allowed at once is {$a->maxsimultaneos}.

To enrol in a new course you need to finish one of the ones in progress first. This does not affect your annual quota.';
