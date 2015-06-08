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
 *			  <richard.havinga@ulcc.ac.uk>. The code for using an external database is taken from Juan leyva's
 *			  <http://www.twitter.com/jleyvadelgado> configurable reports block.
 *            The idea for this reporting tool originated with Dr Jason Davies <j.p.davies@ucl.ac.uk> and 
 *            Dr John Mitchell <j.mitchell@ucl.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'My Feedback';
$string['my_feedback'] = 'My Feedback';
$string['blocktitle'] = 'My Feedback';
$string['blockstring'] = 'My Feedback string';
$string['gradetblgroup1'] = 'Assessment';
$string['gradetblgroup2'] = 'Submission';
$string['gradetblgroup3'] = 'Feedback';
$string['search'] = 'Search';
$string['gradetblheader_course'] = 'Course';
$string['gradetblheader_assessment'] = 'Name (part name)';
$string['gradetblheader_type'] = 'Type';
$string['gradetblheader_duedate'] = 'Due Date';
$string['gradetblheader_submission'] = 'Submitted';
$string['gradetblheader_feedback'] = 'Feedback';
$string['gradetblheader_grade'] = 'Grade';
$string['noenrolments'] = 'This user has not yet been enrolled in any courses';
$string['manual_gradeitem'] = 'manual item';
$string['offline_assignment'] = 'offline';
$string['turnitin_assignment'] = 'Turnitin';
$string['moodle_assignment'] = 'Assignment';
$string['workshop'] = 'Workshop';
$string['quiz'] = 'Quiz';
$string['no_submission'] = 'no submission';
$string['draft_submission'] = 'draft submission';
$string['late_submission_msg'] = 'The assignment was submitted late. Disregard if you were granted an individual extension.';
$string['no_submission_msg'] = 'There is no submission and it is past the due date. Disregard if you have been granted an individual extension.';
$string['draft_submission_msg'] = 'The assignment is still in draft status. It has not yet been submitted';
$string['new_window_msg'] = 'Opens in a new window';
$string['groupwork'] = 'group';
$string['originality'] = 'Originality';
$string['grademark'] = 'GradeMark';
$string['draft'] = 'draft';
$string['rubric'] = 'view rubric';
$string['grading_form'] = 'view grading form';
$string['feedback'] = 'view';
$string['attempt'] = 'review attempt';
$string['attempt'] = 'attempt';
$string['attempts'] = 'attempts';
$string['review'] = 'review';
$string['submission'] = 'view submission';
$string['feedback'] = 'view feedback';
$string['provisional_grades'] = 'The marks shown here are provisional and may include marks for assessments that do not count towards your final grade. Please refer to the student record system to see a formal record of your grade.';
$string['dbhost'] = "DB Host";
$string['dbhostinfo'] = "Remote Database host name (on which the SQL queries will be executed - must be a duplicate of this Moodle database instance - used for avoiding load issues on primary Moodle database).<br />Leave blank to use the default Moodle database.";
$string['dbname'] = "DB Name";
$string['dbnameinfo'] = "Remote Database name (on which the SQL queries will be executed - must be a duplicate of this Moodle database instance - used for avoiding load issues on primary Moodle database).<br />Leave blank to use the default Moodle database.";
$string['dbuser'] = "DB Username";
$string['dbuserinfo'] = "Remote Database username (should have SELECT privileges on above DB).<br />Leave blank to use the default Moodle database.";
$string['dbpass'] = "DB Password";
$string['dbpassinfo'] = "Remote Database password (for above username).<br />Leave blank to use the default Moodle database.";
