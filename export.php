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
 * A referenced file to export data from the tabs and dashboards to excel
 *
 * @package  report_myfeedback
 * @copyright 2022 UCL
 * @author    Delvon Forrester <delvon@esparanza.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *  credits   Original PHP code by Chirp Internet: www.chirp.com.au
 */

require('../../config.php');
require_login();

// Export data is loaded from $_SESSION["exp_sess"], which is set by one of:
// - report_feedback::get_staff_statistics_table()
// - report_feedback::get_student_statistics_table()
// - report_feedback::get_content()
// Access to these seems to be guarded by one or both of:
// - Capability checks on the files included as tabs.
// - Role variables (e.g. $moduletutor) in index.php, which are initialised false,
// ... enabled to true when relevant capability checks are passed, and then
// ... used to deny access to the files included as tabs.


// Because we are using sessions to store the information, if a user opens multiple tabs and tries to export,
// they will just get the data from the last tab they opened.
$data = $_SESSION["exp_sess"];
$userid = $_SESSION['myfeedback_userid'];
$username = $_SESSION['user_name'];
$tutor = $_SESSION['tutor'];

// Arrays to clean up the heading tags passed to the usage reports.
$strfind = array(" ", ":", "\r", "\n");
$strreplace = array("_", "", "", "");

$headingtext = get_string('reportfor', 'report_myfeedback').$username."\r\n";
$event = \report_myfeedback\event\myfeedbackreport_download::create(
    array('context' => context_user::instance($userid), 'relateduserid' => $userid)
);
$filename = get_string('filename', 'report_myfeedback') . date('YmdHis') . ".csv";
$excelheader = get_string('exportheader', 'report_myfeedback') . " \r\n";
if ($tutor == 'p') {
    // Personal tutor dashboard.
    $headingtext = get_string('p_tutor_report', 'report_myfeedback') . " \r\n";
    $event = \report_myfeedback\event\myfeedbackreport_downloadptutor::create(
        array('context' => context_user::instance($USER->id), 'relateduserid' => $userid)
    );
    $filename = get_string('p_tutor_filename', 'report_myfeedback') . date('YmdHis') . ".csv";
    $excelheader = get_string('p_tutor_exportheader', 'report_myfeedback') . " \r\n";
} else if ($tutor == 'm') {
    // Module tutor dashboard.
    $headingtext = get_string('mod_tutor_report', 'report_myfeedback') . " \r\n";
    $event = \report_myfeedback\event\myfeedbackreport_downloadmtutor::create(
        array('context' => context_user::instance($USER->id), 'relateduserid' => $userid)
    );
    $filename = get_string('mod_tutor_filename', 'report_myfeedback') . date('YmdHis') . ".csv";
    $excelheader = get_string('mod_tutor_exportheader', 'report_myfeedback') . " \r\n";
} else if ($tutor == 'd') {
    // Departmental admin dashboard.
    $headingtext = get_string('dept_admin_report', 'report_myfeedback') . " \r\n";
    $event = \report_myfeedback\event\myfeedbackreport_downloaddeptadmin::create(
        array('context' => context_user::instance($USER->id), 'relateduserid' => $userid)
    );
    $filename = get_string('dept_admin_filename', 'report_myfeedback') . date('YmdHis') . ".csv";
    $excelheader = get_string('dept_admin_exportheader', 'report_myfeedback') . " \r\n";
} else if ($tutor == 'ustud') {
    // Usage dashboard for students.
    $headingtext = ucfirst(get_string('usage', 'report_myfeedback')) . " " . $headingtext;
    $filename = str_replace($strfind, $strreplace, $headingtext) . "_" . date('YmdHis') . ".csv";
    $excelheader = get_string('usage_student_exportheader', 'report_myfeedback') . " \r\n";
} else if ($tutor == 'ustaff') {
    // Usage dashboard for students.
    $headingtext = ucfirst(get_string('usage', 'report_myfeedback')) . " " . $headingtext;
    $filename = str_replace($strfind, $strreplace, $headingtext) . "_" . date('YmdHis') . ".csv";
    $excelheader = get_string('usage_staff_exportheader', 'report_myfeedback') . " \r\n";
} else if ($tutor == 'ustudover') {
    // Usage dashboard for students.
    $headingtext = ucfirst(get_string('usage', 'report_myfeedback')) . " " . $headingtext;
    $filename = str_replace($strfind, $strreplace, $headingtext) . "_" . date('YmdHis') . ".csv";
    $excelheader = get_string('usage_studentoverview_exportheader', 'report_myfeedback') . " \r\n";
} else if ($tutor == 'ustaffover') {
    // Usage dashboard for students.
    $headingtext = ucfirst(get_string('usage', 'report_myfeedback')) . " " . $headingtext;
    $filename = str_replace($strfind, $strreplace, $headingtext) . "_" . date('YmdHis') . ".csv";
    $excelheader = get_string('usage_staffoverview_exportheader', 'report_myfeedback') . " \r\n";
}

// Trigger a table download.
$event->trigger();

header("Content-Disposition: attachment; filename=\"$filename\"");
header("Content-Type: text/csv; charset=UTF-8");
$exc = '';

foreach ($data as $row) {
    $row = str_replace(",", ";", $row);
    $exc .= "\r\n" . implode(",", $row);
}
echo $headingtext;
echo $excelheader;
echo strip_tags($exc);
exit;
