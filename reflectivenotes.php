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
 * Add/edit users reflective notes per grade item
 *
 * @package   report_myfeedback
 * @copyright  2022 UCL
 * @author    Delvon Forrester <delvon@esparanza.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_login();

global $CFG, $currentdb;
require_once($CFG->dirroot . '/report/myfeedback/lib.php');

$notename = optional_param('notename', '', PARAM_NOTAGS);
$gradeid = optional_param('gradeid', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);
$instance = optional_param('instance1', 0, PARAM_INT);

$report = new report_myfeedback\local\report();
$report->init();
$report->setup_external_db();
if (!empty($notename) && $gradeid && $userid) {
    $reflectivenotes = strip_tags($notename, '<br>');
    $now = time();
    $sql = "SELECT notes FROM {report_myfeedback}
                    WHERE userid=? AND gradeitemid=? AND iteminstance=?";
    $sql1 = "UPDATE {report_myfeedback}
                    SET modifierid=?, notes=?, timemodified=?
                    WHERE userid=? AND gradeitemid=? AND iteminstance=?";
    $sql2 = "INSERT INTO {report_myfeedback}
                    (userid, gradeitemid, modifierid, iteminstance, notes, timemodified)
                    VALUES (?, ?, ?, ?, ?, ?)";
    $params = array($userid, $gradeid, $instance);
    $params1 = array($USER->id, $reflectivenotes, $now, $userid, $gradeid, $instance);
    $params2 = array($userid, $gradeid, $USER->id, $instance, $reflectivenotes, $now);
    $usernotes = $DB->get_record_sql($sql, $params);

    $event = \report_myfeedback\event\myfeedbackreport_addnotes::create(
            array('context' => context_user::instance($userid), 'relateduserid' => $userid)
    );
    if ($usernotes) {
        $currentdb->execute($sql1, $params1);
        echo get_string('updatesuccessful', 'report_myfeedback');
        $event = \report_myfeedback\event\myfeedbackreport_updatenotes::create(
                array('context' => context_user::instance($userid), 'relateduserid' => $userid)
        );
    } else {
        $currentdb->execute($sql2, $params2);
        echo get_string('insertsuccessful', 'report_myfeedback');
    }

    $event->trigger();

    redirect(new \moodle_url('/report/myfeedback/index.php',
        [
            'userid' => $userid,
            'currenttab' => 'feedback'
        ]
    ));
}
