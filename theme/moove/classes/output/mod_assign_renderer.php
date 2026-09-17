<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Assignment output customisations for IMPEFE.
 *
 * @package theme_moove
 */

namespace theme_moove\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Shows the access-review notice only after an accreditation is submitted.
 */
class mod_assign_renderer extends \mod_assign\output\renderer {
    /**
     * Render the student submission status.
     *
     * @param \mod_assign\output\assign_submission_status $status Submission status data.
     * @return string
     */
    public function render_assign_submission_status(\mod_assign\output\assign_submission_status $status) {
        $output = parent::render_assign_submission_status($status);

        $accessactivities = [11469, 11495, 11496];
        $submitted = $status->submission
            && $status->submission->status === ASSIGN_SUBMISSION_STATUS_SUBMITTED;

        if ($status->view !== \mod_assign\output\assign_submission_status::STUDENT_VIEW
                || !in_array((int) $status->coursemoduleid, $accessactivities, true)
                || !$submitted) {
            return $output;
        }

        $notice = '<strong>Solicitud recibida.</strong> Nuestro equipo revisará la documentación aportada. '
            . 'Hasta que la solicitud sea validada no podrá acceder a los cursos. '
            . 'La validación puede tardar aproximadamente entre 24 y 48 horas laborables. '
            . 'Le avisaremos por correo electrónico cuando el acceso esté habilitado.';

        return $output . \html_writer::div($notice, 'alert alert-info impefe-verification-notice', ['role' => 'status']);
    }
}
