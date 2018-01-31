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

//Because we are using sessions to store the information, if a user opens multiple tabs and tries to export, 
//they will just get the data from the last tab they opened.
$data = $_SESSION["exp_sess"];
$userid = $_SESSION['myfeedback_userid'];
$username = $_SESSION['user_name'];
$tutor = $_SESSION['tutor'];
//$tutor = $_SESSION['reportname'];

//arrays to clean up the heading tags passed to the usage reports
$strfind = array(" ", ":", "\r", "\n");
$strreplace = array("_", "", "", "");
	
$headingtext = get_string('reportfor', 'report_myfeedback').$username."\r\n";
$event = \report_myfeedback\event\myfeedbackreport_download::create(array('context' => context_user::instance($userid), 'relateduserid' => $userid));
$filename = get_string('filename', 'report_myfeedback') . date('YmdHis') . ".csv";// file name for download
$excelheader = get_string('exportheader', 'report_myfeedback') . " \r\n";
if ($tutor == 'p') {//Personal tutor dashboard
    $headingtext = get_string('p_tutor_report', 'report_myfeedback') . " \r\n";
    $event = \report_myfeedback\event\myfeedbackreport_downloadptutor::create(array('context' => context_user::instance($USER->id), 'relateduserid' => $userid));
    $filename = get_string('p_tutor_filename', 'report_myfeedback') . date('YmdHis') . ".csv";
    $excelheader = get_string('p_tutor_exportheader', 'report_myfeedback') . " \r\n";
}elseif ($tutor == 'm') {//Module tutor dashboard
    $headingtext = get_string('mod_tutor_report', 'report_myfeedback') . " \r\n";
    $event = \report_myfeedback\event\myfeedbackreport_downloadmtutor::create(array('context' => context_user::instance($USER->id), 'relateduserid' => $userid));
    $filename = get_string('mod_tutor_filename', 'report_myfeedback') . date('YmdHis') . ".csv";
    $excelheader = get_string('mod_tutor_exportheader', 'report_myfeedback') . " \r\n";
}elseif ($tutor == 'd') {//Departmental admin dashboard
    $headingtext = get_string('dept_admin_report', 'report_myfeedback') . " \r\n";
    $event = \report_myfeedback\event\myfeedbackreport_downloaddeptadmin::create(array('context' => context_user::instance($USER->id), 'relateduserid' => $userid));
    $filename = get_string('dept_admin_filename', 'report_myfeedback') . date('YmdHis') . ".csv";
    $excelheader = get_string('dept_admin_exportheader', 'report_myfeedback') . " \r\n";
}elseif ($tutor == 'ustud') {//Usage dashboard for students
    $headingtext = ucfirst(get_string('usage', 'report_myfeedback')) . " " . $headingtext;
	$filename = str_replace($strfind, $strreplace, $headingtext) . "_" . date('YmdHis') . ".csv";
    $excelheader = get_string('usage_student_exportheader', 'report_myfeedback') . " \r\n";
}elseif ($tutor == 'ustaff') {//Usage dashboard for students
    $headingtext = ucfirst(get_string('usage', 'report_myfeedback')) . " " . $headingtext;
    $filename = str_replace($strfind, $strreplace, $headingtext) . "_" . date('YmdHis') . ".csv";
    $excelheader = get_string('usage_staff_exportheader', 'report_myfeedback') . " \r\n";
}elseif ($tutor == 'ustudover') {//Usage dashboard for students
    $headingtext = ucfirst(get_string('usage', 'report_myfeedback')) . " " . $headingtext;
    $filename = str_replace($strfind, $strreplace, $headingtext) . "_" . date('YmdHis') . ".csv";
    $excelheader = get_string('usage_studentoverview_exportheader', 'report_myfeedback') . " \r\n";
}elseif ($tutor == 'ustaffover') {//Usage dashboard for students
    $headingtext = ucfirst(get_string('usage', 'report_myfeedback')) . " " . $headingtext;
    $filename = str_replace($strfind, $strreplace, $headingtext) . "_" . date('YmdHis') . ".csv";
    $excelheader = get_string('usage_staffoverview_exportheader', 'report_myfeedback') . " \r\n";
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