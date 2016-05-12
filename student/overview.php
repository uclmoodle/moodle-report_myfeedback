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

if (!isset($user->profile_field_programmename))
{
    $user->profile_field_programmename = '';
}

//get the added profile fields
$programme = '';
if (!$programme = $user->profile_field_programmename) {
    //
}
 
echo '<div class="userprofilebox clearfix">';
 
if ($userid != $USER->id) {
    echo '<div class="profilepicture">';
    echo $OUTPUT->user_picture($user, array('size' => 125));
    echo '</div>';
    //}
 
    echo '<div class="descriptionbox"><div class="description">';
 
    echo '<h2 style="margin:-8px 0;">' . $userlinked . '</h2>';
 
    echo ($programme ? get_string('programme', 'report_myfeedback') . $programme : '') . ($year ? ' (Year ' . $year . ')' : '') .
    '<br>' . get_string('parentdepartment', 'report_myfeedback') .
    ' ' . $user->department . '<p> </p>';
 
    echo html_writer::span(get_string('lastmoodlelogin', 'report_myfeedback'));
    echo html_writer::span($userlastlogin);
    echo '</div>';
    echo '</div>';
    echo '</div>';
 
//List of courses enrolled on
    $courselist = '';
    if ($allcourses = enrol_get_users_courses($userid, $onlyactive = TRUE)) {
        $num = 0;
        foreach ($allcourses as $eachcourse) {
            $courselist .= "<span class=\"en-course\">" . $eachcourse->shortname . "</span><a href=\"" .
                    $CFG->wwwroot . "/course/view.php?id=" . $eachcourse->id .
                    "\" >" . ":" . $eachcourse->fullname . "</a>";
            ++$num;
            if ($num < count($allcourses)) {
                $courselist .=", ";
            }
        }
    }
    /* echo '<p class="enroltext">' . get_string('enrolledmodules', 'report_myfeedback') .
      ' <span class="enrolledon">' . $courselist . '</span></p>'; */
}//If user viewing own report end here
 
$content = $report->get_content($currenttab);
echo $content->text;
echo $OUTPUT->container_start('info');
echo $OUTPUT->container_end();

echo '</div>';

// Enable sorting.
echo "<script type=\"text/javascript\">
   $(document).ready(function() {
    var table = $('#grades').DataTable({
    'dom': 'CRlfrtip',
    'order': [[4, 'desc' ]],
    responsive: true
});
 
$('#tableDestroy').on( 'click', function () {
    table.colReorder.reset();
    table.destroy(false);
    table = $('#grades').DataTable({
    'dom': 'CRlfrtip',
    'order': [[4, 'desc' ]],
    responsive: true
});
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
 
table.columns().flatten().each( function ( colIdx ) {
    // Create the select list and search operation
    var select = $('<select><option value=\"\"></option></select>')
        .appendTo(
            table.column(colIdx).footer()
        )
        .on( 'change', function () {
            table
                .column( colIdx )
                .search( $(this).val() )
                .draw();
        } );
  
    // Get the search data for the first column and add to the select list
    table
        .column( colIdx )
        .cache( 'search' )
        .sort()
        .unique()
        .each( function ( d ) {
            select.append( $('<option value=\"'+d+'\">'+d+'</option>') );
        } );
} );
} ); 
   </script>";