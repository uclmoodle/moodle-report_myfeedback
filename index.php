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
$PAGE->requires->jquery_plugin('modal', 'report_myfeedback');

require_login();
$dots = get_string('choosedots');

$userid = optional_param('userid', 0, PARAM_INT); // User id.
$yearview = optional_param('myselect', 0, PARAM_ALPHANUMEXT); // academic year for archive
$modview = optional_param_array('modselect', array(), PARAM_TEXT); // The selected course for Mod tutor Dashboard

$deptview = (isset($_POST['deptselect']) ? $_POST['deptselect'] : $dots); // For top level category on Dept admin dashboard
$progview = (isset($_POST['progselect']) ? $_POST['progselect'] : $dots); // For second level category on Dept admin dashboard
$progmodview = (isset($_POST['progmodselect']) ? $_POST['progmodselect'] : $dots); // For the course on the Dept admin dashboard
$searchuser = (isset($_POST['searchuser']) ? $_POST['searchuser'] : ''); // For the user search input on My students tab
$searchusage = (isset($_POST['searchusage']) ? $_POST['searchusage'] : ''); // For the search input on Usage dashboard

$_SESSION['viewmod'] = $modview;
if ($yearview) {//keep the academic year for the loggen-in session
    $_SESSION['viewyear'] = $yearview;
}
$yrr = get_config('report_myfeedback');//For the archive
$livedomain = isset($yrr->livedomain) ?  $yrr->livedomain : get_string('livedomaindefault', 'report_myfeedback');
$archivedomain = isset($yrr->archivedomain) ?  $yrr->archivedomain : get_string('archivedomaindefault', 'report_myfeedback');

if (isset($_POST['deptselect'])) { setcookie('curdept',$_POST['deptselect']); $_COOKIE['curdept'] = $_POST['deptselect']; }//Save the top level category for life of cookie
if (isset($_POST['progselect'])) { setcookie('curprog',$_POST['progselect']); $_COOKIE['curprog'] = $_POST['progselect']; }//Second level category on dept admin dashbard
if (isset($_POST['progmodselect'])) { setcookie('curmodprog',$_POST['progmodselect']); $_COOKIE['curmodprog'] = $_POST['progmodselect']; }//Course level

echo $OUTPUT->header();

//Initialize the report and set the database to get info from
$report = new report_myfeedback();
$report->init();
$report->setup_ExternalDB();

//get a progress bar when report is getting info
echo "<div id=\"wait\" style=\"width:70px;height:60px;position:absolute;top:40%;left:50%;z-index:9999\"><img src=\"pix/progress.gif\"/></div>
    <script type=\"text/javascript\">jQuery(\"body\").css(\"cursor\", \"progress\");</script>";

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
$ucontext = context_user::instance($USER->id, MUST_EXIST);
//If user don't have the report capability they can't access it
/*try {
  if (!has_capability('report/myfeedback:view', $usercontext)) {
  echo $OUTPUT->notification(get_string('nopermissiontoshow', 'error'));
  die();
  }
  } catch (Exception $ex) {
  echo $ex->getMessage();
  die();
  }*/ 
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
//report/myfeedback:progadmin for dept admin
//report/myfeedback:student for students
//report/myfeedback:usage to see usage reports
$module_tutor = false;
$personal_tutor = false;
$progadmin = false;
$usage = false;
$prog = false;
$is_student = false;
$ownreport = '';
$showstudentstab = true;
if ($userid != $USER->id) {
    $ownreport = '<span class="ownreport"><a href=' . $CFG->wwwroot . '/report/myfeedback/index.php?userid=' . $USER->id . '>' .
            get_string('ownreport', 'report_myfeedback') . '</a></span>';
    $showstudentstab = false;
}
$report_heading = get_string('dashboard', 'report_myfeedback') . get_string('for', 'report_myfeedback') . $user->firstname . ' ' . 
        $user->lastname . $ownreport;

if ($p_tutor_cap = $report->get_dashboard_capability($USER->id, 'report/myfeedback:personaltutor')) {
    $personal_tutor = true;
    $report_heading = get_string('dashboard', 'report_myfeedback') . get_string('for', 'report_myfeedback') . $user->firstname . ' ' . 
            $user->lastname . get_string('personaltutorview', 'report_myfeedback') . $ownreport;
}
$p_tutor_id = $report->get_personal_tutor_id();
//Get the personal tutor details of the user
if ($mytutorid = $report->get_my_personal_tutor($p_tutor_id, $usercontext->id)) {
    //
}

//Added this here because when the dept admin is not accessing a student there is no course to get the context 
//so the report_heading does not have the dept admin view
if ($prog_admin_id = $report->get_dashboard_capability($USER->id, 'report/myfeedback:progadmin')) {
    $prog = true;
    $report_heading = get_string('dashboard', 'report_myfeedback') . get_string('for', 'report_myfeedback') . $user->firstname . ' ' . 
            $user->lastname . get_string('progadminview', 'report_myfeedback') . $ownreport;
}

//Added this here because the usage report is not accessing a student so there is no course to get the context 
//so the report_heading does not have the dept admin view
if ($report->get_dashboard_capability($USER->id, 'report/myfeedback:usage')) {
	$usage = true;
}

if ($student = $report->get_dashboard_capability($USER->id, 'report/myfeedback:student')) {
    $is_student = true;
}

//TODO - should we only do this if we are wanting to look at a student's report though? It would be redunant if we're just veiwing our own report, or the usage dashboard.
//This capability is something that everyone who has been enrolled on a course has on that course.
//Here we want to check the courses and see if the logged in user has modtutor or prog admin capability for the user they are trying to look at so they can see their report.
if ($mods = get_user_capability_course('moodle/course:viewparticipants', $userid, $doanything = false, $fields = 'shortname,visible')) {
    foreach ($mods as $value) {
        if ($value->visible) {
            $coursecontext = context_course::instance($value->id);
            if (has_capability('report/myfeedback:student', $coursecontext, $userid)) {
                $is_student = true; //for roles where they copy from default student role
            }
            if (has_capability('report/myfeedback:modtutor', $coursecontext, $USER->id, $doanything = false)) {
                $module_tutor = true;
                $report_heading = get_string('dashboard', 'report_myfeedback') . get_string('for', 'report_myfeedback') . $user->firstname . ' ' . 
                        $user->lastname . get_string('moduleleaderview', 'report_myfeedback') . $ownreport;
            }

            //If looking at a student account and you have progadmin capability then you are not barred from that user
            if (has_capability('report/myfeedback:progadmin', $coursecontext, $USER->id, $doanything = false)) {
                $progadmin = true;
                $report_heading = get_string('dashboard', 'report_myfeedback') . get_string('for', 'report_myfeedback') . $user->firstname . ' ' . 
                        $user->lastname . get_string('progadminview', 'report_myfeedback') . $ownreport;
            }
        }
    }
}

//If user doesn't have the moodle capability to see the specific user they can't access it
if ($progadmin || $module_tutor || $userid == $USER->id || has_capability('moodle/user:viewdetails', $usercontext)) {
//Has access to the user
} else {
    echo $OUTPUT->notification(get_string('usernotavailable', 'error'));
    die();
}

//Display the different tabs based on the logged-in user's roles
$thistab = optional_param('currenttab', '', PARAM_TEXT);
if ((($thistab == 'overview' || $thistab == 'feedback' || $thistab == 'ptutor') && $userid == $USER->id) || $userid == $USER->id) {
    $report_heading = get_string('dashboard', 'report_myfeedback') . get_string('for', 'report_myfeedback') . $user->firstname . ' ' . $user->lastname . $ownreport;
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
if ($thistab == 'usage') {
    $report_heading = get_string('usage_dashboard', 'report_myfeedback') . $ownreport;
}

echo '<div class="heading">';
echo $OUTPUT->heading($report_heading);
echo '</div>';

//get the year on the programme for the user from their profile
//This requires that the below profile field is added
$year = 1;
profile_load_data($user);
if (isset($user->profile_field_courseyear)) {
    $year = $user->profile_field_courseyear;
}
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
if ($usage && !$viewtutee) {
    $currenttab = optional_param('currenttab', 'usage', PARAM_TEXT);    
}

if ($showstudentstab) {
    if ($prog || $module_tutor || $personal_tutor) {
        $tabs[] = new tabobject('mytutees', new moodle_url($thispageurl, array('userid' => $userid, 'currenttab' => 'mytutees')), get_string('tabs_mytutees', 'report_myfeedback'));
    }
}

if ($viewtutee || $is_student || is_siteadmin() || (!$prog && !$module_tutor && !$personal_tutor && !$usage)) {
    $tabs[] = new tabobject('overview', new moodle_url($thispageurl, array('userid' => $userid, 'currenttab' => 'overview')), get_string('tabs_overview', 'report_myfeedback'));
    $tabs[] = new tabobject('feedback', new moodle_url($thispageurl, array('userid' => $userid, 'currenttab' => 'feedback')), get_string('tabs_feedback', 'report_myfeedback'));
    if ($mytutorid && !$personal_tutor) {
        $tabs[] = new tabobject('ptutor', new moodle_url($thispageurl, array('userid' => $userid, 'currenttab' => 'ptutor')), get_string('tabs_ptutor', 'report_myfeedback'));
    }
    $currenttab = optional_param('currenttab', 'overview', PARAM_TEXT);
}

//If usage and not viewing a tutee's report
if ($usage && !$viewtutee) {
    $tabs[] = new tabobject('usage', new moodle_url($thispageurl, array('userid' => $userid, 'currenttab' => 'usage')), get_string('usage_dashboard', 'report_myfeedback'));
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
	case 'usage':
        require_once('usage/index.php');
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
    $event = \report_myfeedback\event\myfeedbackreport_viewed::create(array('context' => context_system::instance(0), 'relateduserid' => $userid));
    $event->trigger();
    $_SESSION['viewed'] = $userid;
}
//stop the progress bar when page loads.
echo "<script type=\"text/javascript\">
    $(document).ready( function () {

$('#wait').css({'cursor':'default','display':'none'});
$('body').css('cursor', 'default');
});
</script>";