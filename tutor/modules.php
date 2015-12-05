<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

defined('MOODLE_INTERNAL') || die;

$modid = $_REQUEST['currenttab'];
if ($cse_id = $report->get_course_id_from_shortname($modid)) {
    //$modid_array = array($cid->id);
    $cid = $cse_id->id;
    $cse_name = $cse_id->fullname;
    echo html_writer::tag('h3', $modid . ': ' . $cse_name);
    echo html_writer::tag('p', get_string('programme', 'report_myfeedback').'<br>'.get_string('department', 'report_myfeedback'));
    $modcontext = context_course::instance($cid);
    if ($all_enrolled_users = get_enrolled_users($modcontext, $cap = 'mod/assign:submit', $groupid = 0, $userfields = 'u.id', $orderby = null, $limitfrom = 0, $limitnum = 0, $onlyactive = true)) {

        $uids = array();
        foreach ($all_enrolled_users as $uid) {
            $uids[] = $uid->id;
        }
    }
    echo html_writer::tag('p', get_string('enrolledstudents', 'report_myfeedback'). count($uids)); 
    echo html_writer::tag('h4', get_string('overallmodule', 'report_myfeedback'));
    //$student_totals = grade_get_course_grade($userid, $modid_array);
    if ($zscore = $report->get_z_score($cid, $uids)) {
        echo $zscore;
    }
}