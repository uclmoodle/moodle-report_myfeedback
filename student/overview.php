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
 * The main file for the overview tab
 *
 * @package  report_myfeedback
 * @copyright 2022 UCL
 * @author    Delvon Forrester <delvon@esparanza.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$PAGE->requires->js_call_amd('report_myfeedback/overview', 'init');

if (!$canaccessuser) {
    echo report_myfeedback_stop_spinner();
    throw new moodle_exception('nopermissions', '', $PAGE->url->out(), get_string('viewstudentreports', 'report_myfeedback'));
}

if ($user->lastlogin) {
    $userlastlogin = userdate($user->lastlogin) . "&nbsp; (" . format_time(time() - $user->lastaccess) . ")";
} else {
    $userlastlogin = get_string("never");
}

// Get the added profile fields.
$programme = '';
if (isset($user->profile_field_programmename)) {
    $programme = $user->profile_field_programmename;
}

echo '<div class="userprofilebox clearfix">';

if ($userid != $USER->id) {
    echo '<div class="profilepicture">';
    echo $OUTPUT->user_picture($user, array('size' => 125));
    echo '</div>';

    echo '<div class="descriptionbox"><div class="description">';

    echo '<h2 style="margin:-8px 0;">' . $userlinked . '</h2>';

    echo ($programme ? get_string('userprogramme', 'report_myfeedback') . $programme : '')
        . ($year ? ' (' . get_string("year", "report_myfeedback") . $year . ')' : '') .
        '<br>' . get_string('parentdepartment', 'report_myfeedback') .
        ' ' . $user->department . '<p> </p>';

    echo html_writer::span(get_string('lastmoodlelogin', 'report_myfeedback'));
    echo html_writer::span($userlastlogin);
    echo '</div>';
    echo '</div><div class="ac-year-right"><p>' . get_string('academicyear', 'report_myfeedback') . ':</p>';
    require_once(dirname(__FILE__) . '/academicyear.php');
    echo '</div></div>';

    // List of courses enrolled on.
    $courselist = '';
    $limitcourse = 1;
    $allcourses = array();
    $allcourse = get_user_capability_course(
        'report/myfeedback:student',
        $userid,
        $doanything = false,
        $fields = 'id,shortname,fullname,visible'
    );
    if ($allcourse) {
        $cl = get_config('report_myfeedback');
        $lim = (isset($cl->overviewlimit) && $cl->overviewlimit ? $cl->overviewlimit : 9999);
        foreach ($allcourse as $eachc) {
            if ($eachc->visible && $limitcourse <= $lim) {
                $allcourses[] = $eachc;
                ++$limitcourse;
            }
        }
        $num = 0;
        $m1 = get_string('more', 'report_myfeedback');
        $m2 = get_string('moreinfo', 'report_myfeedback');
        $more = "<span><a href='$CFG->wwwroot/user/profile.php?id=$userid&showallcourses=1' title = '$m2' rel='tooltip'>$m1</a>";
        uasort($allcourses, function($a, $b) {
            return strcasecmp($a->shortname, $b->shortname);
        });
        foreach ($allcourses as $eachcourse) {
            $courselist .= "<a href=\"" . $CFG->wwwroot . "/course/view.php?id=" . $eachcourse->id .
                    "\" title=\"" . $eachcourse->fullname . "\" rel=\"tooltip\">" . $eachcourse->shortname . "</a>";
            ++$num;
            if ($num < count($allcourses)) {
                $courselist .= ", ";
            }
        }
        if ($lim < count($allcourse)) {
            $courselist .= $more;
        }
    }
    echo '<div class="enroltext">' . get_string('enrolledmodules', 'report_myfeedback') .
    ' <span class="enrolledon">' . $courselist . '</span></div>';
} else {
    // If user viewing own report end here.
    echo '<div class="ac-year-right"><p>' . get_string('academicyear', 'report_myfeedback') . ':</p>';
    require_once(dirname(__FILE__) . '/academicyear.php');
    echo '</div>';
}

$archiveyear = substr_replace($res, '-', 2, 0); // For building the archive link.
$arch = $res;
$pos = stripos($CFG->wwwroot, $archiveyear);
if (!$personaltutor && !$progadmin && !is_siteadmin()) {
    $res = ''; // If all else fails it should check only it's current database.
}

$report->setup_external_db($res);
$content = $report->get_content($currenttab, $personaltutor, $progadmin, $arch);
echo $content->text;
echo $OUTPUT->container_start('info');
echo $OUTPUT->container_end();
