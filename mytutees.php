<?php
 
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
 
defined('MOODLE_INTERNAL') || die;
 
echo '<p>'.get_string('studentsaccessto', 'report_myfeedback').'</p>';
$check = optional_param('mytick', '', PARAM_TEXT);
      
echo "<form method=\"POST\" id=\"alltutees\" action=\"\"><p>". get_string('alltutees', 'report_myfeedback').
        "<input id=\"myCheckbox\" type=\"checkbox\" name=\"mytick\" value=\"checked\" $check></form></p>";
 
$tutees = $report->get_all_accessible_users($check);
echo $tutees;
echo "<script type=\"text/javascript\">
   $(document).ready(function() {
        var usertable = $('#userstable').DataTable({
        'dom': 'lfrtip',
        'order': [[1, 'desc' ], [0, 'asc' ]]
    });
}); 
$('#myCheckbox').change(function(){
    $('#alltutees').submit();     
});
   </script>";
