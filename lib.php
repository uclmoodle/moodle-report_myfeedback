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
 * @copyright 2022 UCL
 * @author    Jessica Gramp <j.gramp@ucl.ac.uk>
 * @author    Delvon Forrester <delvon@esparanza.co.uk>
 * credits    Based on original work report_mygrades by David Bezemer <david.bezemer@uplearning.nl> which in turn is based on
 *            block_myfeedback by Karen Holland, Mei Jin, Jiajia Chen. Also uses SQL originating from Richard Havinga
 *            <richard.havinga@ulcc.ac.uk>. The code for using an external database is taken from Juan leyva's
 *            <http://www.twitter.com/jleyvadelgado> configurable reports block.
 *            The idea for this reporting tool originated with Dr Jason Davies <j.p.davies@ucl.ac.uk> and
 *            Prof John Mitchell <j.mitchell@ucl.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * This function extends the navigation with the My feedback report.
 *
 * @param global_navigation $navigation The navigation node to extend
 */
function report_myfeedback_extend_navigation(global_navigation $navigation) {
    // TODO: Segun Babalola. Where does $course come from?
    // TODO: Check that pix_icon is not deprecated.
    $url = new moodle_url('/report/myfeedback/index.php', array('course' => $course->id));
    $navigation->add(get_string('pluginname', 'report_myfeedback'), $url, null, null, null, new pix_icon('i/report', ''));
}

/**
 * This function extends the My settings >> activity with the My feedback report.
 *
 * @param global_navigation $navigation The navigation node to extend
 * @param stdClass $user The user object
 * @param stdClass $course The course object
 * @return void
 * @throws coding_exception
 * @throws moodle_exception
 */
function report_myfeedback_extend_navigation_user($navigation, $user, $course) {
    // Backward compatibility to v2.8 and earlier versions.
    $context = context_user::instance($user->id, MUST_EXIST);
    $url = new moodle_url('/report/myfeedback/index.php', array('userid' => $user->id));
    $navigation->add(get_string('pluginname', 'report_myfeedback'),
        $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
}

/**
 * This function extends the navigation with the My feedback report for users you have access to.
 *
 * @param global_navigation $navigation The navigation node to extend
 * @param stdClass $user The user object
 * @param stdClass $context The context
 * @param stdClass $course The course object
 * @param stdClass $coursecontext The context of the course
 */
function report_myfeedback_extend_navigation_user_settings($navigation, $user, $context, $course, $coursecontext) {
    $url = new moodle_url('/report/myfeedback/index.php', array(
        'userid' => $user->id,
        'sesskey' => sesskey()
    ));
    $navigation->add(get_string('pluginname', 'report_myfeedback'), $url,
        navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
}

/**
 * This function extends the navigation in course admin >> reports with the My feedback report.
 *
 * @param global_navigation $navigation The navigation node to extend
 * @param stdClass $course The course object
 * @param stdClass $context The context of the course
 */
function report_myfeedback_extend_navigation_course($navigation, $course, $context) {
    global $USER;
    $url = has_capability('report/myfeedback:modtutor', $context)
        ? new moodle_url('/report/myfeedback/index.php', array('userid' => $USER->id, 'currenttab' => 'mymodules')) :
            new moodle_url('/report/myfeedback/index.php', array('userid' => $USER->id));
    $navigation->add(get_string('pluginname', 'report_myfeedback'),
        $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
}

/**
 * This function extends the navigation with the My feedback report to the user's profile.
 *
 * @param core_user\output\myprofile\tree $tree The node to add to
 * @param stdClass $user The user object
 * @param bool $iscurrentuser Whether the logged-in user is current user
 * @param stdClass $course The course object
 * @return bool
 * @throws coding_exception
 * @throws moodle_exception
 */
function report_myfeedback_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    // For compatibility with v2.9 and later.
    $url = new moodle_url('/report/myfeedback/index.php', array(
            'userid' => $user->id,
            'sesskey' => sesskey()
    ));
    if (!empty($course)) {
        $url->param('course', $course->id);
    }
    $string = get_string('pluginname', 'report_myfeedback');
    $node = new core_user\output\myprofile\node('reports', 'myfeedbackreport', $string, null, $url);
    $tree->add_node($node);
    return true;
}


/**
 * Initialise JS to remove the "loading" spinner.
 */
function report_myfeedback_stop_spinner(): void {
    global $PAGE;

    $PAGE->requires->js_call_amd('report_myfeedback/main', 'init');
}
