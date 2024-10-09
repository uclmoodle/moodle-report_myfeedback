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
 * @copyright 2022 UCL
 * @author    Jessica Gramp <j.gramp@ucl.ac.uk>
 * @author    Delvon Forrester <delvon@esparanza.co.uk>
 *  credits   Based on original work report_mygrades by David Bezemer <david.bezemer@uplearning.nl> which in turn is based on
 *            block_myfeedback by Karen Holland, Mei Jin, Jiajia Chen. Also uses SQL originating from Richard Havinga
 *            <richard.havinga@ulcc.ac.uk>. The code for using an external database is taken from Juan leyva's
 *            <http://www.twitter.com/jleyvadelgado> configurable reports block.
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
$string['myfeedback:student'] = 'My feedback student';
$string['myfeedback:usage'] = 'My feedback usage statistics';
$string['dashboard'] = 'My feedback report';
$string['blocktitle'] = 'My feedback';
$string['blockstring'] = 'My feedback string';
$string['search'] = 'Search';
$string['ownreport'] = 'View own dashboard';
$string['gradetblheader_course'] = 'Course';
$string['gradetblheader_course_info'] = 'The name and link to the course that contains the assessment.';
$string['gradetblheader_module'] = 'Module';
$string['gradetblheader_assessment'] = 'Assessment (part name)';
$string['gradetblheader_assessment_info'] = ' The name and link to the assessment. Turnitin Assignments can contain multiple parts, so the part name for the assessment appears in brackets afterwards. Workshops provide a grade/feedback for the submitted work, as well as a grade/feedback for how well the students assess others\' work, so whether it is the original \'submission\' or the \'assessment\' of peers\' work appears in brackets afterwards.';
$string['gradetblheader_type'] = 'Type';
$string['gradetblheader_type_info'] = 'The type of assessment, whether it be a Moodle Assignment, Turnitin Assignment, Quiz, Workshop (for peer assessment), or a manual grade item entered directly into the Moodle gradebook.';
$string['gradetblheader_duedate'] = 'Due date';
$string['gradetblheader_duedate_info'] = 'The date the assessment was due (if applicable).';
$string['gradetblheader_submissiondate'] = 'Submission date';
$string['gradetblheader_submissiondate_info'] = 'The date the assessment was submitted. If submitted late, a warning icon will appear that shows how late it was when you hover over it. Quizzes with multiple attempts will show the last date. Moodle Assignments that have been uploaded, but not yet submitted (still in draft status) will show \'draft\' instead of a date. Assessments not submitted (but with grades or feedback) will show \'no submission\'.';
$string['gradetblheader_submission_feedback'] = 'Submission / Feedback';
$string['gradetblheader_feedback'] = 'Full feedback';
$string['gradetblheader_feedback_info'] = 'A link to the full feedback, showing any grades, written comments, in-text comments, rubrics or marking guide feedback against particular criteria. Any in-text comments will be displayed within the feedback file for Moodle Assignments. If a feedback file is available, a file icon will display.';
$string['gradetblheader_generalfeedback'] = 'General feedback';
$string['gradetblheader_generalfeedback_info'] = 'Feedback comments provided by the tutor (or by peers or the students themselves where indicated for workshops). Marking guide feedback for each criteria and selected rubric feedback will also be displayed for Moodle Assignments. Turnitin Assignment feedback must be copied and pasted into the report by a student or their tutor.';
$string['gradetblheader_grade'] = 'Grade';
$string['gradetblheader_grade_info'] = 'The grade the student received for the assessment.';
$string['gradetblheader_availablegrade'] = 'Available grade';
$string['gradetblheader_range'] = 'Range';
$string['gradetblheader_range_info'] = 'The range of grades possible for the assessment.';
$string['gradetblheader_bar'] = 'Bar graph';
$string['gradetblheader_bar_info'] = 'A visual representation of the grade achieved as a percentage (for numeric grades only).';
$string['gradetblheader_viewed'] = 'Viewed';
$string['gradetblheader_viewed_info'] = 'The date the feedback was first viewed by the student after being released.';
$string['gradetblheader_weighting'] = 'Weighting';
$string['gradetblheader_relativegrade'] = 'Relative grade';
$string['gradetblheader_selfreflectivenotes'] = 'Self-reflective notes';
$string['gradetblheader_selfreflectivenotes_info'] = 'Self-reflective notes added by the student. All self-reflective notes are visible to a students\' personal tutor and departmental administrators.';
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
$string['tutortblheader_latefeedback_info'] = 'The number of assessments where feedback was returned to students more than {$a->lte} days after the due date, or submission date, whichever is later.';
$string['tutortblheader_lowgrades'] = 'Low graded <br> (<50%)';
$string['tutortblheader_lowgrades_info'] = 'The number of assessments graded to date that scored below 50%.';
$string['tutortblheader_overallgrade'] = 'Overall grade';
$string['tutortblheader_overallgrade_info'] = 'The graph shows the lowest grade, median grade (in red)  and highest grade (numbered and marked above the graph). Each student\'s grade is shown out of 100 (under the graph to the right) and is shown in red (<40%), amber (41-50%) or green (>50%).';
$string['student_overall_info'] = 'The number of students who score in each percentile and the lowest grade, median grade (in red) and highest grade (numbered and marked above the graph)';
$string['student_due_info'] = 'The number of students who are expected to complete this assessment.';
$string['student_nonsub_info'] = 'The number of students who missed submissions for this assessment.';
$string['student_late_info'] = 'The number of students with late submissions for this assessment.';
$string['student_graded_info'] = 'The number of students graded to date with feedback visible for this assessment.';
$string['student_feed_info'] = 'The number of students where feedback was returned to them more than {$a->lte} days after the due date, or submission date, whichever is later.';
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
$string['late_submission_msg'] = 'The assessment was submitted';
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

$string['archivelinksheading'] = 'Archive Links';
$string['archivelink'] = 'Archive URL';
$string['archivelinksettings1'] = 'URL for Archive last year';
$string['archivelinksettings2'] = 'URL for Archive 2 years ago';
$string['archivelinksettings3'] = 'URL for Archive 3 years ago';
$string['archivelinksettings4'] = 'URL for Archive 4 years ago';
$string['archivelinksettings5'] = 'URL for Archive 5 years ago';

$string['studentrecordsystemlinkheading'] = 'Student Record System Link';
$string['studentrecordsystemlinktext'] = 'Student Record System Link';
$string['studentrecordsystemlinksettings'] = 'Set the link for the Student Record System';
$string['studentrecordsystemlink'] = 'https://';
$string['studentrecordsystemtext'] = 'Student Record System Button Text';
$string['studentrecordsystemsettings'] = 'Text on Button to launch the Student Record System';
$string['studentrecordsystem'] = 'Launch Student Record System';
$string['provisional_grades'] = 'The marks shown here are provisional and may include marks for assessments that do not count towards your final grade. Please refer to the
    <a href="" title="Student Record System login" rel="tooltip">student record system</a> to see a formal record of your grade.';
$string['archivedbnotexist'] = "This database does not exist or access details incorrect. Admin must configure My feedback report settings page correctly!";
$string['dbhost'] = "DB Host";
$string['dbhostinfo'] = "Remote Database host name (on which the SQL queries will be executed - must be a duplicate of this Moodle database instance - used for avoiding load issues on primary Moodle database).<br />Leave blank to use the default Moodle database.";
$string['archivedomain'] = 'Archived FQDN';
$string['archivedomaininfo'] = 'The Fully Qualified Domain Name (FQDN) with http/https';
$string['archivedomaindefault'] = 'https://';
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
$string['archivedbsettings'] = "Archived Database Settings (not recommended for production installations)";
$string['archiveyears'] = "Archived years";
$string['archiveyearsinfo'] = "How many years of archive do you want to make available?<br /> Years with <i>no</i> URL or <i>no</i> description will not be shown.";
$string['archivenamingconvention'] = "Archived DB naming convention";
$string['archivenamingconventioninfo'] = 'What naming convention do you use before your academic year e.g."moodle_archive_xxxx" where xxxx is the two digit value for the academic years e.g."1415". <br>The current release only uses the default convention.';
$string['archivenamingconventiondefault'] = "moodle_archive_";
$string['settingsacademicyear'] = 'Academic year';
$string['academicyearinfo'] = "The academic year this instance of Moodle relates to.";
$string['settingsacademicyeartext'] = 'Academic year description';
$string['academicyeartextinfo'] = "A text describing the academic year this instance of Moodle relates to.";
$string['archivedinstance'] = "Archived instance";
$string['archivedinstanceinfo'] = "Whether this instance of Moodle is an archived instance.";
$string['livedomaindefault'] = 'https://';
$string['livedomain'] = 'Live FQDN';
$string['livedomaininfo'] = 'The Fully Qualified Domain Name (FQDN) with http/https';
$string['parentdepartment'] = 'Parent department:';
$string['department'] = 'Department';
$string['lastmoodlelogin'] = 'Last Moodle login: ';
$string['enrolledstudents'] = 'Enrolled students: ';
$string['overallmodule'] = 'Overall Course (to-date): ';
$string['overallfeedback'] = 'Overall feedback';
$string['enrolledmodules'] = 'Moodle Courses currently enrolled on:';
$string['modulesteach'] = 'Moodle Courses currently teaching:';
$string['eventreportviewed'] = 'My feedback report viewed';
$string['eventreportviewed_mystudents'] = 'My feedback My students tab viewed';
$string['eventreportviewed_ptutor'] = 'My feedback Personal tutor dashboard viewed';
$string['eventreportviewed_mtutor'] = 'My feedback Module tutor dashboard viewed';
$string['eventreportviewed_dept'] = 'My feedback Departmental admin dashboard viewed';
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
$string['usage_dashboard'] = 'Usage dashboard';
$string['usage'] = 'Usage';
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
$string['print_report'] = 'Print  ';
$string['export_to_excel'] = 'Export to Excel';
$string['reset_table'] = 'Reset table';
$string['notutor'] = 'You have no personal tutor details';
$string['progadminview'] = ' - departmental admin view';
$string['moduleleaderview'] = ' - tutor view';
$string['personaltutorview'] = ' - tutor view';
$string['personaltutee'] = 'Personal tutee';
$string['othertutee'] = 'Other tutee';
$string['studentsaccessto'] = 'Any personal tutees you are allocated (in Portico) will show by default in the list below. To find a student in any of the Moodle courses you teach or administer, please search for their <b>email address</b>.';
$string['alltutees'] = 'Seach for students ';
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
$string['latefeedbackheading'] = 'Late Feedback';
$string['latefeedback'] = 'Late feedback days';
$string['latefeedbacksettings'] = 'The number of days for feedback to be late';
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
$string['usage_student_exportheader'] = 'Name,Viewed by,Total views,Self-reflective notes,Turnitin feedback,Downloads,Personal tutor,Last accessed';
$string['usage_studentoverview_exportheader'] = 'Name,Students,Viewed by,Total views,Self-reflective notes,Turnitin feedback,Downloads,Personal tutor,Last accessed';
$string['usage_staff_exportheader'] = 'Name,Total views,Own report views,My Students tab views,Students viewed,Student report views,Personal tutor dashboard views,Module tutor dashboard views,Departmental admin dashboard views,Downloads,Last accessed,Personal Tutees';
$string['usage_staffoverview_exportheader'] = 'Name,Staff,Total views,Own report views,My Students tab views,Students viewed,Student report views,Personal tutor dashboard views,Module tutor dashboard views,Departmental admin dashboard views,Downloads,Last accessed,Personal Tutees';
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
$string['comment'] = 'Comment:';
$string['allparts'] = ' (all parts)';
$string['numerrortitle'] = 'Number of errors:';
$string['accumulativetitle'] = 'Accumulative:';
$string['grade'] = 'Grade - ';
$string['comments'] = 'Comments strategy:';
$string['for'] = ' for ';
$string['print_msg'] = 'Use ESC to exit print screen';
$string['yearlevel'] = 'Yr level';
$string['tutee'] = 'Tutee';
$string['p_tut_programme'] = 'Programme';
$string['personaltutors'] = 'Personal tutors';
$string['tutees_plus_minus'] = 'Tutees (+/-)';
$string['overview_text_ptutor_tab'] = 'If you are a student studying a taught programme, your Personal Tutor\'s details will appear below. You can contact them to ask for guidance on your overall academic progress and personal and professional development.';
$string['overview_text_ptutor'] = 'This dashboard shows an overview of assessments for each of your personal tutees. You can see a course breakdown for each student by clicking on the toggle under their name. You can also send an email (blind copied, so students don\'t see each other\'s names) by selecting the checkbox in the Send Mail column and clicking the [Send mail] button.';
$string['overview_text_mtutor'] = 'This dashboard shows an overview of assessments for the modules you teach. You can select the modules you wish to analyse in the My courses list and then click the [analyse] button. Hold down the shift key to select a range of courses, or the ctrl key to select multiple courses individually. For each Moodle course you will see a breakdown of information for each assessment. You can also view a student breakdown for each assessment by clicking on the toggle under the assessment name, or by clicking the [student breakdown] button.';
$string['overview_text_dept'] = 'This dashboard shows an overview of assessments and students for all the Moodle courses within categories where you have been assigned \'My feedback Departmental Administrator\' access in Moodle. Choose the top level (faculty) and then the second level (department) from the drop-down lists. If you choose \'uncategorised\' for the second level list you will see courses from the top level category (assuming you have access at this level). You can also view assessments for a particular course within a category by clicking on a course name, or choosing it from the Course drop-down list. When you select a particular Moodle course you can also view module tutor and group information for that course. You can also send an email to the module tutors (blind copied, so they can\'t see each other\'s names) by selecting the checkbox in the Send Mail column and clicking the [Send mail] button.';
$string['overview_text_usage'] = 'This dashboard shows statistics for My feedback report usage. Please only use one window or tab at a time, as opening more than one could produce unusual results.';
$string['searchusers'] = 'Enter email address';
$string['searchcourses'] = 'Enter course code or name';
$string['searchcategory'] = 'Enter category name';
$string['overallstudentusage'] = 'Overall student usage';
$string['usagetblheader_courses'] = '# courses';
$string['usagetblheader_enrolled'] = '# enrolled';
$string['usagetblheader_viewedby'] = 'Viewed by';
$string['usagetblheader_viewed'] = 'Viewed';
$string['usagetblheader_totalviews'] = 'Total views';
$string['usagetblheader_notes'] = 'Self-reflective notes';
$string['usagetblheader_tiifeedback'] = 'Turnitin feedback';
$string['usagetblheader_downloads'] = 'Downloads';
$string['usagetblheader_lastaccessed'] = 'Last accessed';
$string['usagetblheader_ownreportviews'] = 'Own report views';
$string['usagetblheader_mystudenttabviews'] = 'My Students tab views';
$string['usagetblheader_studentsviewed'] = 'Students viewed';
$string['usagetblheader_studentreportviews'] = 'Student report views';
$string['mystudentssrch_username_info'] = 'The full name of the student who is your personal tutee, or whom you teach or support.';
$string['mystudentssrch_relationship_info'] = 'Either your \'Personal tutee\' or the short name of the courses you teach or administer where that student is enrolled.';
$string['usagesrchheader_username_info'] = 'The full name of the user and the link to their usage report. Shows their email address on hover.';
$string['usagesrchheader_userdept_info'] = 'The name of the user\'s department, to help find people with similar names.';
$string['usagesrchheader_coursename_info'] = 'The full name of the course (and shortname in brackets) and the link to its usage report.';
$string['usagesrchheader_coursecatname_info'] = 'The name of the category this course sits within and the link to its usage report.';
$string['usagesrchheader_catname_info'] = 'The name of the category and the link to its usage report.';
$string['usagesrchheader_pcatname_info'] = 'The name of the parent category this category sits within and the link to its usage report.';
$string['usagetblheader_name_info'] = 'The name of the course or user.';
$string['usagetblheader_courses_info'] = 'The number of courses within this category.';
$string['usagetblheader_students_info'] = 'The number of students enrolled in this course/category.';
$string['usagetblheader_staff_info'] = 'The number of staff enrolled in this course/category.';
$string['usagetblheader_viewedby_info'] = 'How many staff have viewed the report for this student.'; // Not currently implemented.
$string['usagetblheader_viewed_info'] = 'How many users (or whether one user has) viewed the report at least once.';
$string['usagetblheader_totalviews_info'] = 'The total number of views of any MyFeedback report.';
$string['usagetblheader_notes_info'] = 'The number of self-reflective notes added.';
$string['usagetblheader_tiifeedback_info'] = 'The number of Turnitin feedback notes added.';
$string['usagetblheader_downloads_info'] = 'The number of times MyFeedback reports have been downloaded.';
$string['usagetblheader_lastaccessed_info'] = 'The time the report was last accessed.';
$string['usagetblheader_ownreportviews_info'] = 'The number of times the user viewed their own report views (the default view).';
$string['usagetblheader_mystudenttabviews_info'] = 'The number of times the My Students tab was viewed.';
$string['usagetblheader_studentsviewed_info'] = 'The number of students\' MyFeedback reports viewed by this user.';
$string['usagetblheader_studentreportviews_info'] = 'The number of times student reports have been viewed in total.';
$string['usagetblheader_personaltutorviews_info'] = 'The number of times the user has viewed the personal tutor dashboard.';
$string['usagetblheader_modtutorviews_info'] = 'The number of times the user has viewed the module tutor dashboard.';
$string['usagetblheader_progadminviews_info'] = 'The number of times the user has viewed the departmental administrator dashboard.';
$string['usagetblheader_personaltutor_info'] = 'The number of personal tutors / name of the student\'s personal tutor and link to their usage report.';
$string['usagetblheader_personaltutees_info'] = 'The number of personal tutees / link to the personal tutees report for this user.';
$string['usage_categorystudentsoverview_info'] = 'This report shows an overview of student usage within a category.';
$string['usage_categorystaffoverview_info'] = 'This report shows an overview of staff usage within a category.';
$string['usage_categorystudents_info'] = 'This report shows a list of students and their individual usage within a category.';
$string['usage_categorystaff_info'] = 'This report shows a list of staff and their individual usage within a category.';
$string['usage_coursestudentsoverview_info'] = 'This report shows an overview of student usage within each course within a particular category.';
$string['usage_coursestaffoverview_info'] = 'This report shows an overview of staff usage within each course within a particular category.';
$string['usage_coursestudents_info'] = 'This report shows a list of students and their individual usage within a course.';
$string['usage_coursestaff_info'] = 'This report shows a list of staff and their individual usage within a course.';
$string['usage_student_info'] = 'This report shows the usage of an individual student across all their courses.';
$string['usage_staffmember_info'] = 'This report shows the usage of an individual staff member across all their courses.';
$string['usage_personaltutorstudents_info'] = 'This report shows an overview of a tutor\'s personal tutees and their My Feedback activity.';
$string['category'] = 'Category';
$string['view'] = 'view';
$string['viewadminreports'] = 'View admin reports';
$string['viewstudentreports'] = 'View student reports';
$string['viewtutorreports'] = 'View tutor reports';
$string['views'] = 'views';
$string['staff'] = 'staff';
$string['eventreportviewed_usage'] = 'My feedback Usage dashboard viewed';
$string['nopermission'] = 'You do not have permission to view this page';
$string['student'] = 'Student';
$string['reporttype'] = 'Report Type';
$string['staffmember'] = 'Staff member';
$string['personaltutees'] = 'Personal Tutees';
$string['apostrophe_s'] = '\'s ';
$string['usagereport'] = 'usage report';
$string['parent'] = 'Parent';
$string['overview'] = 'overview';
$string['categorystudents'] = 'Category students';
$string['coursestudents'] = 'Course students';
$string['categorystaff'] = 'Category staff';
$string['coursestaff'] = 'Course staff';
$string['personaltutorstudents'] = 'Personal tutor\'s students';
$string['or'] = 'or';
$string['close'] = 'Close';

$string['privacy:metadata:report_myfeedback'] = 'Self-reflective notes and Turnitin feedback manually entered by users.';
$string['privacy:metadata:report_myfeedback:userid'] = 'The Moodle databse ID of the user for whom notes and feedback is stored.';
$string['privacy:metadata:report_myfeedback:notes'] = 'Self-reflective notes entered by user';
$string['privacy:metadata:report_myfeedback:feedback'] = 'Turnitin feedback manually entered by user';
$string['privacy:metadata:report_myfeedback:coursefullname'] = 'Course that stored notes/feedback relate to';
$string['privacy:metadata:report_myfeedback:timemodified'] = 'Last modified timestamp of notes/feedback';
$string['privacy:metadata:report_myfeedback:gradeitemname'] = 'Grade item that stored notes/feedback relate to';

$string['current_academic_year'] = 'Current';
$string['archivelinktext'] = 'Description for archived year {$a}';
$string['archivelinktextinfo'] = 'Description for archive URL {$a} to be used in report.';

$string['usernotavailable'] = 'The details of this user are not available to you.';
$string['teachernopermission'] = 'You do not have permission to add feedback for this student.';
$string['studentnotincourse'] = 'This student is not enrolled in this course.';
$string['addnonfeedback'] = 'Add Turnitin feedback';
