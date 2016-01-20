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
$string['myfeedback:progadmin'] = 'My feedback programme admin';
$string['myfeedback:personaltutor'] = 'My feedback personal tutor';
$string['dashboard'] = 'My feedback report';
$string['blocktitle'] = 'My feedback';
$string['blockstring'] = 'My feedback string';
$string['search'] = 'Search';
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
$string['noenrolments'] = 'This user has not yet been enrolled in any courses';
$string['manual_gradeitem'] = 'Manual item';
$string['offline_assignment'] = 'offline';
$string['turnitin_assignment'] = 'Turnitin';
$string['moodle_assignment'] = 'Assignment';
$string['workshop'] = 'Workshop';
$string['quiz'] = 'Quiz';
$string['no_submission'] = 'no submission';
$string['draft_submission'] = 'draft submission';
$string['late_submission_msg'] = 'The assignment was submitted late.';
$string['no_submission_msg'] = 'There is no submission and it is past the due date. Disregard if you have been granted an individual extension.';
$string['draft_submission_msg'] = 'The assignment is still in draft status. It has not yet been submitted';
$string['new_window_msg'] = 'Opens in a new window';
$string['groupwork'] = 'group';
$string['originality'] = 'Originality';
$string['grademark'] = 'GradeMark';
$string['draft'] = 'draft';
$string['rubric'] = 'view rubric';
$string['grading_form'] = 'view grading form';
$string['feedback'] = 'view feedback';
$string['attempt'] = 'review attempt';
$string['attempt'] = 'attempt';
$string['attempts'] = 'attempts';
$string['review'] = 'review';
$string['submission'] = 'view submission';
$string['fullfeedback'] = 'view full feedback';
$string['programme'] = 'Programme: ';
$string['provisional_grades'] = 'The marks shown here are provisional and may include marks for assessments that do not count towards your final grade. Please refer to the
    <a href="https://evision.ucl.ac.uk/urd/sits.urd/run/siw_lgn" title="Portico login" rel="tooltip">student record system</a> to see a formal record of your grade.';
$string['dbhost'] = "DB Host";
$string['dbhostinfo'] = "Remote Database host name (on which the SQL queries will be executed - must be a duplicate of this Moodle database instance - used for avoiding load issues on primary Moodle database).<br />Leave blank to use the default Moodle database.";
$string['dbname'] = "DB Name";
$string['dbnameinfo'] = "Remote Database name (on which the SQL queries will be executed - must be a duplicate of this Moodle database instance - used for avoiding load issues on primary Moodle database).<br />Leave blank to use the default Moodle database.";
$string['dbuser'] = "DB Username";
$string['dbuserinfo'] = "Remote Database username (should have SELECT privileges on above DB).<br />Leave blank to use the default Moodle database.";
$string['dbpass'] = "DB Password";
$string['dbpassinfo'] = "Remote Database password (for above username).<br />Leave blank to use the default Moodle database.";
$string['parentdepartment'] = 'Parent department:';
$string['department'] = 'Department: ';
$string['lastmoodlelogin'] = 'Last Moodle login: ';
$string['enrolledstudents'] = 'Enrolled students: ';
$string['overallmodule'] = 'Overall Module (to-date): ';
$string['enrolledmodules'] = 'Modules currently enrolled on:';
$string['modulesteach'] = 'Modules currently teaching:';
$string['eventreportviewed'] = 'My feedback report viewed';
$string['eventreportdownload'] = 'My feedback report table downloaded';
$string['tabs_overview'] = 'Overview';
$string['tabs_feedback'] = 'Feedback comments';
$string['tabs_mymodules'] = 'My modules';
$string['tabs_mytutees'] = 'My students';
$string['tabs_ptutor'] = 'Personal tutor';
$string['tabs_tutor'] = 'Personal tutoring';
$string['tabs_feedback_text'] = 'Here you can view the general feedback from the assessed parts of your modules. This is taken from the General feedback section on Moodle and Turnitin assignments. Select view feedback to view any of your assessments.';
$string['tabs_progadmin'] = 'Programme admin overview';
$string['tabs_academicyear'] = 'Academic year';
$string['return-2-dash'] = 'Return to my dashboard';
$string['wordcloud_title'] = 'Words used commonly in feedback';
$string['wordcloud_text'] = 'This word cloud shows the frequency of words and phrases that have appeared in your feedback - the larger the word, the more frequently it is used in feedback. Hover over a word to see how many times and where it has been used.';
$string['email_address'] = 'Email address:';
$string['email_tutor'] = 'Email your tutor';
$string['meet_tutor'] = 'Meet your tutor';
$string['tutor_messages'] = 'Messages';
$string['print_report'] = 'Print';
$string['export_to_excel'] = 'Export to Excel';
$string['reset_table'] = 'Reset table';
$string['notutor'] = 'You have no personal tutor details';
$string['progadminview'] = ' - programme admin view';
$string['moduleleaderview'] = ' - tutor view';
$string['personaltutorview'] = ' - tutor view';
$string['personaltutee'] = 'Personal tutee';
$string['othertutee'] = 'Other tutee';
$string['studentsaccessto'] = 'All the students that I have access to';
$string['relativegradedescription'] = 'This shows your grade position relative to the class for all numeric grades.';
$string['togglegrade'] = 'Toggle grade';
$string['togglegradedescription'] = 'You can toggle between the relative grade and the absolute grade graphs.';
$string['addnotes'] = 'Add notes';
$string['editnotes'] = 'Edit notes';
$string['addnotestitle'] = 'Click here to add self-reflective notes';
$string['editnotestitle'] = 'Click here to edit';
$string['addfeedback'] = 'It is not possible to display turnitin feedback automatically. You can ';
$string['copyfeedback'] = 'paste general feedback from Turnitin';
$string['viewfeedback'] = 'view the feedback directly in turnitin';
$string['editfeedback'] = 'Edit feedback';
$string['addfeedbacktitle'] = 'Click here to add feedback from turnitin';
$string['viewfeedbacktitle'] = 'Click here to view feedback in turnitin';
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
$string['relectivenotesdescription'] = 'All self-reflective notes are visible to your personal tutor and programme administrators';