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
 * @author    Delvon Forrester <delvon@esparanza.co.uk>
 * @credits   Based on original work report_mygrades by David Bezemer <david.bezemer@uplearning.nl> which in turn is based on 
 *            block_myfeedback by Karen Holland, Mei Jin, Jiajia Chen. Also uses SQL originating from Richard Havinga 
 *            <richard.havinga@ulcc.ac.uk>. The code for using an external database is taken from Juan leyva's
 *            <http://www.twitter.com/jleyvadelgado> configurable reports block.
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
    $context = context_user::instance($user->id, MUST_EXIST);
    if (has_capability('report/myfeedback:view', $context)) {
        $url = new moodle_url('/report/myfeedback/index.php', array('userid' => $user->id));
        $navigation->add(get_string('pluginname', 'report_myfeedback'), $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
    }
}

function report_myreport_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('report/myfeedback:view', $context)) {
        $url = new moodle_url('/report/myfeedback/index.php', array('id' => $course->id));
        $navigation->add(get_string('pluginname', 'report_myfeedback'), $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
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
        } else {
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
                if (empty($CFG->noemailever) and ! empty($CFG->emailconnectionerrorsto)) {
                    $body = "Connection error: " . $CFG->wwwroot .
                            "\n\nInfo:" .
                            "\n\tError code: " . $e->errorcode .
                            "\n\tDebug info: " . $e->debuginfo .
                            "\n\tServer: " . $_SERVER['SERVER_NAME'] . " (" . $_SERVER['SERVER_ADDR'] . ")";
                    if (file_exists($CFG->dataroot . '/emailcount')) {
                        $fp = @fopen($CFG->dataroot . '/emailcount', 'r');
                        $content = @fread($fp, 24);
                        @fclose($fp);
                        if ((time() - (int) $content) > 600) {
                            //email directly rather than using messaging
                            @mail($CFG->emailconnectionerrorsto, 'WARNING: Database connection error: ' . $CFG->wwwroot, $body);
                            $fp = @fopen($CFG->dataroot . '/emailcount', 'w');
                            @fwrite($fp, time());
                        }
                    } else {
                        //email directly rather than using messaging
                        @mail($CFG->emailconnectionerrorsto, 'WARNING: Database connection error: ' . $CFG->wwwroot, $body);
                        $fp = @fopen($CFG->dataroot . '/emailcount', 'w');
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
     * Checks whether or not an online pdf feedback annotated file 
     * or any feedback file has been generated
     *
     * @param str $contextid The course context id
     * @param str $gradeid The gradeid
     * @param str $assessmenttype The module activity type
     * @return bool true if there's a pdf feedback annotated file 
     * or any feedback filed for the submission, otherwise false
     */
    public function has_pdf_feedback_file($contextid, $gradeid) {
        global $remotedb;
        // Is there any online pdf annotation feedback or any feedback file?
        if ($remotedb->record_exists('assignfeedback_editpdf_annot', array('gradeid' => $gradeid))) {
            return true;
        }
        $sql = "SELECT max(id) as id
                FROM {files}
                WHERE contextid=? AND component=? AND filearea=?
                AND itemid=?";
        $params = array($contextid, 'assignfeedback_file', 'feedback_files', $gradeid);
        $feedbackfile = $remotedb->get_record_sql($sql, $params, $limitfrom = 0, $limitnum = 1);
        if ($feedbackfile) {
            return true;
        }
        return false;
    }

    /**
     * Checks whether or not there is any workshop feedback file either from peers or tutor
     * 
     * @param type $contextid The context id 
     * @param type $userid The user id
     * @param type $assignid The workshop id
     * @return boolean true if there is a feedback file and false if there ain't
     */
    public function has_workshop_feedback_file($contextid, $userid, $assignid) {
        global $remotedb;
        // Is there any feedback file?
        $sql = "SELECT max(f.id) as id
                FROM {files} f                
                JOIN {workshop_assessments} wa ON f.itemid=wa.id AND f.contextid=? AND f.component=?
                JOIN {workshop_submissions} ws ON wa.submissionid=ws.id AND ws.authorid=?
                JOIN {workshop} w ON ws.workshopid=w.id AND w.id=?";
        $params = array($contextid, 'mod_workshop', $userid, $assignid);
        $feedbackfile = $remotedb->get_record_sql($sql, $params, $limitfrom = 0, $limitnum = 1);
        if ($feedbackfile) {
            return true;
        }
        return false;
    }

    /**
     * Check whether the user has been granted an assignment extension
     * 
     * @param type $userid The user id
     * @param type $assignment The assignment id
     * @return str Due date of the extension or false if no extension
     */
    public function check_assign_extension($userid, $assignment) {
        global $remotedb;
        $sql = "SELECT max(extensionduedate) as extensionduedate
                FROM {assign_user_flags}
                WHERE userid=? AND assignment=?";
        $params = array($userid, $assignment);
        $extension = $remotedb->get_record_sql($sql, $params, $limitfrom = 0, $limitnum = 1);
        if ($extension) {
            return $extension->extensionduedate;
        }
        return false;
    }

    /**
     * Check if the user got an overiide to extend completion date/time
     * 
     * @global type $remotedb The database object
     * @param type $assignid The quiz id
     * @param type $userid The user id
     * @return str Datetime of the override or false if no override
     */
    public function check_quiz_extension($assignid, $userid) {
        global $remotedb;
        $sql = "SELECT max(timeclose) as timeclose
                FROM {quiz_overrides}
                WHERE quiz=? AND userid=?";
        $params = array($assignid, $userid);
        $override = $remotedb->get_record_sql($sql, $params, $limitfrom = 0, $limitnum = 1);
        if ($override) {
            return $override->timeclose;
        }
        return false;
    }

    /**
     * Check if user got an override to extend completion date/time
     * as part of a group
     * 
     * @global type $remotedb The database object
     * @param type $assignid The quiz id
     * @param type $userid The user id
     * @return str extension date or false if no extension
     */
    public function check_quiz_group_extension($assignid, $userid) {
        global $remotedb;
        $sql = "SELECT max(qo.timeclose) as timeclose
                FROM {quiz_overrides} qo
                JOIN {groups_members} gm ON qo.groupid=gm.groupid 
                AND qo.quiz=? AND gm.userid=?";
        $params = array($assignid, $userid);
        $override = $remotedb->get_record_sql($sql, $params, $limitfrom = 0, $limitnum = 1);
        if ($override) {
            return $override->timeclose;
        }
        return false;
    }

    /**
     * Get the submitted date of the last attempt
     * 
     * @global type $remotedb The database object
     * @param type $assignid The assignment id
     * @param type $userid The user id
     * @return str The date of the attempt or false if not attempted.
     */
    public function get_quiz_submissiondate($assignid, $userid) {
        global $remotedb;
        $sql = "SELECT max(timemodified) as timemodified
                FROM {quiz_attempts}
                WHERE quiz=? AND userid=?";
        $params = array($assignid, $userid);
        $attempt = $remotedb->get_record_sql($sql, $params, $limitfrom = 0, $limitnum = 1);
        if ($attempt) {
            return $attempt->timemodified;
        }
        return false;
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
        $attemptcount = $remotedb->count_records_sql($sqlcount, $params, $limitfrom = 0, $limitnum = 0);
        $out = array();
        if ($attemptcount > 0) {
            $url = $CFG->wwwroot . "/mod/quiz/view.php?id=" . $quizurlid;
            $attemptstext = ($attemptcount > 1) ? get_string('attempts', 'report_myfeedback') : get_string(
                            'attempt', 'report_myfeedback');
            $out[] = html_writer::link($url, get_string('review', 'report_myfeedback') . " " . $attemptcount . " " .
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
        $params = array($userid, $contextid);
        $files = $remotedb->get_recordset_sql($sql, $params, $limitfrom = 0, $limitnum = 0);
        $out = array();
        foreach ($files as $file) {
            $out[] = $file->timemodified;
        }
        $br = html_writer::empty_tag('br');
        return implode($br, $out);
    }

    /**
     * Get the peercoments for workshop
     * @param int $userid The id of the user
     * @return str the comments made on all parts of the work
     */
    public function get_workshop_comments($userid) {
        global $remotedb;
        // Group submissions.
        $sql = "SELECT wg.peercomment
                FROM {worshop_grades} wg
                    LEFT JOIN {workshop_submissions} su ON wg.assessmentid = su.id and su.authorid=?";
        $params = array($userid);
        $comments = $remotedb->get_recordset_sql($sql, $params, $limitfrom = 0, $limitnum = 0);
        $out = array();
        foreach ($comments as $comment) {
            $out[] = $comment->peercomment;
        }
        $br = html_writer::empty_tag('br');
        return implode($br, $out);
    }

    /**
     * Check if the grades and feedback have been viewed in the gradebook
     * since the last grade or feedback has been released
     *
     * @param int $contextid The context of the course module
     * @param int $assignmentid The course module id
     * @param int $userid The id of the user
     * @param int $courseid The id of the course
     * @return str date viewed or no if not viewed
     */
    public function check_viewed_gradereport($contextid, $assignmentid, $userid, $courseid, $itemname) {
        global $remotedb;
        $sql = "SELECT max(timecreated) as timecreated
                FROM {logstore_standard_log}
                WHERE contextid=? AND contextinstanceid=? AND userid=? AND courseid=?";
        $sqlone = "SELECT max(timecreated) as timecreated
                FROM {logstore_standard_log}
                WHERE component=? AND action=? AND userid=? AND courseid=?";
        $sqltwo = "SELECT max(g.timemodified) as timemodified
                FROM {grade_grades} g 
                JOIN {grade_items} gi ON g.itemid=gi.id AND g.userid=? 
                AND gi.courseid=? AND gi.itemname=?
                JOIN {course_modules} cm ON gi.iteminstance=cm.instance AND cm.course=?
                AND cm.id=?";
        $params = array($contextid, $assignmentid, $userid, $courseid);
        $paramsone = array('gradereport_user', 'viewed', $userid, $courseid);
        $paramstwo = array($userid, $courseid, $itemname, $courseid, $assignmentid);
        $viewreport = $remotedb->get_record_sql($sql, $params, $limitfrom = 0, $limitnum = 1);
        $userreport = $remotedb->get_record_sql($sqlone, $paramsone, $limitfrom = 0, $limitnum = 1);
        $gradeadded = $remotedb->get_record_sql($sqltwo, $paramstwo, $limitfrom = 0, $limitnum = 1);
        if ($viewreport) {
            if ($gradeadded) {
                $dateviewed = date('d-m-y', $viewreport->timecreated);
                if ($gradeadded->timemodified < $viewreport->timecreated) {
                    return $dateviewed;
                }
            }
        }
        if ($userreport) {
            if ($gradeadded) {
                $dateviewed = date('d-m-y', $userreport->timecreated);
                if ($gradeadded->timemodified < $userreport->timecreated) {
                    return $dateviewed;
                }
            }
        }
        return 'no';
    }

    /**
     * Check if the grades and feedback have been viewed in the gradebook
     * since the last grade or feedback has been released but for manual items 
     * @param int $userid The id of the user
     * @param int $courseid The id of the course
     * @param int $gradeitemid The id of the manual grade item
     * @return str date viewed or no if not viewed
     */
    public function check_viewed_manualitem($userid, $courseid, $gradeitemid) {
        global $remotedb;
        $sqlone = "SELECT max(timecreated) as timecreated
                FROM {logstore_standard_log}
                WHERE component=? AND action=? AND userid=? AND courseid=?";
        $sqltwo = "SELECT max(g.timemodified) as timemodified
                FROM {grade_grades} g 
                JOIN {grade_items} gi ON g.itemid=gi.id AND g.userid=? 
                AND gi.courseid=? AND gi.id=?";
        $paramsone = array('gradereport_user', 'viewed', $userid, $courseid);
        $paramstwo = array($userid, $courseid, $gradeitemid);
        $userreport = $remotedb->get_record_sql($sqlone, $paramsone, $limitfrom = 0, $limitnum = 1);
        $gradeadded = $remotedb->get_record_sql($sqltwo, $paramstwo, $limitfrom = 0, $limitnum = 1);
        if ($userreport) {
            if ($gradeadded) {
                $dateviewed = date('d-m-y', $userreport->timecreated);
                if ($gradeadded->timemodified < $userreport->timecreated) {
                    return $dateviewed;
                }
            }
        }
        return 'no';
    }

    /**
     * Get the overall feedback for quiz for the user based on the grade
     * he currently has in the gradebook for his best attempt
     *
     * @param int $quizid The id of the quiz the user attempted
     * @param int $grade The grade on the quiz
     * @return str the text for the overall feedback
     */
    public function overallfeedback($quizid, $grade) {
        global $remotedb;
        $sql = "SELECT feedbacktext
                FROM {quiz_feedback}
                WHERE quizid=? and mingrade<=? and maxgrade>=?
                limit 1";
        $params = array($quizid, $grade, $grade);
        $feedback = $remotedb->get_record_sql($sql, $params);
        return $feedback->feedbacktext;
    }

    /**
     * Get the Rubric feedback
     * 
     * @param int $userid The id of the user who's feedback being viewed
     * @param int $courseid The course the Rubric is being checked for
     * @param int $iteminstance The instance of the module item 
     * * @param int $itemmodule The module currently being queried
     * @return str the text for the Rubric
     */
    public function rubrictext($userid, $courseid, $iteminstance, $itemmodule) {
        global $remotedb;
        $sql = "SELECT DISTINCT rc.description,rl.definition 
            FROM {gradingform_rubric_criteria} rc
            JOIN {gradingform_rubric_levels} rl
            ON rc.id=rl.criterionid
            JOIN {gradingform_rubric_fillings} rf
            ON rl.id=rf.levelid AND rc.id=rf.criterionid
            JOIN {grading_instances} gin
            ON rf.instanceid=gin.id
            JOIN {assign_grades} ag
            ON gin.itemid=ag.id
            JOIN {grade_items} gi
            ON ag.assignment=gi.iteminstance AND ag.userid=?
            JOIN {grade_grades} gg
            ON gi.id=gg.itemid AND gi.itemmodule=? 
            AND gi.courseid=? AND gg.userid=? AND gi.iteminstance=?";
        $params = array($userid, $itemmodule, $courseid, $userid, $iteminstance);
        $rubrics = $remotedb->get_records_sql($sql, $params);
        $out = '';
        foreach ($rubrics as $rubric) {
            $out .= "<strong>" . $rubric->description . ":- </strong>" . $rubric->definition . "<br/>";
        }
        return $out;
    }

    /**
     * Get the Rubric feedback
     * 
     * @param int $userid The id of the user who's feedback being viewed
     * @param int $courseid The course the Marking guide is being checked for
     * @param int $iteminstance The instance of the module item 
     * * @param int $itemmodule The module currently being queried
     * @return str the text for the Marking guide
     */
    public function marking_guide_text($userid, $courseid, $iteminstance, $itemmodule) {
        global $remotedb;
        $sql = "SELECT DISTINCT gc.shortname,gf.remark 
            FROM {gradingform_guide_criteria} gc
            JOIN {gradingform_guide_fillings} gf
            ON gc.id=gf.criterionid
            JOIN {grading_instances} gin
            ON gf.instanceid=gin.id
            JOIN {assign_grades} ag
            ON gin.itemid=ag.id
            JOIN {grade_items} gi
            ON ag.assignment=gi.iteminstance AND ag.userid=?
            JOIN {grade_grades} gg
            ON gi.id=gg.itemid AND gi.itemmodule=? 
            AND gi.courseid=? AND gg.userid=? AND gi.iteminstance=?";
        $params = array($userid, $itemmodule, $courseid, $userid, $iteminstance);
        $guides = $remotedb->get_records_sql($sql, $params);
        $out = '';
        foreach ($guides as $guide) {
            $out .= "<strong>" . $guide->shortname . ":- </strong>" . $guide->remark . "<br/>";
        }
        return $out;
    }

    /**
     * Get the scales for a manual item 
     * 
     * @global Obj $remotedb DB object
     * @param int $itemid The grade item id
     * @param int $userid The user id
     * @param int $courseid The course id
     * @return str The scale grade for the user
     */
    public function get_grade_scale($itemid, $userid, $courseid) {
        global $remotedb;
        $sql = "SELECT DISTINCT gg.finalgrade, s.scale 
            FROM {grade_grades} gg
            JOIN {grade_items} gi ON gg.itemid=gi.id AND gi.id=? 
            AND gg.userid=? AND gi.courseid=? AND gi.gradetype = 2
            JOIN {scale} s ON gi.scaleid=s.id limit 1";
        $params = array($itemid, $userid, $courseid);
        $scales = $remotedb->get_record_sql($sql, $params);
        $num = 0;
        if ($scales) {
            $scale = explode(',', $scales->scale);
            $num = ($num ? (int) $scales->finalgrade - 1 : 0);
            return $scale[$num];
        } else {
            return '-';
        }
    }

    /**
     * Get the lowest scale grade for the scale
     * @global obj $remotedb The DB object
     * @param int $itemid The grade item id
     * @param int $userid The user id
     * @param int $courseid The course id
     * @return str The lowest scale grade available
     */
    public function get_min_grade_scale($itemid, $userid, $courseid) {
        global $remotedb;
        $sql = "SELECT DISTINCT s.scale 
            FROM {grade_grades} gg
            JOIN {grade_items} gi ON gg.itemid=gi.id AND gi.id=? 
            AND gg.userid=? AND gi.courseid=? AND gi.gradetype = 2
            JOIN {scale} s ON gi.scaleid=s.id limit 1";
        $params = array($itemid, $userid, $courseid);
        $scales = $remotedb->get_record_sql($sql, $params);
        if ($scales) {
            $scale = explode(',', $scales->scale);
            $num = min(array_keys($scale));
            return $scale[$num];
        } else {
            return '-';
        }
    }

    /**
     * Get the highest scale grade for the scale
     * @global obj $remotedb The DB object
     * @param int $itemid The grade item id
     * @param int $userid The user id
     * @param int $courseid The course id
     * @return str The highest scale grade available
     */
    public function get_available_grade_scale($itemid, $userid, $courseid) {
        global $remotedb;
        $sql = "SELECT DISTINCT s.scale 
            FROM {grade_grades} gg
            JOIN {grade_items} gi ON gg.itemid=gi.id AND gi.id=? 
            AND gg.userid=? AND gi.courseid=? AND gi.gradetype = 2
            JOIN {scale} s ON gi.scaleid=s.id limit 1";
        $params = array($itemid, $userid, $courseid);
        $scales = $remotedb->get_record_sql($sql, $params);
        if ($scales) {
            $scale = explode(',', $scales->scale);
            $num = max(array_keys($scale));
            return $scale[$num];
        } else {
            return '-';
        }
    }

    /**
     * Get All scale grades for the scale
     * @global obj $remotedb The DB object
     * @param int $itemid The grade item id
     * @param int $userid The user id
     * @param int $courseid The course id
     * @return str All scale grade in the scale
     */
    public function get_all_grade_scale($itemid, $userid, $courseid) {
        global $remotedb;
        $sql = "SELECT s.scale 
            FROM {grade_grades} gg
            JOIN {grade_items} gi ON gg.itemid=gi.id AND gi.id=? 
            AND gg.userid=? AND gi.courseid=? AND gi.gradetype = 2
            JOIN {scale} s ON gi.scaleid=s.id";
        $params = array($itemid, $userid, $courseid);
        $scales = $remotedb->get_records_sql($sql, $params);
        $out = '';
        if ($scales) {
            foreach ($scales as $scale) {
                $out .= $scale->scale . ", ";
            }
            return chop($out, ', ');
        } else {
            return '-';
        }
    }

    /**
     * Get the grade letter/word for a value grade set as letter 
     * @global obj $remotedb DB object
     * @param int $courseid The course id
     * @param int $grade The final grade the user got
     * @return str The letter grade or word for the user
     */
    public function get_grade_letter($courseid, $grade) {
        global $remotedb;
        $sql = "SELECT l.letter, con.id
            FROM {grade_letters} l
            JOIN {context} con ON l.contextid = con.id AND con.contextlevel=50
            AND con.instanceid=? AND l.lowerboundary <=? 
            ORDER BY l.lowerboundary DESC limit 1";
        $params = array($courseid, $grade);
        $letters = $remotedb->get_record_sql($sql, $params);
        if ($letters) {
            $letter = $letters->letter;
        } else {
            $defaultletters = grade_get_letters();
            $val = (int) $grade;
            foreach ($defaultletters as $boundary => $value) {
                if ($val >= $boundary) {
                    $letter = format_string($value);
                    break;
                }
            }
        }
        return $letter;
    }

    /**
     * Return the lowest grade letter for the context or default letters
     * @global obj $remotedb The DB object
     * @param int $courseid The course id
     * @return str The lowest letter grade available
     */
    public function get_min_grade_letter($courseid) {
        global $remotedb;
        $sql = "SELECT l.letter, con.id
            FROM {grade_letters} l
            JOIN {context} con ON l.contextid = con.id AND con.contextlevel=50
            AND con.instanceid=? ORDER BY l.lowerboundary ASC limit 1";
        $params = array($courseid);
        $letters = $remotedb->get_record_sql($sql, $params);
        if ($letters) {
            $letter = $letters->letter;
        } else {
            $defaultletters = grade_get_letters();
            $boun = min(array_keys($defaultletters));
            $letter = $defaultletters[$boun];
        }
        return $letter;
    }

    /**
     * Return the highest grade letter for the context or default letters
     * @global obj $remotedb The DB object
     * @param int $courseid The course id
     * @return str The highest letter grade available
     */
    public function get_available_grade_letter($courseid) {
        global $remotedb;
        $sql = "SELECT l.letter, con.id
            FROM {grade_letters} l
            JOIN {context} con ON l.contextid = con.id AND con.contextlevel=50
            AND con.instanceid=? ORDER BY l.lowerboundary DESC limit 1";
        $params = array($courseid);
        $letters = $remotedb->get_record_sql($sql, $params);
        if ($letters) {
            $letter = $letters->letter;
        } else {
            $defaultletters = grade_get_letters();
            $boun = max(array_keys($defaultletters));
            $letter = $defaultletters[$boun];
        }
        return $letter;
    }

    /**
     * Return All grade letters for the context or default letters
     * @global obj $remotedb The DB object
     * @param int $courseid The course id
     * @return str All grade letters
     */
    public function get_all_grade_letters($courseid) {
        global $remotedb;
        $sql = "SELECT l.letter, con.id
            FROM {grade_letters} l
            JOIN {context} con ON l.contextid = con.id AND con.contextlevel=50
            AND con.instanceid=?";
        $params = array($courseid);
        $letters = $remotedb->get_records_sql($sql, $params);
        $out = '';
        if ($letters) {
            foreach ($letters as $letter) {
                $out .= $letter->letter . ", ";
            }
        } else {
            $defaultletters = grade_get_letters();
            $revdef = array_reverse($defaultletters);
            foreach ($revdef as $letter) {
                $out .= $letter . ", ";
            }
        }
        return chop($out, ', ');
    }

    /**
     * Returns fractions in the grade and available grade only if there is a fraction in the number
     * @param float $grade The grade 
     * @return float The whole number of fraction
     */
    public function get_fraction($grade) {
        $wholenum = floor($grade);
        if ($wholenum < $grade || $wholenum > $grade) {
            return number_format($grade, 2); //TODO: set decimal places based on the settings of the course/grade item
        }
        return number_format($grade, 0);
    }

    /**
     * Returns the timezone of the user as the moodle functions caches this and this 
     * should not be dependent on moodle's cached settings
     * @global type $USER The user object
     * @global type $remotedb The db object
     * @return mixed The GMT/UTC+-offset
     */
    public function user_timezone() {
        global $USER, $remotedb;
        $sql = "SELECT timezone FROM {user} WHERE id = ?";
        $params = array($USER->id);
        $timezone = $remotedb->get_record_sql($sql, $params);
        return $timezone ? $timezone->timezone : 99;
    }

    /**
     * Returns the text for the timezone given the numeric offset
     * @param mixed $tz The timezone float or 99 if default or server local time
     * @return string The timezone letter
     */
    public function timezone_letter($tz) {
        if (abs($tz) > 13) {
            return date('I') ? 'BST' : 'GMT';
        } else if (abs($tz == 0)) {
            return 'GMT';
        } else if ($tz > 0 && $tz < 14) {
            return 'UTC+' . $tz;
        } else {
            return 'UTC' . $tz;
        }
    }

    /**
     * Return BST or GMT for the duedate or submission date depending on when they were due or submitted
     * @param type $date Date to check for timezone name
     * @return string Return the correct timezone
     */
    public function bst_gmt($date) {
        if (date('I', $date)) {
            return 'BST';
        } else {
            return 'GMT';
        }
    }

    /**
     * Return the correct id for the personal tutor role
     * @global obj $remotedb DB object
     * @return int Personal tutor role id
     */
    public function get_personal_tutor_id() {
        global $remotedb;
        $sql = "SELECT id FROM {role} WHERE shortname = ?";
        $params = array('personal_tutor');
        $tutor = $remotedb->get_record_sql($sql, $params);
        return $tutor ? $tutor->id : 0;
    }

    /**
     * Return the correct id for the program id role
     * @global obj $remotedb DB object
     * @return int Programme admin role id
     */
    public function get_program_admin_id() {
        global $remotedb;
        $sql = "SELECT id FROM {role} WHERE shortname = ?";
        $params = array('progadmin');
        $prog = $remotedb->get_record_sql($sql, $params);
        return $prog ? $prog->id : 0;
    }

    /**
     * Return all users the current user (tutor/admin) has access to
     * @global obj $remotedb The DB object
     * @global obj $COURSE The course object
     * @global obj $USER The user object
     * @return string Table with the list of users they have access to
     */
    public function get_all_accessible_users() {
        global $remotedb, $COURSE, $USER;
        $usertable = "<table style=\"max-width:420px;float:left;\" class=\"userstable\" id=\"userstable\">
                    <thead>
                            <tr class=\"tableheader\">
                                <th></th>
                            </tr>
                        </thead>
                                                <tbody>";
        $myusers = array();
        //get all the users in the course you are programme admin
        if ($mods = get_user_capability_course('report/myfeedback:progadmin', $userid = null, $doanything = false)) {
            foreach ($mods as $value) {
                $context = context_course::instance($value->id);
                $query = 'select u.id as id, firstname, lastname, email from mdl_role_assignments as a, '
                        . 'mdl_user as u where contextid=' . $context->id . ' and roleid=5 and a.userid=u.id;';
                $users = $remotedb->get_recordset_sql($query);
                foreach ($users as $a) {
                    $myusers[$a->id] = "<a href=\"/v288/report/myfeedback/index.php?userid=" . $a->id . "\" title=\"" .
                            $a->email . "\" rel=\"tooltip\">" . $a->firstname . " " . $a->lastname . "</a>";
                }
            }
        }
        // get all the users in the course you are a tutor in
        if ($mods = get_user_capability_course('moodle/course:changeshortname', $userid = null, $doanything = false)) {
            foreach ($mods as $value) {
                $context = context_course::instance($value->id);
                $query = 'select u.id as id, firstname, lastname, email from mdl_role_assignments as a, '
                        . 'mdl_user as u where contextid=' . $context->id . ' and roleid=5 and a.userid=u.id;';
                $users = $remotedb->get_recordset_sql($query);
                foreach ($users as $r) {
                    $myusers[$r->id] = "<a href=\"/v288/report/myfeedback/index.php?userid=" . $r->id . "\" title=\"" .
                            $r->email . "\" rel=\"tooltip\">" . $r->firstname . " " . $r->lastname . "</a>";
                }
            }
        }

        // get all the mentees, i.e. users you have a direct assignment to
        if ($usercontexts = $remotedb->get_records_sql("SELECT c.instanceid, c.instanceid, u.id as id, firstname, lastname, email
                                                    FROM {role_assignments} ra, {context} c, {user} u
                                                   WHERE ra.userid = ?
                                                         AND ra.contextid = c.id
                                                         AND c.instanceid = u.id
                                                         AND c.contextlevel = " . CONTEXT_USER, array($USER->id))) {
            foreach ($usercontexts as $u) {
                $myusers[$u->id] = "<a href=\"/v288/report/myfeedback/index.php?userid=" . $u->id . "\" title=\"" .
                        $u->email . "\" rel=\"tooltip\">" . $u->firstname . " " . $u->lastname . "</a>";
            }
        }

        foreach ($myusers as $k => $result) {
            $usertable.= "<tr>";
            $usertable.="<td>" . $result . "</td>";
            $usertable.="</tr>";
        }
        $usertable.="</tbody><tfoot><tr><td></td></tr></tfoot></table>";
        return $usertable;
    }

    /**
     * This returns the id of the personal tutor role
     * @global obj $remotedb DB object
     * @param int $p_tutor_id Personal tutor id
     * @param int $contextid The context id
     * @return int The id of the personal tutor role or 0 if none
     */
    public function get_my_personal_tutor($p_tutor_id, $contextid) {
        global $remotedb;
        $sql = "SELECT userid FROM {role_assignments}
                WHERE roleid = ? AND contextid = ?
                ORDER BY timemodified DESC limit 1";
        $params = array($p_tutor_id, $contextid);
        $tutor = $remotedb->get_record_sql($sql, $params);
        return $tutor ? $tutor->userid : 0;
    }

    /**
     * Returns a course id given the course shortname
     * @global obj $remotedb DB object
     * @param text $shortname Course shortname
     * @return int The course id
     */
    public function get_course_id_from_shortname($shortname) {
        global $remotedb;
        $sql = "SELECT max(id) as id, fullname FROM {course}
                WHERE shortname = ?";
        $params = array($shortname);
        $cid = $remotedb->get_record_sql($sql, $params);
        return $cid ? $cid : 0;
    }

    /**
     * Returns the graph with the z-score with the lowest, highest and average grade
     * also the number who got between 0-39, 40-49,50-59,60-69 and 70-100 %
     * 
     * @param int $cid The course id
     * @param array $uids The array of user ids
     * @return string The bar graph depicting the z-score
     */
    public function get_z_score($cid, $uids) {
        $totals = array();
        $ctotal = 0;
        $all_totals = grade_get_course_grades($cid, $uids);
        $num_cse_tot = $all_totals->grademax;
        $mean = 0;
        $grade40 = 0;
        $grade50 = 0;
        $grade60 = 0;
        $grade70 = 0;
        $grade70_100 = 0;

        foreach ($all_totals->grades as $total_grade) {
            if ($total_grade->grade) {
                $p_total = $total_grade->grade / $num_cse_tot * 100;
                $totals[] = $p_total;
                $ctotal += $p_total;
                ++$mean;
                if ($p_total < 40) {
                    ++$grade40;
                }
                if ($p_total >= 40 && $p_total < 50) {
                    ++$grade50;
                }
                if ($p_total >= 50 && $p_total < 60) {
                    ++$grade60;
                }
                if ($p_total >= 60 && $p_total < 70) {
                    ++$grade70;
                }
                if ($p_total >= 70) {
                    ++$grade70_100;
                }
            }
        }
        $meangrade = number_format($ctotal / $mean, 0);
        $maxgrade = number_format(max($totals), 0);
        $mingrade = number_format(min($totals), 0);
        $minpadding_t = $mingrade + 0.5;
        $meanpadding = $meangrade - $mingrade;
        $meanpadding_t = $meanpadding + 0.4;
        $maxpadding = $maxgrade - $meangrade;
        $maxpadding_t = $maxpadding + 0.1;

        $result = "<div style='width:50%;'>
            <div>
                <div class='top-text'>
                    <div class='btext' style='width:" . $minpadding_t . "%;'>&nbsp;&nbsp;" . $mingrade . "</div>
                    <div class='btext' style='width:" . $meanpadding_t . "%;color:red'>&nbsp;&nbsp;" . $meangrade . "</div>
                    <div class='btext' style='width:" . $maxpadding_t . "%;'>&nbsp;&nbsp;" . $maxgrade . "</div>
                </div>
                <div class='top-text'>
                    <div class='vline' style='width:" . $mingrade . "%;'>&vert;</div>
                    <div class='vline' style='width:" . $meanpadding . "%;color:red;'>&vert;</div>
                    <div class='vline' style='width:" . $maxpadding . "%;'>&vert;</div>
                </div>
            </div>
            <div class='grades-bar'>                
                    <div class='grade-bar40'>" . $grade40 . "</div>           
                    <div class='grade-bar506070'>" . $grade50 . "</div>
                    <div class='grade-bar506070'>" . $grade60 . "</div>
                    <div class='grade-bar506070'>" . $grade70 . "</div>
                    <div class='grade-bar-100'>" . $grade70_100 . "</div>
            </div>
            <div class='grade-bar-b'>
                <div class='grade-bar-b40'>&nbsp;</div>
                <div class='grade-bar-b1'>&nbsp;</div>
                <div class='grade-bar-b1'>&nbsp;</div>
                <div class='grade-bar-b1'>&nbsp;</div>
            </div>
            <div class='grade-bar-b'>
                <div class='btext-b40'>40</div>
                <div class='btext-b'>50</div>
                <div class='btext-b'>60</div>
                <div class='btext-b'>70</div>
            </div>
        </div>";
        return $result;
    }

    /**
     * Get content to populate the feedback table
     *
     * @return str The table of submission and feedback for the user referred to in the url after
     *         userid=
     */
    public function get_data() {
        global $remotedb, $USER;
        $userid = optional_param('userid', 0, PARAM_INT); // User id.
        if (empty($userid)) {
            $userid = $USER->id;
        }
        $this->content = new stdClass();
        $params = array();
        $now = time();
        // Get any grade items entered directly in the Gradebook.
        $sql = "SELECT DISTINCT c.id AS courseid,
                            c.shortname, 
                            c.fullname AS coursename,
                            gg.itemid AS gradeitemid,
                            gi.itemname AS assessmentname,
                            gg.finalgrade AS grade,
                            gi.itemtype AS gi_itemtype,
                            gi.itemmodule AS assessmenttype,
                            gi.iteminstance AS gi_iteminstance,
                            gi.gradetype AS gradetype,
                            gi.scaleid,
                            gi.aggregationcoef2 AS weighting,
                            gi.display AS display,
                            -1 AS assignid,
                            -1 AS assignmentid,
                            -1 AS teamsubmission,
                            -1 AS submissiondate,
                            -1 AS duedate,
                            gi.itemname AS assessmentlink,
                            -1 AS reviewattempt,
                            -1 AS reviewmarks,
                            -1 AS reviewoverallfeedback,
                            -1 AS tiiobjid,
                            -1 AS subid,
                            -1 AS subpart,
                            -1 AS partname,
                            -1 AS usegrademark,
                            gg.feedback AS feedbacklink,
                            gg.rawgrademax AS highestgrade,
                            gg.userid,
                            -1 AS groupid,
                            -1 AS assigngradeid,
                            -1 AS contextid,
                            '' AS activemethod,
                            -1 AS nosubmissions,
                            '' AS status,
                            '' AS onlinetext
                        FROM {course} c
                        JOIN {grade_items} gi ON c.id=gi.courseid
                             AND itemtype='manual' AND (gi.hidden != 1 AND gi.hidden < ?)
                        JOIN {grade_grades} gg ON gi.id=gg.itemid AND gg.userid = ?
                             AND (gg.hidden != 1 AND gg.hidden < ?)
                        WHERE c.visible=1 AND c.showgrades = 1 ";
        array_push($params, $now, $userid, $now);
        if ($this->mod_is_available("assign")) {
            $sql .= "UNION SELECT DISTINCT c.id AS courseid,
                            c.shortname, 
                            c.fullname AS coursename,
                            gg.itemid AS gradeitemid,
                            gi.itemname AS assessmentname,
                            gg.finalgrade AS grade,
                            gi.itemtype AS gi_itemtype,
                            gi.itemmodule AS assessmenttype,
                            gi.iteminstance AS gi_iteminstance,
                            gi.gradetype AS gradetype,
                            gi.scaleid,
                            gi.aggregationcoef2 AS weighting,
                            gi.display AS display,
                            a.id AS assignid,
                            cm.id AS assignmentid,
                            a.teamsubmission,
                            su.timemodified AS submissiondate,
                            a.duedate AS duedate,
                            a.name AS assessmentlink,
                            -1 AS reviewattempt,
                            -1 AS reviewmarks,
                            -1 AS reviewoverallfeedback,
                            -1 AS tiiobjid,
                            -1 AS subid,
                            -1 AS subpart,
                            -1 AS partname,
                            -1 AS usegrademark,
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
                             AND itemtype='mod' AND (gi.hidden != 1 AND gi.hidden < ?)
                        JOIN {grade_grades} gg ON gi.id=gg.itemid AND gg.userid = ? 
                             AND (gg.hidden != 1 AND gg.hidden < ?)
                        JOIN {course_modules} cm ON gi.iteminstance=cm.instance
                        JOIN {context} con ON cm.id = con.instanceid AND con.contextlevel=70
                        JOIN {modules} m ON cm.module = m.id AND gi.itemmodule = m.name AND gi.itemmodule = 'assign'
                        JOIN {assign} a ON a.id=gi.iteminstance
                        JOIN mdl_assign_plugin_config apc on a.id = apc.assignment AND apc.name='enabled' AND plugin = 'onlinetext'
                        JOIN {assign_grades} ag ON a.id = ag.assignment AND ag.userid=?
                        JOIN mdl_assign_user_flags auf ON a.id = auf.assignment AND auf.workflowstate = 'released'
                        AND  auf.userid = ? OR a.markingworkflow = 0
                        JOIN {grading_areas} ga ON con.id = ga.contextid
                   LEFT JOIN {assign_submission} su ON a.id = su.assignment AND su.userid = ?
                        WHERE c.visible=1 AND c.showgrades = 1 AND cm.visible=1 ";
            array_push($params, $now, $userid, $now, $userid, $userid, $userid);
        }
        if ($this->mod_is_available("quiz")) {
            $sql .= "UNION SELECT DISTINCT c.id AS courseid,
                               c.shortname, 
                               c.fullname AS coursename,
                               gg.itemid AS gradeitemid,
                               gi.itemname AS assessmentname,
                               gg.finalgrade AS grade,
                               gi.itemtype AS gi_itemtype,
                               gi.itemmodule AS assessmenttype,
                               gi.iteminstance AS gi_iteminstance,
                               gi.gradetype AS gradetype,
                               gi.scaleid,
                               gi.aggregationcoef2 AS weighting,
                               gi.display AS display,
                               a.id AS assignid,
                               cm.id AS assignmentid,
                               -1 AS teamsubmission,
                               -1 AS submissiondate,
                               a.timeclose AS duedate,
                               a.name AS assessmentlink,
                               a.reviewattempt AS reviewattempt,
                               a.reviewmarks AS reviewmarks,
                               a.reviewoverallfeedback AS reviewoverallfeedback,
                               -1 AS tiiobjid,
                               -1 AS subid,
                               -1 AS subpart,
                               -1 AS partname,
                               -1 AS usegrademark,
                               gg.feedback AS feedbacklink,
                               gg.rawgrademax AS highestgrade,
                               gg.userid,
                               -1 AS groupid,
                               -1 AS assigngradeid,
                               con.id AS contextid,
                               ga.activemethod,
                               -1 AS nosubmissions,
                               '' AS status,
                               '' AS onlinetext
                        FROM {course} c
                        JOIN {grade_items} gi ON c.id=gi.courseid AND itemtype='mod'
                             AND (gi.hidden != 1 AND gi.hidden < ?)
                        JOIN {grade_grades} gg ON gi.id=gg.itemid AND gg.userid = ?
                             AND (gg.hidden != 1 AND gg.hidden < ?)
                        JOIN {course_modules} cm ON gi.iteminstance=cm.instance
                        JOIN {context} con ON cm.id = con.instanceid AND con.contextlevel=70
                        JOIN {modules} m ON cm.module = m.id AND gi.itemmodule = m.name AND gi.itemmodule = 'quiz'
                        JOIN {quiz} a ON a.id=gi.iteminstance
                        LEFT JOIN {grading_areas} ga ON con.id = ga.contextid
                        WHERE c.visible=1 AND c.showgrades = 1 AND cm.visible=1 ";
            array_push($params, $now, $userid, $now);
        }
        if ($this->mod_is_available("workshop")) {
            $sql .= "UNION SELECT DISTINCT c.id AS courseid,
                               c.shortname, 
                               c.fullname AS coursename,
                               gg.itemid AS gradeitemid,
                               gi.itemname AS assessmentname,
                               gg.finalgrade AS grade,
                               gi.itemtype AS gi_itemtype,
                               gi.itemmodule AS assessmenttype,
                               gi.iteminstance AS gi_iteminstance,
                               gi.gradetype AS gradetype,
                               gi.scaleid,
                               gi.aggregationcoef2 AS weighting,
                               gi.display AS display,
                               a.id AS assignid,
                               cm.id AS assignmentid,
                               -1 AS teamsubmission,
                               su.timemodified AS submissiondate,
                               a.submissionend AS duedate,
                               a.name AS assessmentlink,
                               -1 AS reviewattempt,
                               -1 AS reviewmarks,
                               -1 AS reviewoverallfeedback,
                               -1 AS tiiobjid,
                               su.id AS subid,                                                           
                               -1 AS subpart,
                               -1 AS partname,
                               -1 AS usegrademark,
                               gg.feedback AS feedbacklink,
                               gg.rawgrademax AS highestgrade,
                               gg.userid,
                               -1 AS groupid,
                               -1 AS assigngradeid,
                               con.id AS contextid,
                               ga.activemethod,
                               a.nattachments AS nosubmissions,
                               '' AS status,
                               '' AS onlinetext
                        FROM {course} c
                        JOIN {grade_items} gi ON c.id=gi.courseid AND itemtype='mod'
                             AND (gi.hidden != 1 AND gi.hidden < ?)
                        JOIN {grade_grades} gg ON gi.id=gg.itemid AND gg.userid = ?
                             AND (gg.hidden != 1 AND gg.hidden < ?)
                        JOIN {course_modules} cm ON gi.iteminstance=cm.instance
                        JOIN {context} con ON cm.id = con.instanceid AND con.contextlevel=70
                        JOIN {modules} m ON cm.module = m.id AND gi.itemmodule = m.name AND gi.itemmodule = 'workshop'
                        JOIN {workshop} a ON gi.iteminstance = a.id AND a.phase = 50
                        JOIN {workshop_submissions} su ON a.id = su.workshopid AND su.authorid=?
                   LEFT JOIN {grading_areas} ga ON con.id = ga.contextid
                        WHERE c.visible=1 AND c.showgrades = 1 AND cm.visible=1 ";
            array_push($params, $now, $userid, $now, $userid);
        }
        if ($this->mod_is_available("turnitintool")) {
            $sql .= "UNION SELECT DISTINCT c.id AS courseid,
                               c.shortname, 
                               c.fullname AS coursename,
                               gg.itemid AS gradeitemid,
                               gi.itemname AS assessmentname,
                               su.submission_grade AS grade,
                               gi.itemtype AS gi_itemtype,
                               gi.itemmodule AS assessmenttype,
                               gi.iteminstance AS gi_iteminstance,
                               gi.gradetype AS gradetype,
                               gi.scaleid,
                               gi.aggregationcoef2 AS weighting,
                               gi.display AS display,
                               -1 AS assignid,
                               cm.id AS assignmentid,
                               -1 AS teamsubmission,
                               su.submission_modified AS submissiondate,    
                               tp.dtdue AS duedate,
                               su.submission_title AS assessmentlink,                               
                               -1 AS reviewattempt,
                               -1 AS reviewmarks,
                               -1 AS reviewoverallfeedback,
                               su.submission_objectid AS tiiobjid,
                               su.id AS subid,
                               su.submission_part AS subpart,                                                       
                               tp.partname,
                               t.usegrademark,
                               gg.feedback AS feedbacklink,   
                               t.grade AS highestgrade,
                               su.userid,
                               -1 AS groupid,
                               -1 AS assigngradeid,
                               con.id AS contextid,
                               ga.activemethod,
                               t.numparts AS nosubmissions,
                               '' AS status,
                               '' AS onlinetext
                        FROM {course} c
                        JOIN {grade_items} gi ON c.id=gi.courseid AND itemtype='mod'
                             AND (gi.hidden != 1 AND gi.hidden < ?) 
                        JOIN {course_modules} cm ON gi.iteminstance=cm.instance AND c.id=cm.course
                        JOIN {context} con ON cm.id = con.instanceid AND con.contextlevel=70
                        JOIN {modules} m ON cm.module = m.id AND gi.itemmodule = m.name AND gi.itemmodule = 'turnitintool'
                        JOIN {grade_grades} gg ON gi.id=gg.itemid AND gg.userid = ?
                             AND (gg.hidden != 1 AND gg.hidden < ?)
                        JOIN {turnitintool} t ON t.id=gi.iteminstance
                        JOIN {turnitintool_submissions} su ON t.id = su.turnitintoolid AND su.userid = ?
                        JOIN {turnitintool_parts} tp ON su.submission_part = tp.id AND tp.dtpost < ? 
                   LEFT JOIN {grading_areas} ga ON con.id = ga.contextid 
                        WHERE c.visible = 1 AND c.showgrades = 1 AND cm.visible=1 ";
            array_push($params, $now, $userid, $now, $userid, $now);
        }
        if ($this->mod_is_available("turnitintooltwo")) {
            $sql .= "UNION SELECT DISTINCT c.id AS courseid,
                               c.shortname, 
                               c.fullname AS coursename,
                               gg.itemid AS gradeitemid,
                               gi.itemname AS assessmentname,
                               su.submission_grade AS grade,
                               gi.itemtype AS gi_itemtype,
                               gi.itemmodule AS assessmenttype,
                               gi.iteminstance AS gi_iteminstance,
                               gi.gradetype AS gradetype,
                               gi.scaleid,
                               gi.aggregationcoef2 AS weighting,
                               gi.display AS display,
                               -1 AS assignid,
                               cm.id AS assignmentid,
                               -1 AS teamsubmission,
                               su.submission_modified AS submissiondate,
                               tp.dtdue AS duedate,
                               su.submission_title AS assessmentlink,
                               -1 AS reviewattempt, 
                               -1 AS reviewmarks,
                               -1 AS reviewoverallfeedback,
                               su.submission_objectid AS tiiobjid,
                               su.id AS subid,
                               su.submission_part AS subpart,                              
                               tp.partname,                               
                               t.usegrademark,
                               gg.feedback AS feedbacklink,
                               t.grade AS highestgrade,
                               su.userid,
                               -1 AS groupid,
                               -1 AS assigngradeid,
                               con.id AS contextid,
                               ga.activemethod,
                               t.numparts AS nosubmissions,
                               '' AS status,
                               '' AS onlinetext
                        FROM {course} c
                        JOIN {grade_items} gi ON c.id=gi.courseid AND itemtype='mod'
                             AND (gi.hidden != 1 AND gi.hidden < ?)
                        JOIN {course_modules} cm ON gi.iteminstance=cm.instance AND c.id=cm.course
                        JOIN {context} con ON cm.id = con.instanceid AND con.contextlevel=70
                        JOIN {modules} m ON cm.module = m.id AND gi.itemmodule = m.name AND gi.itemmodule = 'turnitintooltwo'
                        JOIN {grade_grades} gg ON gi.id=gg.itemid AND gg.userid = ?
                             AND (gg.hidden != 1 AND gg.hidden < ?)
                        JOIN {turnitintooltwo} t ON t.id=gi.iteminstance
                        JOIN {turnitintooltwo_submissions} su ON t.id = su.turnitintooltwoid AND su.userid = ?
                        JOIN {turnitintooltwo_parts} tp ON su.submission_part = tp.id AND tp.dtpost < ?
                   LEFT JOIN {grading_areas} ga ON con.id = ga.contextid 
                        WHERE c.visible = 1 AND c.showgrades = 1 AND cm.visible=1 ";
            array_push($params, $now, $userid, $now, $userid, $now);
        }
        //$sql .= " ORDER BY duedate";
        // Get a number of records as a moodle_recordset using a SQL statement.
        $rs = $remotedb->get_recordset_sql($sql, $params, $limitfrom = 0, $limitnum = 0);
        return $rs;
    }

    public function get_content($tab = NULL) {
        global $CFG, $OUTPUT, $USER;
        $userid = optional_param('userid', 0, PARAM_INT); // User id.
        if (empty($userid)) {
            $userid = $USER->id;
        }
        $x = 0;
        $exceltable = array();
        $reset_print_excel1 = null;
        $reset_print_excel = null;
        $tz = $this->user_timezone();
        $usertimezone = $this->timezone_letter($tz);
        $title = "<div>" . get_string('provisional_grades', 'report_myfeedback') . "</div><br />";
        // Print titles for each column: Assessment, Type, Due Date, Submission Date,
        // Submission/Feedback, Grade/Highest Grade.
        $table = "<table class=\"grades\" id=\"grades\" width=\"100%\">
                    <thead>
                            <tr class=\"tableheader\">
                                <th>" .
                get_string('gradetblheader_module', 'report_myfeedback') . "</th>
                                <th>" .
                get_string('gradetblheader_assessment', 'report_myfeedback') . "</th>
                                <th>" .
                get_string('gradetblheader_type', 'report_myfeedback') . "</th>
                                <th>" .
                get_string('gradetblheader_duedate', 'report_myfeedback') . "</th>
                                <th>" .
                get_string('gradetblheader_submissiondate', 'report_myfeedback') . "</th>
                                <th>" .
                get_string('gradetblheader_feedback', 'report_myfeedback') . "</th>
                                <th>" .
                get_string('gradetblheader_grade', 'report_myfeedback') . "</th>
                                                            <th>" .
                get_string('gradetblheader_range', 'report_myfeedback') . "</th>
                            <th class='bar'>" .
                get_string('gradetblheader_bar', 'report_myfeedback') . "</th>
                            </tr>
                        </thead>
                                                <tbody>";
        // Setup the heading for the Comments table
        $commentstable = "<table id=\"feedbackcomments\" width=\"100%\" border=\"0\">
                <thead>
                            <tr class=\"tableheader\">
                                <th>" .
                get_string('gradetblheader_module', 'report_myfeedback') . "</th>
                                <th>" .
                get_string('gradetblheader_assessment', 'report_myfeedback') . "</th>
                                <th>" .
                get_string('gradetblheader_type', 'report_myfeedback') . "</th>
                                <th>" .
                get_string('gradetblheader_submissiondate', 'report_myfeedback') . "</th>
                                <th>" .
                get_string('gradetblheader_grade', 'report_myfeedback') . "</th>
                                <th>" .
                get_string('gradetblheader_generalfeedback', 'report_myfeedback') . "</th>
                                <th>" .
                get_string('gradetblheader_feedback', 'report_myfeedback') . "</th>
                                <th>" .
                get_string('gradetblheader_viewed', 'report_myfeedback') . "</th>
                                                </tr>
                        </thead>
                                                <tbody>";

        $rs = $this->get_data();
        if ($rs->valid()) {
            // The recordset contains records.
            foreach ($rs as $record) {
                // Put data into table for display here.
                // Check permissions for course.
                // Set the variables for each column.
                $gradedisplay = $record->display;
                $gradetype = $record->gradetype;
                $cfggradetype = $CFG->grade_displaytype;
                $feedbacktextlink = "&nbsp;";
                $feedbacktext = "-";
                $submission = "&nbsp;";
                $feedbackfileicon = "&nbsp;";
                $quizgrade = 'yes';
                $assignment = $record->assessmentname;
                /* if (strlen($record->assessmentname) > 35) {
                  $assignment = substr($record->assessmentname, 0, 32) . '...';
                  } */
                $assignmentname = "<a href=\"" . $CFG->wwwroot . "/mod/" . $record->assessmenttype .
                        "/view.php?id=" . $record->assignmentid . "\" title=\"" . $record->assessmentname .
                        "\" rel=\"tooltip\">" . $assignment .
                        "</a>";

                //$duedate = ($record->duedate ? date('d-M-Y H:i', usertime($record->duedate, $tz)) . ' ' . $usertimezone : "-");
                //$due_datesort = ($record->duedate ? usertime($record->duedate, $tz) : "-");
                $duedate = ($record->duedate ? date('d-M-Y H:i', $record->duedate) . ' ' . $this->bst_gmt($record->duedate) : "-");
                $due_datesort = ($record->duedate ? $record->duedate : "-");

                // Submission date.
                $submissiondate = "&nbsp;";
                if ($record->submissiondate) {
                    $submissiondate = $record->submissiondate;
                }
                // Display information for each type of assessment activity.
                $assessmenticon = "";
                if ($record->assessmenttype) {

                    switch ($record->assessmenttype) {
                        case "assign":
                            $assessmenttype = get_string('moodle_assignment', 'report_myfeedback');
                            if ($record->teamsubmission == 1) {
                                $assessmenttype .= " (" .
                                        get_string('groupwork', 'report_myfeedback') . ")";
                                if (!is_numeric($submissiondate) || (!strlen($submissiondate) == 10)) {
                                    $submissiondate = $this->get_group_assign_submission_date(
                                            $userid, $record->contextid);
                                }
                            }
                            // For Moodle Assignments:
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
                                    $submission .= " (" . get_string('draft', 'report_myfeedback') . ")";
                                    // This will set it to no submission later because it checks for all numbers.
                                    $submissiondate = /* date('d-M-Y H:i', $submissiondate) . " " . */get_string('draft', 'report_myfeedback');
                                }
                                if ($submission == null && $submissiondate == null) {
                                    $submissiondate = get_string('no_submission', 'report_myfeedback');
                                }
                            } else {
                                $assessmenttype .= " (" . get_string('offline_assignment', 'report_myfeedback') . ")";
                                $submission = "-";
                                $submissiondate = "-";
                            }
                            // Check whether an online PDF annotated feedback or any feedback file exists.
                            $onlinepdffeedback = false;
                            if ($record->assigngradeid) {
                                $onlinepdffeedback = $this->has_pdf_feedback_file(
                                        $record->contextid, $record->assigngradeid);
                            }

                            // If there are any comments or other feedback (such as online PDF
                            // files, rubrics or marking guides)
                            if ($feedbacktextlink != "&nbsp;" || $record->feedbacklink ||
                                    $onlinepdffeedback || $record->activemethod == "rubric" ||
                                    $record->activemethod == "guide") {
                                $feedbacktextlink = "<a href=\"" . $CFG->wwwroot .
                                        "/mod/assign/view.php?id=" . $record->assignmentid . "\">" .
                                        get_string('feedback', 'report_myfeedback') . "</a>";
                                $feedbacktext = $record->feedbacklink;

                                //Add an icon if has pdf file
                                if ($onlinepdffeedback) {
                                    $feedbackfile = 'Has feedback file.';
                                    $feedbackfileicon = ' <img src="' .
                                            $OUTPUT->pix_url('i/report') . '" ' .
                                            'class="icon" alt="' . $feedbackfile . '" title="' . $feedbackfile . '" rel="tooltip">';
                                }
                                //implementing the rubric guide
                                if ($record->activemethod == "rubric") {
                                    if ($get_rubric = $this->rubrictext($userid, $record->courseid, $record->gi_iteminstance, 'assign')) {
                                        $feedbacktext.="<br/>&nbsp;<br/><span style=\"padding-left:60px;font-weight:bold;\"><img src=\"" .
                                                $CFG->wwwroot . "/report/myfeedback/pix/rubric.png\"> Rubric</span><br/>" . $get_rubric;
                                    }
                                }

                                //implementing the marking guide
                                if ($record->activemethod == "guide") {
                                    if ($get_guide = $this->marking_guide_text($userid, $record->courseid, $record->gi_iteminstance, 'assign')) {
                                        $feedbacktext.="<span style=\"padding-left:55px;font-weight:bold;\"><img src=\"" .
                                                $CFG->wwwroot . "/report/myfeedback/pix/guide.png\"> Marking guide</span><br/>" . $get_guide;
                                    }
                                }
                            }

                            //Checking if the user was given an assignment extension
                            $checkforextension = $this->check_assign_extension($userid, $record->assignid);
                            if ($checkforextension) {
                                $record->duedate = $checkforextension;
                                //$duedate = date('d-M-Y H:i', usertime($checkforextension, $tz)) . ' ' . $usertimezone;
                                //$due_datesort = usertime($checkforextension, $tz);
                                $duedate = date('d-M-Y H:i', $checkforextension) . ' ' . $this->bst_gmt($record->duedate);
                                $due_datesort = $checkforextension;
                            }
                            break;
                        case "turnitintool":
                            $assignmentname .= " ( " . $record->partname . " )";
                            $assessmenttype = get_string('turnitin_assignment', 'report_myfeedback');
                            $newwindowmsg = get_string('new_window_msg', 'report_myfeedback');
                            $newwindowicon = '<img src="' . 'pix/external-link.png' . '" ' .
                                    ' alt="-" title="' . $newwindowmsg . '" rel="tooltip"/>';
                            if ($record->assignmentid && $record->subpart && $record->tiiobjid &&
                                    $record->assessmentname) {
                                // Not sure what utp is, but it seems to work when set to 2 for admin and 1
                                // for students.
                                // Link the submission to the plagiarism comparison report.
                                // If grademark marking is enabled.
                                if ($record->usegrademark == 1) {
                                    // Link the submission to the gradebook.
                                    $feedbacktextlink = "<a href=\"" . $CFG->wwwroot . "/mod/" .
                                            $record->assessmenttype . "/view.php?id=" .
                                            $record->assignmentid . "&jumppage=grade&userid=" . $userid .
                                            "&utp=1&partid=" . $record->subpart . "&objectid=" . $record->tiiobjid .
                                            "\" target=\"_blank\">" . get_string('feedback', 'report_myfeedback') .
                                            "</a> $newwindowicon";
                                } else {
                                    $feedbacktextlink = "<a href=\"" . $CFG->wwwroot . "/mod/" .
                                            $record->assessmenttype . "/view.php?id=" .
                                            $record->assignmentid . "&do=submissions\" target=\"_blank\">" .
                                            get_string('feedback', 'report_myfeedback') . "</a> $newwindowicon";
                                }

                                $submission = "<a href=\"" . $CFG->wwwroot . "/mod/" .
                                        $record->assessmenttype . "/view.php?id=" .
                                        $record->assignmentid . "&do=submissions\" target=\"_blank\">" .
                                        get_string('submission', 'report_myfeedback') . "</a> $newwindowicon";
                            }
                            break;
                        case "turnitintooltwo":
                            $assignmentname .= " ( " . $record->partname . " )";
                            $assessmenttype = get_string('turnitin_assignment', 'report_myfeedback');
                            $newwindowmsg = get_string('new_window_msg', 'report_myfeedback');
                            $newwindowicon = '<img src="' . 'pix/external-link.png' . '" ' .
                                    ' alt="-" title="' . $newwindowmsg . '" rel="tooltip"/>';
                            if ($record->assignmentid && $record->subpart && $record->tiiobjid &&
                                    $record->assessmentname) {
                                // Not sure what utp is, but it seems to work when set to 2 for admin and 1
                                // for students.
                                // Link the submission to the plagiarism comparison report.
                                $submission = "<a href=\"" . $CFG->wwwroot . "/mod/" .
                                        $record->assessmenttype . "/view.php?id=" .
                                        $record->assignmentid . "&jumppage=report&userid=" . $userid .
                                        "&utp=1&partid=" . $record->subpart . "&objectid=" . $record->tiiobjid .
                                        "\" target=\"_blank\">" . get_string('submission', 'report_myfeedback') .
                                        "</a> $newwindowicon";
                                // If grademark marking is enabled.
                                if ($record->usegrademark == 1) {
                                    // Link the submission to the gradebook.
                                    $feedbacktextlink = "<a href=\"" . $CFG->wwwroot . "/mod/" .
                                            $record->assessmenttype . "/view.php?id=" .
                                            $record->assignmentid . "&jumppage=grade&userid=" . $userid .
                                            "&utp=1&partid=" . $record->subpart . "&objectid=" . $record->tiiobjid .
                                            "\" target=\"_blank\">" . get_string('feedback', 'report_myfeedback') .
                                            "</a> $newwindowicon";
                                } else {
                                    $feedbacktextlink = "<a href=\"" . $CFG->wwwroot . "/mod/" .
                                            $record->assessmenttype . "/view.php?id=" .
                                            $record->assignmentid . "&do=submissions\" target=\"_blank\">" .
                                            get_string('feedback', 'report_myfeedback') . "</a> $newwindowicon";
                                }
                            }
                            break;
                        case "workshop":
                            $assessmenttype = get_string('workshop', 'report_myfeedback');
                            $submission = "<a href=\"" . $CFG->wwwroot . "/mod/workshop/submission.php?cmid=" .
                                    $record->assignmentid . "&id=" . $record->subid . "\">" .
                                    get_string('submission', 'report_myfeedback') . "</a>";
                            if ($record->feedbacklink) {
                                $feedbacktextlink = "<a href=\"" . $CFG->wwwroot . "/mod/workshop/submission.php?cmid=" .
                                        $record->assignmentid . "&id=" . $record->subid . "\">" .
                                        get_string('feedback', 'report_myfeedback') . "</a>";
                                $feedbacktext = $record->feedbacklink;
                            }

                            // Check whether an online PDF annotated feedback or any feedback file exists.
                            $workshopfeedback = $this->has_workshop_feedback_file(
                                    $record->contextid, $userid, $record->assignid);

                            //Add an icon if has pdf file
                            if ($workshopfeedback) {
                                $feedbackfile = 'Has feedback file.';
                                $feedbackfileicon = ' <img src="' .
                                        $OUTPUT->pix_url('i/report') . '" ' .
                                        'class="icon" alt="' . $feedbackfile . '" title="' . $feedbackfile . '" rel="tooltip">';
                            }

                            break;
                        case "quiz":
                            $assessmenttype = get_string('quiz', 'report_myfeedback');
                            $submission = "-";

                            //Checking if the user was given an overridden extension or as part of a group
                            $overrideextension = $this->check_quiz_extension($record->assignid, $userid);
                            $overrideextensiongroup = $this->check_quiz_group_extension($record->assignid, $userid);
                            if ($overrideextension && $overrideextensiongroup) {
                                if ($overrideextension > $overrideextensiongroup) {
                                    $record->duedate = $overrideextension;
                                    //$duedate = date('d-M-Y H:i', usertime($overrideextension, $tz)) . ' ' . $usertimezone;
                                    $duedate = date('d-M-Y H:i', $overrideextension) . ' ' . $this->bst_gmt($record->duedate);
                                } else {
                                    $record->duedate = $overrideextensiongroup;
                                    //$duedate = date('d-M-Y H:i', usertime($overrideextensiongroup, $tz)) . ' ' . $usertimezone;
                                    //$due_datesort = usertime($overrideextensiongroup, $tz);
                                    $duedate = date('d-M-Y H:i', $overrideextensiongroup) . ' ' . $this->bst_gmt($record->duedate);
                                    $due_datesort = $overrideextensiongroup;
                                }
                            }
                            if ($overrideextension && !($overrideextensiongroup)) {
                                $record->duedate = $overrideextension;
                                //$duedate = date('d-M-Y H:i', usertime($overrideextension, $tz)) . ' ' . $usertimezone;
                                //$due_datesort = usertime($overrideextension, $tz);
                                $duedate = date('d-M-Y H:i', $overrideextension) . ' ' . $this->bst_gmt($record->duedate);
                                $due_datesort = $overrideextension;
                            }
                            if ($overrideextensiongroup && !($overrideextension)) {
                                $record->duedate = $overrideextensiongroup;
                                //$duedate = date('d-M-Y H:i', usertime($overrideextensiongroup, $tz)) . ' ' . $usertimezone;
                                //$due_datesort = usertime($overrideextensiongroup, $tz);
                                $duedate = date('d-M-Y H:i', $overrideextensiongroup) . ' ' . $this->bst_gmt($record->duedate);
                                $due_datesort = $overrideextensiongroup;
                            }

                            //Checking for quiz last attempt date as submission date
                            $submit = $this->get_quiz_submissiondate($record->assignid, $userid);
                            if ($submit) {
                                $submissiondate = $submit;
                            }
                            if (!$feedbacktextlink = $this->get_quiz_attempts_link($record->assignid, $userid, $record->assignmentid)) {
                                $feedbacktextlink = '&nbsp;';
                            }

                            //Implementing the general feedback from tutor for a quiz attempt
                            $grading_info = grade_get_grades($record->courseid, 'mod', 'quiz', $record->gi_iteminstance, $userid);
                            if (!empty($grading_info->items)) {
                                $item = $grading_info->items[0];
                                if (isset($item->grades[$userid])) {
                                    $grade = $item->grades[$userid];

                                    if (!empty($grade->str_feedback)) {
                                        $feedbacktext = $grade->str_feedback;
                                    }
                                }
                            }

                            //implementing the overall feedback str in the quiz
                            $qid = intval($record->gi_iteminstance);
                            $grade2 = floatval($record->grade);
                            $feedback = $this->overallfeedback($qid, $grade2);
                            if ($feedback) {
                                $feedbacktext.="<br/><span style=\"padding-left:70px;font-weight:bold;\">- Overall-feedback -</span><br/>&nbsp;<br/>" . $feedback;
                            }

                            $now = time();
                            $review1 = $record->reviewattempt;
                            $review2 = $record->reviewmarks;
                            $review3 = $record->reviewoverallfeedback;

                            //Show only when quiz is open
                            if ($now <= $record->duedate) {
                                if ($review1 == 256 || $review1 == 4352 || $review1 == 65792 || $review1 == 69888) {
                                    //
                                } else {
                                    $feedbacktextlink = '&nbsp;';
                                }
                                //Added teh marks as well but if set to not show at all then this is just as hiding the grade in the gradebook and wont
                                //show the review links or the feedback text if these are set.
                                if ($review2 == 256 || $review2 == 4352 || $review2 == 65792 || $review2 == 69888) {
                                    $quizgrade = 'yes';
                                } else {
                                    $quizgrade = 'noreview';
                                }
                                if ($review3 == 256 || $review3 == 4352 || $review3 == 65792 || $review3 == 69888) {
                                    //
                                } else {
                                    $feedbacktext = '-';
                                }
                            } else {
                                // When the quiz is closed what do you show
                                if ($review1 == 16 || $review1 == 272 || $review1 == 4112 || $review1 == 4368 ||
                                        $review1 == 65552 || $review1 == 69748 || $review1 == 69904) {
                                    //
                                } else {
                                    $feedbacktextlink = '&nbsp;';
                                }
                                if ($review2 == 16 || $review2 == 272 || $review2 == 4112 || $review2 == 4368 ||
                                        $review2 == 65552 || $review2 == 69748 || $review2 == 69904) {
                                    $quizgrade = 'yes';
                                } else {
                                    $quizgrade = 'noreview';
                                }
                                if ($review3 == 16 || $review3 == 272 || $review3 == 4112 || $review3 == 4368 ||
                                        $review3 == 65552 || $review3 == 69748 || $review3 == 69904) {
                                    //
                                } else {
                                    $feedbacktext = '-';
                                }
                            }
                            break;
                    }
                } else {
                    //The manual item is assumed to be assignment - not sure why yet 
                    $itemtype = $record->gi_itemtype;
                    if ($itemtype === "manual") {
                        $assessmenttype = get_string('manual_gradeitem', 'report_myfeedback');
                        $assessmenticon = '<img src="' .
                                $OUTPUT->pix_url('i/manual_item') . '" ' .
                                'class="icon" alt="' . $itemtype . '" title="' . $itemtype . '" rel="tooltip">';
                        // Bring the student to their user report in the gradebook.
                        /* if (strlen($record->assessmentname) > 35) {
                          $assignment = substr($record->assessmentname, 0, 32) . '...';
                          } */
                        $assignmentname = "<a href=\"" . $CFG->wwwroot . "/grade/report/user/index.php?id=" . $record->courseid .
                                "&userid=" . $record->userid . "\" title=\"" . $record->assessmentname .
                                "\" rel=\"tooltip\">" . $assignment . "</a>";

                        $submission = "-";
                        $submissiondate = "-";
                        $duedate = "-";
                        $due_datesort = "-";
                        if ($record->feedbacklink) {
                            $feedbacktextlink = "<a href=\"" . $CFG->wwwroot .
                                    "/grade/report/user/index.php?id=" . $record->courseid . "&userid=" . $record->userid . "\">" .
                                    get_string('feedback', 'report_myfeedback') . "</a>";
                            $feedbacktext = $record->feedbacklink;
                        }
                    }
                }

                //Add the assessment icon.
                if ($assessmenticon == "") {
                    $assessmenticon = '<img src="' .
                            $OUTPUT->pix_url('icon', $record->assessmenttype) . '" ' .
                            'class="icon" alt="' . $assessmenttype . '" title="' . $assessmenttype . '"  rel="tooltip" />';
                }
                //Set the sortable date before converting to d-M-y format 
                $sub_datesort = $submissiondate;
                // If no feedback or grade has been received don't display anything.
                if (!($feedbacktextlink == '&nbsp;' && $record->grade == null)) {
                    // Mark late submissions in red.
                    $submissionmsg = "";
                    if (is_numeric($submissiondate) && (strlen($submissiondate) == 10)) {
                        $submittedtime = $submissiondate;
                        //$submissiondate = date('d-M-Y H:i', usertime($submissiondate, $tz)) . ' ' . $usertimezone;
                        $submissiondate = date('d-M-Y H:i', $submissiondate) . ' ' . $this->bst_gmt($submissiondate);
                    } else if (strpos($assessmenttype, get_string('offline_assignment', 'report_myfeedback')) === false && strpos($assessmenttype, get_string('quiz', 'report_myfeedback')) === false && strpos($record->gi_itemtype, get_string('manual_gradeitem', 'report_myfeedback')) === true) {
                        if (strpos($submissiondate, get_string('draft', 'report_myfeedback')) === false) {
                            $submissiondate = get_string('no_submission', 'report_myfeedback');
                            $submissionmsg = get_string('no_submission_msg', 'report_myfeedback');
                        } else {
                            $submissiondate = get_string('draft_submission', 'report_myfeedback');
                            $submissionmsg = get_string('draft_submission_msg', 'report_myfeedback');
                        }
                        $submittedtime = time();
                    }

                    //Late message if submission late
                    if ($submissiondate != "-" && $duedate != "-" && $submittedtime > $record->duedate) {

                        if ($submissionmsg == "") {
                            $submissionmsg = get_string('late_submission_msg', 'report_myfeedback');
                            if ($record->duedate) {
                                $submissionmsg .=' Was ' . format_time($submittedtime - $record->duedate) . ' late.';
                            }
                        }
                        $alerticon = '<img class="smallicon" src="' . $OUTPUT->pix_url('i/warning', 'core') . '" ' . 'class="icon" alt="-" title="' .
                                $submissionmsg . '" rel="tooltip"/>';
                        $submissiondate = "<span style=\"color: #990000;\">" . $submissiondate . " $alerticon</span>";
                    }
                    if (strlen($record->shortname) > 20) {
                        $shortname = substr($record->shortname, 0, 17) . '...';
                    } else {
                        $shortname = $record->shortname;
                    }

                    //Display grades in the format defined within each Moodle course
                    $realgrade = null;
                    $mingrade = null;
                    $availablegrade = null;
                    $graderange = null;
                    if ($record->gi_itemtype == 'mod') {
                        /* $realgrades = grade_get_grades($record->courseid, $record->gi_itemtype, $record->assessmenttype, $record->gi_iteminstance, $userid);
                          foreach ($realgrades->items AS $key => $real) {
                          $inst = $real->id;
                          if ($inst == $record->gradeitemid) {
                          $realgrade = $real->grades[$userid]->str_grade;
                          if (is_numeric($realgrade)) {
                          $realgrade = $this->get_fraction($realgrade);
                          }
                          }
                          } */
                        if ($quizgrade != 'noreview') {
                            //Get the grade display type and grade type 
                            switch ($gradetype) {
                                case GRADE_TYPE_SCALE:
                                    $realgrade = $this->get_grade_scale($record->gradeitemid, $userid, $record->courseid);
                                    $mingrade = $this->get_min_grade_scale($record->gradeitemid, $userid, $record->courseid);
                                    $graderange = $this->get_all_grade_scale($record->gradeitemid, $userid, $record->courseid);
                                    $availablegrade = $this->get_available_grade_scale($record->gradeitemid, $userid, $record->courseid);
                                    break;
                                case GRADE_TYPE_NONE:
                                case GRADE_TYPE_TEXT:
                                    $realgrade = "-";
                                    $mingrade = "-";
                                    $availablegrade = "-";
                                    break;
                                case GRADE_TYPE_VALUE:
                                    if (($gradedisplay == GRADE_DISPLAY_TYPE_LETTER) ||
                                            ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                            $cfggradetype == GRADE_DISPLAY_TYPE_LETTER)) {
                                        $realgrade = $this->get_grade_letter($record->courseid, $record->grade / $record->highestgrade * 100);
                                        $mingrade = $this->get_min_grade_letter($record->gradeitemid, $userid, $record->courseid);
                                        $graderange = $this->get_all_grade_letters($record->courseid);
                                        $availablegrade = $this->get_available_grade_letter($record->courseid, $record->grade);
                                    } else if (($gradedisplay == GRADE_DISPLAY_TYPE_LETTER_PERCENTAGE) ||
                                            ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                            $cfggradetype == GRADE_DISPLAY_TYPE_LETTER_PERCENTAGE)) {
                                        $realgrade = $this->get_grade_letter($record->courseid, $record->grade / $record->highestgrade * 100) .
                                                " (" . $this->get_fraction($record->grade) / $this->get_fraction($record->highestgrade) * 100 . "%)";
                                        $mingrade = $this->get_min_grade_letter($record->gradeitemid, $userid, $record->courseid) . " (0)";
                                        $graderange = $this->get_all_grade_letters($record->courseid);
                                        $availablegrade = $this->get_available_grade_letter($record->courseid, $record->grade) .
                                                " (" . $this->get_fraction($record->highestgrade) / $this->get_fraction($record->highestgrade) * 100 . "%)";
                                    } else if (($gradedisplay == GRADE_DISPLAY_TYPE_LETTER_REAL) ||
                                            ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                            $cfggradetype == GRADE_DISPLAY_TYPE_LETTER_REAL)) {
                                        $realgrade = $this->get_grade_letter($record->courseid, $record->grade / $record->highestgrade * 100) .
                                                " (" . $this->get_fraction($record->grade) . ")";
                                        $mingrade = $this->get_min_grade_letter($record->courseid, $record->grade) . " (0)";
                                        $graderange = $this->get_all_grade_letters($record->courseid);
                                        $availablegrade = $this->get_available_grade_letter($record->courseid, $record->grade) .
                                                " (" . $this->get_fraction($record->highestgrade) . ")";
                                    } else if (($gradedisplay == GRADE_DISPLAY_TYPE_PERCENTAGE) ||
                                            ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                            $cfggradetype == GRADE_DISPLAY_TYPE_PERCENTAGE)) {
                                        $realgrade = $this->get_fraction($record->grade) . "%";
                                        $mingrade = "0";
                                        $availablegrade = $this->get_fraction($record->highestgrade) / $this->get_fraction($record->highestgrade) * 100 . "%";
                                    } else if (($gradedisplay == GRADE_DISPLAY_TYPE_PERCENTAGE_LETTER) ||
                                            ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                            $cfggradetype == GRADE_DISPLAY_TYPE_PERCENTAGE_LETTER)) {
                                        $realgrade = $this->get_fraction($record->grade) . "% (" .
                                                $this->get_grade_letter($record->courseid, $record->grade / $record->highestgrade * 100) . ")";
                                        $mingrade = "0 (" . $this->get_min_grade_letter($record->courseid, $record->grade) . ")";
                                        $graderange = $this->get_all_grade_letters($record->courseid);
                                        $availablegrade = $this->get_fraction($record->highestgrade) / $this->get_fraction($record->highestgrade) * 100 . "% (" .
                                                $this->get_available_grade_letter($record->courseid, $record->grade) . ")";
                                    } else if (($gradedisplay == GRADE_DISPLAY_TYPE_PERCENTAGE_REAL) ||
                                            ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                            $cfggradetype == GRADE_DISPLAY_TYPE_PERCENTAGE_REAL)) {
                                        $realgrade = $this->get_fraction($record->grade) . "% (" .
                                                $this->get_fraction($record->grade) . ")";
                                        $mingrade = "0 (0)";
                                        $availablegrade = $this->get_fraction($record->highestgrade) / $this->get_fraction($record->highestgrade) * 100 . "% (" .
                                                $this->get_fraction($record->highestgrade) . ")";
                                    } else if (($gradedisplay == GRADE_DISPLAY_TYPE_REAL_LETTER) ||
                                            ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                            $cfggradetype == GRADE_DISPLAY_TYPE_REAL_LETTER)) {
                                        $realgrade = $this->get_fraction($record->grade) . " (" .
                                                $this->get_grade_letter($record->courseid, $record->grade / $record->highestgrade * 100) . ")";
                                        $mingrade = "0 (" . $this->get_min_grade_letter($record->courseid, $record->grade) . ")";
                                        $graderange = $this->get_all_grade_letters($record->courseid);
                                        $availablegrade = $this->get_fraction($record->highestgrade) . " (" .
                                                $this->get_available_grade_letter($record->courseid, $record->grade) . ")";
                                    } else if (($gradedisplay == GRADE_DISPLAY_TYPE_REAL_PERCENTAGE) ||
                                            ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                            $cfggradetype == GRADE_DISPLAY_TYPE_REAL_PERCENTAGE)) {
                                        $realgrade = $this->get_fraction($record->grade) . " (" .
                                                $this->get_fraction($record->grade) . "%)";
                                        $mingrade = "0 (0)";
                                        $availablegrade = $this->get_fraction($record->highestgrade) . " (" .
                                                $this->get_fraction($record->highestgrade) / $this->get_fraction($record->highestgrade) * 100 . "%)";
                                    } else {
                                        $realgrade = $this->get_fraction($record->grade);
                                        $mingrade = "0";
                                        $availablegrade = $this->get_fraction($record->highestgrade);
                                    }
                                    break;
                            }
                        }
                    }

                    if ($record->gi_itemtype == 'manual') {
                        switch ($gradetype) {
                            case GRADE_TYPE_SCALE:
                                $realgrade = $this->get_grade_scale($record->gradeitemid, $userid, $record->courseid);
                                $mingrade = $this->get_min_grade_scale($record->gradeitemid, $userid, $record->courseid);
                                $graderange = $this->get_all_grade_scale($record->gradeitemid, $userid, $record->courseid);
                                $availablegrade = $this->get_available_grade_scale($record->gradeitemid, $userid, $record->courseid);
                                break;
                            case GRADE_TYPE_NONE:
                            case GRADE_TYPE_TEXT:
                                $realgrade = "-";
                                $availablegrade = "-";
                                break;
                            case GRADE_TYPE_VALUE:
                                if (($gradedisplay == GRADE_DISPLAY_TYPE_LETTER) ||
                                        ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                        $cfggradetype == GRADE_DISPLAY_TYPE_LETTER)) {
                                    $realgrade = $this->get_grade_letter($record->courseid, $record->grade / $record->highestgrade * 100);
                                    $mingrade = $this->get_min_grade_letter($record->courseid, $record->grade);
                                    $graderange = $this->get_all_grade_letters($record->courseid);
                                    $availablegrade = $this->get_available_grade_letter($record->courseid, $record->grade);
                                } else if (($gradedisplay == GRADE_DISPLAY_TYPE_LETTER_PERCENTAGE) ||
                                        ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                        $cfggradetype == GRADE_DISPLAY_TYPE_LETTER_PERCENTAGE)) {
                                    $realgrade = $this->get_grade_letter($record->courseid, $record->grade / $record->highestgrade * 100) .
                                            " (" . $this->get_fraction($record->grade) . "%)";
                                    $mingrade = $this->get_min_grade_letter($record->courseid, $record->grade) . " (0)";
                                    $graderange = $this->get_all_grade_letters($record->courseid);
                                    $availablegrade = $this->get_available_grade_letter($record->courseid, $record->grade) .
                                            " (" . $this->get_fraction($record->highestgrade) / $this->get_fraction($record->highestgrade) * 100 . "%)";
                                } else if (($gradedisplay == GRADE_DISPLAY_TYPE_LETTER_REAL) ||
                                        ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                        $cfggradetype == GRADE_DISPLAY_TYPE_LETTER_REAL)) {
                                    $realgrade = $this->get_grade_letter($record->courseid, $record->grade / $record->highestgrade * 100) .
                                            " (" . $this->get_fraction($record->grade) . ")";
                                    $mingrade = $this->get_min_grade_letter($record->courseid, $record->grade) . " (0)";
                                    $graderange = $this->get_all_grade_letters($record->courseid);
                                    $availablegrade = $this->get_available_grade_letter($record->courseid, $record->grade) .
                                            " (" . $this->get_fraction($record->highestgrade) . ")";
                                } else if (($gradedisplay == GRADE_DISPLAY_TYPE_PERCENTAGE) ||
                                        ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                        $cfggradetype == GRADE_DISPLAY_TYPE_PERCENTAGE)) {
                                    $realgrade = $this->get_fraction($record->grade) . "%";
                                    $mingrade = "0";
                                    $availablegrade = $this->get_fraction($record->highestgrade) / $this->get_fraction($record->highestgrade) * 100 . "%";
                                } else if (($gradedisplay == GRADE_DISPLAY_TYPE_PERCENTAGE_LETTER) ||
                                        ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                        $cfggradetype == GRADE_DISPLAY_TYPE_PERCENTAGE_LETTER)) {
                                    $realgrade = $this->get_fraction($record->grade) . "% (" .
                                            $this->get_grade_letter($record->courseid, $record->grade / $record->highestgrade * 100) . ")";
                                    $mingrade = "0 (" . $this->get_min_grade_letter($record->courseid, $record->grade) . ")";
                                    $graderange = $this->get_all_grade_letters($record->courseid);
                                    $availablegrade = $this->get_fraction($record->highestgrade) / $this->get_fraction($record->highestgrade) * 100 . "% (" .
                                            $this->get_available_grade_letter($record->courseid, $record->grade) . ")";
                                } else if (($gradedisplay == GRADE_DISPLAY_TYPE_PERCENTAGE_REAL) ||
                                        ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                        $cfggradetype == GRADE_DISPLAY_TYPE_PERCENTAGE_REAL)) {
                                    $realgrade = $this->get_fraction($record->grade) . "% (" .
                                            $this->get_fraction($record->grade) . ")";
                                    $mingrade = "0 (0)";
                                    $availablegrade = $this->get_fraction($record->highestgrade) / $this->get_fraction($record->highestgrade) * 100 . "% (" .
                                            $this->get_fraction($record->highestgrade) . ")";
                                } else if (($gradedisplay == GRADE_DISPLAY_TYPE_REAL_LETTER) ||
                                        ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                        $cfggradetype == GRADE_DISPLAY_TYPE_REAL_LETTER)) {
                                    $realgrade = $this->get_fraction($record->grade) . " (" .
                                            $this->get_grade_letter($record->courseid, $record->grade / $record->highestgrade * 100) . ")";
                                    $mingrade = "0 (" . $this->get_min_grade_letter($record->courseid, $record->grade) . ")";
                                    $graderange = $this->get_all_grade_letters($record->courseid);
                                    $availablegrade = $this->get_fraction($record->highestgrade) . " (" .
                                            $this->get_available_grade_letter($record->courseid, $record->grade) . ")";
                                } else if (($gradedisplay == GRADE_DISPLAY_TYPE_REAL_PERCENTAGE) ||
                                        ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                        $cfggradetype == GRADE_DISPLAY_TYPE_REAL_PERCENTAGE)) {
                                    $realgrade = $this->get_fraction($record->grade) . " (" .
                                            $this->get_fraction($record->grade) . "%)";
                                    $mingrade = "0 (0)";
                                    $availablegrade = $this->get_fraction($record->highestgrade) . " (" .
                                            $this->get_fraction($record->highestgrade) / $this->get_fraction($record->highestgrade) * 100 . "%)";
                                } else {
                                    $realgrade = $this->get_fraction($record->grade);
                                    $mingrade = "0";
                                    $availablegrade = $this->get_fraction($record->highestgrade);
                                }
                        }
                    }

                    //horizontal bar only needed if grade type is value and not letter or scale
                    $grade_percent = $record->grade / $record->highestgrade * 100;

                    $horbar = "<td class=\"bar\"><div class=\"horizontal-bar\"><div style=\"width:" . $grade_percent .
                            "%\" class=\"grade-bar\">&nbsp;</div></div>"
                            . "<div class=\"available-grade\">"
                            . $this->get_fraction($record->grade) . "/" . $this->get_fraction($record->highestgrade) . "</div></td>";
                    if ($gradetype != GRADE_TYPE_VALUE) {
                        $horbar = "<td>-</td>";
                    }
                    if ($gradedisplay == GRADE_DISPLAY_TYPE_LETTER || ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT && $cfggradetype == GRADE_DISPLAY_TYPE_LETTER)) {
                        $horbar = "<td>-</td>";
                    }
                    if ($realgrade == "-" || $realgrade < 1) {
                        $horbar = "<td>-</td>";
                    }
                    if ($quizgrade == 'noreview') {
                        $horbar = "<td>-</td>";
                    }

                    $peercomment = '';
                    $grade_tbl2 = $realgrade;
                    if ($gradetype != GRADE_TYPE_SCALE) {
                        if (($gradedisplay == GRADE_DISPLAY_TYPE_REAL) || ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT && $cfggradetype == GRADE_DISPLAY_TYPE_REAL)) {
                            $grade_tbl2 = $realgrade . '/' . $availablegrade;
                        }
                    }

                    //If no grade is given then don't display 0
                    if ($record->grade == NULL) {
                        $grade_tbl2 = $realgrade = "-";
                    }

                    //implement weighting
                    $weighting = '-';
                    if ($record->weighting != 0 && $gradetype == 1) {
                        $weighting = number_format(($record->weighting * 100), 0, '.', ',') . '%'; //only 1 decimal place
                    }

                    //Data for the viewed feedback
                    $viewed = "<span style=\"color:red;\"> &#10006;</span>";
                    $viewexport = 'no';
                    $check = $this->check_viewed_gradereport($record->contextid, $record->assignmentid, $userid, $record->courseid, $record->assessmentname);
                    if ($check != 'no') {
                        $viewed = "<span style=\"color:green;\">&#10004;</span> " . $check;
                        $viewexport = $check;
                    }
                    if ($record->gi_itemtype == 'manual') {
                        $checkmanual = $this->check_viewed_manualitem($userid, $record->courseid, $record->gradeitemid);
                        if ($checkmanual != 'no') {
                            $viewed = "<span style=\"color:green;\">&#10004;</span> " . $checkmanual;
                            $viewexport = $checkmanual;
                        }
                    }

                    //Show submission only if there is no feedback
                    /* if ($feedbacktextlink) {
                      $sub_or_feedback = $feedbacktextlink;
                      }
                      if (strlen($feedbacktextlink) < 7) {
                      $sub_or_feedback = $submission;
                      } */

                    //Only show if Module tutor or Program admin has their access to the specific course
                    //Personal tutor would only get this far if they had that access so no need to set condition for them
                    $coursecontext = context_course::instance($record->courseid);
                    $usercontext = context_user::instance($record->userid);

                    if ($userid == $USER->id || $USER->id == 2 ||
                            has_capability('moodle/user:viewdetails', $usercontext) ||
                            has_capability('report/myfeedback:progadmin', $coursecontext, $USER->id, $doanything = false) ||
                            has_capability('moodle/course:changeshortname', $coursecontext, $USER->id, $doanything = false)) {
                        $table .= "<tr>";
                        $table .= "<td>" . ($record->coursename && $record->courseid ? "<a href=\"" . $CFG->wwwroot .
                                        "/course/view.php?id=" . $record->courseid . "\" title=\"" . $record->coursename .
                                        "\" rel=\"tooltip\">" . $shortname . "</a>" : "&nbsp;") . "</td>";
                        $table .= "<td>" . $assessmenticon . $assignmentname . "</td>";
                        $table .= "<td>" . $assessmenttype . "</td>";
                        $table .= "<td data-sort=$due_datesort>" . $duedate . "</td>";
                        $table .= "<td data-sort=$sub_datesort>" . $submissiondate . "</td>";
                        $table .= "<td>" . $feedbacktextlink . "</td>"; // Including links to marking guide or rubric.
                        //$table .= "<td>" . $weighting . "</td>";
                        $table .= "<td>" . $realgrade . "</td>"; //($record->grade ? number_format($record->grade, 0) : "&nbsp;") . "</td>";
                        //($record->grade ? number_format($record->grade, 0) : "&nbsp;") . " / " .
                        //$record->highestgrade ? number_format($record->highestgrade, 0) : "&nbsp;") . "</td>";
                        $table .= "<td title=\"$graderange\" rel=\"tooltip\">" . $mingrade . " - " . $availablegrade . "</td>";
                        $table .= $horbar;
                        $table .= "</tr>";

                        // The full excel downloadable table
                        $exceltable[$x]['Module'] = $record->shortname;
                        $exceltable[$x]['Assessment'] = $record->assessmentname;
                        $exceltable[$x]['Type'] = $assessmenttype;
                        $exceltable[$x]['Due date'] = $duedate;
                        $exceltable[$x]['Submission date'] = $submissiondate;
                        $exceltable[$x]['Grade'] = $realgrade;
                        $exceltable[$x]['Grade range'] = $mingrade . " - " . $availablegrade;
                        $exceltable[$x]['Full feedback'] = $feedbacktext;
                        $exceltable[$x]['Viewed'] = $viewexport;
                        ++$x;

                        //The feedback comments table
                        if ($feedbacktext != '-' || $feedbackfileicon != '&nbsp;' || $feedbacktextlink != '&nbsp;') {
                            $commentstable .= "<tr>";
                            $commentstable .= "<td data-filter=$record->shortname>" . ($record->coursename && $record->courseid ? "<a href=\"" . $CFG->wwwroot .
                                            "/course/view.php?id=" . $record->courseid . "\" title=\"" . $record->coursename .
                                            "\" rel=\"tooltip\">" . $shortname . "</a>" : "&nbsp;") . "</td>";
                            $commentstable .= "<td style=\"text-align:left;\">" . $assessmenticon . $assignmentname . "</td>";
                            $commentstable .= "<td style=\"text-align:left;\">" . $assessmenttype . "</td>";
                            $commentstable .= "<td data-sort=$sub_datesort>" . $submissiondate . "</td>";
                            $commentstable .= "<td>" . $grade_tbl2 . "</td>";
                            $commentstable .= "<td width=\"400\" style=\"border:3px solid #ccc; text-align:left;\">" . $feedbacktext . "</td>";
                            $commentstable .= "<td>" . $feedbacktextlink . $feedbackfileicon . "</td>";
                            $commentstable .= "<td>" . $viewed . "</td>";
                            $commentstable.= "</tr>";
                        }
                    }
                }
            }
            $rs->close(); // Close the recordset!
        }
        //By adding individual tds in the tablefoot, it solves the issue with 
        //multi select being responsive
        $table .= "</tbody>
                <tfoot>
                        <tr class=\"tablefooter\">
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tfoot>
                    </table>";

        $commentstable.="</tbody></table>";

        // Buttons for filter reset, download and print
        $reset_print_excel = "<div style=\"float:right;\" class=\"buttonswrapper\"><input id=\"tableDestroy\" type=\"button\" value=\"" .
                get_string('reset_table', 'report_myfeedback') . "\">
                <input id=\"exportexcel\" type=\"button\" value=\"" . get_string('export_to_excel', 'report_myfeedback') . "\">
                <input id=\"reportPrint\" type=\"button\" value=\"" . get_string('print_report', 'report_myfeedback') . "\"></div>";
        $reset_print_excel1 = "<div style=\"float:right;\" class=\"buttonswrapper\"><input id=\"ftableDestroy\" type=\"button\" value=\"" .
                get_string('reset_table', 'report_myfeedback') . "\">
                <input id=\"exportexcel\" type=\"button\" value=\"" . get_string('export_to_excel', 'report_myfeedback') . "\">
                <input id=\"reportPrint\" type=\"button\" value=\"" . get_string('print_report', 'report_myfeedback') . "\"></div>";

        $_SESSION['exp_sess'] = $exceltable;
        $_SESSION['myfeedback_userid'] = $userid;
        $this->content->text = $title . $reset_print_excel . $table;
        if ($tab == 'feedback') {
            $feedbackcomments = "<p>" . get_string('tabs_feedback_text', 'report_myfeedback') . "</p>"; /* .
              "<div><div class=\"wordcloud\">&nbsp;</div>" . "<div class=\"cloudtext\">" .
              get_string('wordcloud_text', 'report_myfeedback') . "</div></div>"; */
            $this->content->text = $feedbackcomments . $reset_print_excel1 . $commentstable;
        }
        if ($tab == 'tutor') {
				$personaltutor = "<h2>" . get_string('tabs_tutor', 'report_myfeedback') . "</h2><br />" .
						"<a href=\"mailto:tutor@example.com\" class=\"btn warning\">" . get_string('email_tutor', 'report_myfeedback') . "</a>" .
						"<a href=\"#\" target=\"_blank\" class=\"btn warning\">" .
						get_string('meet_tutor', 'report_myfeedback') . "</a>" .
						"<h2>" . get_string('tutor_messages', 'report_myfeedback') . "</h2>";
				$this->content->text = $personaltutor;
        }
        return $this->content;
    }

}
