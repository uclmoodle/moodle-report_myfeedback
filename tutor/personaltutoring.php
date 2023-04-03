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
 * The main file for the personal tutor dashboard
 *
 * @package  report_myfeedback
 * @copyright 2022 UCL
 * @author    Delvon Forrester <delvon@esparanza.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$PAGE->requires->js_call_amd('report_myfeedback/personaltutor', 'init');

if (!$canaccessuser) {
    throw new moodle_exception('nopermissions', '', $PAGE->url->out(), get_string('viewtutorreports', 'report_myfeedback'));
}

if ($USER->lastlogin) {
    $userlastlogin = userdate($USER->lastlogin) . "&nbsp; (" . format_time(time() - $USER->lastaccess) . ")";
} else {
    $userlastlogin = get_string("never");
}
profile_load_data($USER);
$programme = '';
if (isset($USER->profile_field_programmename)) {
    $programme = $USER->profile_field_programmename;
}
echo "<p>" . get_string('overview_text_ptutor', 'report_myfeedback') . "</p>";
echo '<div class="userprofilebox clearfix" style="margin-bottom: 10px;">
            <div class="profilepicture">';
if ($userid != $USER->id && !$viewtutee) {
    echo $OUTPUT->user_picture($USER, array('size' => 80));
}
echo '</div>';

echo '<div class="descriptionbox">
              <div class="description">';
if ($userid != $USER->id && !$viewtutee) {
    echo '<h2 style="margin:-8px 0;">' . $USER->firstname . " " . $USER->lastname . '</h2>';
    echo ($programme ? get_string('userprogramme', 'report_myfeedback') . $programme : '');
    echo ($USER->department ? "<br> " . get_string('parentdepartment', 'report_myfeedback') . $USER->department : '');
    echo '<br>' . get_string('email_address', 'report_myfeedback') .
    ' <a href="mailto:' . $USER->email . '">' . $USER->email . '</a><br>';
}
echo '</div></div>';
// Get the late feedback days from config.
$lt = get_config('report_myfeedback');
$a = new stdClass();
$a->lte = isset($lt->latefeedback) ? $lt->latefeedback : 28;
$assessmentmsg = get_string('tutortblheader_assessment_info', 'report_myfeedback');
$assessmenticon = '<img src="' . 'pix/info.png' . '" ' .
        ' alt="-" title="' . $assessmentmsg . '" rel="tooltip"/>';
$latefeedbackmsg = get_string('tutortblheader_latefeedback_info', 'report_myfeedback', $a);
$latefeedbackicon = '<img src="' . 'pix/info.png' . '" ' .
        ' alt="-" title="' . $latefeedbackmsg . '" rel="tooltip"/>';
$overallgrademsg = get_string('tutortblheader_overallgrade_info', 'report_myfeedback');
$overallgradeicon = '<img src="' . 'pix/info.png' . '" ' .
        ' alt="-" title="' . $overallgrademsg . '" rel="tooltip"/>';
$nonsubmissionmsg = get_string('tutortblheader_nonsubmissions_info', 'report_myfeedback');
$nonsubmissionicon = '<img src="' . 'pix/info.png' . '" ' .
        ' alt="-" title="' . $nonsubmissionmsg . '" rel="tooltip"/>';
$latesubmissionmsg = get_string('tutortblheader_latesubmissions_info', 'report_myfeedback');
$latesubmissionicon = '<img src="' . 'pix/info.png' . '" ' .
        ' alt="-" title="' . $latesubmissionmsg . '" rel="tooltip"/>';
$lowgrademsg = get_string('tutortblheader_lowgrades_info', 'report_myfeedback');
$lowgradeicon = '<img src="' . 'pix/info.png' . '" ' .
        ' alt="-" title="' . $lowgrademsg . '" rel="tooltip"/>';
$grademsg = get_string('tutortblheader_graded_info', 'report_myfeedback');
$gradeicon = '<img src="' . 'pix/info.png' . '" ' .
        ' alt="-" title="' . $grademsg . '" rel="tooltip"/>';

// Setup the heading for the Personal tutor dashboard.
$tutortable = "<table id=\"tutortable\" width=\"100%\" border=\"1\">
                <thead><tr class=\"tableheader\">
                            <th><a class=\"btn\" id=\"mail\">" . get_string("sendmail", "report_myfeedback") .
        "</a><br><input title='" . get_string('selectallforemail', 'report_myfeedback')
        . "' rel =\"tooltip\" type=\"checkbox\" id=\"selectall\"/>" .
        get_string('selectall', 'report_myfeedback') . "</th>
                                <th>" .
        get_string('tutortblheader_personaltutees', 'report_myfeedback') . "</th>
                                <th>" .
        get_string('tutortblheader_assessment', 'report_myfeedback') . " " . $assessmenticon . "</th>
                                <th>" .
        get_string('tutortblheader_nonsubmissions', 'report_myfeedback') . " " . $nonsubmissionicon . "</th>
                                <th>" .
        get_string('tutortblheader_latesubmissions', 'report_myfeedback') . " " . $latesubmissionicon . "</th>
                                <th>" .
        get_string('tutortblheader_graded', 'report_myfeedback') . " $gradeicon</th>
                                <th>" .
        get_string('tutortblheader_lowgrades', 'report_myfeedback') . " " . $lowgradeicon . "</th>
            </tr></thead><tbody>";

$due = 0;
$nonsub = 0;
$latesub = 0;
$graded = 0;
$lowgrades = 0;
$exceltable = array();
$x = 0;
$modnames = '';
$useremail = array();
if ($tutees = $report->get_dashboard_tutees()) {
    // Get all personal tutees for the user.
    echo "<div class=\"ac-year-right\"><p>" . get_string('academicyear', 'report_myfeedback') . ":</p>";
    require_once(dirname(__FILE__) . '/../student/academicyear.php');
    echo '</div>';
    $report->setup_external_db($res);
    foreach ($tutees as $uid => $tutee) {
        $namesort = $tutee[1];
        $useremail[$tutee[3]] = $tutee[2];
        $dashzscore = $report->get_dashboard_zscore($uid);
        $dash = "<td></td><td></td><td></td><td></td><td></td><td></td><td></td>";
        $minprog = $maxprog = $avgprog = $myavgprog = 0;
        if ($dashzscore) {
            $graded = $dashzscore->all['graded'];
            $lowgrades = $dashzscore->all['low'];
            $due = $dashzscore->all['due'];
            $nonsub = $dashzscore->all['non'];
            $latesub = $dashzscore->all['late'];
            $dash = $dashzscore->dash;
            $modnames = $dashzscore->names;
            if (isset($dashzscore->all['lowest'])) {
                $minprog = $dashzscore->all['lowest'];
            }
            if (isset($dashzscore->all['highest'])) {
                $maxprog = $dashzscore->all['highest'];
            }
            if (isset($dashzscore->all['mean'])) {
                $avgprog = $dashzscore->all['mean'];
            }
            if (isset($dashzscore->all['useravg'])) {
                $myavgprog = $dashzscore->all['useravg'];
            }
        }
        $tutortable .= "<tr class='recordRow' valign='middle'><td><input class=\"chk1\" type=\"checkbox\" name=\"email" . $userid
            . "\" value=\"" . $tutee[2] . "\"></td>";
        $tutortable .= "<td style=\"text-align:left;min-width:200px;padding-right:30px;\" data-sort=$namesort>
                <table class=\"tutor-inner\" height=\"\"><tr><td>" . $tutee[0] . "<br>
                <div class=\"tutorCanvas\" style=\"text-align:center\">
                <br><span style=\"white-space: nowrap\">" . get_string('coursebreakdown', 'report_myfeedback') .
                "</span><br><span class=\"tangle\">&#9660;</span></div></td></tr></table>" . $modnames . "</td>";
        $tutortable .= $dash . "</tr>";

        // The full excel downloadable table.
        $exceltable[$x]['Name'] = $tutee[4];
        $exceltable[$x]['Lastname'] = $tutee[5];
        $exceltable[$x]['Assessments'] = $due;
        $exceltable[$x]['Nonsubmission'] = $nonsub;
        $exceltable[$x]['Latesubmission'] = $latesub;
        $exceltable[$x]['Graded'] = $graded;
        $exceltable[$x]['Lowgrade'] = $lowgrades;
        ++$x;

        foreach ($dashzscore->all as $key => $eachmod) {
            // Each module excel downloadable table.
            if (is_numeric($key)) {
                $exceltable[$x]['Shortname'] = $eachmod['shortname'];
                $exceltable[$x]['blank'] = '';
                $exceltable[$x]['due'] = $eachmod['due'];
                $exceltable[$x]['non'] = $eachmod['non'];
                $exceltable[$x]['late'] = $eachmod['late'];
                $exceltable[$x]['Grade'] = $eachmod['graded'];
                $exceltable[$x]['Low'] = $eachmod['low'];
                ++$x;
            }
        }
        $exceltable[$x][1] = '';
        ++$x;
    }
}
$tutortable .= "</tbody></table>";
$_SESSION['exp_sess'] = $exceltable;
$_SESSION['myfeedback_userid'] = $userid;
$_SESSION['tutor'] = 'p';
$_SESSION['user_name'] = 'nil';

// Log the event that the user viewed the dashboard.
$event = \report_myfeedback\event\myfeedbackreport_viewed_ptutordash::create(
    array('context' => context_user::instance($USER->id), 'relateduserid' => $userid)
);
$event->trigger();
$config = get_config('report_myfeedback');
$studentrecordsystemlink = $config->studentrecordsystemlink;
$studentrecordsystemlaunchtext = (isset($config->studentrecordsystem) && $config->studentrecordsystem ?
    $config->studentrecordsystem :
    get_string('studentrecordsystem', 'report_myfeedback'));

echo '<div class="personaltutoremails"><span class="personaltutoremail ex_port"><a href="#">'
    . get_string('export_to_excel', 'report_myfeedback') . '</a></span>
    <span class="personaltutoremail reportPrint"  title="'
    . get_string('print_msg', 'report_myfeedback').'" rel="tooltip"><a href="#">'
    . get_string('print_report', 'report_myfeedback') .
        '</a><img id="reportPrint" src="' . 'pix/info.png' . '" ' . ' alt="-"/></span>
        <p class="personaltutoremail"><a href="'
    . $studentrecordsystemlink . '" target="_blank">'
    . $studentrecordsystemlaunchtext . '</a></p></div></div>';

echo '<form method="POST" id="emailform" action="">';
echo '<input type="hidden" name="sesskey" value="' . sesskey() . '" />';
echo $tutortable;
echo '</form>';
