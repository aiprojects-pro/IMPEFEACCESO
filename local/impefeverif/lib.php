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
 * Plugin callbacks.
 *
 * @package    local_impefeverif
 * @copyright  2026 CGD E-Learning Center, S.L.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** Form element name for the birthdate profile field. */
define('LOCAL_IMPEFEVERIF_BIRTHDATE_FIELD', 'profile_field_fecha_nacimiento');

/** Minimum age, in whole years, required to register. */
define('LOCAL_IMPEFEVERIF_MIN_AGE', 16);

/** Upper sanity bound, in whole years, for a plausible birthdate. */
define('LOCAL_IMPEFEVERIF_MAX_AGE', 120);

/**
 * Adjusts the signup form presentation.
 *
 * @param MoodleQuickForm $mform Signup form.
 * @return void
 */
function local_impefeverif_extend_signup_form(MoodleQuickForm $mform): void {
    if ($mform->elementExists('category_1')) {
        $mform->removeElement('category_1', false);
    }

    $fieldname = LOCAL_IMPEFEVERIF_BIRTHDATE_FIELD;

    if (!$mform->elementExists($fieldname)) {
        return;
    }

    $mform->setType($fieldname, PARAM_TEXT);

    $element = $mform->getElement($fieldname);
    $element->updateAttributes([
        'placeholder'  => 'DD/MM/AAAA',
        'maxlength'    => 10,
        'size'         => 12,
        'inputmode'    => 'numeric',
        'autocomplete' => 'bday',
    ]);
}

/**
 * Parses a user-supplied birthdate string into a date at midnight.
 *
 * Accepts DD/MM/YYYY with /, - or . as separator, applied consistently.
 * Day and month may be given with or without a leading zero.
 *
 * @param string $value Raw, untrusted form input.
 * @return DateTimeImmutable|null Midnight on the parsed date, or null if invalid.
 */
function local_impefeverif_parse_birthdate(string $value): ?DateTimeImmutable {
    $value = trim($value);

    // Bail out before the regex on anything of implausible length ("15-03-1990" is 10).
    if ($value === '' || strlen($value) > 10) {
        return null;
    }

    // Anchored, ASCII only, no unbounded quantifiers. The backreference on the
    // separator rejects mixed forms such as "15/03-1990".
    if (!preg_match('#^(\d{1,2})([/.-])(\d{1,2})\2(\d{4})$#', $value, $matches)) {
        return null;
    }

    $normalised = sprintf('%02d/%02d/%04d', (int) $matches[1], (int) $matches[3], (int) $matches[4]);
    $timezone = new DateTimeZone('Europe/Madrid');

    // The leading "!" resets all unspecified fields, giving midnight.
    $born = DateTimeImmutable::createFromFormat('!d/m/Y', $normalised, $timezone);

    if ($born === false) {
        return null;
    }

    // getLastErrors() returns false when clean as of PHP 8.2, an array before.
    // Warnings catch rolled-over dates, e.g. 31/02/1990 becoming 03/03/1990.
    $errors = DateTimeImmutable::getLastErrors();
    if ($errors !== false && (($errors['error_count'] ?? 0) > 0 || ($errors['warning_count'] ?? 0) > 0)) {
        return null;
    }

    return $born;
}

/**
 * Validate the birthdate submitted on the signup form.
 *
 * @param array $data Submitted form data.
 * @return array Error messages keyed by element name; empty array when valid.
 */
function local_impefeverif_validate_extend_signup_form(array $data): array {
    $fieldname = LOCAL_IMPEFEVERIF_BIRTHDATE_FIELD;

    // The element may not be on this form at all.
    if (!array_key_exists($fieldname, $data)) {
        return [];
    }

    $raw = $data[$fieldname];

    if (!is_string($raw) || trim($raw) === '') {
        return [$fieldname => 'Debes indicar tu fecha de nacimiento.'];
    }

    $born = local_impefeverif_parse_birthdate($raw);

    if ($born === null) {
        return [$fieldname => 'La fecha de nacimiento no es válida. Utiliza el formato DD/MM/AAAA, por ejemplo 15/03/1990.'];
    }

    $today = (new DateTimeImmutable('now', new DateTimeZone('Europe/Madrid')))->setTime(0, 0, 0);

    if ($born > $today) {
        return [$fieldname => 'La fecha de nacimiento no puede ser posterior a hoy.'];
    }

    // diff()->y handles leap years and 29 February birthdays correctly.
    $age = (int) $born->diff($today)->y;

    if ($age > LOCAL_IMPEFEVERIF_MAX_AGE) {
        return [$fieldname => 'La fecha de nacimiento no es válida. Utiliza el formato DD/MM/AAAA, por ejemplo 15/03/1990.'];
    }

    if ($age < LOCAL_IMPEFEVERIF_MIN_AGE) {
        return [$fieldname => 'Debes tener ' . LOCAL_IMPEFEVERIF_MIN_AGE . ' años o más para acceder a la formación.'];
    }

    return [];
}
