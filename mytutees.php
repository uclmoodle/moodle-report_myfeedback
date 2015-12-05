<?php
 
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
 
defined('MOODLE_INTERNAL') || die;
 
echo '<p>'.get_string('studentsaccessto', 'report_myfeedback').'</p>';

$tutees = $report->get_all_accessible_users();
echo $tutees;
echo "<script type=\"text/javascript\">
   $(document).ready(function() {
    var usertable = $('#userstable').DataTable({
    'dom': 'lfrtip'
});
} ); 
   </script>";