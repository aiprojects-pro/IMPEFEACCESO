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

namespace local_impefeverif\privacy\local\sitepolicy;

use html_writer;
use moodle_url;

/**
 * Site policy handler used to show access conditions on the signup form.
 *
 * @package    local_impefeverif
 * @copyright  2026 CGD E-Learning Center, S.L.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class handler extends \core_privacy\local\sitepolicy\handler {
    /**
     * No separate pre-signup redirect is required; the signup form has the checkbox.
     *
     * @param bool $forguests
     * @return moodle_url|null
     */
    public static function get_redirect_url($forguests = false) {
        return null;
    }

    /**
     * Returns the public access conditions document URL.
     *
     * @param bool $forguests
     * @return moodle_url|null
     */
    public static function get_embed_url($forguests = false) {
        return new moodle_url('/admin/tool/policy/view.php', ['policyid' => 1]);
    }

    /**
     * Adds the access conditions consent checkbox to the signup form.
     *
     * @param \MoodleQuickForm $mform
     * @return void
     */
    public static function signup_form($mform) {
        $policylink = html_writer::link(static::get_embed_url(), 'Condiciones de acceso', [
            'target' => '_blank',
            'rel' => 'noopener',
        ]);

        $mform->addElement(
            'checkbox',
            'policyagreed',
            '',
            'Doy mi consentimiento a las ' . $policylink . '.'
        );
        $mform->setType('policyagreed', PARAM_INT);
        $mform->addRule('policyagreed', get_string('required'), 'required', null, 'client');
    }
}
