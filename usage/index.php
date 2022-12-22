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
 * The main file for the Usage dashboard
 *
 * @package  report_myfeedback
 * @copyright 2022 UCL
 * @author   Jessica Gramp <j.gramp@ucl.ac.uk> and <jgramp@gmail.com>
 * @author   Delvon Forrester <delvon@esparanza.co.uk>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$PAGE->requires->js_call_amd('report_myfeedback/usage', 'init');

// TODO: turn off error reporting
// Report all errors except E_NOTICE.
error_reporting(E_ALL & E_NOTICE);

// Check permission to view usage reports - must have a role with this permission.
// TODO: Make the permission site wide only (if possible).
if ($report->get_dashboard_capability($USER->id, 'report/myfeedback:usage')) {

    $report->setup_external_db();

    // Display the description for those who have permission.
    echo "<div class=\"usagereport\"><p>" . get_string('overview_text_usage', 'report_myfeedback') . "</p>";

    // Get the report type from the URL.
    $reporttype = optional_param('reporttype', '', PARAM_TEXT);
    $categoryid = optional_param('categoryid', -1, PARAM_INT);
    $courseid = optional_param('courseid', 0, PARAM_INT);
    $reportuserid = optional_param('reportuserid', 0, PARAM_INT);

    $reportmenu = [
        "categorystudentsoverview" => get_string('categorystudents', 'report_myfeedback') . " "
            . get_string('overview', 'report_myfeedback'),
        "categorystaffoverview" => get_string('categorystaff', 'report_myfeedback') . " "
            . get_string('overview', 'report_myfeedback'),
        "categorystudents" => get_string('categorystudents', 'report_myfeedback'),
        "categorystaff" => get_string('categorystaff', 'report_myfeedback'),
        "coursestudentsoverview" => get_string('coursestudents', 'report_myfeedback') . " "
            . get_string('overview', 'report_myfeedback'),
        "coursestaffoverview" => get_string('coursestaff', 'report_myfeedback') . " " . get_string('overview', 'report_myfeedback'),
        "coursestudents" => get_string('coursestudents', 'report_myfeedback'),
        "coursestaff" => get_string('coursestaff', 'report_myfeedback'),
        "student" => get_string('student', 'report_myfeedback'),
        "staffmember" => get_string('staffmember', 'report_myfeedback'),
        "personaltutorstudents" => get_string('personaltutees', 'report_myfeedback')
    ];
    // Display report type menu.
    echo "<form method=\"GET\" id=\"report_form_select\" class=\"report_form\" action=\"\">"
        . get_string('reporttype', 'report_myfeedback') . ": </span>";
    echo '<input type="hidden" name="sesskey" value="' . sesskey() . '" />';
    echo "<input type=\"hidden\" name=\"reportuserid\" value=\"$reportuserid\" />";
    echo "<input type=\"hidden\" name=\"categoryid\" value=\"$categoryid\" />";
    echo "<input type=\"hidden\" name=\"courseid\" value=\"$courseid\" />";
    echo "<input type=\"hidden\" name=\"currenttab\" value=\"usage\" />";
    echo "<select id=\"reportSelect\" value=\"$reporttype\" name=\"reporttype\">";
    echo "<option value=\"\">".get_string('choosedots')."</option>";
    foreach ($reportmenu as $rvalue => $rname) {
        echo "<option value=\"".$rvalue."\"";
        if ($reporttype == $rvalue) {
            echo " selected";
        }
        echo ">".$rname."</option>";
    }
    echo "</select>";
    echo "</form>";
    $reporttitle = "";
    $uids = array();

    // Table for students / staff.
    switch ($reporttype) {
        case 'categorystudentsoverview':
            echo $report->get_subcategory_menu(0, $categoryid);
            echo $report->search_all_categories($searchusage, $reporttype);

            // Get the parent category name for categoryid=0.
            $categoryname = "";
            if ($categoryid > -1) {
                if ($categoryid > 0) {
                    $categoryname = $report->get_category_name($categoryid);
                } else if ($categoryid == 0) {
                    $categoryname = $SITE->fullname;
                }

                // Get the category users.
                echo $report->export_print_buttons();
                $_SESSION['tutor'] = 'ustudover';
                $reporttitle = get_string('category', 'report_myfeedback') . " "
                    . lcfirst(get_string('student', 'report_myfeedback'))
                    . " " . get_string('overview', 'report_myfeedback') . ": " . $categoryname;
                echo "<h3>" . $reporttitle . $report->get_parent_category_link($categoryid, $reporttype) ."</h3>";
                echo '<div class="report_info">'
                    . get_string('usage_categorystudentsoverview_info', 'report_myfeedback') . '</div>';

                $uids = $report->get_unique_category_users($categoryid);

                // Currently the table says '0 students' if there are no students enrolled in the category, so it's not too bad.
                echo $report->get_student_statistics_table($uids, $reporttype, true, $categoryname,
                    "/report/myfeedback/index.php?currenttab=usage&reporttype=$reporttype&categoryid=$categoryid"
                    . $sesskeyqs
                );

                // Get each sub category too.
                $subcategories = $report->get_subcategories($categoryid);
                foreach ($subcategories as $subcat) {
                    $uids = $report->get_unique_category_users($subcat->id);
                    echo $report->get_student_statistics_table($uids, $reporttype, true,
                        $report->get_category_name($subcat->id),
                        "/report/myfeedback/index.php?currenttab=usage&reporttype=$reporttype&categoryid=$subcat->id"
                        . $sesskeyqs,
                        false
                    );
                }
                // Close the table body and table.
                echo '</tbody></table>';
            }
            break;
        case 'categorystaffoverview':
            echo $report->get_subcategory_menu(0, $categoryid);
            echo $report->search_all_categories($searchusage, $reporttype);

            // Get the parent category name for categoryid=0.
            $categoryname = "";

            if ($categoryid > -1) {
                if ($categoryid > 0) {
                    $categoryname = $report->get_category_name($categoryid);
                } else if ($categoryid == 0) {
                    $categoryname = $SITE->fullname;
                }

                echo $report->export_print_buttons();
                $_SESSION['tutor'] = 'ustaffover';
                $reporttitle = get_string('category', 'report_myfeedback') . " "
                    . lcfirst(get_string('staff', 'report_myfeedback')) . " " . get_string('overview', 'report_myfeedback')
                    . ": " . $categoryname;
                echo "<h3>" . $reporttitle . $report->get_parent_category_link($categoryid, $reporttype) . "</h3>";
                echo '<div class="report_info">' . get_string('usage_categorystaffoverview_info', 'report_myfeedback') . '</div>';

                // Get the category users.
                $uids = $report->get_unique_category_users($categoryid, 'report/myfeedback:modtutor');

                // Currently the table says '0 staff' if there are no staff enrolled in the category.
                echo $report->get_staff_statistics_table($uids, true, true, $categoryname,
                    "/report/myfeedback/index.php?currenttab=usage&reporttype=$reporttype&categoryid=$categoryid"
                    . $sesskeyqs
                );

                // Get each sub category too.
                $subcategories = $report->get_subcategories($categoryid);
                foreach ($subcategories as $subcat) {
                    $uids = $report->get_unique_category_users($subcat->id, 'report/myfeedback:modtutor');
                    echo $report->get_staff_statistics_table($uids, true, true, $report->get_category_name($subcat->id),
                        "/report/myfeedback/index.php?currenttab=usage&reporttype=$reporttype&categoryid=$subcat->id"
                        . $sesskeyqs,
                        false
                    );
                }
                // Close the table body and table.
                echo '</tbody></table>';
            }
            break;
        case 'categorystudents':
            echo $report->get_subcategory_menu(0, $categoryid);
            echo $report->search_all_categories($searchusage, $reporttype);

            if ($categoryid > -1) {
                echo $report->export_print_buttons();
                $_SESSION['tutor'] = 'ustud';
                $reporttitle = get_string('category', 'report_myfeedback') . " "
                    . lcfirst(get_string('dashboard_students', 'report_myfeedback')) . ": "
                    . $report->get_category_name($categoryid);

                echo "<h3>" . $reporttitle . $report->get_parent_category_link($categoryid, $reporttype) . "</h3>";
                echo '<div class="report_info">' . get_string('usage_categorystudents_info', 'report_myfeedback') . '</div>';

                // Get the category users.
                $uids = $report->get_unique_category_users($categoryid);

                // Currently the table says '0 students' if there are no students enrolled in the category, so it's not too bad.
                echo $report->get_student_statistics_table($uids, $reporttype, false, $report->get_category_name($categoryid));
            }
            break;
        case 'categorystaff':
            echo $report->get_subcategory_menu(0, $categoryid);
            echo $report->search_all_categories($searchusage, $reporttype);

            if ($categoryid > -1) {
                echo $report->export_print_buttons();
                $_SESSION['tutor'] = 'ustaff';
                $reporttitle = get_string('category', 'report_myfeedback') . " "
                    . lcfirst(get_string('staff', 'report_myfeedback')) . ": " . $report->get_category_name($categoryid);

                // TODO: get all the personal tutors attached to the students in this category?!?!
                echo "<h3>" . $reporttitle . $report->get_parent_category_link($categoryid, $reporttype) . "</h3>";
                echo '<div class="report_info">' . get_string('usage_categorystaff_info', 'report_myfeedback') . '</div>';

                // Get the category users.
                $uids = $report->get_unique_category_users($categoryid, 'report/myfeedback:modtutor');
                // Currently the table says '0 students' if there are no students enrolled in the category.
                echo $report->get_staff_statistics_table($uids, true, false, $report->get_category_name($categoryid));
            }
            break;
        case 'coursestudentsoverview':
            echo $report->search_all_categories($searchusage, $reporttype, $hideor = true);

            if ($categoryid > -1) {
                echo $report->export_print_buttons();
                $_SESSION['tutor'] = 'ustudover';
                if ($categoryid > 0) {
                    $categoryname = $report->get_category_name($categoryid);
                } else if ($categoryid == 0) {
                    $categoryname = $SITE->fullname;
                }

                $reporttitle = get_string('gradetblheader_course', 'report_myfeedback') . " "
                    . lcfirst(get_string('dashboard_students', 'report_myfeedback')) .  " "
                    . lcfirst(get_string('overview', 'report_myfeedback')) . ": " . $categoryname;

                echo "<h3>" . $reporttitle . $report->get_parent_category_link($categoryid, $reporttype) . "</h3>";
                echo '<div class="report_info">' . get_string('usage_coursestudentsoverview_info', 'report_myfeedback') . '</div>';

                // Get all the courses in the category (including subcategories).
                $courses = $report->get_category_courses($categoryid);

                // Print the category overview.
                $uids = $report->get_unique_category_users($categoryid);
                echo $report->get_student_statistics_table($uids, $reporttype, true, $categoryname,
                    "/report/myfeedback/index.php?currenttab=usage&reporttype=coursestudents&categoryid=$categoryid"
                    . $sesskeyqs
                );

                foreach ($courses as $course) {
                    // Get the course students.
                    $uids = array();
                    // Note: active users refers to students who haven't had their enrolments suspended.
                    $coursecontext = context_course::instance($course->id);
                    $modenrolledusers = get_enrolled_users(
                        $coursecontext,
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
                            $uids[] = $puid->id; // Uids per module.
                        }
                    }
                    // Only print the overall stats for each course, not all students
                    // (but shows these as standard rows, not heading rows).
                    echo $report->get_student_statistics_table($uids, $reporttype, true, $course->fullname,
                        "/report/myfeedback/index.php?currenttab=usage&reporttype=coursestudents&courseid=$course->id"
                        . $sesskeyqs,
                        false
                    );
                }
                // Close the table body and table.
                echo '</tbody></table>';
            }
            break;
        case 'coursestaffoverview':
            echo $report->search_all_categories($searchusage, $reporttype, $hideor = true);

            if ($categoryid > -1) {
                echo $report->export_print_buttons();
                $_SESSION['tutor'] = 'ustaffover';
                if ($categoryid > 0) {
                    $categoryname = $report->get_category_name($categoryid);
                } else if ($categoryid == 0) {
                    $categoryname = $SITE->fullname;
                }
                $reporttitle = get_string('gradetblheader_course', 'report_myfeedback') . " "
                    . lcfirst(get_string('staff', 'report_myfeedback')) .  " "
                    . lcfirst(get_string('overview', 'report_myfeedback')) . ": " . $categoryname;

                echo "<h3>" . $reporttitle . $report->get_parent_category_link($categoryid, $reporttype) . "</h3>";
                echo '<div class="report_info">' . get_string('usage_coursestaffoverview_info', 'report_myfeedback') . '</div>';

                // Get all the courses in the category (including subcategories).
                $courses = $report->get_category_courses($categoryid);

                // Print the category overview.
                $uids = $report->get_unique_category_users($categoryid, 'report/myfeedback:modtutor');
                echo $report->get_staff_statistics_table($uids, $reporttype, true, $categoryname,
                        "/report/myfeedback/index.php?currenttab=usage&reporttype=coursestaff&categoryid=$categoryid"
                        . $sesskeyqs
                     );

                foreach ($courses as $course) {
                    // Get the course staff.
                    $uids = array();
                    // Note: active users refers to students who haven't had their enrolments suspended.
                    $coursecontext = context_course::instance($course->id);
                    $modenrolledusers = get_enrolled_users(
                        $coursecontext,
                        $cap = 'report/myfeedback:modtutor',
                        $groupid = 0,
                        $userfields = 'u.id',
                        $orderby = null,
                        $limitfrom = 0,
                        $limitnum = 0,
                        $onlyactive = true
                    );
                    if ($modenrolledusers) {
                        foreach ($modenrolledusers as $puid) {
                            $uids[] = $puid->id; // Uids per module.
                        }
                    }
                    // Only print the overall stats for each course, not all students
                    // (but shows these as standard rows, not heading rows).
                    echo $report->get_staff_statistics_table($uids, $reporttype, true, $course->fullname,
                        "/report/myfeedback/index.php?currenttab=usage&reporttype=coursestaff&courseid=$course->id"
                        . $sesskeyqs,
                        false
                    );
                }
                 // Close the table body and table.
                 echo '</tbody></table>';
            }
            break;

        case 'coursestudents':
            echo $report->search_all_courses($searchusage, "coursestudents");

            if ($courseid > 0) {
                echo $report->export_print_buttons();
                $_SESSION['tutor'] = 'ustud';
                $reporttitle = get_string('gradetblheader_course', 'report_myfeedback') . " "
                    . lcfirst(get_string('dashboard_students', 'report_myfeedback')) . ": " . $report->get_course_name($courseid)
                    . " (".$report->get_course_name($courseid, false).")";

                echo "<h3>" . $reporttitle . $report->get_course_category_link($courseid, "categorystudents") . "</h3>";
                echo '<div class="report_info">' . get_string('usage_coursestudents_info', 'report_myfeedback') . '</div>';

                // Get the course users.
                // Note: active users refers to students who haven't had their enrolments suspended.
                $coursecontext = context_course::instance($courseid);
                $uids = array();
                $modenrolledusers = get_enrolled_users(
                    $coursecontext,
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
                        $uids[] = $puid->id; // Uids per module.
                    }
                }

                // Currently the page says 'Can not find data record in database table course.'
                // if the course id is wrong, so it's not too bad.
                echo $report->get_student_statistics_table($uids, $reporttype, false, $report->get_course_name($courseid, false));
            }
            break;

        case 'coursestaff':
            echo $report->search_all_courses($searchusage, "coursestaff");


            if ($courseid > 0) {
                echo $report->export_print_buttons();
                $_SESSION['tutor'] = 'ustaff';
                $reporttitle = get_string('gradetblheader_course', 'report_myfeedback') . " "
                    . lcfirst(get_string('staff', 'report_myfeedback')) . ": " . $report->get_course_name($courseid)
                    . " (".$report->get_course_name($courseid, false).")";

                // TODO: get all the personal tutors attached to the students in this course?!?!
                echo "<h3>" . $reporttitle . $report->get_course_category_link($courseid, "categorystaff") . "</h3>";
                echo '<div class="report_info">' . get_string('usage_coursestaff_info', 'report_myfeedback') . '</div>';

                $uids = array();
                // Get the course users.
                // Note: active users refers to students who haven't had their enrolments suspended.
                // Not sure it's quite accurate though.
                $coursecontext = context_course::instance($courseid);

                $modenrolledusers = get_enrolled_users(
                    $coursecontext,
                    $cap = 'report/myfeedback:modtutor',
                    $groupid = 0,
                    $userfields = 'u.id',
                    $orderby = null,
                    $limitfrom = 0,
                    $limitnum = 0,
                    $onlyactive = true
                );
                if ($modenrolledusers) {
                    foreach ($modenrolledusers as $puid) {
                        $uids[] = $puid->id; // Uids per module.
                    }
                }

                // Currently the page says 'Can not find data record in database table course.'
                // if the course id is wrong, so it's not too bad.
                echo $report->get_staff_statistics_table($uids, true, false, $report->get_course_name($courseid, false));
            }
            break;

        case 'personaltutorstudents':
            echo $report->search_all_users($searchusage, "personaltutorstudents");

            if ($reportuserid > 0) {
                echo $report->export_print_buttons();
                $_SESSION['tutor'] = 'ustud';
                $reporttitle = get_string('personaltutees', 'report_myfeedback') . ": " . $report->get_names($reportuserid);
                echo "<h3>" . $reporttitle . "</h3>";
                echo '<div class="report_info">' . get_string('usage_personaltutorstudents_info', 'report_myfeedback') . '</div>';

                if ($personaltutees = $report->get_personal_tutees($reportuserid)) {
                    foreach ($personaltutees as $puid) {
                        $uids[] = $puid->id; // Uids per module.
                    }
                }
                // Currently the table says '0 students' if there are no students enrolled in the category, which is ok.
                echo $report->get_student_statistics_table($uids, $reporttype, false, $report->get_names($reportuserid));
            }
            break;

        case 'staffmember':
            echo $report->search_all_users($searchusage, "staffmember");

            if ($reportuserid > 0) {
                echo $report->export_print_buttons();
                $_SESSION['tutor'] = 'ustaff';
                $reporttitle = get_string('staffmember', 'report_myfeedback') . ": " . $report->get_names($reportuserid);
                echo "<h3>" . $reporttitle . "</h3>";
                echo '<div class="report_info">' . get_string('usage_staffmember_info', 'report_myfeedback') . '</div>';

                // Check the user is not suspended or deleted.
                if ($report->is_active_user($reportuserid)) {
                    echo $report->get_staff_statistics_table(array($reportuserid), true);
                } else {
                    echo "This user is suspended or deleted";
                }
            }
            break;
        case 'student':
            echo $report->search_all_users($searchusage, "student");

            if ($reportuserid > 0) {
                echo $report->export_print_buttons();
                $_SESSION['tutor'] = 'ustud';
                $reporttitle = get_string('student', 'report_myfeedback') . ": " . $report->get_names($reportuserid);
                echo "<h3>" . $reporttitle. "</h3>";
                echo '<div class="report_info">' . get_string('usage_student_info', 'report_myfeedback') . '</div>';

                // Check the user is not suspended or deleted.
                if ($report->is_active_user($reportuserid)) {
                    echo $report->get_student_statistics_table(array($reportuserid), $reporttype);
                } else {
                    echo "This user is suspended or deleted";
                }
            }
            break;
    }

    // Set the userid in the session - used for excel export?
    $_SESSION['myfeedback_userid'] = $userid;
    $_SESSION['user_name'] = $reporttitle;

    $event = \report_myfeedback\event\myfeedbackreport_viewed_usagedash::create(
        [
            'context' => context_user::instance($USER->id),
            'relateduserid' => $USER->id
        ]
    );
    $event->trigger();
} else {
    echo get_string('nopermission', 'report_myfeedback') . ".";
}

echo "</div>";
// Stop the progress bar when page loads.
