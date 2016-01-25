<?php
 
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
 
defined('MOODLE_INTERNAL') || die;
if ($user->lastlogin) {
    $userlastlogin = userdate($user->lastlogin) . "&nbsp; (" . format_time(time() - $user->lastaccess) . ")";
} else {
    $userlastlogin = get_string("never");
}
if (!$programme = $user->profile_field_programmename) {
    //
}
echo '<div class="userprofilebox clearfix">
            <div class="profilepicture">';
echo $OUTPUT->user_picture($user, array('size' => 80));
      echo '</div>';
//}

      echo '<div class="descriptionbox">
              <div class="description">';

//if ($userid != $USER->id) {
echo '<h2 style="margin:-8px 0;">' . $user->firstname . " " . $user->lastname . '</h2>';
//}
//TODO - After integration project - get the Programme and affiliated department

echo $user->department . "  " . $programme .
 '<br>' . get_string('email_address', 'report_myfeedback') .
    ' ' . $user->email;

echo '</div></div></div>';