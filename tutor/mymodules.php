<?php

/*
 * The main file for the Module tutor dashboard
 * 
 * @package  report_myfeedback
 * @author    Delvon Forrester <delvon@esparanza.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($USER->lastlogin) {
    $userlastlogin = userdate($USER->lastlogin) . "&nbsp; (" . format_time(time() - $USER->lastaccess) . ")";
} else {
    $userlastlogin = get_string("never");
}
//get the programme they are on for the user from their profile
//This requires that the below profile field is added
$programme = '';
if (isset($USER->profile_field_programmename)) {
    $programme = $USER->profile_field_programmename;
}
echo "<p>" . get_string('overview_text_mtutor', 'report_myfeedback') . "</p>";
echo '<div class="fullhundred clearfix">
        <div class="mymods-container">
          <div class="userprofilebox clearfix">
            <div class="profilepicture">';
echo $OUTPUT->user_picture($USER, array('size' => 100));
echo '</div>';
//}

echo '<div class="descriptionbox">
              <div class="description">';

//if ($userid != $USER->id) {
echo '<h2 style="margin:-8px 0;">' . $USER->firstname . " " . $USER->lastname . '</h2>';
//}

echo $USER->department . "  " . $programme .
 '<br><p> </p>';
echo html_writer::span(get_string('lastmoodlelogin', 'report_myfeedback'));
echo html_writer::span($userlastlogin);
echo '</div>';

$courselist = '';
$num = 0;
$my_tutor_mods = array();
//Get all courses the logged-in user has modtutor capability in
//Used this function as it gets courses added at category level as well - So courses they may have the capability in under other users.
if ($tutor_mods = get_user_capability_course('report/myfeedback:modtutor', $USER->id, $doanything = false, $fields = 'visible,shortname,fullname')) {//enrol_get_users_courses($USER->id, $onlyactive = TRUE)) {
    foreach ($tutor_mods as $value) {
        if ($value->visible) {
            $my_tutor_mods[] = $value;
        }
    }
}
//Sort the module in aplphabetic order but case-insensitive
uasort($my_tutor_mods, function($a, $b) {
            return strcasecmp($a->shortname, $b->shortname);
        });

if ($my_tutor_mods) {
    foreach ($my_tutor_mods as $eachcourse) {
        $courselist .= "<a href=\"" . $CFG->wwwroot . "/course/view.php?id=" . $eachcourse->id .
                "\" title=\"" . $eachcourse->fullname . "\" rel=\"tooltip\">" . $eachcourse->shortname . "</a>";
        ++$num;
        if ($num < count($my_tutor_mods)) {
            $courselist .=", ";
        }
    }
}
echo '</div></div>';
/* echo '<p class="enroltext">' . get_string('modulesteach', 'report_myfeedback') .
  ' <span class="enrolledon">' . $courselist . '</span></p> */
echo '</div><div class="mymods-container-right">
            <p class="personaltutoremail">
            <a href="' . get_string('studentrecordsystemlink', 'report_myfeedback') . '" target="_blank">' . get_string('studentrecordsystem', 'report_myfeedback') . '</a></p>  
            <span class="personaltutoremail reportPrint"  title="'.get_string('print_msg', 'report_myfeedback').'" rel="tooltip"><a href="#">' . get_string('print_report', 'report_myfeedback') . 
        '</a><img id="reportPrint" src="' . 'pix/info.png' . '" ' . ' alt="-"/></span>
            <p class="personaltutoremail ex_port">
            <a href="#">' . get_string('export_to_excel', 'report_myfeedback') . '</a></p>
            </div></div>';
//Get the late feedback days from config
/*$lt = get_config('report_myfeedback');
$a = new stdClass();
$a->lte = isset($lt->latefeedback) ? $lt->latefeedback : 28;*/
// Setup the heading for the Personal tutor dashboard
$assessmentmsg = get_string('tutortblheader_assessment_info', 'report_myfeedback');
$assessmenticon = '<img class="studentimgdue" src="' . 'pix/info.png' . '" ' .
        ' alt="-" title="' . $assessmentmsg . '" rel="tooltip"/>';
//$latefeedbackmsg = get_string('tutortblheader_latefeedback_info', 'report_myfeedback',$a);
//$latefeedbackicon = '<img  class="studentimgfeed" src="' . 'pix/info.png' . '" ' .
  //      ' alt="-" title="' . $latefeedbackmsg . '" rel="tooltip"/>';
$overallgrademsg = get_string('tutortblheader_overallgrade_info', 'report_myfeedback');
$overallgradeicon = '<img class="studentimgall" src="' . 'pix/info.png' . '" ' .
        ' alt="-" title="' . $overallgrademsg . '" rel="tooltip"/>';
$nonsubmissionmsg = get_string('tutortblheader_nonsubmissions_info', 'report_myfeedback');
$nonsubmissionicon = '<img class="studentimgnon" src="' . 'pix/info.png' . '" ' .
        ' alt="-" title="' . $nonsubmissionmsg . '" rel="tooltip"/>';
$latesubmissionmsg = get_string('tutortblheader_latesubmissions_info', 'report_myfeedback');
$latesubmissionicon = '<img class="studentimglate" src="' . 'pix/info.png' . '" ' .
        ' alt="-" title="' . $latesubmissionmsg . '" rel="tooltip"/>';
$lowgrademsg = get_string('tutortblheader_lowgrades_info', 'report_myfeedback');
$lowgradeicon = '<img  class="studentimglow" src="' . 'pix/info.png' . '" ' .
        ' alt="-" title="' . $lowgrademsg . '" rel="tooltip"/>';
$grademsg = get_string('tutortblheader_graded_info', 'report_myfeedback');
$gradeicon = '<img  class="studentimggraded" src="' . 'pix/info.png' . '" ' .
        ' alt="-" title="' . $grademsg . '" rel="tooltip"/>';

$exceltable = array();
$x = 0;
$zscore = new stdClass();
$zscore->users = array();
$zscore->assess = array();
//echo "<div class=\"ac-year-right\"><p>" . get_string('academicyear', 'report_myfeedback') . ":</p>";
//require_once(dirname(__FILE__) . '/../student/academicyear.php');
//echo '</div>';
$report->setup_ExternalDB();
$my_tutor_mods = array();
if ($archive_mods = get_user_capability_course('report/myfeedback:modtutor', $USER->id, $doanything = false, $fields = 'visible,shortname,fullname,category')) {
    foreach ($archive_mods as $value) {
        if ($value->visible && $value->id) {
            $my_tutor_mods[] = $value;
        }
    }
}
uasort($my_tutor_mods, function($a, $b) {
            return strcasecmp($a->shortname, $b->shortname);
        });
if ($my_tutor_mods) {
    require_once(dirname(__FILE__) . '/modules.php');
    // Segun Babalola 2019-07-15.
    // Commenting out the "require_once" line below because it is causing a warning in the UI:
    // "Class coursecat is now alias to autoloaded class core_course_category, "
    // "course_in_list is an alias to core_course_list_element. "
    // "Class coursecat_sortable_records is deprecated without replacement. Do not include coursecatlib.php"
    // Looks like the implementation of class autoloading means the require_once statement is no longer needed.
    // require_once($CFG->libdir . '/coursecatlib.php');
    echo '<div class="fullhundred db">';
    foreach ($my_tutor_mods as $t) {
        foreach ($m as $v) {
            $s_name = $mod_name = $prog = $dept = $cse_name = '';
            $my_id = 0;
            $modtable = "<table id=\"modtable" . $t->id . "\" class=\"modtable\" width=\"100%\" border=\"1\" style=\"text-align:center\">
                <thead>
                            <tr class=\"tableheader\">
                                <th>" .
                    get_string('tutortblheader_name', 'report_myfeedback') . "</th>
                                <th class='overallgrade'>" .
                    get_string('tutortblheader_overallgrade', 'report_myfeedback') . " $overallgradeicon</th>
                                <th><span class='studentsassessed'>" .
                    get_string('tutortblheader_assessment', 'report_myfeedback') . "</span> $assessmenticon</th>
                                <th>" .
                    get_string('tutortblheader_nonsubmissions', 'report_myfeedback') . " $nonsubmissionicon</th>
                                <th>" .
                    get_string('tutortblheader_latesubmissions', 'report_myfeedback') . " $latesubmissionicon</th>
                                <th>" .
                    get_string('tutortblheader_graded', 'report_myfeedback') . "  $gradeicon</th>
                                <th>" .
                    get_string('tutortblheader_lowgrades', 'report_myfeedback') . " $lowgradeicon</th> 
                           </tr></thead><tbody>";

            $modscore = '';
            $modgraph = '';
            $due = $nonsub = $latesub = $graded = $lowgrades = $latefeedback = 0;
            if ($v == $t->shortname) {
                $mod_context = context_course::instance($t->id);
                $my_id = $t->category;
                $s_name = $t->shortname;
                $mod_name = $t->fullname;
                if ($get_cat = $report->get_prog_admin_dept_prog(array($t), true)) {
                    $dept = $get_cat['dept'];
                    $prog = $get_cat['prog'];
                }

                $cse_name .= '<div class="fullRec">' . ($mod_name && $s_name ? '<h3 style="color: #444"><b>' . $s_name . ': ' . $mod_name . '</b></h3>' : '');
                $cse_name .= ($dept ? get_string('faculty', 'report_myfeedback') . $dept . '<br>' : '');
                $cse_name .= ($prog ? get_string('programme', 'report_myfeedback') . $prog . '<br>' : '');

                $uids = array();
                if ($all_enrolled_users = get_enrolled_users($mod_context, $cap = 'report/myfeedback:student', $groupid = 0, $userfields = 'u.id', $orderby = null, $limitfrom = 0, $limitnum = 0, $onlyactive = true)) {
                    foreach ($all_enrolled_users as $uid) {
                        $uids[] = $uid->id;
                    }
                }

                if (count($uids) > 0) {
                    if ($zscore = $report->get_module_z_score($t->id, $uids, true)) {
                        $modgraph = ''; //'<h3 style="color: #444"><b>Overall Module</b></h3>' . $zscore->modgraph;
                        $modscore = $zscore->graph;
                        $due = $zscore->due;
                        $nonsub = $zscore->nonsub;
                        $latesub = $zscore->latesub;
                        $graded = $zscore->graded;
                        //$latefeedback = $zscore->latefeedback;
                        $lowgrades = $zscore->lowgrades;
                        $cse_name .= get_string('enrolledstudents', 'report_myfeedback') . count($uids) . '<br>';
                    }
                }
                $modtable .= $modscore;
                $modtable .= '</tbody></table>';
                if (!$modscore) {
                    $modtable = '<h2><i style="color:#5A5A5A">' . get_string('nodatatodisplay', 'report_myfeedback') . '</i></h2>';
                }
                echo $cse_name;
                if ($modscore) {
                    echo $modgraph . '<p style = "margin: 20px 0"><span class="aToggle" style="background-color:#619eb6;color:#fff">'
                    . get_string('assessmentbreakdown', 'report_myfeedback') . '</span><span class="sToggle">' . get_string('studentbreakdown', 'report_myfeedback') . '</span></p>';
                }
                echo $modtable . '</div>';

                // The full excel downloadable table
                $exceltable[$x]['Name'] = $s_name . ':' . $mod_name;
                $exceltable[$x]['LName'] = '';
                $exceltable[$x]['Assessments'] = $due;
                $exceltable[$x]['Nonsubmission'] = $nonsub;
                $exceltable[$x]['Latesubmission'] = $latesub;
                $exceltable[$x]['Graded'] = $graded;
                //$exceltable[$x]['Latefeedback'] = $latefeedback;
                $exceltable[$x]['Lowgrade'] = $lowgrades;
                //$exceltable[$x]['score'] = '';
                ++$x;

                foreach ($zscore->assess as $key => $eachassess) {
                    // Each assessment excel downloadable table
                    $exceltable[$x]['Name'] = $eachassess['name'];
                    $exceltable[$x]['LName'] = '';
                    $exceltable[$x]['due'] = $eachassess['due'];
                    $exceltable[$x]['non'] = $eachassess['non'];
                    $exceltable[$x]['late'] = $eachassess['late'];
                    $exceltable[$x]['Grade'] = $eachassess['graded'];
                    //$exceltable[$x]['Latefeed'] = $eachassess['feed'];
                    $exceltable[$x]['Low'] = $eachassess['low'];
                    //$exceltable[$x]['ascore'] = '';
                    ++$x;
                }
                $exceltable[$x][1] = '';
                ++$x;
                $c = 1;
                foreach ($zscore->users as $key => $eachusr) {
                    // Each student excel downloadable table
                    if ($eachusr['count'] > 0) {
                        $c = $eachusr['count'];
                    }
                    $exceltable[$x]['Name'] = isset($eachusr['fname']) ? $eachusr['fname'] : '';
                    $exceltable[$x]['LName'] = isset($eachusr['lname']) ? $eachusr['lname'] : '';
                    $exceltable[$x]['due'] = $eachusr['due'];
                    $exceltable[$x]['non'] = $eachusr['non'];
                    $exceltable[$x]['late'] = $eachusr['late'];
                    $exceltable[$x]['Grade'] = $eachusr['graded'];
                    //$exceltable[$x]['Latefeed'] = $eachusr['feed'];
                    $exceltable[$x]['Low'] = $eachusr['low'];
                    //$exceltable[$x]['uscore'] = '';//$eachusr['score'] / $eachusr['count'];
                    ++$x;
                }
                $exceltable[$x][2] = '';
                ++$x;
            }
        }
    }
} else {
    echo '<h2><i style="color:#d69859">' . get_string('nomodule', 'report_myfeedback') . '</i></h2>';
}
//echo '</div>';
//These session variables are used in the export file
$_SESSION['exp_sess'] = $exceltable;
$_SESSION['myfeedback_userid'] = $userid;
$_SESSION['tutor'] = 'm';
$_SESSION['user_name'] = 'nil';

//trigger teh logging to database
$event = \report_myfeedback\event\myfeedbackreport_viewed_mtutordash::create(array('context' => context_user::instance($USER->id), 'relateduserid' => $userid));
$event->trigger();

//Use datables to display the table, sort, etc.
echo "<script type=\"text/javascript\">
    $(document).ready(function() {

$('#wait').css({'cursor':'default','display':'none'});
$('body').css('cursor', 'default');

// Create the DataTable
  var table = $('.modtable').DataTable( {
    dom: '',
    fixedHeader: true,
    columnDefs: [
      { targets: [ 0,1,2,3,4,5,6 ],
        searchable: false, 
        orderable:  false 
      }
    ],
    order: [ ]
 });

    $('.overallgrade').show();
    $('span.studentsassessed').text('" . get_string('dashboard_students', 'report_myfeedback') . "');
    $('.studentimgdue').attr('title', '" . get_string('student_due_info', 'report_myfeedback') . "');
    $('.studentimgnon').attr('title', '" . get_string('student_nonsub_info', 'report_myfeedback') . "');
    $('.studentimglate').attr('title', '" . get_string('student_late_info', 'report_myfeedback') . "');
    $('.studentimggraded').attr('title', '" . get_string('student_graded_info', 'report_myfeedback') . "');
    $('.studentimglow').attr('title', '" . get_string('student_low_info', 'report_myfeedback') . "');
});

$('.ex_port').on( 'click', function() {
window.location.href= 'export.php';
});

$('.reportPrint').on( 'click', function () {
        print();
});
/*var w = $('td:first-child').innerWidth();*/
$('td#assess-name').css({
    'max-width': '300px',
    'white-space': 'nowrap',
    'overflow': 'hidden',
    'text-overflow': 'ellipsis'
});

$('.sToggle').click(function(e){
        $(this).closest('.fullRec').find('table.tutor-inner.assRec').hide();
        $(this).closest('.fullRec').find('table.tutor-inner.stuRecP').hide();
        $('.settableheight').hide();
        $('.overallgrade').hide();
        $('.assessdue').show();
        $('span.studentsassessed').text('" . get_string('dashboard_assessments', 'report_myfeedback') . "');
        $('.studentimgdue').attr('title', '" . get_string('tutortblheader_assessment_info', 'report_myfeedback') . "');
        $('.studentimgnon').attr('title', '" . get_string('tutortblheader_nonsubmissions_info', 'report_myfeedback') . "');
        $('.studentimglate').attr('title', '" . get_string('tutortblheader_latesubmissions_info', 'report_myfeedback') . "');
        $('.studentimggraded').attr('title', '" . get_string('tutortblheader_graded_info', 'report_myfeedback') . "');
        $('.studentimglow').attr('title', '" . get_string('tutortblheader_lowgrades_info', 'report_myfeedback') . "');
        $(this).closest('.fullRec').find('table.tutor-inner.stuRecM').show();
        $(this).closest('.fullRec').find('span.aToggle').css({'background-color':'#f5f5f5','color':'#444'});
        $(this).closest('.fullRec').find('span.sToggle').css({'background-color':'#619eb6','color':'#fff'});    
});
$('.aToggle').click(function(e){
        $(this).closest('.fullRec').find('table.tutor-inner.stuRecM').hide();
        $(this).closest('.fullRec').find('table.tutor-inner.assRec').show();
        $('.settableheight').show();
        $('.overallgrade').show();
        $('span.studentsassessed').text('" . get_string('dashboard_students', 'report_myfeedback') . "');
        $('.studentimgdue').attr('title', '" . get_string('student_due_info', 'report_myfeedback') . "');
        $('.studentimgnon').attr('title', '" . get_string('student_nonsub_info', 'report_myfeedback') . "');
        $('.studentimglate').attr('title', '" . get_string('student_late_info', 'report_myfeedback') . "');
        $('.studentimggraded').attr('title', '" . get_string('student_graded_info', 'report_myfeedback') . "');
        $('.studentimglow').attr('title', '" . get_string('student_low_info', 'report_myfeedback') . "');
        $('.modtable .modangle').text('\u25bc');
        $(this).closest('.fullRec').find('span.aToggle').css({'background-color':'#619eb6','color':'#fff'});
        $(this).closest('.fullRec').find('span.sToggle').css({'background-color':'#f5f5f5','color':'#444'});    
});

$('.assess-br').click(function(e){
    var thisAs = $(this).closest('.assRec');
    var rem = '.stuRecP.'+$(thisAs).attr('data-aid');
    if ($(rem).is(':visible')){
        $(rem).nextUntil('.settableheight').hide();
        $(rem).hide();
        $('.assessdue').hide();
        $(thisAs).find('.modangle').text('\u25bc');
    } else if ($(rem).is(':hidden')){
        $(rem).nextUntil(':not(rem)').show();
        $(rem).show();
        $('.assessdue').hide();
        $(thisAs).find('.modangle').text('\u25b2');
    }
 });
</script>";
