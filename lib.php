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

defined('MOODLE_INTERNAL') || die;

/**
 * This function extends the navigation with the report items.
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param stdClass $context The context of the course
 */
function report_myfeedback_extend_navigation(global_navigation $navigation) {
    $url = new moodle_url('/report/myfeedback/index.php', array('course' => $course->id));
    $navigation->add(get_string('pluginname', 'report_myfeedback'), $url, null, null, new pix_icon('i/report', ''));
}

function report_myfeedback_extend_navigation_user($navigation, $user, $course) {
    $url = new moodle_url('/report/myfeedback/index.php' , array('userid' => $user->id));
    $navigation->add(get_string('pluginname', 'report_myfeedback'), $url, navigation_node::TYPE_SETTING, null, null,
            new pix_icon('i/report', ''));
}

function report_myreport_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('report/myreport:view', $context)) {
        $url = new moodle_url('/report/myfeedback/index.php', array('id' => $course->id));
        $navigation->add(get_string('pluginname', 'report_myfeedback'), $url, navigation_node::TYPE_SETTING, null, null,
                new pix_icon('i/report', ''));
    }
}

class report_myfeedback {

	/**
	 * Initialises the report and sets the title
	 */
	public function init() {
		$this->title = get_string('my_feedback', 'report_myfeedback');
	}
	
	/**
	 * Sets up global $DB moodle_database instance
	 *
	 * @global stdClass $CFG The global configuration instance.
	 * @see config.php
	 * @see config-dist.php
	 * @global stdClass $DB The global moodle_database instance.
	 * @return void|bool Returns true when finished setting up $DB. Returns void when $DB has already been set.
	 */
	function setup_ExternalDB() {
	    global $CFG, $DB, $remotedb;
	    
	    // Use a custom $remotedb (and not current system's $DB) if set - code sourced from configurable
	    // Reports plugin.
	    $remotedbhost = get_config('report_myfeedback', 'dbhost');
	    $remotedbname = get_config('report_myfeedback', 'dbname');
	    $remotedbuser = get_config('report_myfeedback', 'dbuser');
	    $remotedbpass = get_config('report_myfeedback', 'dbpass');
	    if (empty($remotedbhost) OR empty($remotedbname) OR empty($remotedbuser)) {
	                $remotedb = $DB;

	                setup_DB();
	    }else{
            //
    	    if (!isset($CFG->dblibrary)) {
    	        $CFG->dblibrary = 'native';
    	        // use new drivers instead of the old adodb driver names
    	        switch ($CFG->dbtype) {
    	            case 'postgres7' :
    	                $CFG->dbtype = 'pgsql';
    	                break;
    	
    	            case 'mssql_n':
    	                $CFG->dbtype = 'mssql';
    	                break;
    	
    	            case 'oci8po':
    	                $CFG->dbtype = 'oci';
    	                break;
    	
    	            case 'mysql' :
    	                $CFG->dbtype = 'mysqli';
    	                break;
    	        }
    	    }
    	
    	    if (!isset($CFG->dboptions)) {
    	        $CFG->dboptions = array();
    	    }
    	
    	    if (isset($CFG->dbpersist)) {
    	        $CFG->dboptions['dbpersist'] = $CFG->dbpersist;
    	    }
    	
    	    if (!$remotedb = moodle_database::get_driver_instance($CFG->dbtype, $CFG->dblibrary)) {
    	        throw new dml_exception('dbdriverproblem', "Unknown driver $CFG->dblibrary/$CFG->dbtype");
    	    }
    	
    	    try {
    	        $remotedb->connect($remotedbhost, $remotedbuser, $remotedbpass, $remotedbname, $CFG->prefix, $CFG->dboptions);
    	    } catch (moodle_exception $e) {
    	        if (empty($CFG->noemailever) and !empty($CFG->emailconnectionerrorsto)) {
    	            $body = "Connection error: ".$CFG->wwwroot.
    	            "\n\nInfo:".
    	            "\n\tError code: ".$e->errorcode.
    	            "\n\tDebug info: ".$e->debuginfo.
    	            "\n\tServer: ".$_SERVER['SERVER_NAME']." (".$_SERVER['SERVER_ADDR'].")";
    	            if (file_exists($CFG->dataroot.'/emailcount')){
    	                $fp = @fopen($CFG->dataroot.'/emailcount', 'r');
    	                $content = @fread($fp, 24);
    	                @fclose($fp);
    	                if((time() - (int)$content) > 600){
    	                    //email directly rather than using messaging
    	                    @mail($CFG->emailconnectionerrorsto,
    	                            'WARNING: Database connection error: '.$CFG->wwwroot,
    	                            $body);
    	                    $fp = @fopen($CFG->dataroot.'/emailcount', 'w');
    	                    @fwrite($fp, time());
    	                }
    	            } else {
    	                //email directly rather than using messaging
    	                @mail($CFG->emailconnectionerrorsto,
    	                        'WARNING: Database connection error: '.$CFG->wwwroot,
    	                        $body);
    	                $fp = @fopen($CFG->dataroot.'/emailcount', 'w');
    	                @fwrite($fp, time());
    	            }
    	        }
    	        // rethrow the exception
    	        throw $e;
    	    }
    	
    	    $CFG->dbfamily = $remotedb->get_dbfamily(); // TODO: BC only for now
    	
    	    return true;
    	}
    	return false;
	}

	/**
	 * Gets whether or not the module is installed and visible
	 *
	 * @param str $modname The name of the module
	 * @return bool true if the module exists and is not hidden in the site admin settings,
	 *         otherwise false
	 */
	public function mod_is_available($modname) {
		global $remotedb;
		$installedplugins = core_plugin_manager::instance()->get_plugins_of_type('mod');
		// Is the module installed?
		if (array_key_exists($modname, $installedplugins)) {
			// Is the module visible?
			if ($remotedb->get_field('modules', 'visible', array('name' => $modname
			))) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * Gets whether or not an online pdf feedback file has been generated
	 *
	 * @param str $gradeid The gradeid
	 * @return book true if there's a pdf feedback file for the submission, otherwise false
	 */
	public function has_pdf_feedback_file($gradeid) {
		global $remotedb;
		// Is there some online pdf feedback?
		if ($remotedb->record_exists('assignfeedback_editpdf_annot', array('gradeid' => $gradeid
		))) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get a user's quiz attempts for a particular quiz
	 *
	 * @param int $quizid The id of the quiz which comes from the gi.iteminstance
	 * @param int $userid The id of the user
	 * @param int $quizurlid The id of the quiz that can be used to access the quiz via the URL
	 * @return str Any comments left by a marker on a Turnitin Assignment via the Moodle Comments
	 *         feature (not in Turnitin), each on a new line
	 */
	public function get_quiz_attempts_link($quizid, $userid, $quizurlid) {
		global $CFG, $remotedb;
		$sqlcount = "SELECT count(attempt) as attempts
						FROM {quiz_attempts} qa
						WHERE quiz=? and userid=?";
		$params = array($quizid, $userid
		);
		$attemptcount = $remotedb->count_records_sql($sqlcount, $params, $limitfrom = 0,
				$limitnum = 0);
		$out = array();
		if ($attemptcount > 0) {
			$url = $CFG->wwwroot . "/mod/quiz/view.php?id=" . $quizurlid;
			$attemptstext = ($attemptcount > 1) ? get_string('attempts', 'report_myfeedback') : get_string(
					'attempt', 'report_myfeedback');
			$out[] = html_writer::link($url,
					get_string('review', 'report_myfeedback') . " " . $attemptcount . " " .
					$attemptstext);
		}
		$br = html_writer::empty_tag('br');
		return implode($br, $out);
	}

	/**
	 * Get group assignment submission date - since it won't come through for a user in a group
	 * unless they were the one's to upload the file
	 *
	 * @param int $userid The id of the user
	 * @param int $contextid The context of the file
	 * @return str submission dates, each on a new line if there are multiple
	 */
	public function get_group_assign_submission_date($userid, $contextid) {
		global $remotedb;
		// Group submissions.
		$sql = "SELECT su.timemodified
				FROM {files} f
				JOIN {assign_submission} su ON f.itemid = su.id
		   LEFT JOIN {groups_members} gm ON su.groupid = gm.groupid AND gm.userid = ?
				WHERE contextid=? AND filesize > 0 AND su.groupid <> 0";
		$params = array($userid, $contextid
		);
		$files = $remotedb->get_recordset_sql($sql, $params, $limitfrom = 0, $limitnum = 0);
		$out = array();
		foreach ($files as $file) {
			$out[] = $file->timemodified;
		}
		$br = html_writer::empty_tag('br');
		return implode($br, $out);
	}

	/**
	 * Get content to populate the feedback table
	 *
	 * @return str The table of submission and feedback for the user refrred to in the url after
	 *         userid=
	 */
	public function get_content() {
		global $remotedb, $USER, $COURSE, $CFG, $OUTPUT, $maxcommentlength;
		$userid = optional_param('userid', 0, PARAM_INT); // User id.
		if (empty($userid)) {
			$userid = $USER->id;
		}
		$this->content = new stdClass();
		$sql = "";
		if ($this->mod_is_available("assign")) {
			$sql .= "SELECT c.id AS courseid,
							c.fullname AS coursename,
							gi.itemname AS assessmentname,
							gg.finalgrade AS grade,
							gi.itemmodule AS assessmenttype,
							a.id AS assignid,
							cm.id AS assignmentid,
							a.teamsubmission,
							su.timemodified AS submissiondate,
							a.duedate AS duedate,
							a.name AS assessmentlink,
							'' AS tiiobjid,
							'' AS subid,
							'' AS subpart,
							'' AS partname,
							'' AS usegrademark,
							gg.feedback AS feedbacklink,
							gg.rawgrademax AS highestgrade,
							gg.userid,
							su.groupid,
							ag.id AS assigngradeid,
							con.id AS contextid,
							ga.activemethod,
							a.nosubmissions AS nosubmissions,
							su.status,
							apc.value AS onlinetext
						FROM {course} c
						JOIN {grade_items} gi ON c.id=gi.courseid
                             AND itemtype='mod' AND (gi.hidden = 0 or gi.hidden < unix_timestamp(current_timestamp))
						JOIN {grade_grades} gg ON gi.id=gg.itemid AND gg.userid = ?
						JOIN {course_modules} cm ON gi.iteminstance=cm.instance
						JOIN {context} con ON cm.id = con.instanceid
						JOIN {modules} m ON cm.module = m.id AND gi.itemmodule = m.name AND gi.itemmodule = 'assign'
						JOIN {assign} a ON a.id=gi.iteminstance
						JOIN mdl_assign_plugin_config apc on a.id = apc.assignment AND plugin = 'onlinetext'
						JOIN mdl_assign_user_flags auf ON a.id = auf.assignment AND auf.workflowstate = 'released'
                        AND  auf.userid = ? OR a.markingworkflow = 0
				   LEFT JOIN {assign_grades} ag ON a.id = ag.assignment AND ag.userid=?
				   LEFT JOIN {assign_submission} su ON a.id = su.assignment AND su.userid = ?
				   LEFT JOIN {grading_areas} ga ON con.id = ga.contextid
						WHERE cm.visible=1 ";
		}
		if ($this->mod_is_available("quiz")) {
			// If it's not the first select statement add a union.
			if ($sql != "") {
				$sql .= "UNION ";
			}
			$sql .= "SELECT c.id AS courseid,
							   c.fullname AS coursename,
							   gi.itemname AS assessmentname,
							   gg.finalgrade AS grade,
							   gi.itemmodule AS assessmenttype,
							   a.id AS assignid,
							   cm.id AS assignmentid,
							   '' AS teamsubmission,
							   '' AS submissiondate,
							   a.timeclose AS duedate,
							   a.name AS assessmentlink,
							   '' AS tiiobjid,
							   '' AS subid,
							   '' AS subpart,
							   '' AS partname,
							   '' AS usegrademark,
							   gg.feedback AS feedbacklink,
							   gg.rawgrademax AS highestgrade,
							   gg.userid,
							   '' AS groupid,
							   '' AS assigngradeid,
							   con.id AS contextid,
							   ga.activemethod,
							   ''  AS nosubmissions,
							   '' AS status,
							   '' AS onlinetext
						FROM {course} c
						JOIN {grade_items} gi ON c.id=gi.courseid AND itemtype='mod'
                             AND (gi.hidden = 0 or gi.hidden < unix_timestamp(current_timestamp))
						JOIN {grade_grades} gg ON gi.id=gg.itemid AND gg.userid = ?
						JOIN {course_modules} cm ON gi.iteminstance=cm.instance
						JOIN {context} con ON cm.id = con.instanceid
						JOIN {modules} m ON cm.module = m.id AND gi.itemmodule = m.name AND gi.itemmodule = 'quiz'
						JOIN {quiz} a ON a.id=gi.iteminstance
						LEFT JOIN {grading_areas} ga ON con.id = ga.contextid
						WHERE cm.visible=1 ";
		}
		if ($this->mod_is_available("workshop")) {
			// If it's not the first select statement add a union.
			if ($sql != "") {
				$sql .= "UNION ";
			}
			$sql .= "SELECT c.id AS courseid,
							   c.fullname AS coursename,
							   gi.itemname AS assessmentname,
							   gg.finalgrade AS grade,
							   gi.itemmodule AS assessmenttype,
							   a.id AS assignid,
							   cm.id AS assignmentid,
							   '' AS teamsubmission,
							   su.timemodified AS submissiondate,
							   a.submissionend AS duedate,
							   a.name AS assessmentlink,
							   '' AS tiiobjid,
							   su.id AS subid,
							   '' AS subpart,
							   '' AS partname,
							   '' AS usegrademark,
							   gg.feedback AS feedbacklink,
							   gg.rawgrademax AS highestgrade,
							   gg.userid,
							   '' AS groupid,
							   '' AS assigngradeid,
							   con.id AS contextid,
							   ga.activemethod,
							   a.nattachments AS nosubmissions,
							   '' AS status,
							   '' AS onlinetext
						FROM {course} c
						JOIN {grade_items} gi ON c.id=gi.courseid AND itemtype='mod'
                             AND (gi.hidden = 0 or gi.hidden < unix_timestamp(current_timestamp))
						JOIN {grade_grades} gg ON gi.id=gg.itemid AND gg.userid = ?
						JOIN {course_modules} cm ON gi.iteminstance=cm.instance
						JOIN {context} con ON cm.id = con.instanceid
						JOIN {modules} m ON cm.module = m.id AND gi.itemmodule = m.name AND gi.itemmodule = 'workshop'
						JOIN {workshop} a ON gi.iteminstance = a.id AND a.phase = 50
						JOIN {workshop_submissions} su ON a.id = su.workshopid AND su.authorid=?
				   LEFT JOIN {grading_areas} ga ON con.id = ga.contextid
						WHERE cm.visible=1 ";
		}
		if ($this->mod_is_available("turnitintool")) {
			// If it's not the first select statement add a union.
			if ($sql != "") {
				$sql .= "UNION ";
			}
			$sql .= "SELECT c.id,
							   c.fullname AS coursename,
							   gi.itemname AS assessmentname,
							   su.submission_grade AS grade,
							   gi.itemmodule AS assessmenttype,
							   '' AS assignid,
							   cm.id AS assignmentid,
							   '' AS teamsubmission,
							   su.submission_modified AS submissiondate,
							   tp.dtdue AS duedate,
							   su.submission_title AS assessmentlink,
							   su.submission_objectid AS tiiobjid,
							   su.id AS subid,
							   su.submission_part AS subpart,
							   tp.partname,
							   t.usegrademark,
							   gg.feedback AS feedbacklink,
							   t.grade AS gradetype,
							   su.userid,
							   '' AS groupid,
							   '' AS assigngradeid,
							   con.id AS contextid,
							   ga.activemethod,
							   t.numparts AS nosubmissions,
							   '' AS status,
							   '' AS onlinetext
						FROM {course} c
						JOIN {grade_items} gi ON c.id=gi.courseid AND itemtype='mod'
                             AND (gi.hidden = 0 or gi.hidden < unix_timestamp(current_timestamp))
						JOIN {course_modules} cm ON gi.iteminstance=cm.instance AND c.id=cm.course
						JOIN {context} con ON cm.id = con.instanceid
						JOIN {modules} m ON cm.module = m.id AND gi.itemmodule = m.name AND gi.itemmodule = 'turnitintool'
						JOIN {grade_grades} gg ON gi.id=gg.itemid AND gg.userid = ?
						JOIN {turnitintool} t ON t.id=gi.iteminstance
						JOIN {turnitintool_submissions} su ON t.id = su.turnitintoolid AND su.userid = ?
						JOIN {turnitintool_parts} tp ON su.submission_part = tp.id AND tp.dtpost < unix_timestamp(current_timestamp)
				   LEFT JOIN {grading_areas} ga ON con.id = ga.contextid ";
		}
		if ($this->mod_is_available("turnitintooltwo")) {
			// If it's not the first select statement add a union.
			if ($sql != "") {
				$sql .= "UNION ";
			}
			$sql .= "SELECT c.id,
							   c.fullname AS coursename,
							   gi.itemname AS assessmentname,
							   su.submission_grade AS grade,
							   gi.itemmodule AS assessmenttype,
							   '' AS assignid,
							   cm.id AS assignmentid,
							   '' AS teamsubmission,
							   su.submission_modified AS submissiondate,
							   tp.dtdue AS duedate,
							   su.submission_title AS assessmentlink,
							   su.submission_objectid AS tiiobjid,
							   su.id AS subid,
							   su.submission_part AS subpart,
							   tp.partname,
							   t.usegrademark,
							   gg.feedback AS feedbacklink,
							   t.grade AS gradetype,
							   su.userid,
							   '' AS groupid,
							   '' AS assigngradeid,
							   con.id AS contextid,
							   ga.activemethod,
							   t.numparts AS nosubmissions,
							   '' AS status,
							   '' AS onlinetext
						FROM {course} c
						JOIN {grade_items} gi ON c.id=gi.courseid AND itemtype='mod'
                             AND (gi.hidden = 0 or gi.hidden < unix_timestamp(current_timestamp))
						JOIN {course_modules} cm ON gi.iteminstance=cm.instance AND c.id=cm.course
						JOIN {context} con ON cm.id = con.instanceid
						JOIN {modules} m ON cm.module = m.id AND gi.itemmodule = m.name AND gi.itemmodule = 'turnitintooltwo'
						JOIN {grade_grades} gg ON gi.id=gg.itemid AND gg.userid = ?
						JOIN {turnitintooltwo} t ON t.id=gi.iteminstance
						JOIN {turnitintooltwo_submissions} su ON t.id = su.turnitintooltwoid AND su.userid = ?
						JOIN {turnitintooltwo_parts} tp ON su.submission_part = tp.id AND tp.dtpost < unix_timestamp(current_timestamp)
				   LEFT JOIN {grading_areas} ga ON con.id = ga.contextid ";
		}
		$sql .= "WHERE c.visible = 1 AND c.showgrades = 1 AND cm.visible=1
						ORDER BY duedate ";
		// Get a number of records as a moodle_recordset using a SQL statement.
		// These are the most params needed, but depending on how many modules are installed it may not need all.
		$params = array($userid, $userid, $userid, $userid, $userid, $userid, $userid, $userid,
				$userid, $userid, $userid
		);
		$rs = $remotedb->get_recordset_sql($sql, $params, $limitfrom = 0, $limitnum = 0);
		// Print titles for each column: Course, Assessment, Type, Due Date, Submission Date,
		// Submission, Feedback, Grade, Highest Grade.
		$newtext = "<table class=\"grades\" id=\"grades\">
						<thead>
							<tr>
								<th>" .
								get_string('gradetblheader_course', 'report_myfeedback') . "</th>
								<th>" .
								get_string('gradetblheader_assessment', 'report_myfeedback') . "</th>
								<th>" .
								get_string('gradetblheader_type', 'report_myfeedback') . "</th>
								<th>" .
								get_string('gradetblheader_duedate', 'report_myfeedback') . "</th>
								<th>" .
								get_string('gradetblheader_submissiondate', 'report_myfeedback') . "</th>
								<th>" .
								get_string('gradetblheader_submission', 'report_myfeedback') . "</th>
								<th>" .
								get_string('gradetblheader_feedback', 'report_myfeedback') . "</th>
								<th>" .
								get_string('gradetblheader_grade', 'report_myfeedback') . "</th>
								<th>" .
								get_string('gradetblheader_highestgrade', 'report_myfeedback') . "</th>
							</tr>
						</thead>";
		if ($rs->valid()) {
			// The recordset contains records.
			foreach ($rs as $record) {
				// Put data into table for display here.
				// Check permissions for course.
				// Set the variables for each column.
				$feedbacktext = "&nbsp;";
				$submission = "&nbsp;";
				$assignmentname = "<a href=\"" . $CFG->wwwroot . "/mod/" . $record->assessmenttype .
				"/view.php?id=" . $record->assignmentid . "\">" . $record->assessmentname .
				"</a>";
				// Submission date.
				$submissiondate = "&nbsp;";
				if ($record->submissiondate) {
					$submissiondate = $record->submissiondate;
				}
				// Display information for each type of assessment activity.
				if ($record->assessmenttype) {
					$assessmenttype = '<img src="' .
							$OUTPUT->pix_url('icon', $record->assessmenttype) . '" ' .
							'class="icon" alt="' . $record->assessmenttype . '">';
					switch ($record->assessmenttype) {
						case "assign":
							$assessmenttype .= get_string('moodle_assignment', 'report_myfeedback');
							if ($record->teamsubmission == 1) {
								$assessmenttype .= " (" .
										get_string('groupwork', 'report_myfeedback') . ")";
								if (!is_numeric($submissiondate) || (!strlen($submissiondate) == 10)) {
									$submissiondate = $this->get_group_assign_submission_date(
											$userid, $record->contextid);
								}
							}
							// Nosubmissions 1 = offline assignments - no files can be submitted.
							// Nosubmissions 0 = file submissions are accepted.
							// If file or online text submissions are enabled.
							if ($record->nosubmissions == 0) {
								// Check there's been a submission.
								if (is_numeric($submissiondate) && (strlen($submissiondate) == 10)) {
									$submission = "<a href=\"" . $CFG->wwwroot . "/mod/" .
											$record->assessmenttype . "/view.php?id=" .
											$record->assignmentid . "&action=view\">" .
											get_string('submission', 'report_myfeedback') . "</a>";
								}
								if ($record->status == "draft") {
									$submission .= " (" . get_string('draft', 'report_myfeedback').")";
									// This will set it to no submission later because it checks for all numbers.
									$submissiondate = "<i>" . $submissiondate . " (draft)</i>";
								}
								if ($submission == null && $submissiondate == null) {
									$submissiondate = get_string('no_submission',
											'report_myfeedback');
								}
							} else {
								$submissiondate = get_string('offline_assignment',
										'report_myfeedback');
							}
							// Check whether an online PDF feedback file exists.
							$onlinepdffeedback = false;
							if ($record->assigngradeid) {
								$onlinepdffeedback = $this->has_pdf_feedback_file(
										$record->assigngradeid);
							}
							// If there are any comments or other feedback (such as online PDF
							// files, rubrics or marking guides)
							// TODO: Check whether rubrics / marking guide has been filled out.
							if ($feedbacktext != "&nbsp;" || $record->feedbacklink ||
									$onlinepdffeedback || $record->activemethod == "rubric" ||
									$record->activemethod == "guide") {
										$feedbacktext = "<a href=\"" . $CFG->wwwroot .
										"/mod/assign/view.php?id=" . $record->assignmentid . "\">" .
										get_string('feedback', 'report_myfeedback') . "</a>";
									}
									break;
						case "turnitintool":
							$assignmentname .= " (" . $record->partname . ")";
							$assessmenttype .= get_string('turnitin_assignment', 'report_myfeedback');
							$newwindowmsg = get_string('new_window_msg', 'report_myfeedback');
							$newwindowicon = '<img src="' . 'pix/external-link.png' . '" ' .
									' alt="-" title="' . $newwindowmsg . '" />';
							if ($record->assignmentid && $record->subpart && $record->tiiobjid &&
									$record->assessmentname) {
										// Not sure what utp is, but it seems to work when set to 2 for admin and 1
										// for students.
										// Link the submission to the plagiarism comparison report.
										$submission = "<a href=\"" . $CFG->wwwroot . "/mod/turnitintool/view.php?id=" .
												$record->assignmentid . "&jumppage=report&userid=" . $userid .
												"&utp=1&partid=" . $record->subpart . "&objectid=" . $record->tiiobjid .
												"\" target=\"_blank\">" . get_string('submission', 'report_myfeedback') .
												"</a> $newwindowicon";
										// If grademark marking is enabled.
										if ($record->usegrademark == 1) {
											// Link the submission to the gradebook.
											$feedbacktext = "<a href=\"" . $CFG->wwwroot . "/mod/turnitintool/view.php?id=" .
													$record->assignmentid . "&jumppage=grade&userid=" . $userid .
													"&utp=1&partid=" . $record->subpart . "&objectid=" . $record->tiiobjid .
													"\" target=\"_blank\">" . get_string('feedback', 'report_myfeedback') .
													"</a> $newwindowicon";
										} else {
											$feedbacktext = "<a href=\"" . $CFG->wwwroot . "/mod/turnitintool/view.php?id=" .
													$record->assignmentid . "&do=submissions\">" .
													get_string('feedback', 'report_myfeedback') . "</a>";
										}
									}
									break;
						case "workshop":
							$assessmenttype .= $string['workshop'] = get_string('workshop', 'report_myfeedback');
							$submission = "<a href=\"" . $CFG->wwwroot . "/mod/workshop/submission.php?cmid=" .
									$record->assignmentid . "&id=" . $record->subid . "\">" .
									get_string('submission', 'report_myfeedback') . "</a>";
							$feedbacktext = "<a href=\"" . $CFG->wwwroot . "/mod/workshop/submission.php?cmid=" .
									$record->assignmentid . "&id=" . $record->subid . "\">" .
									get_string('feedback', 'report_myfeedback') . "</a>";
							if ($record->nosubmissions == 0) {
								$submissiondate = get_string('offline_assignment', 'report_myfeedback');
							}
							break;
						case "quiz":
							$assessmenttype .= get_string('quiz', 'report_myfeedback');
							$submission = "-";
							$submissiondate = "-";
							$feedbacktext = $this->get_quiz_attempts_link($record->assignid, $userid,
									$record->assignmentid);
							break;
					}
				}
				// Mark late submissions in red.
				$submissionmsg = "";
				if (is_numeric($submissiondate) && (strlen($submissiondate) == 10)) {
					$submissiondate = date('d-m-Y H:i', $submissiondate);
					$submittedtime = $submissiondate;
				} else if ($submissiondate != get_string('offline_assignment', 'report_myfeedback') &&
						strpos($assessmenttype, "quiz") === false) {
							if (strpos($submissiondate, "draft") === false) {
								$submissiondate = get_string('no_submission', 'report_myfeedback');
								$submissionmsg = get_string('no_submission_msg', 'report_myfeedback');
							} else {
								$submissiondate = get_string('draft_submission', 'report_myfeedback');
								$submissionmsg = get_string('draft_submission_msg', 'report_myfeedback');
							}
							$submittedtime = time();
						}
						if ($submittedtime > $record->duedate &&
								$submissiondate != get_string('offline_assignment', 'report_myfeedback')) {
									if ($submissionmsg == "") {
										$submissionmsg = get_string('late_submission_msg', 'report_myfeedback');
									}
									$alerticon = '<img src="' . 'pix/warning2-faded.png' . '" ' . 'class="icon" alt="-" title="' .
											$submissionmsg . '" />';
									$submissiondate = "<span style=\"color: red;\">" . $submissiondate . " $alerticon</span>";
								}
								$newtext .= "<tr>";
								$newtext .= "<td>" . ($record->coursename && $record->courseid ? "<a href=\"" . $CFG->wwwroot .
										"/course/view.php?id=" . $record->courseid . "\">" . $record->coursename . "</a>" : "&nbsp;") .
										"</td>";
								$newtext .= "<td>" . $assignmentname . "</td>";
								$newtext .= "<td>" . $assessmenttype . "</td>";
								$newtext .= "<td>" . ($record->duedate ? date('d-m-Y H:i', $record->duedate) : "&nbsp;") . "</td>";
								$newtext .= "<td>" . $submissiondate . "</td>";
								$newtext .= "<td>" . $submission . "</td>";
								$newtext .= "<td>" . $feedbacktext . "</td>"; // Including links to marking guide or rubric.
								$newtext .= "<td>" . ($record->grade ? number_format($record->grade, 0) : "&nbsp;") . "</td>";
								$newtext .= "<td>" . ($record->highestgrade ? number_format($record->highestgrade, 0) : "&nbsp;") . "</td>";
								$newtext .= "</tr>";
			}
			$rs->close(); // Close the recordset!
		}
		$newtext .= "</table>";
		$this->content->text = $newtext;
		return $this->content;
	}

	/**
	 * Tell Moodle the report can only be shown once - not sure if this is relevant to reports, or just
	 * blocks.
	 *
	 * @return bool only one report can be shown
	 */
	public function instance_allow_multiple() {
		return false;
	}
}