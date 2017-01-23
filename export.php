<?php

/*
 * A referenced file to export data from the tabs and dashboards to excel
 * 
 * @package  report_myfeedback
 * @author    Delvon Forrester <delvon@esparanza.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @credits   Original PHP code by Chirp Internet: www.chirp.com.au
 */

require('../../config.php');
$data = $_SESSION["exp_sess"];
$userid = $_SESSION['myfeedback_userid'];
$username = $_SESSION['user_name'];
$tutor = $_SESSION['tutor'];

$headingtext = get_string('reportfor', 'report_myfeedback').$username."\r\n";
$event = \report_myfeedback\event\myfeedbackreport_download::create(array('context' => context_user::instance($userid), 'relateduserid' => $userid));
$filename = get_string('filename', 'report_myfeedback') . date('YmdHis') . ".csv";// file name for download
$excelheader = get_string('exportheader', 'report_myfeedback') . " \r\n";
if ($tutor == 'p') {//Personal tutor dashboard
    $headingtext = get_string('p_tutor_report', 'report_myfeedback') . " \r\n";
    $event = \report_myfeedback\event\myfeedbackreport_downloadptutor::create(array('context' => context_user::instance($USER->id), 'relateduserid' => $userid));
    $filename = get_string('p_tutor_filename', 'report_myfeedback') . date('YmdHis') . ".csv";
    $excelheader = get_string('p_tutor_exportheader', 'report_myfeedback') . " \r\n";
}
if ($tutor == 'm') {//Module tutor dashboard
    $headingtext = get_string('mod_tutor_report', 'report_myfeedback') . " \r\n";
    $event = \report_myfeedback\event\myfeedbackreport_downloadmtutor::create(array('context' => context_user::instance($USER->id), 'relateduserid' => $userid));
    $filename = get_string('mod_tutor_filename', 'report_myfeedback') . date('YmdHis') . ".csv";
    $excelheader = get_string('mod_tutor_exportheader', 'report_myfeedback') . " \r\n";
}
if ($tutor == 'd') {//Departmental admin dashboard
    $headingtext = get_string('dept_admin_report', 'report_myfeedback') . " \r\n";
    $event = \report_myfeedback\event\myfeedbackreport_downloaddeptadmin::create(array('context' => context_user::instance($USER->id), 'relateduserid' => $userid));
    $filename = get_string('dept_admin_filename', 'report_myfeedback') . date('YmdHis') . ".csv";
    $excelheader = get_string('dept_admin_exportheader', 'report_myfeedback') . " \r\n";
}

// Trigger a table download
$event->trigger();

header("Content-Disposition: attachment; filename=\"$filename\"");
header("Content-Type: text/csv; charset=UTF-8");
$exc = '';

foreach ($data as $row) {
    $row = str_replace(",", ";", $row);
    $exc.= "\r\n" . implode(",", $row);
}
echo $headingtext;
echo $excelheader;
echo strip_tags($exc);
exit;