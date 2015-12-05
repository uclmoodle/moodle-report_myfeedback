<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

defined('MOODLE_INTERNAL') || die;

$programme = '';
if ($mytutorid) {
    $mytutorobj = $remotedb->get_record('user', array('id' => $mytutorid));
    profile_load_data($mytutorobj);
    if (!$programme = $mytutorobj->profile_field_programmename) {
        //
    }

    echo '<div class="userprofilebox clearfix">';
    echo '<div class="profilepicture">';
    echo $OUTPUT->user_picture($mytutorobj, array('size' => 100));
    echo '</div>';

    echo '<div class="descriptionbox"><div class="description">';

    echo '<h2 style="margin:-8px 0;">' . $mytutorobj->firstname . " " . $mytutorobj->lastname . '</h2>';

    echo $mytutorobj->department . "  " . $programme .
    '<br>' . get_string('email_address', 'report_myfeedback') .
    ' ' . $mytutorobj->email . '<p> </p>';

    echo '</div></div><div class="personaltutoremail">
       <a href="mailto:' . $mytutorobj->email . '?Subject=Your%20Personal%20Tutee">'
    . get_string("email_tutor", "report_myfeedback") . '</a></div></div>';
} else {
   echo get_string('notutor', 'report_myfeedback');
}