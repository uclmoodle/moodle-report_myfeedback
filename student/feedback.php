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

$('#feedbackcomments').on('click', '.addnote', function () {
   var gradeid = $(this).data('gid');
   var instn = $(this).data('inst');
   $('#grade_id').val(gradeid);
   $('#instance1').val(instn);
   $('#user_id').val($(this).data('uid'));
   $('#notename').val($('#note-val'+gradeid+instn).text());
   $('#Abs2').modal('show');
});

$('#feedbackcomments').on('click', '.addfeedback', function () {
   var gradeid2 = $(this).data('gid');
   var instn = $(this).data('inst');
   $('#grade_id2').val(gradeid2);
   $('#instance').val(instn);
   $('#user_id2').val($(this).data('uid'));
   $('#feedname').val($('#feed-val'+gradeid2+instn).text());
   $('#Abs1').modal('show');
});
} ); 
   </script>";