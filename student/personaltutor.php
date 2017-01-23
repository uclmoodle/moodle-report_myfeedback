<?php

/*
 * The main file for the personal tutor tab
 * 
 * @package  report_myfeedback
 * @author    Delvon Forrester <delvon@esparanza.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$programme = '';
if ($mytutorid) {
    $mytutorobj = $remotedb->get_record('user', array('id' => $mytutorid));
    profile_load_data($mytutorobj);
    if (!$programme = $mytutorobj->profile_field_programmename) {
        //
    }
    echo "<p>" . get_string('overview_text_ptutor_tab', 'report_myfeedback') . "</p>";
    echo '<div class="userprofilebox clearfix">';
    echo '<div class="profilepicture">';
    echo $OUTPUT->user_picture($mytutorobj, array('size' => 100));
    echo '</div>';

    echo '<div class="descriptionbox"><div class="description">';

    echo '<h2 style="margin:-8px 0;">' . $mytutorobj->firstname . " " . $mytutorobj->lastname . '</h2>';

    echo $mytutorobj->department . "  " . $programme .
    '<br>' . get_string('email_address', 'report_myfeedback') .
    ' <a href="mailto:' . $mytutorobj->email . '">'. $mytutorobj->email .' </a><p> </p>';

    echo '</div></div>';
    if ($USER->id == $userid) {
        echo '<div class="personaltutoremail">
       <a href="mailto:' . $mytutorobj->email . '?Subject='.get_string("email_tutor_subject", "report_myfeedback").'">'
        . get_string("email_tutor", "report_myfeedback") . '</a></div>';
    }
    echo '</div>';
} else {
    echo get_string('notutor', 'report_myfeedback');
}
echo "<script type=\"text/javascript\">
    $(document).ready(function() {

$('#wait').css({'cursor':'default','display':'none'});
$('body').css('cursor', 'default');
 });
</script>";