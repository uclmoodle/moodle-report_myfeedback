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
echo '<div class="fullhundred clearfix">
        <div class="mymods-container">
          <div class="userprofilebox clearfix">
            <div class="profilepicture">';
echo $OUTPUT->user_picture($user, array('size' => 100));
      echo '</div>';
//}

      echo '<div class="descriptionbox">
              <div class="description">';

//if ($userid != $USER->id) {
echo '<h2 style="margin:-8px 0;">' . $user->firstname . " " . $user->lastname . '</h2>';
//}
//TODO - After integration project - get the Programme and affiliated department

echo $user->department . "  " . $programme .
 '<br><p> </p>';
echo html_writer::span(get_string('lastmoodlelogin', 'report_myfeedback'));
echo html_writer::span($userlastlogin);
        echo '</div>';

$courselist = '';
$num = 0;
if ($my_mods) {
    foreach ($my_mods as $eachcourse) {
        $courselist .= "<span class=\"en-course\">" . $eachcourse->shortname . "</span><a href=\"" .
                $CFG->wwwroot . "/course/view.php?id=" . $eachcourse->id .
                "\" >" . ":" . $eachcourse->fullname . "</a>";
        ++$num;
        if ($num < count($my_mods)) {
            $courselist .=", ";
        }
    }
    echo '</div></div>';
    echo '<p class="enroltext">' . get_string('modulesteach', 'report_myfeedback') .
    ' <span class="enrolledon">' . $courselist . '</span></p></div>
            <div class="mymods-container-right">
            <p class="mymods-content personaltutoremail">
            <a href="https://evision.ucl.ac.uk/urd/sits.urd/run/siw_lgn" target="_blank">Student Record System</a></p>  
             
            <p class="mymods-content personaltutoremail">Download grades</p>
            </div></div>';
    echo '<div class="fullhundred db">';
    foreach ($my_mods as $mymod) {
        $mod_context = context_course::instance($mymod->id);
        if ($all_enrolled_users = get_enrolled_users($mod_context, $cap = 'mod/assign:submit', $groupid = 0, $userfields = 'u.id', $orderby = null, $limitfrom = 0, $limitnum = 0, $onlyactive = true)) {

            $uids = array();
            foreach ($all_enrolled_users as $uid) {
                $uids[] = $uid->id;
            }
        }
        
        echo '<h3>' . $mymod->shortname . ': ' . $mymod->fullname . '</h3>';
        if ($zscore = $report->get_z_score($mymod->id, $uids)) {
            echo $zscore;
        }
        echo '<p>&nbsp;</p>';
    }
    echo '</div>';
}