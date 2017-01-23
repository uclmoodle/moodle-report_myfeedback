<?php

/*
 * The main file for the overview tab
 * 
 * @package  report_myfeedback
 * @author    Delvon Forrester <delvon@esparanza.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($user->lastlogin) {
    $userlastlogin = userdate($user->lastlogin) . "&nbsp; (" . format_time(time() - $user->lastaccess) . ")";
} else {
    $userlastlogin = get_string("never");
}

//get the added profile fields
$programme = '';
if (isset($user->profile_field_programmename)) {
    $programme = $user->profile_field_programmename;
}

echo '<div class="userprofilebox clearfix">';

if ($userid != $USER->id) {
    echo '<div class="profilepicture">';
    echo $OUTPUT->user_picture($user, array('size' => 125));
    echo '</div>';
    //}

    echo '<div class="descriptionbox"><div class="description">';

    echo '<h2 style="margin:-8px 0;">' . $userlinked . '</h2>';

    echo ($programme ? get_string('userprogramme', 'report_myfeedback') . $programme : '') . ($year ? ' (' . get_string("year", "report_myfeedback") . $year . ')' : '') .
    '<br>' . get_string('parentdepartment', 'report_myfeedback') .
    ' ' . $user->department . '<p> </p>';

    echo html_writer::span(get_string('lastmoodlelogin', 'report_myfeedback'));
    echo html_writer::span($userlastlogin);
    echo '</div>';
    echo '</div><div class="ac-year-right"><p>' . get_string('academicyear', 'report_myfeedback') . ':</p>';
    require_once(dirname(__FILE__) . '/academicyear.php');
    echo '</div></div>';

    //List of courses enrolled on
    $courselist = '';
    $limitcourse = 1;
    $allcourses = array();
    if ($allcourse = get_user_capability_course('report/myfeedback:student', $userid, $doanything = false, $fields = 'id,shortname,fullname,visible')) {//enrol_get_users_courses($userid, $onlyactive = TRUE)) {
        $cl = get_config('report_myfeedback');
        $lim = (isset($cl->overviewlimit) && $cl->overviewlimit ? $cl->overviewlimit : 9999);
        foreach ($allcourse as $eachc) {
            if ($eachc->visible && $limitcourse <= $lim) {
                $allcourses[] = $eachc;
                ++$limitcourse;
            }
        }
        $num = 0;
        $m1 = get_string('more', 'report_myfeedback');
        $m2 = get_string('moreinfo', 'report_myfeedback');
        $more = "<span><a href='$CFG->wwwroot/user/profile.php?id=$userid&showallcourses=1' title = '$m2' rel='tooltip'>$m1</a>";
        uasort($allcourses, function($a, $b) {
                    return strcasecmp($a->shortname, $b->shortname);
                });
        foreach ($allcourses as $eachcourse) {
            $courselist .= "<a href=\"" . $CFG->wwwroot . "/course/view.php?id=" . $eachcourse->id .
                    "\" title=\"" . $eachcourse->fullname . "\" rel=\"tooltip\">" . $eachcourse->shortname . "</a>";
            ++$num;
            if ($num < count($allcourses)) {
                $courselist .=", ";
            }
        }
        if ($lim < count($allcourse)) {
            $courselist .= $more;
        }
    }
    echo '<div class="enroltext">' . get_string('enrolledmodules', 'report_myfeedback') .
    ' <span class="enrolledon">' . $courselist . '</span></div>';
} else {//If user viewing own report end here
    echo '<div class="ac-year-right"><p>' . get_string('academicyear', 'report_myfeedback') . ':</p>';
    require_once(dirname(__FILE__) . '/academicyear.php');
    echo '</div>';
}

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

//before initializing Datatables get the cells in the second row of the header so you can reference them later on 
var filterCells = $('thead tr:eq(1) td');

// Initialize the DataTable
  var table = $('#grades').DataTable( {
    dom: 'RlfBrtip',
    fixedHeader: true,
    pageLength: 25,
    orderCellsTop: true,
    columnDefs: [
      { targets: [ 5, 8 ],
        searchable: false, 
        orderable:  false 
      }
    ],
    order: [[ 4, 'desc' ]],
    buttons: [ 'colvis' ],
    stateSave: true,
    stateSaveCallback: function(settings,data) {
      localStorage.setItem( 'Overview', JSON.stringify(data) )
    },
    stateLoadCallback: function(settings) {
    return JSON.parse( localStorage.getItem( 'Overview' ) )
    },
    responsive: true
  } );
  
  // Add filtering
  table.columns().every( function () {
    if ( this.searchable() ) {
      var that = this;

      // Create the `select` element
      var select = $('<select><option value=\"\"></option></select>')
        .appendTo(
          filterCells.eq( table.colReorder.transpose( this.index(), 'toOriginal' ) )
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
$('#tableDestroy').on( 'click', function () {
    table.colReorder.reset();
    table.destroy(false);
    $('thead select').val('').change();
    table.state.clear();
    location.reload();
} );
 
$('#exportexcel').on( 'click', function() {
window.location.href= 'export.php';
});
 
$('#reportPrint').on( 'click', function () {
        print();
});

$('#toggle-grade').on( 'click', function () {
$('.t-rel').toggleClass('off');
});

} ); 
   </script>";
