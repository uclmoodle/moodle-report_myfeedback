<?php
 
/*
 * The main file for the My students tab
 * 
 * @package  report_myfeedback
 * @author    Delvon Forrester <delvon@esparanza.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
defined('MOODLE_INTERNAL') || die;

echo '<p>'.get_string('studentsaccessto', 'report_myfeedback').'</p>';
//$check = optional_param('mytick', '', PARAM_TEXT);
      
/*echo "<form method=\"POST\" id=\"alltutees\" action=\"\"><p>". get_string('alltutees', 'report_myfeedback').
        "<input id=\"myCheckbox\" type=\"checkbox\" name=\"mytick\" value=\"checked\" $check></form></p>";*/
$tutees = $report->get_all_accessible_users($personal_tutor,$searchuser,$module_tutor,$prog);
echo $tutees;
$event = \report_myfeedback\event\myfeedbackreport_viewed_mystudents::create(array('context' => context_user::instance($USER->id), 'relateduserid' => $userid));
$event->trigger();
echo "<script type=\"text/javascript\">
   $(document).ready(function() {
   $('#wait').css({'cursor':'default','display':'none'});
   $('body').css('cursor', 'default');

        var usertable = $('#userstable').DataTable({
        'dom': 'rtip',
        'order': [[1, 'desc' ], [0, 'asc' ]]
    });
}); 
$('#myCheckbox').change(function(){
    $('#alltutees').submit();     
});

   </script>";
