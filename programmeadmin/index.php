<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * The main file for the Dept admin dashboard
 *
 * @package   report_myfeedback
 * @copyright 2022 UCL
 * @author    Delvon Forrester <delvon@esparanza.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$PAGE->requires->js_call_amd('report_myfeedback/programmeadmin', 'init');

if (!$report->get_dashboard_capability($USER->id, 'report/myfeedback:progadmin')) {
    echo report_myfeedback_stop_spinner();
    throw new moodle_exception('nopermissions', '', $PAGE->url->out(), get_string('viewadminreports', 'report_myfeedback'));
}

echo "<p>" . get_string('overview_text_dept', 'report_myfeedback') . "</p>";
$config = get_config('report_myfeedback');
$studentrecordsystemlink = $config->studentrecordsystemlink;
$studentrecordsystemlaunchtext = (isset($config->studentrecordsystem) && $config->studentrecordsystem ?
    $config->studentrecordsystem :
    get_string('studentrecordsystem', 'report_myfeedback'));

echo '<div style="float:right">
            <p><span class="personaltutoremail">
            <a href="' . $studentrecordsystemlink . '" target="_blank">' . $studentrecordsystemlaunchtext . '</a></span>
            <span class="personaltutoremail reportPrint"  title="'.get_string('print_msg',
        'report_myfeedback').'" rel="tooltip">
                <a href="#">' . get_string('print_report', 'report_myfeedback') .
        '</a><img id="reportPrint" src="' . 'pix/info.png' . '" ' . ' alt="-"/></span>
            <span class="personaltutoremail x_port">
            <a href="#">' . get_string('export_to_excel', 'report_myfeedback') . '</a></span></p>
            </div>';

$report->setup_external_db();
$adminmods = array();

$getmods = get_user_capability_course(
    'report/myfeedback:progadmin',
    $USER->id,
    $doanything = false,
    $fields = 'category,fullname,visible'
);
if ($getmods) {
    foreach ($getmods as $value) {
        // The above function gets all courses even if they are not enrolled but in other users.
        // Particularly for category assignments.
        if ($value->visible && $value->id) {
            $adminmods[] = $value;
        }
    }
}

$padmin = $report->get_prog_admin_dept_prog($adminmods);
$x = 0;
$exceltable = array();
$curdept = 0;
$deptview = (isset($_COOKIE['curdept']) ? $_COOKIE['curdept'] : $deptview);
if ($padmin) {
    // Start of top level category.
    echo "<form method=\"POST\" id=\"prog_form_dept\" class=\"prog_form\" action=\"\">"
            . '<input type="hidden" name="sesskey" value="' . sesskey() . '" />'
            . get_string('faculty', 'report_myfeedback')
            . "<select id=\"deptSelect\" value=$deptview name=\"deptselect\"><option>"
            . get_string('choosedots')
            . '</option>';
    foreach ($padmin as $k => $pro) {
        foreach ($pro as $key => $tar) {
            if ($key == 'dept') {
                $tar1 = str_replace(' ', '%20', $tar);
                echo "<option value=" . $tar1;
                if ($deptview == $tar1) {
                    $curdept = $k;
                    echo " selected";
                }
                echo ">" . $tar;
            }
        }
    }
    echo "</select></form>";
} else {
    echo '<h2><i style="color:#5A5A5A">' . get_string('nodataforyear', 'report_myfeedback'). '</i></h2>';
}

// If top level category is selected.
// Start of the second level category.
$curprog = 0;
$progview = (isset($_COOKIE['curprog']) && isset($_COOKIE['curdept']) && $deptview != $dots ? $_COOKIE['curprog'] : $progview);
if ($curdept) {
    echo "<form method=\"POST\" id=\"prog_form_prog\" class=\"prog_form\" action=\"\">"
            . '<input type="hidden" name="sesskey" value="' . sesskey() . '" />'
            . get_string('programme', 'report_myfeedback')
            . "</span>
    <input type=\"hidden\" name=\"deptselect\" value=$deptview />
    <select id=\"progSelect\" value=$progview name=\"progselect\"><option>".get_string('choosedots')."</option>";
    $pg = $padmin;
    foreach ($pg[$curdept]['prog'] as $tky => $tre) {
        $p1 = str_replace(' ', '%20', $tre['name']);
        echo "<option value=" . $p1;
        if ($progview == $p1) {
            $curprog = $tky;
            echo " selected";
        }
        echo ">" . $tre['name'];
    }
    echo "</select></form>";
}

// If a second level category is selected - start of module.
$curmod = 0;
$progmodview = (isset($_COOKIE['curmodprog']) && isset($_COOKIE['curprog']) && isset($_COOKIE['curdept']) && $deptview != $dots
                    && $progview != $dots ? $_COOKIE['curmodprog'] : $progmodview);
if ($curprog) {
    echo "<form method=\"POST\" id=\"prog_form_mod\" class=\"prog_form\" action=\"\">"
            . '<input type="hidden" name="sesskey" value="' . sesskey() . '" />'
            . get_string('coursecolon', 'report_myfeedback')
            . "</span>
    <input type=\"hidden\" name=\"deptselect\" value=$deptview />
    <input type=\"hidden\" name=\"progselect\" value=$progview />
    <select id=\"progmodSelect\" value=$progmodview name=\"progmodselect\"><option>".get_string('choosedots')."</option>";
    $pg1 = $padmin;
    foreach ($pg1[$curdept]['prog'][$curprog]['mod'] as $cs1 => $cses) {
        $c1 = str_replace(' ', '%20', $cses);
        echo "<option value=" . $c1;
        if ($progmodview == $c1) {
            $curmod = $cs1;
            echo " selected";
        }
        echo ">" . $cses;
    }
    echo "</select></form>";
}
    // Get the late feedback days from config.
if ($curdept && $curprog) {
    echo "<div id='selected-prog-container'>";
    echo "<div id='selected-prog-buttons'>";
    $pgmods = $padmin;
    $ptutoruids = array();
    $ptutormod = array();
    $dup = 0;
    $pgtot = array();
    $astot = array();
    $pmtot = array();
    $pgusers = array();
    $pguserspermod = array();
    $pgtutcontext = array();
    $pgeach = array();
    $pgeach1 = array();
    if (count($pgmods[$curdept]['prog'][$curprog]['mod']) <= $report->get_course_limit() || $curmod) { // CATALYST CUSTOM.
        foreach ($pgmods[$curdept]['prog'][$curprog]['mod'] as $key1 => $pmod) {// All modules in the category.
            // CATALYST CUSTOM START - IF a course is selected, process just that selected course.
            if ($curmod && $curmod != $key1) {
                continue;
            }
            // CATALYST CUSTOM END.
            $progmodcontext = context_course::instance($key1);
            $pgtutcontext[$key1] = $progmodcontext->id;
            $puids = array();

            $modenrolledusers = get_enrolled_users(
                $progmodcontext,
                $cap = 'report/myfeedback:student',
                $groupid = 0,
                $userfields = 'u.id',
                $orderby = null,
                $limitfrom = 0,
                $limitnum = 0,
                $onlyactive = true
            );
            if ($modenrolledusers) {
                foreach ($modenrolledusers as $puid) {
                    $puids[] = $puid->id; // Uids per module.
                    $ptutoruids[] = $puid->id; // Uids for the module.
                    $ptutormod[$key1][] = $puid->id; // All uids but sorted by module.
                }
            }

            if (count($puids) > 0) {
                if ($pscore = $report->get_module_z_score($key1, $puids)) {
                    $pgtot = array_merge($pscore->totals, $pgtot); // Merge all modules to get the category stats.

                    $astot[$key1] = $pscore->users; // The assessments for that module.
                    $pmtot[$key1] = $pscore->totals; // The scores of all modules.

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
                    $pgeach1[$key1] = $pmod; // Holds the course names.
                }
            }
        }
    } else {
        echo get_string('courselimitinfo', 'report_myfeedback');
    }
    // Table for the modules.
    $namemsg = get_string('usagetblheader_name_info', 'report_myfeedback');
    $nameicon = '<img class="studentimgdue" src="' . 'pix/info.png' . '" ' .
            ' alt="-" title="' . $namemsg . '" rel="tooltip"/>';
    $assessmentmsg = get_string('tutortblheader_assessment_info', 'report_myfeedback');
    $assessmenticon = '<img class="studentimgdue" src="' . 'pix/info.png' . '" ' .
            ' alt="-" title="' . $assessmentmsg . '" rel="tooltip"/>';
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
    if ($curmod) {
        // Get users with scores per module instead of the entire category.
        $pgusers = (isset($pguserspermod[$curmod]) ? $pguserspermod[$curmod] : array());
        // Get user ids per module.
        $ptutoruids = (isset($ptutormod[$curmod]) ? $ptutormod[$curmod] : $ptutoruids);
        $modtut = (isset($ptutormod[$curmod]) ? $pgtutcontext[$curmod] : 0);
        echo (isset($pgeach1[$curmod]) ? '<h3>'.get_string('coursecolon', 'report_myfeedback') . $pgeach1[$curmod] . '</h3>' .
            '<span class="aToggle modass" style="background-color:#619eb6;color:#fff">'
            . get_string('statsperassessment', 'report_myfeedback'). '</span>
            <span class="sToggle">' . get_string('statsperstudent', 'report_myfeedback'). '</span><span class="pToggle">'
            . get_string('modtutorstats', 'report_myfeedback') . '</span></p></div>' :
            '<h3>' . get_string('secondlevelcat', 'report_myfeedback') . $padmin[$curdept]['prog'][$curprog]['name'] . '</h3>' .
            '<span class="aToggle" style="background-color:#619eb6;color:#fff">'
            . get_string('statspercourse', 'report_myfeedback'). '</span>
            <span class="sToggle">' . get_string('statsperstudent', 'report_myfeedback'). '</span></p></div>');

        if (isset($pgeach[$curmod]->assess)) {
            $breakdown = '<p style="margin-top:10px"><span class="assess-br">'
                . get_string('studentbreakdown', 'report_myfeedback')
                . '</span><br><span class="assess-br modangle">&#9660;</span></p>';
            $asse = $report->get_assessment_analytics($pgeach[$curmod]->assess, count($pgeach[$curmod]->assess),
                        $display = 'assRec', $style = '', $breakdown, $astot[$curmod]);
            if ($asse->aname) {
                $progtable .= '<tr class="permod"><td class="assign-td">' . $asse->aname . '</td><td  class="overallgrade">' .
                        $asse->a_vas . '</td><td>' . $asse->ad . '</td><td>' . $asse->an . '</td><td>' .
                        $asse->al . '</td><td>' . $asse->ag . '</td><td>' . $asse->alo . '</td></tr>';
            } else {
                $progtable .= '<tr><td colspan=8><i style="color:#5A5A5A">' . get_string('nodatatodisplay', 'report_myfeedback')
                    . '</i></td></tr>';
                $dup = 1;
            }
        } else {
            $progtable .= '<tr><td colspan=8><i style="color:#5A5A5A">' . get_string('nodatatodisplay', 'report_myfeedback')
                . '</i></td></tr>';
            $dup = 1;
        }
    } else {
        echo '<h3><b>' . get_string('secondlevelcat', 'report_myfeedback') . $padmin[$curdept]['prog'][$curprog]['name']
            . '</b></h3>' . '<p style = "margin: 20px 0">
            <span class="aToggle" style="background-color:#619eb6;color:#fff">' . get_string('statspercourse', 'report_myfeedback')
            . '</span><span class="sToggle">' . get_string('statsperstudent', 'report_myfeedback'). '</span></p></div>';

        foreach ($pgeach as $k1 => $each) {
            $scol1 = $scol2 = $scol3 = '';
            $progtable .= '<tr class="permod"><td class="mod' . $k1 . '">' . '<a href="#" class="progmodClick">'
                . $pgeach1[$k1] . '</a>';
            $progtable .= '<form method="POST" class="prog_form_mod_click">
            <input type="hidden" name="sesskey" value="' . sesskey() . '" />
            <input type="hidden" name="currenttab" value="progadmin" />
        <input type="hidden" name="deptselect" value="' . $deptview . '" />
        <input type="hidden" name="progselect" value="' . $progview . '" />
        <input type="hidden" name="progmodselect" value="' . str_replace(' ', '_', $pgeach1[$k1]) . '">
            </form></td><td class="overallgrade">' . $each->modgraph . '</td><td>' . $each->due . '</td><td><span class="'
                . $scol1 . '">' . $each->nonsub . '</span></td><td><span class="' . $scol2 . '">' . $each->latesub
                . '</span></td><td>' . $each->graded . '</td><td><span class="' . $scol3 . '">' . $each->lowgrades
                . '</span></td></tr>';
        }
    }

    // Modules on the second level excel downloadable table.
    foreach ($pgeach as $k3 => $each) {
        $exceltable[$x]['Name'] = $pgeach1[$k3];
        $exceltable[$x]['LName'] = '';
        $exceltable[$x]['Assessments'] = $each->due;
        $exceltable[$x]['Nonsubmission'] = $each->nonsub;
        $exceltable[$x]['Latesubmission'] = $each->latesub;
        $exceltable[$x]['Graded'] = $each->graded;
        $exceltable[$x]['Lowgrade'] = $each->lowgrades;
        ++$x;
    }
    $exceltable[$x][2] = '';
    ++$x;

    // Users for the export table on department level.
    foreach ($pgusers as $k4 => $eachu) {
        $getname = $currentdb->get_record('user', array('id' => $k4), $list = 'firstname,lastname');
        $fname = ($getname ? $getname->firstname : '');
        $lname = ($getname ? $getname->lastname : '');
        $exceltable[$x]['Name'] = $fname;
        $exceltable[$x]['LName'] = $lname;
        $exceltable[$x]['Assessments'] = $eachu['due'];
        $exceltable[$x]['Nonsubmission'] = $eachu['non'];
        $exceltable[$x]['Latesubmission'] = $eachu['late'];
        $exceltable[$x]['Graded'] = $eachu['graded'];
        $exceltable[$x]['Lowgrade'] = $eachu['low'];
        ++$x;
    }

    $stu = $report->get_user_analytics($pgusers, $cid = 't', $display = 'stuRec', $style = 'display:none');
    $progadminptutors = array();

    $progtable .= "<tr class=\"recordRow\"><td>" . $stu->uname . "</td>";
    $progtable .= "<td  class='overallgrade'>" . $stu->u_vas . "</td>";
    $progtable .= "<td>" . $stu->ud . "</td>";
    $progtable .= "<td>" . $stu->un . "</td>";
    $progtable .= "<td>" . $stu->ul . "</td>";
    $progtable .= "<td>" . $stu->ug . "</td>";
    $progtable .= "<td>" . $stu->ulo . "</td></tr>";

    if (!isset($pgeach[$curmod]->assess) && !$pgeach && !$dup) {
        $progtable .= '<tr><td colspan=8><i style="color:#5A5A5A">' . get_string('nodatatodisplay', 'report_myfeedback')
            . '</i></td></tr>';
    }
    $progtable .= '</tbody></table>';
    $ptutorid = ($modtut ? 3 : $ptutorid); // Var $modtut is course context.
    $tutgroup = ($modtut ? $pgusers : array()); // Array of users in course.
    $activemod = ($modtut ? $curmod : 0); // Current module.

    echo $progtable;
    if ($curmod) {
        echo $report->get_progadmin_ptutor($progadminptutors, $ptutorid, $modtut, $tutgroup, $activemod);
    }
    echo "</div>";

    $_SESSION['exp_sess'] = $exceltable;
    $_SESSION['myfeedback_userid'] = $userid;
    $_SESSION['tutor'] = 'd';
    $_SESSION['user_name'] = 'nil';
}

$event = \report_myfeedback\event\myfeedbackreport_viewed_deptdash::create(
    array('context' => context_user::instance($USER->id), 'relateduserid' => $userid)
);
$event->trigger();
