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
 * Observadores de eventos.
 *
 * @package    local_impefeverif
 * @copyright  2026 CGD E-Learning Center, S.L.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\event\cohort_member_added',
        'callback'  => '\local_impefeverif\observer::cohort_member_added',
        'internal'  => false,
    ],
    [
        'eventname' => '\core\event\user_enrolment_created',
        'callback'  => '\local_impefeverif\observer::user_enrolment_created',
        'internal'  => false,
    ],
    [
        'eventname' => '\core\event\user_enrolment_deleted',
        'callback'  => '\local_impefeverif\observer::user_enrolment_deleted',
        'internal'  => false,
    ],
    [
        'eventname' => '\core\event\course_completed',
        'callback'  => '\local_impefeverif\observer::course_completed',
        'internal'  => false,
    ],
    [
        'eventname' => '\mod_assign\event\submission_graded',
        'callback'  => '\local_impefeverif\observer::submission_graded',
        'internal'  => false,
    ],
];
