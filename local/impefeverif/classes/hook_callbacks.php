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

use core\hook\output\before_standard_footer_html_generation;
use html_writer;
use moodle_url;

/**
 * Output hooks for IMPEFE access customisations.
 *
 * @package    local_impefeverif
 * @copyright  2026 CGD E-Learning Center, S.L.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Adds IMPEFE legal links to the standard footer.
     *
     * @param before_standard_footer_html_generation $hook
     * @return void
     */
    public static function before_standard_footer_html_generation(before_standard_footer_html_generation $hook): void {
        $hook->add_html(
            html_writer::div(
                html_writer::link('https://www.impefe.es/aviso-legal/', 'Aviso legal', [
                    'target' => '_blank',
                    'rel' => 'noopener',
                ])
                . html_writer::span(' | ', 'mx-1')
                . html_writer::link(
                    new moodle_url('/admin/tool/policy/view.php', ['policyid' => 1]),
                    'Condiciones de acceso',
                    ['target' => '_blank', 'rel' => 'noopener']
                ),
                'impefe-legalfooter'
            )
        );
    }
}
