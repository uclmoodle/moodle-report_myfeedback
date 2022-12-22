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
 * The main file for the personal tutor tab
 *
 * @package  report_myfeedback
 * @copyright 2022 UCL
 * @author    Delvon Forrester <delvon@esparanza.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$programme = '';
if ($mytutorid) {
    $mytutorobj = $currentdb->get_record('user', array('id' => $mytutorid));
    profile_load_data($mytutorobj);
    echo "<p>" . get_string('overview_text_ptutor_tab', 'report_myfeedback') . "</p>";
    echo '<div class="userprofilebox clearfix">';
    echo '<div class="profilepicture">';
    echo $OUTPUT->user_picture($mytutorobj, array('size' => 100));
    echo '</div>';

    echo '<div class="descriptionbox"><div class="description">';

    echo '<h2 style="margin:-8px 0;">' . $mytutorobj->firstname . " " . $mytutorobj->lastname . '</h2>';

    echo $mytutorobj->department . "  " . $programme .
    '<br>' . get_string('email_address', 'report_myfeedback') .
    ' <a href="mailto:' . $mytutorobj->email . '">'. $mytutorobj->email .' </a><p> </p>';

    echo '</div></div>';
    if ($USER->id == $userid) {
        echo '<div class="personaltutoremail">
       <a href="mailto:' . $mytutorobj->email . '?Subject='.get_string("email_tutor_subject", "report_myfeedback").'">'
        . get_string("email_tutor", "report_myfeedback") . '</a></div>';
    }
    echo '</div>';
} else {
    echo get_string('notutor', 'report_myfeedback');
}
