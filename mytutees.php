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
 * The main file for the My students tab
 *
 * @package  report_myfeedback
 * @copyright 2022 UCL
 * @author    Delvon Forrester <delvon@esparanza.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$PAGE->requires->js_call_amd('report_myfeedback/mytutees', 'init');

$tutorview = $report->get_dashboard_capability($USER->id, 'report/myfeedback:modtutor') ||
        $report->get_dashboard_capability($USER->id, 'report/myfeedback:personaltutor', $usercontext->id);
if (!($canaccessuser && $tutorview)) {
    echo report_myfeedback_stop_spinner();
    throw new moodle_exception('nopermissions', '', $PAGE->url->out(), get_string('viewtutorreports', 'report_myfeedback'));
}

echo '<p>'.get_string('studentsaccessto', 'report_myfeedback').'</p>';

$tutees = $report->get_all_accessible_users($personaltutor, $moduletutor, $prog, $searchuser);
echo $tutees;
$event = \report_myfeedback\event\myfeedbackreport_viewed_mystudents::create(
    array('context' => context_user::instance($USER->id), 'relateduserid' => $userid)
);
$event->trigger();
