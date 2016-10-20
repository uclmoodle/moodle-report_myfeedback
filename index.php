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
global $PAGE, $COURSE, $USER, $OUTPUT, $remotedb, $CFG;
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/grade/report/overview/lib.php');
require_once($CFG->dirroot . '/grade/lib.php');
require_once($CFG->dirroot . '/grade/querylib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/user/lib.php');

$url = new moodle_url('/report/myfeedback/index.php');

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
//$dv=(isset($_SESSION['viewdept']))? $_SESSION['viewdept']: get_string('choosedots');
//$pv=(isset($_SESSION['viewprog']))? $_SESSION['viewprog']:get_string('choosedots';
$userid = optional_param('userid', 0, PARAM_INT); // User id.
$yearview = optional_param('myselect', 0, PARAM_ALPHANUMEXT);
$modview = optional_param_array('modselect', array(), PARAM_TEXT);
//$deptview = optional_param('deptselect', get_string('choosedots', PARAM_ALPHANUMEXT);
//$progview = optional_param('progselect', get_string('choosedots', PARAM_ALPHANUMEXT);
//$progmodview = optional_param('progmodselect', get_string('choosedots', PARAM_ALPHANUMEXT);
$deptview = (isset($_POST['deptselect']) ? $_POST['deptselect'] : get_string('choosedots'));
$progview = (isset($_POST['progselect']) ? $_POST['progselect'] : get_string('choosedots'));
$progmodview = (isset($_POST['progmodselect']) ? $_POST['progmodselect'] : get_string('choosedots'));
$_SESSION['viewmod'] = $modview;
if ($yearview) {
    $_SESSION['viewyear'] = $yearview;
}
//$_SESSION['viewdept'] = $deptview;
//$_SESSION['viewprog'] = $progview;
//$_SESSION['viewprogmod'] = $progmodview;
//echo 'deptview: ', $_SESSION['viewdept'], '<br>Progview: ', $_SESSION['viewprog'], '<br>Mod: ', $_SESSION['viewprogmod'],
//       '<br>curdept: ', $_SESSION['curdept'], '<br>curprog: ', $_SESSION['curprog'], '<br>curMod: ', $_SESSION['curmod'];

$report = new report_myfeedback();
$report->init();
$report->setup_ExternalDB();

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
/* try {
  if (!has_capability('report/myfeedback:view', $usercontext)) {
  echo $OUTPUT->notification(get_string('nopermissiontoshow', 'error'));
  die();
  }
  } catch (Exception $ex) {
  echo $ex->getMessage();
  die();
  } */
$user = $remotedb->get_record('user', array('id' => $userid, 'deleted' => 0));
$userlinked = "<a href='" . $CFG->wwwroot . "/user/view.php?id=" . $userid . "'>" . $user->firstname .
        " " . $user->lastname . "</a>";
$_SESSION['user_name'] = $user->firstname . ' ' . $user->lastname;

if (empty($user->username)) {
    echo $OUTPUT->notification(get_string('userdeleted'));
    die();
}

//Set user roles to determine what pages they can access
//report/myfeedback:modtutor for tutors
//moodle/user:viewalldetails for personal tutor
$module_tutor = false;
$personal_tutor = false;
$progadmin = false;
$prog = false;
$is_student = false;
$ownreport = '';
$showstudentstab = true;
if ($userid != $USER->id) {
    $ownreport = '<span class="ownreport"><a href=' . $CFG->wwwroot . '/report/myfeedback/index.php?userid=' . $USER->id . '>' .
            get_string('ownreport', 'report_myfeedback') . '</a></span>';
    $showstudentstab = false;
}
$report_heading = get_string('dashboard', 'report_myfeedback') . ' for ' . $user->firstname . ' ' . $user->lastname . $ownreport;

//if (has_capability('moodle/user:viewalldetails', $usercontext)) {
//This is just temporary as role can change for different systems
//TODO: The personal tutor role may be any id so check for the ID by adding a function in the lib file to look for the name
//then pass this id number to the user_has_role_assignment function below:
//It is hard to use has_capability because the context id is trick as you cannot assign personal tutor on a 
//course basis or they will be personal tutor for all users in the course and that is not allways the case.
$p_tutor_id = $report->get_personal_tutor_id();
if (user_has_role_assignment($USER->id, $p_tutor_id)) {
    $personal_tutor = true;
    $report_heading = get_string('dashboard', 'report_myfeedback') . ' for ' . $user->firstname . ' ' . $user->lastname . get_string('personaltutorview', 'report_myfeedback') . $ownreport;
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
    $report_heading = get_string('dashboard', 'report_myfeedback') . ' for ' . $user->firstname . ' ' . $user->lastname . get_string('progadminview', 'report_myfeedback') . $ownreport;
}

if (user_has_role_assignment($USER->id, 5)) {
    $is_student = true;
}

//get all courses that the user is a teacher in (has the edit course capability is used here)
$my_mods = array();
//$student_mods_ids = array();
$my_mods_ids = array();
//This capability is something that everyone has , especially this is what helps to set a mod tutor so they can see the dashboard.
if ($mods = get_user_capability_course('moodle/course:viewparticipants', $userid, $doanything = false, $fields = 'shortname,visible')) {//enrol_get_users_courses($userid, $onlyactive = TRUE)) {
    foreach ($mods as $value) {
        if ($value->visible) {
            $coursecontext = context_course::instance($value->id);
            if (has_capability('mod/assign:submit', $coursecontext, $userid)) {
                $is_student = true; //for roles where they copy from default student role
            }
            if (has_capability('report/myfeedback:modtutor', $coursecontext, $USER->id, $doanything = false)) {
                $my_mods[] = $value;
                $my_mods_ids[] = $value->id;
                $module_tutor = true;
                $report_heading = get_string('dashboard', 'report_myfeedback') . ' for ' . $user->firstname . ' ' . 
                        $user->lastname . get_string('moduleleaderview', 'report_myfeedback') . $ownreport;
            }

            //If looking at a student account and you have progadmin capability then you are not barred from that user
            if (has_capability('report/myfeedback:progadmin', $coursecontext, $USER->id, $doanything = false)) {
                $progadmin = true;
                $report_heading = get_string('dashboard', 'report_myfeedback') . ' for ' . $user->firstname . ' ' . $user->lastname . 
                        get_string('progadminview', 'report_myfeedback') . $ownreport;
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

$thistab = optional_param('currenttab', '', PARAM_TEXT);
if ((($thistab == 'overview' || $thistab == 'feedback' || $thistab == 'ptutor') && $userid == $USER->id) || $userid == $USER->id) {
    $report_heading = get_string('dashboard', 'report_myfeedback') . ' for ' . $user->firstname . ' ' . $user->lastname . $ownreport;
}
if ($thistab == 'mytutees') {
    $report_heading = get_string('tabs_mytutees', 'report_myfeedback') . $ownreport;
}
if ($thistab == 'tutor') {
    $report_heading = get_string('tabs_tutor', 'report_myfeedback') . $ownreport;
}
if ($thistab == 'mymodules') {
    $report_heading = get_string('tabs_mtutor', 'report_myfeedback') . $ownreport;
}
if ($thistab == 'progadmin') {
    $report_heading = get_string('progadmin_dashboard', 'report_myfeedback') . $ownreport;
}

echo '<div class="heading">';
echo $OUTPUT->heading($report_heading);
echo '</div>';

//get the number of years for the user
//If they are in year 3 then they would have year 1, 2 and 3
$year = 1;
profile_load_data($user);
if (isset($user->profile_field_courseyear)) {
    $year = $user->profile_field_courseyear;
}
//$years = intval($year);
//Tabs setup
$currenttab = optional_param('currenttab', 'overview', PARAM_TEXT);
$thispageurl = 'index.php';
$tabs = array();

//If departmental admin and not viewing a tutee's report
if ($prog && !$viewtutee) {
    $tabs[] = new tabobject('progadmin', new moodle_url($thispageurl, array('userid' => $userid, 'currenttab' => 'progadmin')), get_string('progadmin_dashboard', 'report_myfeedback'));
}
//If tutor and not viewing a tutee's report
if ($module_tutor && !$viewtutee) {
    $tabs[] = new tabobject('mymodules', new moodle_url($thispageurl, array('userid' => $userid, 'currenttab' => 'mymodules')), get_string('tabs_mtutor', 'report_myfeedback'));
}
//If personal tutor and not viewing a tutee's report
if ($personal_tutor && !$viewtutee) {
    $tabs[] = new tabobject('tutor', new moodle_url($thispageurl, array('userid' => $userid, 'currenttab' => 'tutor')), get_string('tabs_tutor', 'report_myfeedback'));
}

if ($personal_tutor && !$viewtutee) {
    $currenttab = optional_param('currenttab', 'tutor', PARAM_TEXT);    
}
if ($module_tutor && !$viewtutee) {
    $currenttab = optional_param('currenttab', 'mymodules', PARAM_TEXT);    
}
if ($prog && !$viewtutee) {
    $currenttab = optional_param('currenttab', 'progadmin', PARAM_TEXT);    
}

if ($showstudentstab) {
    if ($prog || $module_tutor || $personal_tutor) {
        $tabs[] = new tabobject('mytutees', new moodle_url($thispageurl, array('userid' => $userid, 'currenttab' => 'mytutees')), get_string('tabs_mytutees', 'report_myfeedback'));
    }
}

if ($viewtutee || $is_student || is_siteadmin() || (!$prog && !$module_tutor && !$personal_tutor)) {
    $tabs[] = new tabobject('overview', new moodle_url($thispageurl, array('userid' => $userid, 'currenttab' => 'overview')), get_string('tabs_overview', 'report_myfeedback'));
    $tabs[] = new tabobject('feedback', new moodle_url($thispageurl, array('userid' => $userid, 'currenttab' => 'feedback')), get_string('tabs_feedback', 'report_myfeedback'));
    if (($mytutorid && !$personal_tutor) || is_siteadmin()) {
        $tabs[] = new tabobject('ptutor', new moodle_url($thispageurl, array('userid' => $userid, 'currenttab' => 'ptutor')), get_string('tabs_ptutor', 'report_myfeedback'));
    }
    $currenttab = optional_param('currenttab', 'overview', PARAM_TEXT);
}

echo $OUTPUT->tabtree($tabs, $currenttab);

switch ($currenttab) {
    case 'overview':
        require_once('student/overview.php');
        break;
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
    case 'shortname':
        require_once('tutor/modules.php');
        break;
    case 'progadmin':
        require_once('programmeadmin/index.php');
        break;
    default:
        break;
}
//End of tabs setup
echo $OUTPUT->footer();

//Only log the action if the related user is changed in the session
if (array_key_exists('viewed', $_SESSION)) {
    if ($_SESSION['viewed'] != $userid) {
        // Trigger a viewed event.
        $event = \report_myfeedback\event\myfeedbackreport_viewed::create(array('context' => context_system::instance(0), 'relateduserid' => $userid));
        $event->trigger();
        $_SESSION['viewed'] = $userid;
    }
} else {
    $_SESSION['viewed'] = $userid;
}