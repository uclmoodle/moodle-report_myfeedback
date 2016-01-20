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
 * My Feedback Report.
 *
 * @package   report_myfeedback
 * @author    Jessica Gramp <j.gramp@ucl.ac.uk>
 * @author    Delvon Forrester <delvon@esparanza.co.uk>
 * @credits   Based on original work report_mygrades by David Bezemer <david.bezemer@uplearning.nl> which in turn is based on 
 *            block_myfeedback by Karen Holland, Mei Jin, Jiajia Chen. Also uses SQL originating from Richard Havinga 
 *            <richard.havinga@ulcc.ac.uk>. The code for using an external database is taken from Juan leyva's
 *            <http://www.twitter.com/jleyvadelgado> configurable reports block.
 *            The idea for this reporting tool originated with Dr Jason Davies <j.p.davies@ucl.ac.uk> and 
 *            Dr John Mitchell <j.mitchell@ucl.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/grade/report/overview/lib.php');
require_once($CFG->dirroot . '/grade/lib.php');
require_once($CFG->dirroot . '/grade/querylib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/user/lib.php');

global $PAGE, $COURSE, $DB, $remotedb, $CFG;
$url = new moodle_url('/report/myfeedback/index.php');
$maxfilenamelength = 15;
$maxcommentlength = 50;

$PAGE->set_url($url);
$PAGE->set_pagelayout('report');
$PAGE->set_context(context_course::instance($COURSE->id));
$PAGE->navigation->add(get_string('pluginname', 'report_myfeedback'), $url);
$PAGE->set_heading($COURSE->fullname);
$PAGE->set_title(get_string('pluginname', 'report_myfeedback'));

$PAGE->requires->jquery_plugin('dataTables', 'report_myfeedback');
$PAGE->requires->jquery_plugin('tooltip', 'report_myfeedback');

require_login();

echo $OUTPUT->header();

$userid = optional_param('userid', 0, PARAM_INT); // User id.

if (empty($userid)) {
    $userid = $USER->id;
    $usercontext = context_user::instance($userid, MUST_EXIST);
} else {
    $usercontext = context_user::instance($userid, MUST_EXIST);
}
//$coursecontext = context_course::instance($COURSE->id);
//Show the Module leader view or other view if the user is not looking at a student report
$viewtutee = false;
if ($userid != $USER->id) {
    $viewtutee = true;
}

//If user don't have the report capability they can't access it
try {
    if (!has_capability('report/myfeedback:view', $usercontext)) {
        echo $OUTPUT->notification(get_string('nopermissiontoshow', 'error'));
        die();
    }
} catch (Exception $ex) {
    echo $ex->getMessage();
    die();
}

$report = new report_myfeedback();
$report->init();
$report->setup_ExternalDB();

$user = $remotedb->get_record('user', array('id' => $userid, 'deleted' => 0
        ));
$userlinked = "<a href='" . $CFG->wwwroot . "/user/view.php?id=" . $userid . "'>" . $user->firstname .
        " " . $user->lastname . "</a>";

if (empty($user->username)) {
    echo $OUTPUT->notification(get_string('userdeleted'));
    die();
}

//Set user roles to determine what pages they can access
//moodle/course:changeshortname for tutors
//moodle/user:viewalldetails for personal tutor
$module_tutor = false;
$personal_tutor = false;
$progadmin = false;
$prog = false;
$is_student = false;
$report_heading = get_string('dashboard', 'report_myfeedback') . ' for '.$user->firstname.' '.$user->lastname;
//if (has_capability('moodle/user:viewalldetails', $usercontext)) {
//This is just temporary as role can change for different systems
//TODO: The personal tutor role may be any id so check for the ID by adding a function in the lib file to look for the name
//then pass this id number to the user_has_role_assignment function below:
//It is hard to use has_capability as commented out a few lines below because the context id is trick as you cannot assign personal tutor on a 
//course basis or they will be personal tutor for all users in the course and that is not allways the case.
$p_tutor_id = $report->get_personal_tutor_id();
if (user_has_role_assignment($USER->id, $p_tutor_id)) {
    $personal_tutor = true;
    $report_heading = get_string('dashboard', 'report_myfeedback') . ' for '.$user->firstname.' '.$user->lastname . get_string('personaltutorview', 'report_myfeedback');
}
//Get the personal tutor details of the user
if ($mytutorid = $report->get_my_personal_tutor($p_tutor_id, $usercontext->id)) {
    $mytutorint = array(intval($mytutorid));
    $mytutor = user_get_users_by_id($mytutorint);
}

//Added this here because when the programme admin is not accessing a student there is no course to get the context 
//so the report_heading does not have the Programme admin view
$prog_admin_id = $report->get_program_admin_id();
if (user_has_role_assignment($USER->id, $prog_admin_id)) {
    $prog = true;
    $report_heading = get_string('dashboard', 'report_myfeedback') . ' for '.$user->firstname.' '.$user->lastname . get_string('progadminview', 'report_myfeedback');
}

if (user_has_role_assignment($USER->id, 5)) {
    $is_student = true;
}

//get all courses that the user is a teacher in (has the edit course capability is used here)
$my_mods = array();
$student_mods_ids = array();
$my_mods_ids = array();
if ($mods = enrol_get_users_courses($userid, $onlyactive = TRUE)) {
    foreach ($mods as $value) {
        if ($value->visible) {
            $student_mods_ids[] = $value->id;
            $coursecontext = context_course::instance($value->id);
            if (has_capability('moodle/course:changeshortname', $coursecontext, $USER->id, $doanything = false)) {
                $my_mods[] = $value;
                $my_mods_ids = $value->id;
                $module_tutor = true;
                $report_heading = get_string('dashboard', 'report_myfeedback') . ' for '.$user->firstname.' '.$user->lastname . get_string('moduleleaderview', 'report_myfeedback');
            }

            //If looking at a student account and you have progadmin capability then you are not barred from that user
            if (has_capability('report/myfeedback:progadmin', $coursecontext, $USER->id, $doanything = false)) {
                $progadmin = true;
                $report_heading = get_string('dashboard', 'report_myfeedback') . ' for '.$user->firstname.' '.$user->lastname . get_string('progadminview', 'report_myfeedback');
            }
        }
    }
}

//If user don't have the moodle capability to see the specific user they can't access it
if ($progadmin || $module_tutor || $userid == $USER->id || has_capability('moodle/user:viewdetails', $usercontext)) {
    //Has access to the user
} else {
    echo $OUTPUT->notification(get_string('usernotavailable', 'error'));
    die();
}

echo '<div class="heading">';
echo $OUTPUT->heading($report_heading);
echo '</div>';

//get the number of years for the user
//If they are in year 3 then they would have year 1, 2 and 3
$year = 1;
profile_load_data($user);
if (!$year = $user->profile_field_year) {
    //
}
//$years = intval($year);
//Tabs setup
$thispageurl = 'index.php';
$tabs = array();
if ($prog || $module_tutor || $personal_tutor || is_siteadmin()) {
    $tabs[] = new tabobject('mytutees', new moodle_url($thispageurl, array('userid' => $userid, 'currenttab' => 'mytutees')), get_string('tabs_mytutees', 'report_myfeedback'));
    $currenttab = optional_param('currenttab', 'mytutees', PARAM_TEXT);
}

if ($viewtutee || $is_student || is_siteadmin()) {
    $tabs[] = new tabobject('overview', new moodle_url($thispageurl, array('userid' => $userid, 'currenttab' => 'overview')), get_string('tabs_overview', 'report_myfeedback'));
    //$tabs[] = new tabobject('year', new moodle_url($thispageurl, array('userid' => $userid, 'currenttab' => 'year')), get_string('tabs_academicyear', 'report_myfeedback'));
    $tabs[] = new tabobject('feedback', new moodle_url($thispageurl, array('userid' => $userid, 'currenttab' => 'feedback')), get_string('tabs_feedback', 'report_myfeedback'));
    if ($mytutorid || is_siteadmin()) {
        $tabs[] = new tabobject('ptutor', new moodle_url($thispageurl, array('userid' => $userid, 'currenttab' => 'ptutor')), get_string('tabs_ptutor', 'report_myfeedback'));
    }
    /* if ($module_tutor || $prog) {
      $tabs[] = new tabobject('return-2-dash', new moodle_url($thispageurl), get_string('return-2-dash', 'report_myfeedback'));
      } */
    if (($userid == $USER->id && $is_student) || ($prog || $module_tutor || $personal_tutor || is_siteadmin() && $viewtutee)) {
    $currenttab = optional_param('currenttab', 'overview', PARAM_TEXT);
    }
}
//If tutor and not viewing a tutee's report
/* if ($module_tutor && !$viewtutee) {
  $tabs[] = new tabobject('mymodules', new moodle_url($thispageurl, array('userid' => $userid, 'currenttab' => 'mymodules')), get_string('tabs_mymodules', 'report_myfeedback'));
  foreach ($my_mods as $my_mod) {
  $tabs[] = new tabobject($my_mod->shortname, new moodle_url($thispageurl, array('userid' => $userid, 'currenttab' => $my_mod->shortname)), substr($my_mod->shortname, 0, 15));
  }
  $currenttab = optional_param('currenttab', 'mymodules', PARAM_TEXT);
  }
  if ($personal_tutor) {
  $tabs[] = new tabobject('tutor', new moodle_url($thispageurl, array('userid' => $userid, 'currenttab' => 'tutor')), get_string('tabs_tutor', 'report_myfeedback'));
  }

  //If Program admin
  if ($prog && !$viewtutee) {
  //$tabs[] = new tabobject('progadmin', new moodle_url($thispageurl, array('userid' => $userid, 'currenttab' => 'progadmin')), get_string('tabs_progadmin', 'report_myfeedback'));
  $currenttab = optional_param('currenttab', 'mytutees', PARAM_TEXT);
  } */

echo $OUTPUT->tabtree($tabs, $currenttab);

switch ($currenttab) {
    case 'overview':
        require_once('student/overview.php');
        break;
    //case 'year':
    //require_once('student/year.php');
    //  break;
    case 'feedback':
        require_once('student/feedback.php');
        break;
    case 'ptutor':
        require_once('student/personaltutor.php');
        break;
    case 'mytutees':
        require_once('mytutees.php');
        break;
    case 'tutor':
        require_once('tutor/personaltutoring.php');
        break;
    case 'mymodules':
        require_once('tutor/mymodules.php');
        break;
    case 'progadmin':
        require_once('programmeadmin/index.php');
        break;
    default:
        require_once('tutor/modules.php');
        break;
}
//End of tabs setup
echo $OUTPUT->footer();

// Trigger a viewed event.
$event = \report_myfeedback\event\myfeedbackreport_viewed::create(array('context' => context_course::instance($COURSE->id), 'relateduserid' => $userid));
$event->trigger();
