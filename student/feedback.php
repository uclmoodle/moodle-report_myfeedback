<?php
 
/*
 * The main file for the feedback comments tab
 * 
 * @package  report_myfeedback
 * @author    Delvon Forrester <delvon@esparanza.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
defined('MOODLE_INTERNAL') || die;
echo "<div class=\"ac-year-right\"><p>" . get_string('academicyear', 'report_myfeedback') . ":</p>";
require_once(dirname(__FILE__) . '/academicyear.php');
echo "</div>";
$archiveyear = substr_replace($res, '-', 2, 0); //for building the archive link
$arch = $res;
$pos = stripos($CFG->wwwroot, $archiveyear);
/*if (!$personal_tutor && !$progadmin && !is_siteadmin() && !$archivedinstance && $pos === false && $res != 'current') {
    //echo '<script>location.replace("http://127.0.0.1:88/v288/report/myfeedback/index.php?userid=' . $userid . '&currenttab=' . $currenttab . '");</script>';
    echo '<script>location.replace("'.$archivedomain.$archiveyear.'/report/myfeedback/index.php?userid='.$userid.'&currenttab='.$currenttab.'");</script>';
}
if (!$personal_tutor && !$progadmin && !is_siteadmin() && $archivedinstance && $pos === false && $res != 'current') {
    //echo '<script>location.replace("http://127.0.0.1:88/v288/report/myfeedback/index.php?userid=' . $userid . '&currenttab=' . $currenttab . '");</script>';
    echo '<script>location.replace("'.$archivedomain.$archiveyear.'/report/myfeedback/index.php?userid='.$userid.'&currenttab='.$currenttab.'");</script>';
}
if (isset($yrr->archivedinstance) && $yrr->archivedinstance && $res == 'current') {//Go back to the live domain which is the current academic year
    //echo '<script>location.replace("http://127.0.0.1:88/v2810/report/myfeedback/index.php?userid=' . $userid . '&archive=yes&currenttab=' . $currenttab . '");</script>';
    echo '<script>location.replace("'.$livedomain.'report/myfeedback/index.php?userid='.$userid.'&archive=yes&currenttab='.$currenttab.'");</script>';
}*/
if (!$personal_tutor && !$progadmin && !is_siteadmin()) {
    $res = '';//If all else fails it should check only it's current database
}
$report->setup_ExternalDB($res);
$content = $report->get_content($currenttab, $personal_tutor, $progadmin, $arch);
echo $content->text;
echo $OUTPUT->container_start('info');
echo $OUTPUT->container_end();
// Enable sorting.
echo "<script type=\"text/javascript\">  
/* Plugin API method to determine is a column is sortable */
$.fn.dataTable.Api.register( 'column().searchable()', function () {
  var ctx = this.context[0];
  return ctx.aoColumns[ this[0] ].bSearchable;
} );

$(document).ready( function () {

$('#wait').css({'cursor':'default','display':'none'});
$('body').css('cursor', 'default');

var filterCells = $('thead tr:eq(1) td');

  // Create the DataTable
  var feedbacktable = $('#feedbackcomments').DataTable({
    dom: 'RlfBrtip',
    fixedHeader: true,
    pageLength: 25,
    orderCellsTop: true,
    columnDefs: [
      { targets: [ 5, 6, 7 ],
        searchable: false, 
        orderable:  false 
      }
    ],
    order: [[ 3, 'desc' ]],
    buttons: [ 'colvis' ],
    stateSave: true,
    stateSaveCallback: function(settings,data) {
      localStorage.setItem( 'Feedback', JSON.stringify(data) )
    },
    stateLoadCallback: function(settings) {
    return JSON.parse( localStorage.getItem( 'Feedback' ) )
    },
    responsive: true
  } );
  
  // Add filtering
  feedbacktable.columns().every( function () {
    if ( this.searchable() ) {
      var that = this;

      // Create the `select` element
      var select = $('<select><option value=\"\"></option></select>')
        .appendTo(
          filterCells.eq( feedbacktable.colReorder.transpose( this.index(), 'toOriginal' ) )
        )
        .on( 'change', function() {
          that
            .search($(this).val())
            .draw();
        } );

      // Add data
      this
        .data()
        .sort()
        .unique()
        .each( function(d) {
          select.append($('<option>' + d + '</option>'));
        } );
      
      // Restore state saved values
      var state = this.state.loaded();
      if ( state ) {
        var val = state.columns[ this.index() ];
        select.val( val.search.search );
      }
    }
  } );

//when button is clicked to reset table
$('#ftableDestroy').on( 'click', function () {
    feedbacktable.colReorder.reset();
    feedbacktable.destroy(false);
    $('thead select').val('').change();
    feedbacktable.state.clear();
    location.reload();
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