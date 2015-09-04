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
 * @credits   Based on original work report_mygrades by David Bezemer <david.bezemer@uplearning.nl> which in turn is based on 
 * 			  block_myfeedback by Karen Holland, Mei Jin, Jiajia Chen. Also uses SQL originating from Richard Havinga 
 *			  <richard.havinga@ulcc.ac.uk>. The code for using an external database is taken from Juan leyva's
 *			  <http://www.twitter.com/jleyvadelgado> configurable reports block.
 *            The idea for this reporting tool originated with Dr Jason Davies <j.p.davies@ucl.ac.uk> and 
 *            Dr John Mitchell <j.mitchell@ucl.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/grade/report/overview/lib.php');
require_once($CFG->dirroot . '/grade/lib.php');

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

$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('dataTables', 'report_myfeedback');
$PAGE->requires->jquery_plugin('footable', 'report_myfeedback');
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
if ($userid != $USER->id && !has_capability('moodle/user:viewdetails', $usercontext)) {
    echo $OUTPUT->notification(get_string('usernotavailable', 'error'));
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
echo $OUTPUT->heading(
        get_string('pluginname', 'report_myfeedback') . " " . get_string('for', 'calendar') . " " .
        $userlinked);
$content = $report->get_content();
echo $content->text;
echo $OUTPUT->container_start('info');
echo $OUTPUT->container_end();
// Enable sorting with dataTable.
echo "<script>$('#grades').dataTable({'aaSorting': []});</script>";
echo $OUTPUT->footer();