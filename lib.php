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

defined('MOODLE_INTERNAL') || die;

/**
 * This function extends the navigation with the report items.
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param stdClass $context The context of the course
 */
function report_myfeedback_extend_navigation(global_navigation $navigation) {
    $url = new moodle_url('/report/myfeedback/index.php', array('course' => $course->id));
    $navigation->add(get_string('pluginname', 'report_myfeedback'), $url, null, null, new pix_icon('i/report', ''));
}

function report_myfeedback_extend_navigation_user($navigation, $user, $course) {
    $url = new moodle_url('/report/myfeedback/index.php' , array('userid' => $user->id));
    $navigation->add(get_string('pluginname', 'report_myfeedback'), $url, navigation_node::TYPE_SETTING, null, null,
            new pix_icon('i/report', ''));
}

function report_myreport_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('report/myreport:view', $context)) {
        $url = new moodle_url('/report/myfeedback/index.php', array('id' => $course->id));
        $navigation->add(get_string('pluginname', 'report_myfeedback'), $url, navigation_node::TYPE_SETTING, null, null,
                new pix_icon('i/report', ''));
    }
}