<?php

/**
 * Add/edit users reflective notes per grade item
 *
 * @package   report_myfeedback
 * @author    Delvon Forrester <delvon@esparanza.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
global $CFG, $remotedb;
require_once($CFG->dirroot . '/report/myfeedback/lib.php');
$report = new report_myfeedback();
$report->init();
$report->setup_ExternalDB();
if (isset($_POST['notename']) && isset($_POST['grade_id']) && isset($_POST['userid'])) {
    $usr_id = $_POST['userid'];
    $grade_id = $_POST['grade_id'];
    $instance = $_POST['instance1'];
    $r_notes = strip_tags($_POST['notename'], '<br>');
    $now = time();
    $sql = "SELECT notes FROM {report_myfeedback}
                    WHERE userid=? AND gradeitemid=? AND iteminstance=?";
    $sql1 = "UPDATE {report_myfeedback} 
                    SET modifierid=?, notes=?, timemodified=? 
                    WHERE userid=? AND gradeitemid=? AND iteminstance=?";
    $sql2 = "INSERT INTO {report_myfeedback}
                    (userid, gradeitemid, modifierid, iteminstance, notes, timemodified)
                    VALUES (?, ?, ?, ?, ?, ?)";
    $params = array($usr_id, $grade_id, $instance);
    $params1 = array($USER->id, $r_notes, $now, $usr_id, $grade_id, $instance);
    $params2 = array($usr_id, $grade_id, $USER->id, $instance, $r_notes, $now);
    $usernotes = $DB->get_record_sql($sql, $params);

    $event = \report_myfeedback\event\myfeedbackreport_addnotes::create(array('context' => context_user::instance($usr_id), 'relateduserid' => $usr_id));
    if ($usernotes) {
        $remotedb->execute($sql1, $params1);
        echo get_string('updatesuccessful', 'report_myfeedback');
        $event = \report_myfeedback\event\myfeedbackreport_updatenotes::create(array('context' => context_user::instance($usr_id), 'relateduserid' => $usr_id));
    } else {
        $remotedb->execute($sql2, $params2);
        echo get_string('insertsuccessful', 'report_myfeedback');
    }
 
    $event->trigger();

    header('Location: index.php?userid=' . $usr_id . '&currenttab=feedback');
}