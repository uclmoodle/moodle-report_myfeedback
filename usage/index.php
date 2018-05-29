<?php

/*
 * The main file for the Usage dashboard
 * 
 * @package  report_myfeedback
 * @author   Jessica Gramp <j.gramp@ucl.ac.uk> and <jgramp@gmail.com>
 * @author   Delvon Forrester <delvon@esparanza.co.uk>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//TODO: turn off error reporting
// Report all errors except E_NOTICE
error_reporting(E_ALL & E_NOTICE);

defined('MOODLE_INTERNAL') || die;

$report->setup_ExternalDB();

//check permission to view usage reports - must have a role with this permission
//TODO: Make the permission site wide only (if possible).
if ($report->get_dashboard_capability($USER->id, 'report/myfeedback:usage')) {
	//display the description for those who have permission
	echo "<div class=\"usagereport\"><p>" . get_string('overview_text_usage', 'report_myfeedback') . "</p>";
	
	//get the report type from the URL
	$report_type = optional_param('reporttype', '', PARAM_TEXT);  
	$categoryid = optional_param('categoryid', -1, PARAM_INT);
	$courseid = optional_param('courseid', 0, PARAM_INT);
	$reportuserid = optional_param('reportuserid', 0, PARAM_INT);
	//$personaltutorid = optional_param('personaltutorid', 1, PARAM_INT);
	//$staffuserid = 143; //add this to userid in URL instead
	
	//$report_type =  'coursestudents'; //'coursestaff'; //'categorystaff'; //'staffmember'; //'personaltutorstudents' //'categorystudents'; //student?? 	    //categorystaff //coursestaff
	$report_menu = array(
		"categorystudentsoverview" => get_string('categorystudents', 'report_myfeedback') . " " . get_string('overview', 'report_myfeedback'),
		"categorystaffoverview" => get_string('categorystaff', 'report_myfeedback') . " " . get_string('overview', 'report_myfeedback'),
		"categorystudents" => get_string('categorystudents', 'report_myfeedback'),
		"categorystaff" => get_string('categorystaff', 'report_myfeedback'),
		"coursestudentsoverview" => get_string('coursestudents', 'report_myfeedback') . " " . get_string('overview', 'report_myfeedback'),
		"coursestaffoverview" => get_string('coursestaff', 'report_myfeedback') . " " . get_string('overview', 'report_myfeedback'),
		"coursestudents" => get_string('coursestudents', 'report_myfeedback'),
		"coursestaff" => get_string('coursestaff', 'report_myfeedback'),
		"student" => get_string('student', 'report_myfeedback'),
		"staffmember" => get_string('staffmember', 'report_myfeedback'),
		"personaltutorstudents" => get_string('personaltutees', 'report_myfeedback')
		);
	//display report type menu
	echo "<form method=\"GET\" id=\"report_form_select\" class=\"report_form\" action=\"\">".get_string('reporttype', 'report_myfeedback').": </span>";
		echo "<input type=\"hidden\" name=\"reportuserid\" value=\"$reportuserid\" />";
		echo "<input type=\"hidden\" name=\"categoryid\" value=\"$categoryid\" />";
		echo "<input type=\"hidden\" name=\"courseid\" value=\"$courseid\" />";
		echo "<input type=\"hidden\" name=\"currenttab\" value=\"usage\" />";
		echo "<select id=\"reportSelect\" value=\"$report_type\" name=\"reporttype\">";
			echo "<option value=\"\">".get_string('choosedots')."</option>";
			foreach($report_menu as $rvalue => $rname){
				echo "<option value=\"".$rvalue."\"";
				if ($report_type == $rvalue) {
					echo " selected";
				}
				echo ">".$rname."</option>";
			}
		echo "</select>";
	echo "</form>";
	$reporttitle = "";
	$uids = array();

    //Table for students / staff
	switch($report_type){
		case 'categorystudentsoverview':
			echo $report->get_subcategory_menu(0, $categoryid);
			echo $report->search_all_categories($searchusage, $report_type);
			
			//get the parent category name for categoryid=0
			$categoryname = "";
			if($categoryid > -1){
				if($categoryid > 0){
					$categoryname = $report->get_category_name($categoryid);
				}elseif($categoryid == 0){
					$categoryname = $SITE->fullname;
				}	
			
				//get the category users
				echo $report->export_print_buttons();
				$_SESSION['tutor'] = 'ustudover';
				$reporttitle = get_string('category', 'report_myfeedback') . " " . lcfirst(get_string('student', 'report_myfeedback')) . " " . get_string('overview', 'report_myfeedback') . ": " . $categoryname;
				echo "<h3>" . $reporttitle . $report->get_parent_category_link($categoryid, $report_type) ."</h3>";
				echo '<div class="report_info">' . get_string('usage_categorystudentsoverview_info', 'report_myfeedback') . '</div>';
				
				$uids = $report->get_unique_category_users($categoryid);
				
				//Currently the table says '0 students' if there are no students enrolled in the category, so it's not too bad
				echo $report->get_student_statistics_table($uids, $report_type, true, $categoryname, "/report/myfeedback/index.php?currenttab=usage&reporttype=$report_type&categoryid=$categoryid");
				
				//get each sub category too
				$subcategories = $report->get_subcategories($categoryid);
				foreach($subcategories as $subcat){
					$uids = $report->get_unique_category_users($subcat->id);
					echo $report->get_student_statistics_table($uids, $report_type, true, $report->get_category_name($subcat->id), "/report/myfeedback/index.php?currenttab=usage&reporttype=$report_type&categoryid=$subcat->id", false);
				}
				//close the table body and table
				echo '</tbody></table>';
			}
			break;
			
		case 'categorystaffoverview':
			echo $report->get_subcategory_menu(0, $categoryid);
			echo $report->search_all_categories($searchusage, $report_type);
			
			//get the parent category name for categoryid=0
			$categoryname = "";

			if($categoryid > -1){
				if($categoryid > 0){
					$categoryname = $report->get_category_name($categoryid);
				}elseif($categoryid == 0){
					$categoryname = $SITE->fullname;
				}	
			
				echo $report->export_print_buttons();
				$_SESSION['tutor'] = 'ustaffover';
				$reporttitle = get_string('category', 'report_myfeedback') . " " . lcfirst(get_string('staff', 'report_myfeedback')) . " " . get_string('overview', 'report_myfeedback') . ": " . $categoryname;
				echo "<h3>" . $reporttitle . $report->get_parent_category_link($categoryid, $report_type) . "</h3>";
				echo '<div class="report_info">' . get_string('usage_categorystaffoverview_info', 'report_myfeedback') . '</div>';
				
				//get the category users
				$uids = $report->get_unique_category_users($categoryid, 'report/myfeedback:modtutor');
				
				//Currently the table says '0 staff' if there are no staff enrolled in the category
				echo $report->get_staff_statistics_table($uids, true, true, $categoryname, "/report/myfeedback/index.php?currenttab=usage&reporttype=$report_type&categoryid=$categoryid");
				
				//get each sub category too
				$subcategories = $report->get_subcategories($categoryid);
				foreach($subcategories as $subcat){
					$uids = $report->get_unique_category_users($subcat->id, 'report/myfeedback:modtutor');
					echo $report->get_staff_statistics_table($uids, true, true, $report->get_category_name($subcat->id), "/report/myfeedback/index.php?currenttab=usage&reporttype=$report_type&categoryid=$subcat->id", false);
				}
				//close the table body and table
				echo '</tbody></table>';
			}
			break;
		
		case 'categorystudents':
			echo $report->get_subcategory_menu(0, $categoryid);
			echo $report->search_all_categories($searchusage, $report_type);

			
			if($categoryid > -1){
				echo $report->export_print_buttons();
				$_SESSION['tutor'] = 'ustud';
				$reporttitle = get_string('category', 'report_myfeedback') . " " . lcfirst(get_string('dashboard_students', 'report_myfeedback')) . ": " . $report->get_category_name($categoryid);
				
				echo "<h3>" . $reporttitle . $report->get_parent_category_link($categoryid, $report_type) . "</h3>";
				echo '<div class="report_info">' . get_string('usage_categorystudents_info', 'report_myfeedback') . '</div>';
				
				//get the category users
				$uids = $report->get_unique_category_users($categoryid);
				
				//Currently the table says '0 students' if there are no students enrolled in the category, so it's not too bad
				echo $report->get_student_statistics_table($uids, $report_type, false, $report->get_category_name($categoryid));
			}
			break;
			
		case 'categorystaff':
			echo $report->get_subcategory_menu(0, $categoryid);
			echo $report->search_all_categories($searchusage, $report_type);
			
			if($categoryid > -1){
				echo $report->export_print_buttons();
				$_SESSION['tutor'] = 'ustaff';
				$reporttitle = get_string('category', 'report_myfeedback') . " " . lcfirst(get_string('staff', 'report_myfeedback')) . ": " . $report->get_category_name($categoryid);
				
				//TODO: get all the personal tutors attached to the students in this category?!?!
				echo "<h3>" . $reporttitle . $report->get_parent_category_link($categoryid, $report_type) . "</h3>";
				echo '<div class="report_info">' . get_string('usage_categorystaff_info', 'report_myfeedback') . '</div>';
				
				//get the category users
				$uids = $report->get_unique_category_users($categoryid, 'report/myfeedback:modtutor');
				//Currently the table says '0 students' if there are no students enrolled in the category
				echo $report->get_staff_statistics_table($uids, true, false, $report->get_category_name($categoryid));
			}
			break;
			
		case 'coursestudentsoverview':
			//echo $report->get_subcategory_menu(0, $categoryid);
			echo $report->search_all_categories($searchusage, $report_type, $hideor = true);
			
			if($categoryid > -1){
				echo $report->export_print_buttons();
				$_SESSION['tutor'] = 'ustudover';
				if($categoryid > 0){
					$categoryname = $report->get_category_name($categoryid);
				}elseif($categoryid == 0){
					$categoryname = $SITE->fullname;
				}
				
				$reporttitle = get_string('gradetblheader_course', 'report_myfeedback') . " " . lcfirst(get_string('dashboard_students', 'report_myfeedback')) .  " " . lcfirst(get_string('overview', 'report_myfeedback')) . ": " . $categoryname;
				
				echo "<h3>" . $reporttitle . $report->get_parent_category_link($categoryid, $report_type) . "</h3>";
				echo '<div class="report_info">' . get_string('usage_coursestudentsoverview_info', 'report_myfeedback') . '</div>';
				
				//get all the courses in the category (including subcategories)
				$courses = $report->get_category_courses($categoryid);
				
				//print the category overview
				$uids = $report->get_unique_category_users($categoryid);
				echo $report->get_student_statistics_table($uids, $report_type, true, $categoryname, "/report/myfeedback/index.php?currenttab=usage&reporttype=coursestudents&categoryid=$categoryid");
				
				foreach($courses as $course){
					//get the course students 
					$uids = array();
					//Note: active users refers to students who haven't had their enrolments suspended.
					$course_context = context_course::instance($course->id);
					if ($mod_enrolled_users = get_enrolled_users($course_context, $cap = 'report/myfeedback:student', $groupid = 0, $userfields = 'u.id', $orderby = null, $limitfrom = 0, $limitnum = 0, $onlyactive = true)) {
						foreach ($mod_enrolled_users as $puid) {
							$uids[] = $puid->id; //uids per module
						}
					}
					//only print the overall stats for each course, not all students (but shows these as standard rows, not heading rows)
					echo $report->get_student_statistics_table($uids, $report_type, true, $course->fullname, "/report/myfeedback/index.php?currenttab=usage&reporttype=coursestudents&courseid=$course->id", false);
				}
				//close the table body and table
				echo '</tbody></table>';
			}
			break;
			
	case 'coursestaffoverview':
			//echo $report->get_subcategory_menu(0, $categoryid);
			echo $report->search_all_categories($searchusage, $report_type, $hideor = true);
			
			if($categoryid > -1){
				echo $report->export_print_buttons();
				$_SESSION['tutor'] = 'ustaffover';
				if($categoryid > 0){
					$categoryname = $report->get_category_name($categoryid);
				}elseif($categoryid == 0){
					$categoryname = $SITE->fullname;
				}
				$reporttitle = get_string('gradetblheader_course', 'report_myfeedback') . " " . lcfirst(get_string('staff', 'report_myfeedback')) .  " " . lcfirst(get_string('overview', 'report_myfeedback')) . ": " . $categoryname;
				
				echo "<h3>" . $reporttitle . $report->get_parent_category_link($categoryid, $report_type) . "</h3>";
				echo '<div class="report_info">' . get_string('usage_coursestaffoverview_info', 'report_myfeedback') . '</div>';
				
				//get all the courses in the category (including subcategories)
				$courses = $report->get_category_courses($categoryid);
				
				//print the category overview
				$uids = $report->get_unique_category_users($categoryid, 'report/myfeedback:modtutor');
				echo $report->get_staff_statistics_table($uids, $report_type, true, $categoryname, "/report/myfeedback/index.php?currenttab=usage&reporttype=coursestaff&categoryid=$categoryid");
				
				foreach($courses as $course){
					//get the course staff 
					$uids = array();
					//Note: active users refers to students who haven't had their enrolments suspended.
					$course_context = context_course::instance($course->id);
					if ($mod_enrolled_users = get_enrolled_users($course_context, $cap = 'report/myfeedback:modtutor', $groupid = 0, $userfields = 'u.id', $orderby = null, $limitfrom = 0, $limitnum = 0, $onlyactive = true)) {
						foreach ($mod_enrolled_users as $puid) {
							$uids[] = $puid->id; //uids per module
						}
					}
					//only print the overall stats for each course, not all students (but shows these as standard rows, not heading rows)
					echo $report->get_staff_statistics_table($uids, $report_type, true, $course->fullname, "/report/myfeedback/index.php?currenttab=usage&reporttype=coursestaff&courseid=$course->id", false);
				}
				//close the table body and table
				echo '</tbody></table>';
			}
			break;
		
		case 'coursestudents':
			echo $report->search_all_courses($searchusage, "coursestudents");
			
			if($courseid > 0){
				echo $report->export_print_buttons();
				$_SESSION['tutor'] = 'ustud';
				$reporttitle = get_string('gradetblheader_course', 'report_myfeedback') . " " . lcfirst(get_string('dashboard_students', 'report_myfeedback')) . ": " . $report->get_course_name($courseid) . " (".$report->get_course_name($courseid, false).")";
				
				echo "<h3>" . $reporttitle . $report->get_course_category_link($courseid, "categorystudents") . "</h3>";
				echo '<div class="report_info">' . get_string('usage_coursestudents_info', 'report_myfeedback') . '</div>';
				
				//get the course users 
				//Note: active users refers to students who haven't had their enrolments suspended.
				$course_context = context_course::instance($courseid);
				$uids = array();
				if ($mod_enrolled_users = get_enrolled_users($course_context, $cap = 'report/myfeedback:student', $groupid = 0, $userfields = 'u.id', $orderby = null, $limitfrom = 0, $limitnum = 0, $onlyactive = true)) {
					foreach ($mod_enrolled_users as $puid) {
						$uids[] = $puid->id; //uids per module
					}
				}
				
				//Currently the page says 'Can not find data record in database table course.' if the course id is wrong, so it's not too bad.
				echo $report->get_student_statistics_table($uids, $report_type, false, $report->get_course_name($courseid, false));
			}
			break;
			
		case 'coursestaff':
			echo $report->search_all_courses($searchusage, "coursestaff");

			
			if($courseid > 0){
				echo $report->export_print_buttons();
				$_SESSION['tutor'] = 'ustaff';
				$reporttitle = get_string('gradetblheader_course', 'report_myfeedback') . " " . lcfirst(get_string('staff', 'report_myfeedback')) . ": " . $report->get_course_name($courseid) . " (".$report->get_course_name($courseid, false).")";
				
				//TODO: get all the personal tutors attached to the students in this course?!?!
				echo "<h3>" . $reporttitle . $report->get_course_category_link($courseid, "categorystaff") . "</h3>";
				echo '<div class="report_info">' . get_string('usage_coursestaff_info', 'report_myfeedback') . '</div>';
				
				$uids = array();
				//get the course users 
				//Note: active users refers to students who haven't had their enrolments suspended. Not sure it's quite accurate though.
				$course_context = context_course::instance($courseid);

				if ($mod_enrolled_users = get_enrolled_users($course_context, $cap = 'report/myfeedback:modtutor', $groupid = 0, $userfields = 'u.id', $orderby = null, $limitfrom = 0, $limitnum = 0, $onlyactive = true)) {
					foreach ($mod_enrolled_users as $puid) {
						$uids[] = $puid->id; //uids per module
					}
				}
				
				//Currently the page says 'Can not find data record in database table course.' if the course id is wrong, so it's not too bad.
				echo $report->get_staff_statistics_table($uids, true, false, $report->get_course_name($courseid, false));
			}
			break;
			
		case 'personaltutorstudents':
			echo $report->search_all_users($searchusage, "personaltutorstudents");

			if($reportuserid > 0){
				echo $report->export_print_buttons();
				$_SESSION['tutor'] = 'ustud';
				$reporttitle = get_string('personaltutees', 'report_myfeedback') . ": " . $report->get_names($reportuserid);
				echo "<h3>" . $reporttitle . "</h3>";
				echo '<div class="report_info">' . get_string('usage_personaltutorstudents_info', 'report_myfeedback') . '</div>';
			
				if($personal_tutees = $report->get_personal_tutees($reportuserid)){
					foreach ($personal_tutees as $puid) {
						$uids[] = $puid->id; //uids per module
					}
				}
				//Currently the table says '0 students' if there are no students enrolled in the category, which is ok
				echo $report->get_student_statistics_table($uids, $report_type, false, $report->get_names($reportuserid));
			}
			break;
	
		case 'staffmember':
			echo $report->search_all_users($searchusage, "staffmember");
			
			if($reportuserid > 0){
				echo $report->export_print_buttons();
				$_SESSION['tutor'] = 'ustaff';
				$reporttitle = get_string('staffmember', 'report_myfeedback') . ": " . $report->get_names($reportuserid);
				echo "<h3>" . $reporttitle . "</h3>";
				echo '<div class="report_info">' . get_string('usage_staffmember_info', 'report_myfeedback') . '</div>';
				
				//check the user is not suspended or deleted
				if($report->is_active_user($reportuserid)){
					echo $report->get_staff_statistics_table(array($reportuserid), true);
				}else{
					echo "This user is suspended or deleted";
				}
			}
			break;
		
		case 'student':
			echo $report->search_all_users($searchusage, "student");
			
			if($reportuserid > 0){
				echo $report->export_print_buttons();
				$_SESSION['tutor'] = 'ustud';
				$reporttitle = get_string('student', 'report_myfeedback') . ": " . $report->get_names($reportuserid);
				echo "<h3>" .  $reporttitle. "</h3>";
				echo '<div class="report_info">' . get_string('usage_student_info', 'report_myfeedback') . '</div>';
				
				//check the user is not suspended or deleted
				if($report->is_active_user($reportuserid)){
					echo $report->get_student_statistics_table(array($reportuserid), $report_type);
				}else{
					echo "This user is suspended or deleted";
				}
			}
			break;
	 }


	 //set the userid in the session - used for excel export?
    //$_SESSION['exp_sess'] = $exceltable;
    $_SESSION['myfeedback_userid'] = $userid;
    //$_SESSION['user_name'] = 'nil';
	$_SESSION['user_name'] =  $reporttitle;
	 
	 $event = \report_myfeedback\event\myfeedbackreport_viewed_usagedash::create(array('context' => context_user::instance($USER->id), 'relateduserid' => $USER->id));
$event->trigger();
}else{
	echo get_string('nopermission', 'report_myfeedback') . ".";
}

echo "</div>";
//stop the progress bar when page loads.
echo "<script type=\"text/javascript\">
    $(document).ready( function () {

$('#wait').css({'cursor':'default','display':'none'});
$('body').css('cursor', 'default');

$('#reportSelect').change(function(){
    $('#report_form_select').submit();    
});

$('#categorySelect').change(function(){
    $('#report_category_select').submit();    
});

$('td#long-name').css({
    'max-width': '300px',
    'white-space': 'nowrap',
    'overflow': 'hidden',
    'text-overflow': 'ellipsis'
});

var usertable = $('#userstable').DataTable({
        'dom': 'rtip',
        'order': [[0, 'asc' ]]
});

var usagetable = $('#usagetable').DataTable({
		/*dom: 'f',*/
		pageLength: -1,
		filtering: false,
		ordering: true,
		searching: false,
		pagination: false,
		order: [[1, 'desc' ]]
});

$('.reportPrint').on( 'click', function () {
        print();
});

$('.x_port').on( 'click', function() {
window.location.href= 'export.php';
});

});
</script>";