<?php

/**
 * Add/edit users reflective notes per grade item
 *
 * @package   report_myfeedback
 * @author    Delvon Forrester <delvon@esparanza.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_login();

global $CFG, $remotedb;
require_once($CFG->dirroot . '/report/myfeedback/lib.php');

$notename = optional_param('notename', '', PARAM_NOTAGS);
$gradeid = optional_param('gradeid', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);
$instance = optional_param('instance1', 0, PARAM_INT);

$report = new report_myfeedback();
$report->init();
$report->setup_ExternalDB();
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
        $remotedb->execute($sql1, $params1);
        echo get_string('updatesuccessful', 'report_myfeedback');
        $event = \report_myfeedback\event\myfeedbackreport_updatenotes::create(
                array('context' => context_user::instance($userid), 'relateduserid' => $userid)
        );
    } else {
        $remotedb->execute($sql2, $params2);
        echo get_string('insertsuccessful', 'report_myfeedback');
    }

    $event->trigger();

    header('Location: index.php?userid=' . $userid . '&currenttab=feedback');
}
