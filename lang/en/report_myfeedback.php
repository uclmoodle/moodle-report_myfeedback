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
 * My Feedback Report.
 *
 * @package   report_myfeedback
 * @author    Jessica Gramp <j.gramp@ucl.ac.uk>
 * @credits   Based on original work report_mygrades by David Bezemer <david.bezemer@uplearning.nl> which in turn is based on 
 * 			  block_myfeedback by Karen Holland, Mei Jin, Jiajia Chen. Also uses SQL originating from Richard Havinga 
 * 			  <richard.havinga@ulcc.ac.uk>. The code for using an external database is taken from Juan leyva's
 * 			  <http://www.twitter.com/jleyvadelgado> configurable reports block.
 *            The idea for this reporting tool originated with Dr Jason Davies <j.p.davies@ucl.ac.uk> and 
 *            Dr John Mitchell <j.mitchell@ucl.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['pluginname'] = 'My feedback';
$string['my_feedback'] = 'My feedback';
$string['myfeedback:view'] = 'View My feedback';
$string['myfeedback:progadmin'] = 'My feedback departmental admin';
$string['myfeedback:personaltutor'] = 'My feedback personal tutor';
$string['myfeedback:modtutor'] = 'My feedback module tutor';
$string['dashboard'] = 'My feedback report';
$string['blocktitle'] = 'My feedback';
$string['blockstring'] = 'My feedback string';
$string['search'] = 'Search';
$string['ownreport'] = 'View own dashboard';
$string['gradetblheader_course'] = 'Course';
$string['gradetblheader_module'] = 'Module';
$string['gradetblheader_assessment'] = 'Assessment (part name)';
$string['gradetblheader_type'] = 'Type';
$string['gradetblheader_duedate'] = 'Due date';
$string['gradetblheader_submissiondate'] = 'Submission date';
$string['gradetblheader_submission_feedback'] = 'Submission / Feedback';
$string['gradetblheader_feedback'] = 'Full feedback';
$string['gradetblheader_generalfeedback'] = 'General feedback';
$string['gradetblheader_grade'] = 'Grade';
$string['gradetblheader_availablegrade'] = 'Available grade';
$string['gradetblheader_range'] = 'Range';
$string['gradetblheader_bar'] = 'Bar graph';
$string['gradetblheader_viewed'] = 'Viewed';
$string['gradetblheader_weighting'] = 'Weighting';
$string['gradetblheader_relativegrade'] = 'Relative grade';
$string['gradetblheader_selfreflectivenotes'] = 'Self-reflective notes';
$string['selectall'] = 'Select all';
$string['tutortblheader_name'] = 'Name';
$string['tutortblheader_personaltutees'] = 'Personal tutees / Course names';
$string['tutortblheader_assessment'] = 'Assessments';
$string['tutortblheader_assessment_info'] = 'A count of all assessments with a due date in the past, or that have been  submitted to, plus any manual grade items.';
$string['tutortblheader_nonsubmissions'] = 'Non-submissions';
$string['tutortblheader_nonsubmissions_info'] = 'The number of missed submissions for all due assessments.';
$string['tutortblheader_latesubmissions'] = 'Late <br> submissions';
$string['tutortblheader_latesubmissions_info'] = 'The number of late submissions for all due assessments.';
$string['tutortblheader_graded'] = 'Graded <br> assessments';
$string['tutortblheader_graded_info'] = 'The number of assessments graded to date with feedback visible to students.';
$string['tutortblheader_latefeedback'] = 'Late feedback';
$string['tutortblheader_latefeedback_info'] = 'The number of assessments where feedback was returned to students more than 4 weeks after the due date, or submission date, whichever is later.';
$string['tutortblheader_lowgrades'] = 'Low graded <br> (<50%)';
$string['tutortblheader_lowgrades_info'] = 'The number of assessments graded to date that scored below 50%.';
$string['tutortblheader_overallgrade'] = 'Overall grade';
$string['tutortblheader_overallgrade_info'] = 'The graph shows the lowest grade, median grade (in red)  and highest grade (numbered and marked above the graph). Each student\'s grade is shown out of 100 (under the graph to the right) and is shown in red (<40%), amber (41-50%) or green (>50%).';
$string['student_overall_info'] = 'The number of students who score in each percentile and the lowest grade, median grade (in red) and highest grade (numbered and marked above the graph)';
$string['student_due_info'] = 'The number of students who are expected to complete this assessment.';
$string['student_nonsub_info'] = 'The number of students who missed submissions for this assessment.';
$string['student_late_info'] = 'The number of students with late submissions for this assessment.';
$string['student_graded_info'] = 'The number of students graded to date with feedback visible for this assessment.';
$string['student_feed_info'] = 'The number of students with late feedback for this assessment.';
$string['student_low_info'] = 'The number of students who scored below 50% for this assessment.';
$string['dashboard_students'] = 'Students';
$string['dashboard_assessments'] = 'Assessments';
$string['noenrolments'] = 'This user has not yet been enrolled in any courses';
$string['manual_gradeitem'] = 'Manual item';
$string['offline_assignment'] = 'offline';
$string['turnitin_assignment'] = 'Turnitin';
$string['moodle_assignment'] = 'Assignment';
$string['workshop'] = 'Workshop';
$string['quiz'] = 'Quiz';
$string['no_submission'] = 'no submission';
$string['draft_submission'] = 'draft submission';
$string['late_submission_msg'] = 'The assignment was submitted';
$string['no_submission_msg'] = 'There is no submission and it is past the due date. Disregard if you have been granted an individual extension.';
$string['draft_submission_msg'] = 'The assignment is still in draft status. It has not yet been submitted';
$string['new_window_msg'] = 'Opens in a new window';
$string['groupwork'] = 'group';
$string['originality'] = 'Originality';
$string['grademark'] = 'GradeMark';
$string['draft'] = 'draft';
$string['rubric'] = 'view rubric';
$string['rubrictext'] = ' Rubric';
$string['markingguide'] = ' Marking guide';
$string['grading_form'] = 'view grading form';
$string['feedback'] = 'view feedback';
$string['attempt'] = 'review attempt';
$string['attempt'] = 'attempt';
$string['attempts'] = 'attempts';
$string['review'] = 'review';
$string['reviewlastof'] = 'review last of {$a} attempts ';
$string['reviewaattempt'] = 'review {$a} attempt ';
$string['submission'] = 'view submission';
$string['fullfeedback'] = 'view full feedback';
$string['programme'] = 'Second level: ';
$string['userprogramme'] = 'Programme: ';
$string['faculty'] = 'Top level: ';
$string['studentrecordsystem'] = 'Launch Student Record System';
$string['studentrecordsystemlink'] = '';
$string['provisional_grades'] = 'The marks shown here are provisional and may include marks for assessments that do not count towards your final grade. Please refer to the
student record system to see a formal record of your grade.';
$string['archivedbnotexist'] = "This databse does not exist or access details incorrect. Admin must configure My feedback report settings page correctly!";
$string['dbhost'] = "DB Host";
$string['dbhostinfo'] = "Remote Database host name (on which the SQL queries will be executed - must be a duplicate of this Moodle database instance - used for avoiding load issues on primary Moodle database).<br />Leave blank to use the default Moodle database.";
$string['archivedomain'] = 'Archived FQDN';
$string['archivedomaininfo'] = 'The Fully Qualified Domain Name (FQDN) with http/https';
$string['archivedomaindefault'] = '';
$string['archivedbhost'] = "Archived DB Host";
$string['archivedbhostinfo'] = "Archived Database host name (on which the SQL queries will be executed)";
$string['dbname'] = "DB Name";
$string['dbnameinfo'] = "Remote Database name (on which the SQL queries will be executed - must be a duplicate of this Moodle database instance - used for avoiding load issues on primary Moodle database).<br />Leave blank to use the default Moodle database.";
$string['dbuser'] = "DB Username";
$string['dbuserinfo'] = "Remote Database username (should have SELECT privileges on above DB).<br />Leave blank to use the default Moodle database.";
$string['archivedbuser'] = "Archived DB Username";
$string['archivedbuserinfo'] = "Archive Database username (should have SELECT privileges on above DB).";
$string['dbpass'] = "DB Password";
$string['dbpassinfo'] = "Remote database password (for above username).<br />Leave blank to use the default Moodle database.";
$string['archivedbpass'] = "Archived DB Password";
$string['archivedbpassinfo'] = "Archive database password (for above username).";
$string['archivedbsettings'] = "Archived Database Settings";
$string['archiveyears'] = "Archived years";
$string['archiveyearsinfo'] = "How many years of archive do you want to make available?";
$string['archivenamingconvention'] = "Archived DB naming convention";
$string['archivenamingconventioninfo'] = 'What naming convention do you use before your academic year e.g."moodle_archive_xxxx" where xxxx is the two digit value for the academic years e.g."1415". <br>The current release only uses the default convention.';
$string['archivenamingconventiondefault'] = "moodle_archive_";
$string['parentdepartment'] = 'Parent department:';
$string['department'] = 'Department: ';
$string['lastmoodlelogin'] = 'Last Moodle login: ';
$string['enrolledstudents'] = 'Enrolled students: ';
$string['overallmodule'] = 'Overall Course (to-date): ';
$string['overallfeedback'] = 'Overall feedback';
$string['enrolledmodules'] = 'Moodle Courses currently enrolled on:';
$string['modulesteach'] = 'Moodle Courses currently teaching:';
$string['eventreportviewed'] = 'My feedback report viewed';
$string['eventreportaddfeedback'] = 'My feedback Turnitin feedback added';
$string['eventreportaddnotes'] = 'My feedback notes added';
$string['eventreportupdatefeedback'] = 'My feedback Turnitin feedback updated';
$string['eventreportupdatenotes'] = 'My feedback notes updated';
$string['eventreportdownload'] = 'My feedback report table downloaded';
$string['eventptutordownload'] = 'Personal tutor dashboard analytics downloaded';
$string['eventmtutordownload'] = 'Module tutor dashboard analytics downloaded';
$string['tabs_overview'] = 'Overview';
$string['tabs_feedback'] = 'Feedback comments';
$string['tabs_mymodules'] = 'My courses';
$string['tabs_mytutees'] = 'My students';
$string['tabs_ptutor'] = 'Personal tutor';
$string['tabs_tutor'] = 'Personal tutor dashboard';
$string['tabs_mtutor'] = 'Module tutor dashboard';
$string['progadmin_dashboard'] = 'Departmental admin dashboard';
$string['tabs_feedback_text'] = 'Here you can view the general feedback from your assessments. This is taken from the General feedback section on assessments including Moodle Assignments, Peer Assessed Workshops and Quizzes. The feedback from Turnitin Assignments needs to be manually copied and pasted in to the report, since this can\'t be imported automatically. Click \'view feedback\' to view your assessment feedback in full. If you are on a small screen you may need to click the plus icon to see this link.';
$string['tabs_progadmin'] = 'Departmental admin overview';
$string['tabs_academicyear'] = 'Academic year';
$string['return-2-dash'] = 'Return to my dashboard';
$string['wordcloud_title'] = 'Words used commonly in feedback';
$string['wordcloud_text'] = 'This word cloud shows the frequency of words and phrases that have appeared in your feedback - the larger the word, the more frequently it is used in feedback. Hover over a word to see how many times and where it has been used.';
$string['email_address'] = 'Email address:';
$string['email_tutor'] = 'Email your tutor';
$string['email_tutor_subject'] = 'Your%20Personal%20Tutee';
$string['email_tutee_subject'] = 'Your%20Personal%20Tutor';
$string['email_dept_subject'] = 'Your%20Departmental%20Admin';
$string['sendmail'] = 'Send mail';
$string['meet_tutor'] = 'Meet your tutor';
$string['tutor_messages'] = 'Messages';
$string['print_report'] = 'Print';
$string['export_to_excel'] = 'Export to Excel';
$string['reset_table'] = 'Reset table';
$string['notutor'] = 'You have no personal tutor details';
$string['progadminview'] = ' - departmental admin view';
$string['moduleleaderview'] = ' - tutor view';
$string['personaltutorview'] = ' - tutor view';
$string['personaltutee'] = 'Personal tutee';
$string['othertutee'] = 'Other tutee';
$string['studentsaccessto'] = 'Any personal tutees you are allocated will show by default in the list below. To see all the students in the Moodle courses you teach, please click the \'Show all students\' checkbox';
$string['alltutees'] = 'Show all students ';
$string['relativegradedescription'] = 'This shows your grade position relative to the class for all numeric grades.';
$string['togglegrade'] = 'Toggle grade';
$string['togglegradedescription'] = 'You can toggle between the relative grade and the absolute grade graphs.';
$string['addnotes'] = 'Add notes';
$string['editnotes'] = 'Edit notes';
$string['addnotestitle'] = 'Click here to add self-reflective notes';
$string['editnotestitle'] = 'Click here to edit';
$string['addfeedback'] = 'It is not possible to display Turnitin feedback automatically.';
$string['copyfeedback'] = 'Paste general feedback from Turnitin';
$string['viewfeedback'] = 'view the feedback directly in Turnitin';
$string['editfeedback'] = 'Edit feedback';
$string['addfeedbacktitle'] = 'Click here to add feedback from Turnitin';
$string['viewfeedbacktitle'] = 'Click here to view feedback in Turnitin';
$string['editfeedbacktitle'] = 'Click here to edit';
$string['selfaddedfeedback'] = 'Self-added feedback';
$string['studentaddedfeedback'] = 'Student-added feedback';
$string['addeditnotes'] = 'Add/Edit notes';
$string['savenotes'] = 'Save notes and close';
$string['addeditfeedback'] = 'Add/Edit feedback';
$string['savefeedback'] = 'Save feedback and close';
$string['insertsuccessful'] = 'Data successfully inserted';
$string['updatesuccessful'] = 'Data successfully updated';
$string['dbsettings'] = 'Current Academic Year Database Settings';
$string['relectivenotesdescription'] = 'All self-reflective notes are visible to your personal tutor and departmental administrators';
$string['academicyear'] = 'Academic year';
$string['noarchivesupporth1'] = '<h1>No support for that version of Moodle!</h1>';
$string['noarchivesupporth2'] = '<h2>Too many database changes were made for the report to support this!</h2><h2>Select a different tab or refresh your screen to continue...</h2>';
$string['mymodules'] = 'My courses';
$string['moddescription'] = 'Use the Ctrl/Shift to select multiple courses if applicable.';
$string['suborassessed'] = 'Assessed';
$string['courselimitinfo'] = '<p style="color: #990000;">Too many courses to show statistics for this category. Please choose a course in the drop-down list above to show statistics for that course.</p>';
$string['courselimitheading'] = 'Dept Admin Second Level Category Course Limit';
$string['courselimit'] = 'Course limit';
$string['courselimitsettings'] = 'Set the limit for the number of courses to show statistics for second level category';
$string['overviewlimitheading'] = 'Overview Tab Course Limit';
$string['overviewlimit'] = 'Overview tab course limit';
$string['overviewlimitsettings'] = 'Set the limit for the number of courses to show in the overview tab';
$string['more'] = ', ...more ';
$string['moreinfo'] = 'Click to view full list of courses';
$string['bargraphdesc'] = 'The lowest grade is {$a->minimum}%, the median grade is {$a->mean}% and the highest grade is {$a->maximum}%. The number of students who scored within each grade range is displayed within the graph itself.';
$string['studentgraphdesc'] = 'The lowest grade is {$a->minimum}%, the median grade is {$a->mean}% and the highest grade is {$a->maximum}%. The student\'s score is {$a->studentscore}%.';
$string['year'] = 'Year ';
$string['reportfor'] = 'Report for ';
$string['filename'] = 'MyFeedback_report_';
$string['p_tutor_filename'] = 'MyFeedback_p_tutor_report_';
$string['mod_tutor_filename'] = 'MyFeedback_mod_tutor_report_';
$string['dept_admin_filename'] = 'MyFeedback_dept_admin_report_';
$string['exportheader'] = 'Course,Assessment,Type,Due date,Submission date,Grade,Grade range,General feedback,Viewed';
$string['p_tutor_exportheader'] = 'Tutee Firstname/Course Shortname,Lastname,Assessments,Non-submissions,Late submissions,Graded assessments,Low grades';
$string['mod_tutor_exportheader'] = 'Course Name/User Firstname,Lastname,Assessments,Non-submissions,Late submissions,Graded assessments,Low grades';
$string['dept_admin_exportheader'] = 'Course Name/User Firstname,Lastname,Assessments,Non-submissions,Late submissions,Graded assessments,Low grades';
$string['p_tutor_report'] = 'Personal tutor dashboard report';
$string['mod_tutor_report'] = 'Module tutor dashboard report';
$string['dept_admin_report'] = 'Departmental admin dashboard report - stats at selected second level category only';
$string['nodata'] = 'No data';
$string['nodatatodisplay'] = 'No data to display';
$string['nodataforyear'] = 'No data for this academic year';
$string['nographtodisplay'] = 'No graph to display';
$string['nomodule'] = 'No course for this academic year';
$string['assessmentbreakdown'] = 'Assessment breakdown';
$string['studentbreakdown'] = 'Student breakdown';
$string['coursebreakdown'] = 'Course breakdown';
$string['analyse'] = 'Analyse';
$string['coursecolon'] = 'Course: ';
$string['statsperstudent'] = 'Stats per student';
$string['statspercourse'] = 'Stats per course';
$string['statsperassessment'] = 'Stats per assessment';
$string['modtutorstats'] = 'Module tutor stats';
$string['secondlevelcat'] = 'Second level category: ';
$string['moduletutors'] = 'Module tutors';
$string['tutorgroups'] = 'Tutor groups (+/-)';
$string['selectallforemail'] = 'Select all for emailing';
$string['groupname'] = 'Group name';
$string['relationship'] = 'Relationship';
$string['browsersupport'] = 'Your browser does not support the HTML5 canvas tag.';
$string['uncategorized'] = 'Uncategorized';
$string['hasfeedbackfile'] = 'Has feedback file.';
$string['tutorfeedback'] = 'Tutor feedback:';
$string['peerfeedback'] = 'Peer feedback:';
$string['selfassessment'] = 'Self-assessment:';
$string['waslate'] = ' {$a->late} late.';
$string['comments'] = 'Comments:';
$string['allparts'] = ' (all parts)';

$string['yearlevel'] = 'Yr level';
$string['tutee'] = 'Tutee';
$string['programme'] = 'Programme';
$string['personaltutors'] = 'Personal tutors';
$string['tutees+-'] = 'Tutees (+/-)';
