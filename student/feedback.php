<?php
 
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
 
defined('MOODLE_INTERNAL') || die;
 
$content = $report->get_content($currenttab);
echo $content->text;
echo $OUTPUT->container_start('info');
echo $OUTPUT->container_end();
// Enable sorting.
echo "<script type=\"text/javascript\">
   $(document).ready(function() {                 
var feedbacktable = $('#feedbackcomments').DataTable({
    'dom': 'CRlfrtip',
    'order': [[ 3, 'desc' ]],
    responsive: true
});
 
$('#ftableDestroy').on( 'click', function () {
    feedbacktable.colReorder.reset();
    feedbacktable.destroy(false);    
    feedbacktable = $('#feedbackcomments').DataTable({
    'dom': 'CRlfrtip',
    'order': [[ 3, 'desc' ]],
    responsive: true
});
} );
 
$('#exportexcel').on( 'click', function() {
window.location.href= 'export.php';
});
 
$('#reportPrint').on( 'click', function () {
        print();
});
 
} ); 
   </script>";