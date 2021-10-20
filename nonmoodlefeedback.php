<?php

/**
 * Add/edit users non-Moodle(turnitin) feedback turnitin grade item
 *
 * @package   report_myfeedback
 * @author    Delvon Forrester <delvon@esparanza.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');
require_login();

global $CFG, $remotedb;
require_once($CFG->dirroot . '/report/myfeedback/lib.php');

$feedname = optional_param('feedname', '', PARAM_NOTAGS);
$gradeid = optional_param('gradeid2', 0, PARAM_INT);
$userid = optional_param('userid2', 0, PARAM_INT);
$instance = optional_param('instance', 0, PARAM_INT);

$report = new report_myfeedback();
$report->init();
$report->setup_ExternalDB();
if (!empty($feedname) && $gradeid2 && $userid2) {
    $feednotes = strip_tags($feedname, '<br>');
    $now = time();
    $sql = "SELECT feedback FROM {report_myfeedback}
                    WHERE userid=? AND gradeitemid=? AND iteminstance=?";
    $sql1 = "UPDATE {report_myfeedback} 
                    SET modifierid=?, feedback=?, timemodified=?   
                    WHERE userid=? AND gradeitemid=? AND iteminstance=?";
    $sql2 = "INSERT INTO {report_myfeedback}
                    (userid, gradeitemid, modifierid, iteminstance, feedback, timemodified)
                    VALUES (?, ?, ?, ?, ?, ?)";
    $params = array($userid, $gradeid, $instance);
    $params1 = array($USER->id, $feednotes, $now, $userid, $gradeid, $instance);
    $params2 = array($userid, $gradeid, $USER->id, $instance, $feednotes, $now);
    $userfeedback = $DB->get_record_sql($sql, $params);

    $event = \report_myfeedback\event\myfeedbackreport_addfeedback::create(
            array('context' => context_user::instance($userid), 'relateduserid' => $userid)
    );
    if ($userfeedback) {
        $remotedb->execute($sql1, $params1);
        echo get_string('updatesuccessful', 'report_myfeedback');
        $event = \report_myfeedback\event\myfeedbackreport_updatefeedback::create(
                array('context' => context_user::instance($userid), 'relateduserid' => $userid)
        );
    } else {
        $remotedb->execute($sql2, $params2);
        echo get_string('insertsuccessful', 'report_myfeedback');
    }

    $event->trigger();

    header('Location: index.php?userid=' . $userid . '&currenttab=feedback');
}
