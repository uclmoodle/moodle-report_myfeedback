<?php

/*
 * The main file for the Dept admin dashboard
 * 
 * @package  report_myfeedback
 * @author    Delvon Forrester <delvon@esparanza.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
//echo "<div style='float:right'><div class=\"ac-year-right\"><p>" . get_string('academicyear', 'report_myfeedback') . ":</p>";
//require_once(dirname(__FILE__) . '/../student/academicyear.php');
//echo '</div>';
echo "<p>" . get_string('overview_text_dept', 'report_myfeedback') . "</p>";
echo '<div style="float:right">
            <p><span class="personaltutoremail">
            <a href="' . get_string('studentrecordsystemlink', 'report_myfeedback') . '" target="_blank">' . get_string('studentrecordsystem', 'report_myfeedback') . '</a></span>  
            <span class="personaltutoremail reportPrint"  title="'.get_string('print_msg', 'report_myfeedback').'" rel="tooltip"><a href="#">' . get_string('print_report', 'report_myfeedback') . 
        '</a><img id="reportPrint" src="' . 'pix/info.png' . '" ' . ' alt="-"/></span>
            <span class="personaltutoremail x_port">
            <a href="#">' . get_string('export_to_excel', 'report_myfeedback') . '</a></span></p>
            </div>';
//</div>';
$report->setup_ExternalDB();
$admin_mods = array();
if ($get_mods = get_user_capability_course('report/myfeedback:progadmin', $USER->id, $doanything = false, $fields = 'category,fullname,visible')) {//enrol_get_users_courses($USER->id, $onlyactive = TRUE)) {
    foreach ($get_mods as $value) {//The above function gets all courses even if they are not enrolled but in other users. Particularly for category assignments.
        if ($value->visible && $value->id) {
            $admin_mods[] = $value;
        }
    }
}

$padmin = $report->get_prog_admin_dept_prog($admin_mods);
$x = 0;
$exceltable = array();
$cur_dept = 0;
$deptview = (isset($_COOKIE['curdept']) ? $_COOKIE['curdept'] : $deptview);
if ($padmin) {
//Start of top level category
    echo "<form method=\"POST\" id=\"prog_form_dept\" class=\"prog_form\" action=\"\">".get_string('faculty', 'report_myfeedback')."<select id=\"deptSelect\" 
            value=$deptview name=\"deptselect\"><option>".get_string('choosedots')."</option>";
    foreach ($padmin as $k => $pro) {
        foreach ($pro as $key => $t_ar) {
            if ($key == 'dept') {
                $t_ar1 = str_replace(' ', '%20', $t_ar);
                echo "<option value=" . $t_ar1;
                if ($deptview == $t_ar1) {
                    $cur_dept = $k;                                   
                    echo " selected";
                }
                echo ">" . $t_ar;
            }
        }
    }
    echo "</select></form>";
} else {
    echo '<h2><i style="color:#5A5A5A">' . get_string('nodataforyear', 'report_myfeedback'). '</i></h2>';
}

//If top level category is selected
//Start of the second level category
$cur_prog = 0;
$progview = (isset($_COOKIE['curprog']) && isset($_COOKIE['curdept']) && $deptview != $dots ? $_COOKIE['curprog'] : $progview);
if ($cur_dept) {
    echo "<form method=\"POST\" id=\"prog_form_prog\" class=\"prog_form\" action=\"\">".get_string('programme', 'report_myfeedback')."</span>
    <input type=\"hidden\" name=\"deptselect\" value=$deptview />
    <select id=\"progSelect\" value=$progview name=\"progselect\"><option>".get_string('choosedots')."</option>";
    $pg = $padmin;
    foreach ($pg[$cur_dept]['prog'] as $tky => $tre) {
        $p1 = str_replace(' ', '%20', $tre['name']);
        echo "<option value=" . $p1;
        if ($progview == $p1) {
            $cur_prog = $tky;
            echo " selected";
        }
        echo ">" . $tre['name'];
    }
    echo "</select></form>";
}

//If a second level category is selected
//Start of Module
$cur_mod = 0;
$progmodview = (isset($_COOKIE['curmodprog']) && isset($_COOKIE['curprog']) && isset($_COOKIE['curdept']) && $deptview != $dots && $progview != $dots ? $_COOKIE['curmodprog'] : $progmodview);
if ($cur_prog) {
    echo "<form method=\"POST\" id=\"prog_form_mod\" class=\"prog_form\" action=\"\">".get_string('coursecolon', 'report_myfeedback')."</span>
    <input type=\"hidden\" name=\"deptselect\" value=$deptview />
    <input type=\"hidden\" name=\"progselect\" value=$progview />
    <select id=\"progmodSelect\" value=$progmodview name=\"progmodselect\"><option>".get_string('choosedots')."</option>";
    $pg1 = $padmin;
    foreach ($pg1[$cur_dept]['prog'][$cur_prog]['mod'] as $cs1 => $cses) {
        $c1 = str_replace(' ', '%20', $cses);
        echo "<option value=" . $c1;
        if ($progmodview == $c1) {
            $cur_mod = $cs1;
            echo " selected";
        }
        echo ">" . $cses;
    }
    echo "</select></form>";
}
    //Get the late feedback days from config
    /*$lt = get_config('report_myfeedback');
    $a = new stdClass();
    $a->lte = isset($lt->latefeedback) ? $lt->latefeedback : 28;*/
if ($cur_dept && $cur_prog) {
    echo "<div id='selected-prog-container'>";
    echo "<div id='selected-prog-buttons'>";
    $pgmods = $padmin;
    $p_tutor_uids = array();
    $p_tutor_mod = array();
    $dup = 0;
    $pgtot = array();
    $astot = array();
    $pmtot = array();
    $pgusers = array();
    $pguserspermod = array();
    $pgtutcontext = array();
    $pgeach = array();
    $pgeach1 = array();
    if (count($pgmods[$cur_dept]['prog'][$cur_prog]['mod']) <= $report->get_course_limit()) {
        foreach ($pgmods[$cur_dept]['prog'][$cur_prog]['mod'] as $key1 => $pmod) {//All modules in the category
            $prog_mod_context = context_course::instance($key1);
            $pgtutcontext[$key1] = $prog_mod_context->id;
            $puids = array();
            if ($mod_enrolled_users = get_enrolled_users($prog_mod_context, $cap = 'report/myfeedback:student', $groupid = 0, $userfields = 'u.id', $orderby = null, $limitfrom = 0, $limitnum = 0, $onlyactive = true)) {
                foreach ($mod_enrolled_users as $puid) {
                    $puids[] = $puid->id; //uids per module
                    $p_tutor_uids[] = $puid->id; //uids for the module
                    $p_tutor_mod[$key1][] = $puid->id; //all uids but sorted by module
                }
            }

            if (count($puids) > 0) {
                if ($pscore = $report->get_module_z_score($key1, $puids)) {
                    $pgtot = array_merge($pscore->totals, $pgtot); //merge all modules to get the category stats
                    //foreach ($pscore->assess as $a => $asses) {
                    $astot[$key1] = $pscore->users; //The assessments for that module
                    $pmtot[$key1] = $pscore->totals; //The scores of all modules
                    //}
                    foreach ($pscore->users as $pf1 => $pcu) {
                        if (isset($pcu['assess'])) {
                            foreach ($pcu['assess'] as $pf2 => $pcu1) {
                                $pgusers[$pf1]['assess'][$pf2]['score'] = $pcu1['score'];
                                $pguserspermod[$key1][$pf1]['assess'][$pf2]['score'] = $pcu1['score'];
                            }
                        }
                        foreach ($pcu as $key3 => $val3) {
                            if ($key3 != 'score' && $key3 != 'assess') {
                                if (!isset($pgusers[$pf1][$key3])) {
                                    $pgusers[$pf1][$key3] = 0;
                                }
                                if (!isset($pguserspermod[$key1][$pf1][$key3])) {
                                    $pguserspermod[$key1][$pf1][$key3] = 0;
                                }

                                // Segun Babalola, 2019-07-18.
                                // The following two lines of code are causes warnings in the UI because
                                // the value of $val3 is sometimes non-numeric. This is unrelated to the issue I'm
                                // trying to fix (i.e. https://tracker.moodle.org/browse/CONTRIB-6841), however I will
                                // guard execution of these statements with a check for now. Hopefully the root cause of
                                // the issue will be addressed in future.
                                if (is_numeric($val3)) {
                                    $pgusers[$pf1][$key3] += $val3;
                                    $pguserspermod[$key1][$pf1][$key3] += $val3;
                                }
                            }
                        }
                    }

                    $pgeach[$key1] = $pscore;
                    $pgeach1[$key1] = $pmod; //holds the course names
                }
            }
        }
    } else {
        echo get_string('courselimitinfo', 'report_myfeedback');
    }
    //Table for the modules
	$namemsg = get_string('usagetblheader_name_info', 'report_myfeedback');
    $nameicon = '<img class="studentimgdue" src="' . 'pix/info.png' . '" ' .
            ' alt="-" title="' . $namemsg . '" rel="tooltip"/>';
    $assessmentmsg = get_string('tutortblheader_assessment_info', 'report_myfeedback');
    $assessmenticon = '<img class="studentimgdue" src="' . 'pix/info.png' . '" ' .
            ' alt="-" title="' . $assessmentmsg . '" rel="tooltip"/>';
    //$latefeedbackmsg = get_string('tutortblheader_latefeedback_info', 'report_myfeedback',$a);
    //$latefeedbackicon = '<img  class="studentimgfeed" src="' . 'pix/info.png' . '" ' .
     //       ' alt="-" title="' . $latefeedbackmsg . '" rel="tooltip"/>';
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
    $progtable = "<table id=\"progtable\" class=\"progtable\" width=\"100%\" border=\"1\" style=\"text-align:center\">
                <thead>
                            <tr class=\"tableheader\">
                                <th>" .
            get_string('tutortblheader_name', 'report_myfeedback') . "</th>
                                <th class='overallgrade'>" .
            get_string('tutortblheader_overallgrade', 'report_myfeedback') . " " . $overallgradeicon . "</th>
                                <th><span class='studentsassessed'>" .
            get_string('tutortblheader_assessment', 'report_myfeedback') . "</span> " . $assessmenticon . "</th>
                                <th>" .
            get_string('tutortblheader_nonsubmissions', 'report_myfeedback') . " " . $nonsubmissionicon . "</th>
                                <th>" .
            get_string('tutortblheader_latesubmissions', 'report_myfeedback') . " " . $latesubmissionicon . "</th>
                                <th>" .
            get_string('tutortblheader_graded', 'report_myfeedback') . " $gradeicon</th> 
                                <th>" .
            get_string('tutortblheader_lowgrades', 'report_myfeedback') . " " . $lowgradeicon . "</th>
                                </tr></thead><tbody>";
                                
    $modtut = null;
    if ($cur_mod) {
        $pgusers = (isset($pguserspermod[$cur_mod]) ? $pguserspermod[$cur_mod] : array()); //Get users with scores per module instead of the entire category
        $p_tutor_uids = (isset($p_tutor_mod[$cur_mod]) ? $p_tutor_mod[$cur_mod] : $p_tutor_uids); // Get user ids per module
        $modtut = (isset($p_tutor_mod[$cur_mod]) ? $pgtutcontext[$cur_mod] : 0);
        echo (isset($pgeach1[$cur_mod]) ? '<h3>'.get_string('coursecolon', 'report_myfeedback') . $pgeach1[$cur_mod] . '</h3>' . /*
                  $report->get_module_graph('module' . $cur_mod, $pmtot[$cur_mod], count($pmtot[$cur_mod])) . '<p style = "margin: 20px 0"> */
                '<span class="aToggle modass" style="background-color:#619eb6;color:#fff">' . get_string('statsperassessment', 'report_myfeedback'). '</span>
                <span class="sToggle">' . get_string('statsperstudent', 'report_myfeedback'). '</span><span class="pToggle">' . get_string('modtutorstats', 'report_myfeedback') . '</span></p></div>' :
                '<h3>' . get_string('secondlevelcat', 'report_myfeedback') . $padmin[$cur_dept]['prog'][$cur_prog]['name'] . '</h3>' . /* .
                  $report->get_module_graph('prog' . $cur_prog, $pmtot[$cur_mod], count($pmtot[$cur_mod])) . '<p style = "margin: 20px 0"> */
                '<span class="aToggle" style="background-color:#619eb6;color:#fff">' . get_string('statspercourse', 'report_myfeedback'). '</span>
                <span class="sToggle">' . get_string('statsperstudent', 'report_myfeedback'). '</span></p></div>'); //<span class="pToggle">Personal tutor stats</span>

        if (isset($pgeach[$cur_mod]->assess)) {
            $asse = $report->get_assessment_analytics($pgeach[$cur_mod]->assess, count($pgeach[$cur_mod]->assess), $display = 'assRec', $style = '', 
                    $breakdown = '<p style="margin-top:10px"><span class="assess-br">' 
                    . get_string('studentbreakdown', 'report_myfeedback'). '</span><br><span class="assess-br modangle">&#9660;</span></p>', $astot[$cur_mod]);
            if ($asse->aname) {
                $progtable .= '<tr class="permod"><td class="assign-td">' . $asse->aname . '</td><td  class="overallgrade">' .
                        $asse->a_vas . '</td><td>' . $asse->ad . '</td><td>' . $asse->an . '</td><td>' .
                        $asse->al . '</td><td>' . $asse->ag . '</td><td>' . $asse->alo . '</td></tr>';
            } else {
                $progtable .= '<tr><td colspan=8><i style="color:#5A5A5A">' . get_string('nodatatodisplay', 'report_myfeedback'). '</i></td></tr>';
                $dup = 1;
            }
        } else {
            $progtable .= '<tr><td colspan=8><i style="color:#5A5A5A">' . get_string('nodatatodisplay', 'report_myfeedback'). '</i></td></tr>';
            $dup = 1;
        }

        echo "<script type=\"text/javascript\">
        $(document).ready(function() {
        $('.overallgrade').show();
        $('span.studentsassessed').text('".get_string('dashboard_students','report_myfeedback')."');
        $('.studentimgdue').attr('title', '".get_string('student_due_info','report_myfeedback')."');
        $('.studentimgnon').attr('title', '".get_string('student_nonsub_info','report_myfeedback')."');
        $('.studentimglate').attr('title', '".get_string('student_late_info','report_myfeedback')."');
        $('.studentimggraded').attr('title', '".get_string('student_graded_info','report_myfeedback')."');
        $('.studentimglow').attr('title', '".get_string('student_low_info','report_myfeedback')."');
        });
        </script>";
    } else {
        echo '<h3><b>' . get_string('secondlevelcat', 'report_myfeedback') . $padmin[$cur_dept]['prog'][$cur_prog]['name'] . '</b></h3>' . /*
          $report->get_module_graph('prog' . $cur_prog, $pgtot, count($pgtot)) . */ '<p style = "margin: 20px 0">
                <span class="aToggle" style="background-color:#619eb6;color:#fff">' . get_string('statspercourse', 'report_myfeedback'). '</span>
                <span class="sToggle">' . get_string('statsperstudent', 'report_myfeedback'). '</span></p></div>'; //<span class="pToggle">Personal tutor stats</span>

        foreach ($pgeach as $k1 => $each) {
            $scol1 = $scol2 = $scol3 = '';
            $progtable .= '<tr class="permod"><td class="mod' . $k1 . '">' . '<a href="#" class="progmodClick">' . $pgeach1[$k1] . '</a><form method="POST" class="prog_form_mod_click">
            <input type="hidden" name="currenttab" value="progadmin" />
        <input type="hidden" name="deptselect" value="' . $deptview . '" />
        <input type="hidden" name="progselect" value="' . $progview . '" />    
        <input type="hidden" name="progmodselect" value="' . str_replace(' ', '_', $pgeach1[$k1]) . '">
            </form></td><td class="overallgrade">' . $each->modgraph . '</td><td>' . $each->due . '</td><td><span class="' . $scol1 . '">' . $each->nonsub . '</span></td><td><span class="' . $scol2 . '">' .
                    $each->latesub . '</span></td><td>' . $each->graded . '</td><td><span class="' . $scol3 . '">' . $each->lowgrades . '</span></td></tr>';
        }
    }

    // Modules on the second level excel downloadable table
    foreach ($pgeach as $k3 => $each) {
        $exceltable[$x]['Name'] = $pgeach1[$k3];
        $exceltable[$x]['LName'] = '';
        $exceltable[$x]['Assessments'] = $each->due;
        $exceltable[$x]['Nonsubmission'] = $each->nonsub;
        $exceltable[$x]['Latesubmission'] = $each->latesub;
        $exceltable[$x]['Graded'] = $each->graded;
        //$exceltable[$x]['Latefeedback'] = $each->latefeedback;
        $exceltable[$x]['Lowgrade'] = $each->lowgrades;
        ++$x;
    }
    $exceltable[$x][2] = '';
    ++$x;

    //Users for the export table on department level
    foreach ($pgusers as $k4 => $eachu) {
        $getname = $remotedb->get_record('user', array('id' => $k4), $list = 'firstname,lastname');
        $fname = ($getname ? $getname->firstname : '');
        $lname = ($getname ? $getname->lastname : '');
        $exceltable[$x]['Name'] = $fname;
        $exceltable[$x]['LName'] = $lname;
        $exceltable[$x]['Assessments'] = $eachu['due'];
        $exceltable[$x]['Nonsubmission'] = $eachu['non'];
        $exceltable[$x]['Latesubmission'] = $eachu['late'];
        $exceltable[$x]['Graded'] = $eachu['graded'];
        //$exceltable[$x]['Latefeedback'] = $eachu['feed'];
        $exceltable[$x]['Lowgrade'] = $eachu['low'];
        ++$x;
    }

    $stu = $report->get_user_analytics($pgusers, $cid = 't', $display = 'stuRec', $style = 'display:none');
    $prog_admin_P_tutors = array();
    /* foreach ($p_tutor_uids as $u_id) {//Get the context of the users to check for personal tutors
      $prog_admin_P_tutors[] = context_user::instance($u_id)->id;
      } */
    $progtable .= "<tr><td>" . $stu->uname . "</td>";
    $progtable .= "<td  class='overallgrade'>" . $stu->u_vas . "</td>";
    $progtable .= "<td>" . $stu->ud . "</td>";
    $progtable .= "<td>" . $stu->un . "</td>";
    $progtable .= "<td>" . $stu->ul . "</td>";
    $progtable .= "<td>" . $stu->ug . "</td>";
    //$progtable .= "<td>" . $stu->uf . "</td>";
    $progtable .= "<td>" . $stu->ulo . "</td></tr>";

    if (!isset($pgeach[$cur_mod]->assess) && !$pgeach && !$dup)
        $progtable .= '<tr><td colspan=8><i style="color:#5A5A5A">' . get_string('nodatatodisplay', 'report_myfeedback'). '</i></td></tr>';
    $progtable .= '</tbody></table>';
    $p_tutor_id = ($modtut ? 3 : $p_tutor_id); //$modtut is course context
    $tutgroup = ($modtut ? $pgusers : array()); //Array of users in course
    $activemod = ($modtut ? $cur_mod : 0); //Current module

    echo $progtable;
    if ($cur_mod) {
        echo $report->get_progadmin_ptutor($prog_admin_P_tutors, $p_tutor_id, $modtut, $tutgroup, $activemod);
    }
    echo "</div>";

    $_SESSION['exp_sess'] = $exceltable;
    $_SESSION['myfeedback_userid'] = $userid;
    $_SESSION['tutor'] = 'd';
    $_SESSION['user_name'] = 'nil';
}

$event = \report_myfeedback\event\myfeedbackreport_viewed_deptdash::create(array('context' => context_user::instance($USER->id), 'relateduserid' => $userid));
$event->trigger();

echo "<script type=\"text/javascript\">
    $(document).ready( function () {

$('#wait').css({'cursor':'default','display':'none'});
$('body').css('cursor', 'default');
 
$('#deptSelect').change(function(){
    $('#prog_form_dept').submit();    
});
$('#progSelect').change(function(){
    $('#prog_form_prog').submit();    
});
$('#progmodSelect').change(function(){
    $('#prog_form_mod').submit();    
});
$('.progmodClick').click(function(){
    $(this).closest('.permod').find('.prog_form_mod_click').submit();    
});

$('.reportPrint').on( 'click', function () {
        print();
});

$('.x_port').on( 'click', function() {
window.location.href= 'export.php';
});

$('.sToggle').click(function(e){
        $(this).closest('#selected-prog-container').find('.progtable').show();
        $(this).closest('#selected-prog-container').find('.permod').hide();
        $(this).closest('#selected-prog-container').find('.ptutor').hide();
        $(this).closest('#selected-prog-container').find('tr.recordRow').show();
        $('.overallgrade').hide();
        $('span.studentsassessed').text('".get_string('dashboard_assessments','report_myfeedback')."');
        $('.studentimgdue').attr('title', '".get_string('tutortblheader_assessment_info','report_myfeedback')."');
        $('.studentimgnon').attr('title', '".get_string('tutortblheader_nonsubmissions_info','report_myfeedback')."');
        $('.studentimglate').attr('title', '".get_string('tutortblheader_latesubmissions_info','report_myfeedback')."');
        $('.studentimggraded').attr('title', '".get_string('tutortblheader_graded_info','report_myfeedback')."');
        $('.studentimglow').attr('title', '".get_string('tutortblheader_lowgrades_info','report_myfeedback')."');
        $('.assessdue').show();
        $(this).closest('#selected-prog-container').find('table.tutor-inner.stuRec').show();
        $(this).closest('#selected-prog-container').find('span.sToggle').css({'background-color':'#619eb6','color':'#fff'});
        $(this).closest('#selected-prog-container').find('span.aToggle').css({'background-color':'#f5f5f5','color':'#444'});  
        $(this).closest('#selected-prog-container').find('span.pToggle').css({'background-color':'#f5f5f5','color':'#444'});    
});
$('.aToggle').click(function(e){
        $(this).closest('#selected-prog-container').find('.progtable').show();
        $(this).closest('#selected-prog-container').find('tr.recordRow').hide();
        $(this).closest('#selected-prog-container').find('.ptutor').hide();
        $(this).closest('#selected-prog-container').find('.permod').show();
        $(this).closest('#selected-prog-container').find('table.tutor-inner.stuRec').hide();
        $('.progtable .modangle').text('\u25bc');
        $(this).closest('#selected-prog-container').find('span.aToggle').css({'background-color':'#619eb6','color':'#fff'});
        $(this).closest('#selected-prog-container').find('span.sToggle').css({'background-color':'#f5f5f5','color':'#444'});  
        $(this).closest('#selected-prog-container').find('span.pToggle').css({'background-color':'#f5f5f5','color':'#444'}); 
});

$('.modass').click(function(e) {        
        $('.overallgrade').show();
        $('span.studentsassessed').text('".get_string('dashboard_students','report_myfeedback')."');
        $('.studentimgdue').attr('title', '".get_string('student_due_info','report_myfeedback')."');
        $('.studentimgnon').attr('title', '".get_string('student_nonsub_info','report_myfeedback')."');
        $('.studentimglate').attr('title', '".get_string('student_late_info','report_myfeedback')."');
        $('.studentimggraded').attr('title', '".get_string('student_graded_info','report_myfeedback')."');
        $('.studentimglow').attr('title', '".get_string('student_low_info','report_myfeedback')."');
});

$('.pToggle').click(function(e){
        $(this).closest('#selected-prog-container').find('.progtable').hide();
        $(this).closest('#selected-prog-container').find('.ptutor').show();
        $(this).closest('#selected-prog-container').find('span.pToggle').css({'background-color':'#619eb6','color':'#fff'});
        $(this).closest('#selected-prog-container').find('span.sToggle').css({'background-color':'#f5f5f5','color':'#444'});  
        $(this).closest('#selected-prog-container').find('span.aToggle').css({'background-color':'#f5f5f5','color':'#444'}); 
});

$('.assess-br').click(function(e){
    var thisAs = $(this).closest('.assRec');
    var rem = '.stuRec.'+$(thisAs).attr('data-aid');
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

$('u.hidetable').click(function(e){
    var thisEl = $(this).closest('td.maintable').find('table.innertable');
    if($(thisEl).is(':visible')) {
        $(thisEl).hide();
        $(this).text('" . get_string('show') . "');
    } else if ($(thisEl).is(':hidden')) {
        $(thisEl).show();
        $(this).text('" . get_string('hide') . "');
    }        
});

$('#selectall1').change(function(){
      $('.chk2').prop('checked', $(this).prop('checked'));
});
$(\"#emailform1\").click(function(){
                var mylink1 = [];
                $(\"input:checked\").each(function(i){
                if ($(this).val() != 'on') {
                 mylink1.push($(this).val());
                 }
                });
                if (mylink1.length > 0) {
                $(\"a#mail1\").attr(\"href\", \"mailto:?bcc=\" + mylink1.join
        (\";\")+\"&Subject=" . get_string('email_dept_subject', 'report_myfeedback') . "\");
    }
});

/*var w = $('td:first-child').innerWidth();*/
$('td#assess-name').css({
    'max-width': '300px',
    'white-space': 'nowrap',
    'overflow': 'hidden',
    'text-overflow': 'ellipsis'
});
});
</script>";