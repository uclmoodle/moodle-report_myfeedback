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
    $navigation->add(get_string('pluginname', 'report_myfeedback'), $url, null, null, null, new pix_icon('i/report', ''));
}

function report_myfeedback_extend_navigation_user($navigation, $user, $course) {//backward compatibility to v2.8 and earlier versions
    $context = context_user::instance($user->id, MUST_EXIST);
    // if (has_capability('report/myfeedback:view', $context)) {
    $url = new moodle_url('/report/myfeedback/index.php', array('userid' => $user->id));
    $navigation->add(get_string('pluginname', 'report_myfeedback'), $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
    //}
}

function report_myfeedback_extend_navigation_user_settings($navigation, $user, $context, $course, $coursecontext) {
    //if (has_capability('report/myfeedback:view', $context)) {
    $url = new moodle_url('/report/myfeedback/index.php', array('userid' => $user->id));
    $navigation->add(get_string('pluginname', 'report_myfeedback'), $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
    // }
}

function report_myfeedback_extend_navigation_course($navigation, $course, $context) {
    global $USER;
    //if (has_capability('report/myfeedback:view', $context)) {
    $url = new moodle_url('/report/myfeedback/index.php', array('userid' => $USER->id));
    $navigation->add(get_string('pluginname', 'report_myfeedback'), $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
    // }
}

function report_myfeedback_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {//for comaptibility with v2.9 and later   
    $url = new moodle_url('/report/myfeedback/index.php', array('userid' => $user->id));
    if (!empty($course)) {
        $url->param('course', $course->id);
    }
    $string = get_string('pluginname', 'report_myfeedback');
    $node = new core_user\output\myprofile\node('reports', 'myfeedbackreport', $string, null, $url);
    $tree->add_node($node);
    return true;
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
    function setup_ExternalDB($ext_db = null) {
        global $CFG, $DB, $remotedb;

        // Use a custom $remotedb (and not current system's $DB) if set - code sourced from configurable
        // Reports plugin.
        $remotedbhost = get_config('report_myfeedback', 'dbhost');
        $remotedbname = get_config('report_myfeedback', 'dbname');
        $remotedbuser = get_config('report_myfeedback', 'dbuser');
        $remotedbpass = get_config('report_myfeedback', 'dbpass');

        if ($ext_db && $ext_db != 'current') {
            $remotedbhost = get_config('report_myfeedback', 'dbhostarchive');
            $remotedbname = get_config('report_myfeedback', 'namingconvention') . $ext_db;
            $remotedbuser = get_config('report_myfeedback', 'dbuserarchive');
            $remotedbpass = get_config('report_myfeedback', 'dbpassarchive');
        }

        if (empty($remotedbhost) OR empty($remotedbname) OR empty($remotedbuser)) {
            $remotedb = $DB;
            setup_DB();
        } else {
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
                if ($CFG->debug != 0) {
                    throw $e;
                } else {
                    echo get_string('archivedbnotexist', 'report_myfeedback');
                }
            }
            $DB = $remotedb;
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
     * @param str $iteminstance The assign id
     * @param str $userid The user id
     * @param str $gradeid The gradeid
     * @return bool true if there's a pdf feedback annotated file 
     * or any feedback filed for the submission, otherwise false
     */
    public function has_pdf_feedback_file($iteminstance, $userid, $gradeid) {
        global $remotedb;
        // Is there any online pdf annotation feedback or any feedback file?
        if ($remotedb->get_record('assignfeedback_editpdf_annot', array('gradeid' => $gradeid), $fields = 'id', $strictness = IGNORE_MULTIPLE)) {
            return true;
        }
        if ($remotedb->get_record('assignfeedback_editpdf_cmnt', array('gradeid' => $gradeid), $fields = 'id', $strictness = IGNORE_MULTIPLE)) {
            return true;
        }

        $sql = "SELECT af.numfiles 
                FROM {assign_grades} ag
                JOIN {assignfeedback_file} af on ag.id=af.grade
                AND ag.id=? AND ag.userid=? AND af.assignment=?";
        $params = array($gradeid, $userid, $iteminstance);
        $feedbackfile = $remotedb->get_record_sql($sql, $params);
        if ($feedbackfile) {
            if ($feedbackfile->numfiles != 0) {
                return true;
            }
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
    public function has_workshop_feedback_file($userid, $subid) {
        global $remotedb;
        // Is there any feedback file?
        $sql = "SELECT DISTINCT max(wa.id) as id, wa.feedbackauthorattachment
                FROM {workshop_assessments} wa 
                JOIN {workshop_submissions} ws ON wa.submissionid=ws.id 
                AND ws.authorid=? AND ws.id=? and ws.example = 0";
        $params = array($userid, $subid);
        $feedbackfile = $remotedb->get_record_sql($sql, $params);
        if ($feedbackfile) {
            if ($feedbackfile->feedbackauthorattachment != 0) {
                return true;
            }
        }
        return false;
    }

    public function has_workshop_feedback($userid, $subid, $assignid, $cid, $itemnumber) {
        global $remotedb;
        $feedback = '';

        //Get the other feedback that comes when graded so will have a grade id otherwise it is not unique
        $peer = "SELECT DISTINCT wg.id, wg.peercomment, wa.reviewerid, wa.feedbackreviewer, w.conclusion
        FROM {workshop} w
        JOIN {workshop_submissions} ws ON ws.workshopid=w.id AND w.course=? AND w.useexamples=0      
        JOIN {workshop_assessments} wa ON wa.submissionid=ws.id AND ws.authorid=?
        AND ws.workshopid=? AND ws.example=0 AND wa.submissionid=?
        LEFT JOIN {workshop_grades} wg ON wg.assessmentid=wa.id AND wa.submissionid=?";
        $arr = array($cid, $userid, $assignid, $subid, $subid);
        //$peer1 = '<b>' . get_string('peerfeedback', 'report_myfeedback') . '</b>';
        //$self = $pfeed = false;
        if ($assess = $remotedb->get_records_sql($peer, $arr)) {
            if ($itemnumber == 1) {
                foreach ($assess as $a) {
                    if ($a->feedbackreviewer && strlen($a->feedbackreviewer) > 0) {
                        $feedback = (strip_tags($a->feedbackreviewer) ? "<br/><b>" . get_string('tutorfeedback', 'report_myfeedback') . "</b><br/>" . $a->feedbackreviewer : '');
                    }
                }
                return $feedback;
            }
            /*foreach ($assess as $a1) {
                if ($a1->peercomment && $a1->reviewerid == $userid) {
                    $peer1 = '<b>' . get_string('selfassessment', 'report_myfeedback') . '</b>';
                    $self = true;
                }
            }
            if ($self) {
                $feedback .= $peer1;
            }
            foreach ($assess as $a12) {
                if ($a12->peercomment && $a12->reviewerid == $userid) {
                    $feedback .= (strip_tags($a12->peercomment) ? "<br/>" . $a12->peercomment : '');
                }
            }
            foreach ($assess as $a2) {
                if ($a2->peercomment && $a2->reviewerid != $userid) {
                    $peer1 = '<b>' . get_string('peerfeedback', 'report_myfeedback') . '</b>';
                    $pfeed = true;
                }
            }
            if ($pfeed) {
                $feedback .= $peer1;
            }
            foreach ($assess as $a22) {
                if ($a22->peercomment && $a22->reviewerid != $userid) {
                    $feedback .= (strip_tags($a22->peercomment) ? "<br/>" . $a22->peercomment : '');
                }
            }*/
        }

        if ($itemnumber != 1) {
            //get the feedback from author as this does not necessarily mean they are graded
            $auth = "SELECT DISTINCT wa.id, wa.feedbackauthor, wa.reviewerid
            FROM {workshop} w
            JOIN {workshop_submissions} ws ON ws.workshopid=w.id AND w.course=? AND w.useexamples=0      
            JOIN {workshop_assessments} wa ON wa.submissionid=ws.id AND ws.authorid=?
            AND ws.workshopid=? AND ws.example=0 AND wa.submissionid=?";
            $par = array($cid, $userid, $assignid, $subid);
            $self = $pfeed = false;
            if ($asse = $remotedb->get_records_sql($auth, $par)) {
                foreach ($asse as $cub) {
                    if ($cub->feedbackauthor && $cub->reviewerid != $userid) {
                        $pfeed = true;
                    }
                }
                if ($pfeed) {
                    $feedback .= '<b>' . get_string('peerfeedback', 'report_myfeedback') . '</b>';
                }
                foreach ($asse as $as) {
                    if ($as->feedbackauthor && $as->reviewerid != $userid) {
                        $feedback .= (strip_tags($as->feedbackauthor) ? $as->feedbackauthor : '');
                    }
                }
                foreach ($asse as $cub1) {
                    if ($cub1->feedbackauthor && $cub1->reviewerid == $userid) {
                        $self = true;
                    }
                }
                if ($self) {
                    $feedback .= '<b>' . get_string('selfassessment', 'report_myfeedback') . '</b>';
                }
                foreach ($asse as $as1) {
                    if ($as1->feedbackauthor && $as1->reviewerid == $userid) {
                        $feedback .= (strip_tags($as1->feedbackauthor) ? $as1->feedbackauthor : '');
                    }
                }
            }
        }

        //get the rubrics
        /* $sql = "SELECT r.id AS rid, r.description, l.definition
          FROM {workshopform_rubric} r
          LEFT JOIN {workshopform_rubric_levels} l ON (l.dimensionid = r.id) AND r.workshopid=?
          JOIN {workshop_grades} wg ON wg.dimensionid=l.dimensionid AND l.grade=wg.grade and wg.strategy='rubric'
          JOIN {workshop_assessments} wa ON wg.assessmentid=wa.id AND wa.submissionid=?
          JOIN {workshop_submissions} ws ON wa.submissionid=ws.id AND ws.id=?
          AND ws.workshopid=? AND ws.example=0
          JOIN {workshop} w ON ws.workshopid=w.id AND w.course=? AND w.useexamples=0";
          $params = array($assignid, $subid, $subid, $assignid, $cid);
          $r = 0;
          if ($feedbackcheck = $remotedb->get_records_sql($sql, $params)) {
          foreach ($feedbackcheck as $rub) {
          if ($rub->description || $rub->definition) {
          $r = 1;
          }
          }
          if ($r) {
          $feedback .= "<span style=\"font-weight:bold;\"><img src=\"" .
          $CFG->wwwroot . "/report/myfeedback/pix/rubric.png\">" . get_string('rubrictext', 'report_myfeedback') . "</span>";
          }
          foreach ($feedbackcheck as $rec) {
          $feedback .= "<br/><strong>" . $rec->description . "</strong>: " . $rec->definition;
          }
          } */

        //get the conclusion
        /* $conc = "SELECT DISTINCT wg.id, w.conclusion
          FROM {workshop} w
          JOIN {workshop_submissions} ws ON ws.workshopid=w.id AND w.course=?
          AND w.useexamples=0 AND w.phase = 50 AND w.conclusion !=''
          JOIN {workshop_assessments} wa ON wa.submissionid=ws.id AND ws.authorid=?
          AND ws.workshopid=? AND ws.example=0 AND wa.submissionid=?
          JOIN {workshop_grades} wg ON wg.assessmentid=wa.id AND wa.submissionid=?";
          $arr1 = array($cid, $userid, $assignid, $subid, $subid);
          if ($conclusion = $remotedb->get_records_sql($conc, $arr1)) {
          foreach ($conclusion as $c) {
          $feedback .= (strip_tags($c->conclusion) ? "<br/>Conclusion: " . $c->conclusion : '');
          }
          } */
        return $feedback;
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
        $extension = $remotedb->get_record_sql($sql, $params);
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
        $override = $remotedb->get_record_sql($sql, $params);
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
        $override = $remotedb->get_record_sql($sql, $params);
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
    public function get_quiz_submissiondate($assignid, $userid, $grade, $availablegrade, $sumgrades) {
        global $remotedb;
        $a = $grade / $availablegrade * $sumgrades;
        $sql = "SELECT id, timefinish, sumgrades
                FROM {quiz_attempts}
                WHERE quiz=? AND userid=?";
        $params = array($assignid, $userid);
        $attempts = $remotedb->get_records_sql($sql, $params);
        if ($attempts) {
            foreach ($attempts as $attempt) {
                if ($a == $attempt->sumgrades) {
                    return $attempt->timefinish;
                }
            }
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
    public function get_quiz_attempts_link($quizid, $userid, $quizurlid, $archivedomain_year, $archive, $newwindowicon) {
        global $CFG, $remotedb;
        $sqlcount = "SELECT count(attempt) as attempts, max(id) as id
                        FROM {quiz_attempts} qa
                        WHERE quiz=? and userid=?";
        $params = array($quizid, $userid);
        $attemptcount = $remotedb->get_records_sql($sqlcount, $params);
        $out = array();
        if ($attemptcount) {
            foreach ($attemptcount as $attempt) {
                $a = $attempt->attempts;
                if ($a > 0) {
                    $attr = array();
                    $newicon = '';
                    $url = $CFG->wwwroot . "/mod/quiz/review.php?attempt=" . $attempt->id;
                    if ($archive) {// If an archive year then change the domain
                        $url = $archivedomain_year . "/mod/quiz/review.php?attempt=" . $attempt->id;
                        $attr = array("target" => "_blank");
                        $newicon = $newwindowicon;
                    }
                    //$attemptstext = ($attemptcount > 1) ? get_string('attempts', 'report_myfeedback') : get_string(
                    //               'attempt', 'report_myfeedback');
                    $attemptstext = ($a > 1) ? get_string('reviewlastof', 'report_myfeedback',$a) . $newicon : get_string('reviewaattempt', 'report_myfeedback',$a) . $newicon;
                    $out[] = html_writer::link($url, $attemptstext, $attr);
                }
            }
        }
        $br = html_writer::empty_tag('br');
        return implode($br, $out);
    }

    /**
     * Get group assignment submission date - since it won't come through for a user in a group
     * unless they were the one's to upload the file
     *
     * @param int $userid The id of the user
     * @param int $assignid The id of the assignment
     * @return str submission dates, each on a new line if there are multiple
     */
    public function get_group_assign_submission_date($userid, $assignid) {
        global $remotedb;
        // Group submissions.
        $sql = "SELECT max(su.timemodified) as subdate
                FROM {assign_submission} su
                JOIN {groups_members} gm ON su.groupid = gm.groupid AND gm.userid = ?
                AND su.assignment=?";
        $params = array($userid, $assignid);
        $files = $remotedb->get_record_sql($sql, $params);
        if ($files) {
            return $files->subdate;
        }
        return get_string('no_submission', 'report_myfeedback');
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
                FROM {workshop_grades} wg
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
        $viewreport = $remotedb->get_record_sql($sql, $params);
        $userreport = $remotedb->get_record_sql($sqlone, $paramsone);
        $gradeadded = $remotedb->get_record_sql($sqltwo, $paramstwo);
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
        $userreport = $remotedb->get_record_sql($sqlone, $paramsone);
        $gradeadded = $remotedb->get_record_sql($sqltwo, $paramstwo);
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
        $sql = "SELECT DISTINCT rc.id, rc.description,rl.definition 
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
        $rubrics = $remotedb->get_recordset_sql($sql, $params);
        $out = '';
        if ($rubrics) {
            foreach ($rubrics as $rubric) {
                if ($rubric->description || $rubric->definition) {
                    $out .= "<strong>" . $rubric->description . ": </strong>" . $rubric->definition . "<br/>";
                }
            }
        }
        return $out;
    }

    /**
     * Get the Marking guide feedback
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
        $guides = $remotedb->get_recordset_sql($sql, $params);
        $out = '';
        if ($guides) {
            foreach ($guides as $guide) {
                if ($guide->shortname || $guide->remark) {
                    $out .= "<strong>" . $guide->shortname . ": </strong>" . $guide->remark . "<br/>";
                }
            }
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
    public function get_grade_scale($itemid, $userid, $courseid, $grade) {
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
            $num = ($grade ? (int) $scales->finalgrade - 1 : 0);
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
     * @param int $cid The Course id
     * @param float $decimals The number of decimals if set in grade items 
     * @return float The grade with the number of decimal places as set
     */
    public function get_fraction($grade, $cid, $decimals) {
        global $CFG;
        //TODO: use my own function other than grade_get_setting because when we start using multiple DBs 
        //then $DB would be the incorrect database or we need to set $DB to the database being used
        if (fmod($grade, 1)) {
            if (is_null($decimals)) {
                $decima = grade_get_setting($cid, 'decimalpoints', $CFG->grade_decimalpoints);
                if ($decima) {
                    return number_format($grade, $decima);
                }
            }
            return number_format($grade, $decimals);
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
    public function get_all_accessible_users($check) {
        global $remotedb, $USER, $CFG;
        $usertable = "<table style=\"float:left;\" class=\"userstable\" id=\"userstable\">
                    <thead>
                            <tr class=\"tableheader\">
                                <th class=\"tblname\">" . get_string('name'). 
                "</th><th class=\"tblrelationship\">" . get_string('relationship', 'report_myfeedback'). "</th>
                            </tr>
                        </thead><tbody>";

        $myusers = array();
        $admin_mods = array();
        $tutor_mods = array();
        if ($check) {
            //get all the users in the course you are deaprtmental admin for
            if ($mods = get_user_capability_course('report/myfeedback:progadmin', $USER->id, $doanything = false, $fields = 'visible')) {//enrol_get_users_courses($USER->id, $onlyactive = TRUE)) {
                //get_user_capability_course('report/myfeedback:progadmin', $userid = null, $doanything = false)) {
                foreach ($mods as $mod) {
                    if ($mod->visible && $mod->id) {
                        //$con = context_course::instance($mod->id);
                        //if (has_capability('report/myfeedback:progadmin', $con, $USER->id, $doanything = false)) {
                        $admin_mods[] = $mod->id;
                        //}
                        //if (has_capability('report/myfeedback:modtutor', $con, $USER->id, $doanything = false)) {
                        //  $tutor_mods[] = $mod->id;
                        //}
                    }
                }

                foreach ($admin_mods as $value) {
                    $context = context_course::instance($value);
                    $query = 'select u.id as id, firstname, lastname, email from mdl_role_assignments as a, '
                            . 'mdl_user as u where contextid=' . $context->id . ' and roleid=5 and a.userid=u.id;';
                    $users = $remotedb->get_recordset_sql($query);
                    foreach ($users as $a) {
                        $myusers[$a->id][0] = "<a href=\"" . $CFG->wwwroot . "/report/myfeedback/index.php?userid=" . $a->id . "\" title=\"" .
                                $a->email . "\" rel=\"tooltip\">" . $a->firstname . " " . $a->lastname . "</a>";
                        $myusers[$a->id][1] = ''; //get_string('othertutee', 'report_myfeedback');
                        $myusers[$a->id][2] = 1;
                        $myusers[$a->id][3] = '';
                    }
                }
            }
            // get all the users in the course you are a tutor for
            if ($tutor_mods = get_user_capability_course('report/myfeedback:modtutor', $USER->id, $doanything = false, $fields = 'visible')) {
                //get_user_capability_course('report/myfeedback:modtutor', $userid = null, $doanything = false)) {
                foreach ($tutor_mods as $value) {
                    if ($value->visible) {
                        $context = context_course::instance($value->id);
                        $query = 'select u.id as id, firstname, lastname, email from mdl_role_assignments as a, '
                                . 'mdl_user as u where contextid=' . $context->id . ' and roleid=5 and a.userid=u.id';
                        $users = $remotedb->get_recordset_sql($query);
                        foreach ($users as $r) {
                            $myusers[$r->id][0] = "<a href=\"" . $CFG->wwwroot . "/report/myfeedback/index.php?userid=" . $r->id . "\" title=\"" .
                                    $r->email . "\" rel=\"tooltip\">" . $r->firstname . " " . $r->lastname . "</a>";
                            $myusers[$r->id][1] = ''; //get_string('othertutee', 'report_myfeedback');
                            $myusers[$r->id][2] = 2;
                            if ($stmods = get_user_capability_course('moodle/grade:view', $r->id, $doanything = false, $fields = 'shortname,visible')) {//enrol_get_users_courses($r->id, $onlyactive = TRUE)) {
                                $num = 0;
                                foreach ($stmods as $stval) {
                                    if ($stval->visible) {
                                        $coursecontext = context_course::instance($stval->id);
                                        if (has_capability('report/myfeedback:modtutor', $coursecontext, $USER->id, $doanything = false)) {
                                            $myusers[$r->id][1] .= $stval->shortname . ", ";
                                        }
                                    }
                                }
                                $myusers[$r->id][1] = rtrim($myusers[$r->id][1], ", ");
                            }
                            $myusers[$r->id][3] = $myusers[$r->id][1];
                        }
                    }
                }
            }
        }
        // get all the mentees, i.e. users you have a direct assignment to
        if ($usercontexts = $remotedb->get_records_sql("SELECT c.instanceid, u.id as id, firstname, lastname, email
                                                    FROM {role_assignments} ra, {context} c, {user} u
                                                   WHERE ra.userid = ?
                                                         AND ra.contextid = c.id
                                                         AND c.instanceid = u.id
                                                         AND c.contextlevel = " . CONTEXT_USER, array($USER->id))) {
            foreach ($usercontexts as $u) {
                $myusers[$u->id][0] = "<a href=\"" . $CFG->wwwroot . "/report/myfeedback/index.php?userid=" . $u->id . "\" title=\"" .
                        $u->email . "\" rel=\"tooltip\">" . $u->firstname . " " . $u->lastname . "</a>";
                $myusers[$u->id][1] = get_string('personaltutee', 'report_myfeedback');
                $myusers[$u->id][2] = 3;
                $myusers[$u->id][3] = '';
            }
        }

        foreach ($myusers as $result) {
            $usertable.= "<tr>";
            $usertable.="<td>" . $result[0] . "</td>";
            $usertable.="<td class=\"ellip\" data-sort=$result[2] title=\"$result[3]\" rel=\"tooltip\">" . $result[1] . "</td>";
            $usertable.="</tr>";
        }
        $usertable.="</tbody><tfoot><tr><td></td><td></td></tr></tfoot></table>";

        return $usertable;
    }

    public function get_tutees_for_prog_ptutors($uid) {
        global $remotedb, $CFG;
        $myusers = array();
        // get all the mentees, i.e. users you have a direct assignment to
        if ($usercontexts = $remotedb->get_records_sql("SELECT u.id as id, firstname, lastname, email
                                                    FROM {role_assignments} ra, {context} c, {user} u
                                                   WHERE ra.userid = ?
                                                         AND ra.contextid = c.id
                                                         AND c.instanceid = u.id
                                                         AND c.contextlevel = " . CONTEXT_USER, array($uid))) {
            foreach ($usercontexts as $u) {
                $myusers[$u->id]['prog'] = '';
                $myusers[$u->id]['year'] = '';
                profile_load_data($u);
                $myusers[$u->id]['name'] = $u->firstname . " " . $u->lastname;
                $myusers[$u->id]['click'] = $u->email;
                $myusers[$u->id]['here'] = "<a href=\"" . $CFG->wwwroot . "/report/myfeedback/index.php?userid=" . $u->id . "\">Click here</a>";
                if (isset($u->profile_field_courseyear)) {
                    $myusers[$u->id]['year'] = $u->profile_field_courseyear;
                }
                if (isset($u->profile_field_programmename)) {
                    $myusers[$u->id]['prog'] = $u->profile_field_programmename;
                }
            }
        }
        $inner_table = '<table class="innertable" width="100%" style="text-align:center; display:none"><thead><tr><th>'.get_string('tutee', 'report_myfeedback').'</th><th>'.get_string('programme', 'report_myfeedback').'</th><th>'.get_string('yearlevel', 'report_myfeedback').'</th><th>My feedback</th></tr></thead><tbody>';
        foreach ($myusers as $res) {
            $inner_table .= "<tr>";
            $inner_table .= "<td>" . $res['name'] . "</td><td>" . $res['prog'] . "</td><td>" . $res['year'] . "</td><td>" . $res['here'] . "</td></tr>";
        }
        $inner_table .= "</tbody></table>";
        return $inner_table;
    }

    public function get_tutees_for_prog_tutor_groups($uid, $cid, $tutgroup) {
        global $remotedb, $CFG;
        $myusers = array();

        // get all the users in each tutor group and add their stats
        if ($tutorgroups = $remotedb->get_records_sql("SELECT distinct u.id as userid, g.name, g.id as id
                                                    FROM {groups} g 
                                                    JOIN {groups_members} gm ON g.id=gm.groupid AND g.courseid = ?
                                                    JOIN {user} u ON u.id=gm.userid AND userid != ? 
                                                    AND groupid IN ( SELECT groupid FROM {groups_members}, {groups}
                                                    WHERE userid = ? AND courseid = ?)", array($cid, $uid, $uid, $cid))) {
            foreach ($tutorgroups as $tgroup) {
                $myusers[$tgroup->id]['due'] = 0;
                $myusers[$tgroup->id]['non'] = 0;
                $myusers[$tgroup->id]['late'] = 0;
                $myusers[$tgroup->id]['graded'] = 0;
                $myusers[$tgroup->id]['low'] = 0;
                //$myusers[$tgroup->id]['feed'] = 0;
                $myusers[$tgroup->id]['count'] = 0;
            }

            foreach ($tutorgroups as $u) {
                foreach ($tutgroup as $gp => $val) {
                    if ($gp == $u->userid) {
                        $myusers[$u->id]['name'] = $u->name;
                        $sum = array();
                        foreach ($tutgroup[$u->userid]['assess'] as $userscores) {
                            if ($userscores['score']) {
                                $sum[] = $userscores['score'];
                            }
                        }
                        $myusers[$u->id]['assess'][$u->userid]['score'] = array_sum($sum) / count($sum);
                        $myusers[$u->id]['due'] += $tutgroup[$u->userid]['due'];
                        $myusers[$u->id]['non'] += $tutgroup[$u->userid]['non'];
                        $myusers[$u->id]['late'] += $tutgroup[$u->userid]['late'];
                        $myusers[$u->id]['graded'] += $tutgroup[$u->userid]['graded'];
                        $myusers[$u->id]['low'] += $tutgroup[$u->userid]['low'];
                        // $myusers[$u->id]['feed'] += $tutgroup[$u->userid]['feed'];
                        $myusers[$u->id]['count'] += count($sum);
                    }
                }
            }
            //$myusers[$u->id]['here'] = "<a href=\"" . $CFG->wwwroot . "/report/myfeedback/index.php?userid=" . $u->id . "\">Click here</a>";
        }

        $stu = $this->get_user_analytics($myusers, $tgid = 't' . $cid, $display = 'stuRec', $style = '', $breakdodwn = null, $fromassess = false, $fromtut = true);
        $inner_table = "<table class=\"innertable\" width=\"100%\" style=\"text-align:center; display:none\">
            <thead><tr><th>" . get_string('groupname', 'report_myfeedback'). "</th><th>" . get_string('tutortblheader_assessment', 'report_myfeedback') . "</th><th>" .
        get_string('tutortblheader_nonsubmissions', 'report_myfeedback') . "</th><th>" . get_string('tutortblheader_latesubmissions', 'report_myfeedback') . "</th><th>" .
        get_string('tutortblheader_graded', 'report_myfeedback') . "</th><th>" . get_string('tutortblheader_lowgrades', 'report_myfeedback') . "</th></tr></thead><tbody>";

        $inner_table .= "<tr>";
        $inner_table .= "<td>" . $stu->uname . "</td>";
        //$inner_table .= "<td>" . $stu->u_vas . "</td>";
        $inner_table .= "<td>" . $stu->ud . "</td>";
        $inner_table .= "<td>" . $stu->un . "</td>";
        $inner_table .= "<td>" . $stu->ul . "</td>";
        $inner_table .= "<td>" . $stu->ug . "</td>";
        //$inner_table .= "<td>" . $stu->uf . "</td>";
        $inner_table .= "<td>" . $stu->ulo . "</td>";
        //$inner_table .= "<td>" . $res['name'] . "</td><td>" . $res['name'] . "</td><td>" . $res['name'] . "</td><td>" . $res['name'] . "</td></tr>";
        //}
        $inner_table .= "</tbody></table>";
        return $inner_table;
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

    public function get_all_assessments($cid) {
        global $remotedb;
        $now = time();
        $items = array('turnitintool', 'turnitintooltwo', 'workshop', 'quiz', 'assign');
        foreach ($items as $key => $item) {
            if (!$this->mod_is_available($item)) {
                unset($items[$key]);
            }
        }
        $items = '"' . implode('","', $items) . '"';
        $sql = "SELECT id, itemname, itemmodule
                FROM {grade_items} gi 
                WHERE (hidden != 1 AND hidden < ?) AND courseid = ?
                AND (itemmodule IN ($items) OR (itemtype = 'manual'))";
        $params = array($now, $cid);
        $assess = $remotedb->get_records_sql($sql, $params);
        // if ($assess) {
        return $assess;
        // }
    }

    public function get_module_graph($cid, $grades_totals, $enrolled = null) {
        $ctotal = 0;
        $mean = 0;
        $grade40 = 0;
        $grade50 = 0;
        $grade60 = 0;
        $grade70 = 0;
        $grade100 = 0;

        foreach ($grades_totals as $total_grade) {
            $p_total = $total_grade;
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
                ++$grade100;
            }
        }
        $meangrade = ($mean ? round($ctotal / $mean) : 0);
        $maxgrade = ($ctotal ? round(max($grades_totals)) : 0);
        $mingrade = ($ctotal ? round(min($grades_totals)) : 0);
        $pos_min = round($mingrade * 3.7) + 5;
        $pos_max = round($maxgrade * 3.7) + 5;
        $pos_avg = round($meangrade * 3.7) + 5;
        $pos_min_text = $pos_min - 7;
        $pos_avg_text = $pos_avg - 7;
        $pos_max_text = $pos_max - 7;
        $enrl = '';
        //($enrolled ? strtoupper(get_string('suborassessed', 'report_myfeedback')) . ': ' . $mean : '');
        if ($pos_avg_text - $pos_min_text < 18) {
            $pos_avg_text = $pos_min_text + 18;
        }
        if ($pos_max_text - $pos_avg_text < 18) {
            $pos_max_text = $pos_avg_text + 18;
        }
        if ($pos_min_text < 1) {
            $pos_min_text = 1;
        }
        if ($mingrade != 100 && $pos_min_text > 312) {
            $pos_min_text = 312;
        }
        if ($mingrade == 100 && $pos_min_text > 306) {
            $pos_min_text = 306;
        }
        if ($pos_avg_text < 18) {
            $pos_avg_text = 18;
        }
        if ($meangrade != 100 && $pos_avg_text > 338) {
            $pos_avg_text = 340;
        }
        if ($meangrade == 100 && $pos_avg_text > 338) {
            $pos_avg_text = 330;
        }
        if ($pos_max_text < 34) {
            $pos_max_text = 34;
        }
        if ($pos_max_text > 354) {
            $pos_max_text = 354;
        }
        $result = '<i style="color:#5A5A5A">' . get_string('nodata', 'report_myfeedback') . '</i>';
        if ($ctotal) {
            $a = new stdClass();
            $a->minimum = $mingrade;
            $a->mean = $meangrade;
            $a->maximum = $maxgrade;
            $result = '<div title="' . get_string('bargraphdesc', 'report_myfeedback', $a) . '" rel="tooltip" class="modCanvas"><canvas id="modCanvas' . $cid . 
                    '" width="380" height="70" style="border:0px solid #d3d3d3;">'.get_string("browsersupport", "report_myfeedback").'</canvas>
        <script>
        var c = document.getElementById("modCanvas' . $cid . '");
        var ctx = c.getContext("2d");
        var g40 = ' . $grade40 . ';
        var g50 = ' . $grade50 . ';
        var g60 = ' . $grade60 . ';
        var g70 = ' . $grade70 . ';
        var g100 = ' . $grade100 . ';
        var submis = "' . $enrl . '";
        var min = ' . $mingrade . ';
        var max = ' . $maxgrade . ';
        var avg = ' . $meangrade . ';
        var posmin = ' . $pos_min . ';
        var posmax = ' . $pos_max . ';
        var posavg = ' . $pos_avg . ';
        var posmintext = ' . $pos_min_text . ';
        var posmaxtext = ' . $pos_max_text . ';
        var posavgtext = ' . $pos_avg_text . ';
        //ctx.scale(0.8,1);
        ctx.beginPath();
        ctx.fillStyle = "#555";
        ctx.strokeStyle = "#555";
        ctx.font="14px Arial";
        ctx.strokeRect(5,20,370,25);
        ctx.moveTo(posmin, 15);
        ctx.lineTo(posmin, 20);
        ctx.fillText(min,posmintext,12);

        ctx.moveTo(posmax, 14);
        ctx.lineTo(posmax, 20);
        ctx.fillText(max,posmaxtext,12);        

        ctx.fillText(40,146,64);
        ctx.fillText(50,183,64);
        ctx.fillText(60,220,64);
        ctx.fillText(70,257,64);
        ctx.stroke();
        
        ctx.beginPath();
        ctx.fillStyle = "#555";
        ctx.font="11px Arial";
        ctx.fillText(submis,5,57);

        var grd = ctx.createLinearGradient(0, 0, 146, 0);
        grd.addColorStop(0, "#ce9d93");
        grd.addColorStop(1, "#d4b5af");
        ctx.fillStyle = grd;
        ctx.fillRect(6, 21, 146, 23);

        var grd2 = ctx.createLinearGradient(0, 0, 222, 0);
        grd2.addColorStop(0, "#b9ccb7");
        grd2.addColorStop(1, "#82c783");
        ctx.fillStyle = grd2;
        ctx.fillRect(152, 21, 222, 23);

        ctx.moveTo(153, 20);
        ctx.lineTo(153, 50);
        ctx.moveTo(190, 20);
        ctx.lineTo(190, 50);
        ctx.moveTo(227, 20);
        ctx.lineTo(227, 50);
        ctx.moveTo(264, 20);
        ctx.lineTo(264, 50);
        ctx.stroke();

        ctx.beginPath();
        ctx.font="14px Arial";
        ctx.fillStyle = "#c05756";
        ctx.strokeStyle = "#c05756";
        ctx.moveTo(posavg, 15);
        ctx.lineTo(posavg, 20);
        ctx.stroke();
        ctx.fillText(avg,posavgtext,12);

        ctx.fillStyle = "#000";
        ctx.fillText(g40,67,38);
        ctx.fillText(g50,160,38);
        ctx.fillText(g60,197,38);
        ctx.fillText(g70,234,38);
        ctx.fillText(g100,300,38);

        </script></div>';
        }
        return $result;
    }

    public function get_assessment_analytics($assess, $uidnum, $display = null, $style = null, $breakdown = null, $users = null, $pmod = false) {
        global $OUTPUT;

        $aname = $ad = $an = $al = $ag = $af = $alo = $a_vas = '';
        foreach ($assess as $aid => $a) {
            $scol1 = $scol2 = $scol3 = $scol4 = '';
            $a_due = $a_non = $a_graded = $a_late = $a_feed = $a_low = 0;
            $assessmenticon = $a_name = '';
            $assess_graph = '<h4><i style="color:#5A5A5A">' . get_string('nographtodisplay', 'report_myfeedback'). '</i></h4>';
            $uname = $ud = $un = $ul = $ug = $uf = $ulo = $u_vas = '';
            $assess_totals = array();
            if (array_sum($a['score']) != null) {
                foreach ($a['score'] as $e) {
                    if ($e != null) {
                        $assess_totals[] = $e;
                    }
                }
                $assess_graph = $this->get_module_graph($aid, $assess_totals);
            }
            $a_name = '<span title="' . $a['name'] . '" rel="tooltip"><b>' . $a['name'] . '</b></span>';
            $a_due = $a['due'];
            $a_non = $a['non'];
            $a_late = $a['late'];
            //$a_feed = $a['feed'];
            $a_graded = $a['graded'];
            $a_low = $a['low'];
            /* if ($a_due) {
              if (number_format(($a_non / $a_due * 100), 0) > 25) {
              $scol1 = 'amberlight';
              }
              if (number_format(($a_non / $a_due * 100), 0) > 50) {
              $scol1 = 'redlight';
              }
              if (number_format(($a_late / $a_due * 100), 0) > 25) {
              $scol2 = 'amberlight';
              }
              if (number_format(($a_late / $a_due * 100), 0) > 50) {
              $scol2 = 'redlight';
              }
              }
              if ($a_graded) {
              if (number_format(($a_low / $a_graded * 100), 0) > 25) {
              $scol3 = 'amberlight';
              }
              if (number_format(($a_low / $a_graded * 100), 0) > 50) {
              $scol3 = 'redlight';
              }
              } */
            $item = $a['icon'];
            if ($item) {
                $itemtype = $item;
                $assessmenticon = '<img src="' .
                        $OUTPUT->pix_url('icon', $item) . '" ' .
                        'class="icon" alt="' . $itemtype . '" title="' . $itemtype . '" rel="tooltip">';
            } else {
                $itemtype = 'manual';
                $assessmenticon = '<img src="' .
                        $OUTPUT->pix_url('i/manual_item') . '" ' .
                        'class="icon" alt="' . $itemtype . '" title="' . $itemtype . '" rel="tooltip">';
            }

            //Get the inner users per assessment
            if ($users) {
                $au = $this->get_user_analytics($users, $aid, $disp = ($pmod ? 'stuRecP a' . $aid : 'stuRec a' . $aid), $sty = 'display:none', $break = null, $fromassess = true);
                $uname = $au->uname;
                $ud = $au->ud;
                $un = $au->un;
                $ul = $au->ul;
                $ug = $au->ug;
                //$uf = $au->uf;
                $ulo = $au->ulo;
                $u_vas = $au->u_vas;
            }
            $height = '80px';
            $minortable = "<div style='height:81px' class='settableheight'><table data-aid='a" . $aid . "' style='" . $style . " ' class='tutor-inner " . $display . "' height='" . $height . "' align='center'><tr class='accord'><td>";
            $ad .= $minortable . $a_due . "</td></tr></table></div>" . $ud;
            $an .= $minortable . "<span class=$scol1>" . $a_non . "</span></td></tr></table></div>" . $un;
            $al .= $minortable . "<span class=$scol2>" . $a_late . "</span></td></tr></table></div>" . $ul;
            $ag .= $minortable . $a_graded . "</td></tr></table></div>" . $ug;
            //$af .= $minortable . $a_feed . "</td></tr></table></div>" . $uf;
            $alo .= $minortable . "<span class=$scol3>" . $a_low . "</span></td></tr></table></div>" . $ulo;
            $a_vas .= "<div style='height:81px' class='settableheight'><table data-aid='a" . $aid . "' style='" . $style . " ' class='tutor-inner " . $display . "' height='" . $height . "' align='center'><tr class='accord'><td class='overallgrade'>" . $assess_graph . "</td></tr></table></div>" . $u_vas;
            $aname .= "<div style='height:81px' class='settableheight'><table data-aid='a" . $aid . "' style='" . $style . " ' class='tutor-inner " . $display . "' height='" . $height . "' align='center'><tr class='accord'><td id='assess-name'>" . $assessmenticon . $a_name . $breakdown . "</td></tr></table></div>" . $uname;
        }
        $as = new stdClass();
        $as->ad = $ad;
        $as->an = $an;
        $as->al = $al;
        $as->ag = $ag;
        //$as->af = $af;
        $as->alo = $alo;
        $as->a_vas = $a_vas;
        $as->aname = $aname;
        return $as;
    }

    public function get_user_analytics($users, $cid, $display = null, $style = null, $breakdodwn = null, $fromassess = null, $tutgroup = false) {
        global $remotedb, $CFG;
        $userassess = array();
        $assessuser = array(); //if adding user for each assessment

        $u_name = $uname = $ud = $un = $ul = $ug = $uf = $ulo = $u_vas = $fname = $lname = '';
        $u_due = $u_non = $u_graded = $u_late = $u_feed = $u_low = 0;

        $user_graph = '<i style="color:#5A5A5A">' . get_string('nographtodisplay', 'report_myfeedback'). '</i>';
        if ($users) {
            //need to run through the entire array to create the arrays needed otherwise it will omit 
            //elements below what the current array element it's working on
            foreach ($users as $usid1 => $usr1) {
                if ($usr1['count'] > 0) {
                    foreach ($usr1['assess'] as $asid => $usr_val) {
                        if ($usr_val['score'] > 0) {
                            $userassess[$usid1][$asid] = $usr_val['score'];
                            if ($asid == $cid && $fromassess) {
                                $assessuser[$usid1][$asid] = $usr_val['score'];
                            }
                        }
                    }
                }
            }

            //Iterate through array again to get the correct amount of elements for each user
            foreach ($users as $usid => $usr) {
                $scol1 = $scol2 = $scol3 = $scol4 = '';
                if (!isset($usr['name']) || !$usr['name']) {
                    $getname = $remotedb->get_record('user', array('id' => $usid), $list = 'firstname,lastname,email');
                    if ($getname) {
                        $u_name = "<a href=\"" . $CFG->wwwroot . "/report/myfeedback/index.php?userid=" . $usid . "\" title=\"" .
                                $getname->email . "\" rel=\"tooltip\">" . $getname->firstname . " " . $getname->lastname . "</a>";
                        $fname = $getname->firstname;
                        $lname = $getname->lastname;
                    }
                } else {
                    $u_name = $usr['name'];
                }
                $users[$usid]['name'] = $u_name;
                $users[$usid]['fname'] = $fname;
                $users[$usid]['lname'] = $lname;

                if ($fromassess) {
                    $userassess = $assessuser;
                    $u_due = $usr['assess'][$cid]['due'];
                    $u_non = $usr['assess'][$cid]['non'];
                    $u_late = $usr['assess'][$cid]['late'];
                    //$u_feed = $usr['assess'][$cid]['feed'];
                    $u_graded = $usr['assess'][$cid]['graded'];
                    $u_low = $usr['assess'][$cid]['low'];
                } else {
                    $u_due = $usr['due'];
                    $u_non = $usr['non'];
                    $u_late = $usr['late'];
                    //$u_feed = $usr['feed'];
                    $u_graded = $usr['graded'];
                    $u_low = $usr['low'];
                }
                if (count($userassess) > 0) {
                    if ($tutgroup) {
                        foreach ($users[$usid]['assess'] as $uas) {
                            $a = array();
                            foreach ($uas as $tgtot) {
                                if ($tgtot != null) {
                                    $a[] = $tgtot;
                                }
                            }
                        }
                        $user_graph = $this->get_module_graph('tg' . $usid, $a, count($a));
                    } else {
                        $user_graph = $this->get_dashboard_zscore($usid, $userassess, $usid . $cid, $cid);
                    }
                }
                if (!$fromassess || ($fromassess && isset($usr['assess'][$cid]['yes']))) {
                    //if ($u_non || $u_graded || $u_late || $u_feed || $u_low) {//Only if data we display a user
                    $height = '80px';
                    $studenttable = "<table style='" . $style . "' class='tutor-inner " . $display . "' height='" . $height . "' align='center'><tr class='st-accord'><td>";
                    $uname .= $studenttable . $u_name . $breakdodwn . '</td></tr></table>';
                    $ud .= "<table style='" . $style . "' class='tutor-inner " . $display . "' height='" . $height . "' align='center'><tr class='st-accord'><td class='assessdue'>" . $u_due . "</td></tr></table>";
                    $un .= $studenttable . "<span class=$scol1>" . $u_non . "</span></td></tr></table>";
                    $ul .= $studenttable . "<span class=$scol2>" . $u_late . "</span></td></tr></table>";
                    $ug .= $studenttable . $u_graded . "</td></tr></table>";
                    //$uf .= $studenttable . $u_feed . "</td></tr></table>";
                    $ulo .= $studenttable . "<span class=$scol3>" . $u_low . "</span></td></tr></table>";
                    $u_vas .= "<table style='" . $style . "' class='tutor-inner " . $display . "' height='" . $height . "' align='center'><tr class='st-accord'><td class='overallgrade'>" . $user_graph . '</td></tr></table>';
                    //}
                }
            }
        }

        $us = new stdClass();
        $us->ud = $ud;
        $us->un = $un;
        $us->ul = $ul;
        $us->ug = $ug;
        //$us->uf = $uf;
        $us->ulo = $ulo;
        $us->u_vas = $u_vas;
        $us->uname = $uname;
        $us->newusers = ($fromassess ? '' : $users);
        return $us;
    }

    /**
     * Returns the graph with the z-score with the lowest, highest and median grade
     * also the number who got between 0-39, 40-49, 50-59, 60-69 and 70-100 %
     * 
     * @param int $cid The course id
     * @param array $uids The array of user ids
     * @return string The bar graph depicting the z-score
     */
    public function get_module_z_score($cid, $uids, $pmod = false) {
        $users = array();
        $assess = array();
        $userassess = array();
        $as = $this->get_all_assessments($cid);
        foreach ($uids as $ui) {
            $users[$ui]['due'] = 0;
            $users[$ui]['non'] = 0;
            $users[$ui]['late'] = 0;
            $users[$ui]['feed'] = 0;
            $users[$ui]['graded'] = 0;
            $users[$ui]['low'] = 0;
            $users[$ui]['score'] = null;
            $users[$ui]['count'] = 0;
            foreach ($as as $k => $a) {
                $assess[$k]['name'] = $a->itemname;
                $assess[$k]['icon'] = $a->itemmodule;
                $assess[$k]['due'] = 0;
                $assess[$k]['non'] = 0;
                $assess[$k]['late'] = 0;
                $assess[$k]['feed'] = 0;
                $assess[$k]['graded'] = 0;
                $assess[$k]['low'] = 0;
                $assess[$k]['feed'] = 0;
                $assess[$k]['score'][$ui] = null;
                $users[$ui]['assess'][$k]['name'] = $a->itemname;
                $users[$ui]['assess'][$k]['icon'] = $a->itemmodule;
                $users[$ui]['assess'][$k]['due'] = 0;
                $users[$ui]['assess'][$k]['non'] = 0;
                $users[$ui]['assess'][$k]['late'] = 0;
                $users[$ui]['assess'][$k]['graded'] = 0;
                $users[$ui]['assess'][$k]['low'] = 0;
                $users[$ui]['assess'][$k]['feed'] = 0;
                $users[$ui]['assess'][$k]['score'] = 0;
            }
        }
        foreach ($uids as $uid) {
            $eachcse_grade = $this->get_eachcourse_dashboard_grades($uid, $cid, $mod = 'Y');
            $eachcse_sub = $this->get_eachcourse_dashboard_submissions($uid, $cid, $mod = 'Y');

            foreach ($eachcse_sub as $sid => $sub) {
                $users[$uid]['due'] += $sub['due'];
                $users[$uid]['non'] += $sub['nosub'];
                $users[$uid]['late'] += $sub['late'];
                $users[$uid]['feed'] += $sub['feed'];
                $users[$uid]['assess'][$sid]['due'] += $sub['due'];
                $users[$uid]['assess'][$sid]['non'] += $sub['nosub'];
                $users[$uid]['assess'][$sid]['late'] += $sub['late'];
                $users[$uid]['assess'][$sid]['feed'] += $sub['feed'];
                $users[$uid]['assess'][$sid]['yes'] = '1';
                $assess[$sid]['due'] += $sub['due'];
                $assess[$sid]['non'] += $sub['nosub'];
                $assess[$sid]['late'] += $sub['late'];
                $assess[$sid]['feed'] += $sub['feed'];
            }
            foreach ($eachcse_grade as $gid => $gr) {
                $users[$uid]['graded'] += $gr['graded'];
                $users[$uid]['low'] += $gr['low'];
                $users[$uid]['score'] += $gr['score'][$uid];
                $users[$uid]['count'] += 1;
                $users[$uid]['assess'][$gid]['graded'] += $gr['graded'];
                $users[$uid]['assess'][$gid]['low'] += $gr['low'];
                $users[$uid]['assess'][$gid]['score'] = $gr['score'][$uid];
                $assess[$gid]['graded'] += $gr['graded'];
                $assess[$gid]['low'] += $gr['low'];
                $assess[$gid]['score'][$uid] = $gr['score'][$uid];
                $userassess[$uid][$gid] = $gr['score'][$uid];
            }
        }

        $graph_totals = array();
        $ct = 1;

        $due = $nonsub = $graded = $latesub = $latefeedback = $lowgrades = 0;

        foreach ($users as $u) {
            if ($u['count']) {
                $ct = $u['count'];
            }
            $graph_totals[] = $u['score'] / $ct;
            $due += $u['due'];
            $nonsub += $u['non'];
            $latesub += $u['late'];
            $graded += $u['graded'];
            $latefeedback += $u['feed'];
            $lowgrades += $u['low'];
        }
        $uidnum = count($uids);
        $stmod = 'stuRecM';
        $st = 'stuRec';

        $mainassess = $this->get_assessment_analytics($assess, $uidnum, $display = 'assRec', $style = '', 
                $breakdown = '<p style="margin-top:10px"><span class="assess-br">' . get_string('studentbreakdown', 'report_myfeedback'). 
                '</span><br><span class="assess-br modangle">&#9660;</span></p>', $users, $pmod);
        $mainuser = $this->get_user_analytics($users, $cid, $display = ($pmod ? $stmod : $st), $style = 'display:none');
        $users = $mainuser->newusers;

        $result = new stdClass();
        $result->graph = '';
        $maingraph = $this->get_module_graph($cid, $graph_totals, $enrolled = $uidnum);

        if ($pmod) {
            if (!$due && !$graded) {
                $result->graph .= "<tr><td></td>";
                $result->graph .= "<td></td>";
                $result->graph .= "<td></td>";
                $result->graph .= "<td></td>";
                $result->graph .= "<td></td>";
                $result->graph .= "<td></td>";
                //$result->graph .= "<td></td>";
                $result->graph .= "<td></td></tr>";
            } else {
                $result->graph .= "<tr class='recordRow' ><td>" . $mainassess->aname . $mainuser->uname . /* $uname . */ "</td>";
                $result->graph .= "<td class='overallgrade'>" . $mainassess->a_vas . $mainuser->u_vas . /* $u_vas . */ "</td>";
                $result->graph .= "<td>" . $mainassess->ad . $mainuser->ud . /* $ud . */ "</td>";
                $result->graph .= "<td>" . $mainassess->an . $mainuser->un . /* $un . */ "</td>";
                $result->graph .= "<td>" . $mainassess->al . $mainuser->ul . /* $ul . */ "</td>";
                $result->graph .= "<td>" . $mainassess->ag . $mainuser->ug . /* $ug . */ "</td>";
                //$result->graph .= "<td>" . $mainassess->af . $mainuser->uf . /* $uf . */ "</td>";
                $result->graph .= "<td>" . $mainassess->alo . $mainuser->ulo . /* $ulo . */ "</td></tr>";
            }
        }
        $result->modgraph = $maingraph;
        $result->due = $due;
        $result->nonsub = $nonsub;
        $result->latesub = $latesub;
        $result->graded = $graded;
        //$result->latefeedback = $latefeedback;
        $result->lowgrades = $lowgrades;
        $result->users = $users;
        $result->assess = $assess;
        $result->totals = $graph_totals;
        return $result;
    }

    public function get_progadmin_ptutor($ptutors, $p_tutor_id, $modtut = null, $tutgroup = null, $currentmod = null) {
        global $remotedb;
        $myusers = array();
        if ($modtut) {
            if ($csectexts = context::instance_by_id($modtut, IGNORE_MISSING)) {
                //$uss = $userctexts->get_parent_context_ids(true);
                //$ptutors = ($uss ? $uss : $ptutors);
                $ptutors = get_users_by_capability($csectexts, 'report/myfeedback:modtutor', $fields = 'u.id, u.firstname, u.lastname, u.email');
                foreach ($ptutors as $u) {
                    //if ($u->id != $USER->id) {
                    $myusers[$u->id][0] = $u->firstname . " " . $u->lastname;
                    $myusers[$u->id][1] = $u->email;
                    $myusers[$u->id][2] = ($modtut ? $this->get_tutees_for_prog_tutor_groups($u->id, $currentmod, $tutgroup) : $this->get_tutees_for_prog_ptutors($u->id));
                    $myusers[$u->id][3] = $u->id;
                }
            }
        }
        //$contextlevel = ($modtut ? "" : "AND contextlevel=30");
        //$params = ($modtut ? array($p_tutor_id, $t) : array($p_tutor_id, $t, 30));
        //$role = ($modtut ? 'Module tutor' : 'Personal tutor');
        $tutor = ($modtut ? '<th>' . get_string('moduletutors', 'report_myfeedback'). '</th><th>' . get_string('tutorgroups', 'report_myfeedback'). 
                '</th><th></a><input title="' . get_string('selectallforemail', 'report_myfeedback') . '" rel ="tooltip" type="checkbox" id="selectall1"/>' . get_string('selectall', 'report_myfeedback') .
                        ' <a class="btn" id="mail1"> ' . get_string('sendmail', 'report_myfeedback') . '</th>' : '<th>' . get_string('personaltutors', 'report_myfeedback') . '</th><th colspan="4">' . get_string('tutees+-', 'report_myfeedback') . '</th>');
        $usertable = '<form method="POST" id="emailform1" action=""><table id="ptutor" class="ptutor" width="100%" style="display:none" border="1"><thead><tr class="tableheader">' . $tutor . '</tr></thead><tbody>';

        //foreach ($ptutors as $t) {
        //$us = $context, $usertable)
        // Get the details of personal tutors who is assigned to each user contextid
        /* if ($usercontexts = $remotedb->get_records_sql("SELECT c.id as cid, u.id as 
          FROM {role_assignments} ra, {context} c, {user} u
          WHERE ra.roleid = ?
          AND ra.contextid = ?
          AND ra.userid=u.id
          AND c.instanceid = u.id " . $contextlevel, array($p_tutor_id, $t))) { */

        //}
        //}
        //}

        foreach ($myusers as $result) {
            $usertable.= "<tr>";
            $usertable.= "<td>" . $result[0] . "</td>";
            $usertable.= "<td class='maintable'><u class='hidetable'>" . get_string('show') . "</u><br>" . $result[2] . "</td>";
            //$usertable.= "<td><u>" . $role . "</u></td>";
            $usertable.= "<td><input class=\"chk2\" type=\"checkbox\" name=\"email" . $result[3] . "\" value=\"" . $result[1] . "\"> " . $result[1] . "</td>";
            //$usertable.="<td class=\"ellip\" data-sort=$result[2] title=\"$result[3]\" rel=\"tooltip\">" . $result[1] . "</td>";
            $usertable.= "</tr>";
        }

        $usertable.="</tbody></table></form>";
        return $usertable;
    }

    /**
     * Return the position the user's grade falls into for the bar graph
     * @global obj $remotedb The DB object
     * @param int $itemid Grade item id
     * @param int $grade The grade
     * @return string  The relative graph with the sections that should be shaded
     */
    public function get_activity_min_and_max_grade($itemid, $grade) {
        global $remotedb;
        $sql = "SELECT min(finalgrade) as min, max(finalgrade) as max FROM {grade_grades}
                WHERE itemid = ?";
        $params = array($itemid);
        $activity = $remotedb->get_record_sql($sql, $params);
        $loc = 100;
        if ($activity) {
            $res = (int) $activity->max - (int) $activity->min;
            if ($res > 0) {
                $p_res = $res / 5;
                $pos1 = $activity->min + $p_res;
                $pos2 = $activity->min + $p_res * 2;
                $pos3 = $activity->min + $p_res * 3;
                $pos4 = $activity->min + $p_res * 4;
                if ($grade < $pos1) {
                    $loc = 20;
                }
                if ($grade >= $pos1 && $grade < $pos2) {
                    $loc = 40;
                }
                if ($grade >= $pos2 && $grade < $pos3) {
                    $loc = 60;
                }
                if ($grade >= $pos3 && $grade < $pos4) {
                    $loc = 80;
                }
                if ($grade >= $pos4) {
                    $loc = 100;
                }
            }
        }
        $relgraph = '<canvas id="myCanvas2' . $itemid . '" width="190" height="30" style="border:0px solid #d3d3d3;">
                '.get_string("browsersupport", "report_myfeedback").'</canvas>
             <script>
                var c1 = document.getElementById("myCanvas2' . $itemid . '");
                var ctx1 = c1.getContext("2d");
                var pos1 = ' . $loc . ';
                ctx1.beginPath();
                
                ctx1.strokeStyle = "#888";
                ctx1.font="10px Arial";
                ctx1.strokeRect(5,1,180,18);
                ctx1.strokeRect(5,1,36,18);
                ctx1.strokeRect(41,1,36,18);
                ctx1.strokeRect(77,1,36,18);
                ctx1.strokeRect(113,1,36,18);
                ctx1.strokeRect(149,1,36,18);
                ctx1.fillText("MIN",5,29);
                ctx1.fillText("MAX",162,29);
                ctx1.stroke();                
                ctx1.fillStyle = "#8bc278";
                ctx1.fillRect(6,2,34,16);
                if (pos1 >= 20) {
                  ctx1.fillRect(42,2,34,16);
                }
                if (pos1 >= 40) {
                  ctx1.fillRect(78,2,34,16);
                }
                if (pos1 >= 60) {
                  ctx1.fillRect(114,2,34,16);
                }
                if (pos1 >= 80) {
                  ctx1.fillRect(150,2,34,16);
                }
             </script>';
        return $relgraph;
    }

    /**
     * get the self-reflective notes for each grade item added by the user
     * 
     * @global obj $remotedb The DB object
     * @param int $userid The user id who the notes relate to
     * @param int $gradeitemid The id of the specific grade item
     * @return str The self-reflective notes
     */
    public function get_notes($userid, $gradeitemid, $instn) {
        global $remotedb;

        $sql = "SELECT DISTINCT notes
                 FROM {report_myfeedback}
                 WHERE userid=? AND gradeitemid=? AND iteminstance=?";
        $params = array($userid, $gradeitemid, $instn);
        $usernotes = $remotedb->get_record_sql($sql, $params);
        $displaynotes = '';
        if ($usernotes) {
            $displaynotes = $usernotes->notes;
        }
        return $displaynotes;
    }

    /**
     * get the non-Moodle feedback for each grade item added by the user
     * 
     * @global obj $remotedb The DB object
     * @param int $userid The user id who the notes relate to
     * @param int $gradeitemid The id of the specific grade item
     * @return str The non-Moodle feedback
     */
    public function get_turnitin_feedback($userid, $gradeitemid, $inst) {
        global $remotedb;

        $sql = "SELECT DISTINCT modifierid, feedback
                 FROM {report_myfeedback}
                 WHERE userid=? AND gradeitemid=? AND iteminstance=?";
        $params = array($userid, $gradeitemid, $inst);
        $turnitinfeedback = $remotedb->get_record_sql($sql, $params);
        if ($turnitinfeedback) {
            return $turnitinfeedback;
        }
        return null;
    }

    /**
     * Get the mount of archived years set in the report settings and return the 
     * years (1415) as an array including the current year ('current')
     * @return array mixed The archived years
     */
    public function get_archived_dbs() {
        $dbs = get_config('report_myfeedback');
        $ac_year = array('current');
        $archived_years = 0;
        if (isset($dbs->archivedyears)) {
            if ($dbs->archivedyears) {
                $archived_years = $dbs->archivedyears;
            }
        }
        if (isset($dbs->dbhostarchive)) {
            if ($dbs->dbhostarchive) {
                for ($i = 1; $i <= $archived_years; $i++) {
                    $ac_year[] = $this->get_academic_years($i);
                }
            }
        }
        return $ac_year;
    }

    public function get_course_limit() {
        $cl = get_config('report_myfeedback');
        return (isset($cl->courselimit) && $cl->courselimit ? $cl->courselimit : 200);
    }

    /**
     * Return the academic years based on the current month 
     * @param int $ac_year How many years from today's date
     * @return string The academic year in the form of 1415, 1314
     */
    public function get_academic_years($ac_year = null) {
        $month = date("m");
        $year = date("Y-m-d", strtotime("- {$ac_year} Years"));
        $academicyear = date("y", strtotime($year)) . date("y", strtotime($year . "+ 1 Years"));
        if ($month < 9) {
            $academicyear = date("y", strtotime($year . "- 1 Years")) . date("y", strtotime($year));
        }
        return $academicyear;
    }

    /**
     * Return the personal tutees and their stats 
     * @global object $remotedb The current DB object
     * @global stdClass $CFG The global config settings
     * @global object $USER The logged in user global properties
     * @global object $OUTPUT The global output properties
     * @return string Return th ename and other details of the personal tutees
     */
    public function get_dashboard_tutees() {
        global $remotedb, $CFG, $USER, $OUTPUT;
        $myusers = array();
        // get all the mentees, i.e. users you have a direct assignment to as their personal tutor
        if ($usercontexts = $remotedb->get_records_sql("SELECT c.instanceid, c.instanceid, u.id as id, firstname, lastname, email, department
                                                    FROM {role_assignments} ra, {context} c, {user} u
                                                   WHERE ra.userid = ?
                                                         AND ra.contextid = c.id
                                                         AND c.instanceid = u.id
                                                         AND c.contextlevel = " . CONTEXT_USER, array($USER->id))) {
            foreach ($usercontexts as $u) {
                $user = $remotedb->get_record('user', array('id' => $u->id, 'deleted' => 0));
                $year = null;
                profile_load_data($user);
                if (!$year = $user->profile_field_courseyear) {
                    
                }
                $myusers[$u->id][0] = "<div><div style=\"float:left;margin-right:5px;\">" .
                        $OUTPUT->user_picture($user, array('size' => 40)) . "</div><div style=\"float:left;\"><a href=\"" .
                        $CFG->wwwroot . "/report/myfeedback/index.php?userid=" . $u->id . "\">" . $u->firstname . " " . $u->lastname . "</a><br>" .
                        ($year ? ' ' . get_string('year', 'report_myfeedback') . $year . ', ' : '') . $u->department . "</div></div>";
                $myusers[$u->id][1] = $u->firstname . '+' . $u->lastname; //what we want to sort by (without spaces)
                $myusers[$u->id][2] = $u->email;
                $myusers[$u->id][3] = $u->firstname . ' ' . $u->lastname; //Display name with spaces
                $myusers[$u->id][4] = $u->firstname; //Display name with spaces
                $myusers[$u->id][5] = $u->lastname; //Display name with spaces
            }
        }
        return $myusers;
    }

    /**
     * Get the number of graded assessments and low grades of the tutees
     * @global object $remotedb The current DB object
     * @param int $userid The id of the user who's details are being retrieved
     * @param int $courseid The id of the course 
     * @return array int Retun the int value fo the graded assessments and low grades.
     */
    public function get_eachcourse_dashboard_grades($userid, $courseid, $modtutor = null) {
        global $remotedb;
        $checkdb = 'current';
        $archive = false;
        if (isset($_SESSION['viewyear'])) {
            $checkdb = $_SESSION['viewyear']; // check if previous academinc year,
        }
        if ($checkdb != 'current') {
            $archive = true;
        }
        $now = time();
        $items = array('turnitintool', 'turnitintooltwo', 'workshop', 'quiz', 'assign');
        foreach ($items as $key => $item) {
            if (!$this->mod_is_available($item)) {
                unset($items[$key]);
            }
        }
        $items = '"' . implode('","', $items) . '"';
        $sql = "SELECT DISTINCT c.id AS cid, gg.id as tid, finalgrade, gg.timemodified as feed_date, gi.id as gid, grademax, cm.id AS cmid
                FROM {course} c
                JOIN {grade_items} gi ON c.id=gi.courseid AND (gi.hidden != 1 AND gi.hidden < $now)
                JOIN {grade_grades} gg ON gi.id = gg.itemid AND gg.userid = ? AND (gi.hidden != 1 AND gi.hidden < ?)
                AND (gg.hidden != 1 AND gg.hidden < ?) AND gi.courseid = ?
                AND gg.finalgrade IS NOT NULL AND (gi.itemmodule IN ($items) OR (gi.itemtype = 'manual'))
                LEFT JOIN {course_modules} cm ON gi.iteminstance=cm.instance AND c.id=cm.course AND cm.course = $courseid                 
                LEFT JOIN {context} con ON cm.id = con.instanceid AND con.contextlevel=70
                LEFT JOIN {modules} m ON cm.module = m.id AND gi.itemmodule = m.name
                WHERE c.visible=1 AND c.showgrades = 1";
        $params = array($userid, $now, $now, $courseid);
        $gr = $remotedb->get_recordset_sql($sql, $params);
        $grades = array();
        if ($gr->valid()) {
            foreach ($gr as $rec) {//
                //Here we check id sections have any form of restrictions
                $show = 1;
                if (!$archive || ($archive && $checkdb > 1415)) {
                    if ($rec->cid) {
                        $rec->id = $rec->cid;
                        $modinfo = get_fast_modinfo($rec, $userid);
                        if ($rec->cmid >= 1) {
                            $cm = $modinfo->get_cm($rec->cmid);
                            if ($cm->uservisible) {
                                // User can access the activity.
                            } else {
                                $show = 0;
                            }
                        }
                    }
                }
                if ($show) {
                    $grades[$rec->gid] = $rec;
                }
            }
            $gr->close();
        }
        $result = array(0, 0);
        $result[0] = count($grades);
        $modresult = array();
        foreach ($grades as $b) {
            $modresult[$b->gid]['graded'] = 0;
            $modresult[$b->gid]['low'] = 0;
        }
        foreach ($grades as $grade) {
            if ($grade->grademax > 0) {
                if (round($grade->finalgrade) / $grade->grademax * 100 < 50) {
                    $result[1] += 1;
                    $modresult[$grade->gid]['low'] += 1;
                }
            }
            $modresult[$grade->gid]['graded'] += 1;
            $modresult[$grade->gid]['score'][$userid] = ($grade->grademax > 0 ? round($grade->finalgrade) / $grade->grademax * 100 : round($grade->finalgrade));
        }
        if ($modtutor) {
            return $modresult;
        } else {
            return $result;
        }
    }

    /**
     * Get all the due assessments, non-submissions and late submission
     * from the assign mod type, quiz, workshop and turnitin v1 and v2
     * @global object $remotedb The current DB object
     * @param int $userid The id of the user you getting the details for
     * @param int $courseid The id of the course 
     * @return array int Return the int value for the assessment due, non and late submissions
     */
    public function get_eachcourse_dashboard_submissions($userid, $courseid, $modtutor = null) {
        global $remotedb;
        $checkdb = 'current';
        $archive = false;
        if (isset($_SESSION['viewyear'])) {
            $checkdb = $_SESSION['viewyear']; // check if previous academinc year,
        }
        if ($checkdb != 'current') {
            $archive = true;
        }
        $now = time();
        $sql = "SELECT DISTINCT c.id AS cid, gi.id as tid, gg.id, gg.timemodified as due, gg.timemodified as sub, gi.itemtype as type, 
                    -1 AS status, -1 AS nosubmissions, -1 AS cmid, gg.timemodified as feed_date
                 FROM {course} c
                 JOIN {grade_items} gi ON c.id=gi.courseid AND gi.itemtype = 'manual' AND (gi.hidden != 1 AND gi.hidden < $now)
                 JOIN {grade_grades} gg ON gi.id=gg.itemid AND gg.userid = $userid AND gi.courseid = $courseid AND (gg.hidden != 1 AND gg.hidden < $now)
                 WHERE c.visible=1 AND c.showgrades = 1 ";
        if ($this->mod_is_available('assign')) {
            $sql .= "UNION SELECT DISTINCT c.id AS cid, gi.id as tid, a.id, a.duedate as due, su.timemodified as sub, gi.itemmodule as type, 
                    su.status AS status, a.nosubmissions AS nosubmissions, cm.id AS cmid, gg.timemodified as feed_date
                 FROM {course} c
                 JOIN {grade_items} gi ON c.id=gi.courseid
                 AND itemtype='mod' AND gi.itemmodule='assign' AND (gi.hidden != 1 AND gi.hidden < $now)
                 LEFT JOIN {grade_grades} gg ON gi.id=gg.itemid AND gg.userid = $userid
                             AND (gg.hidden != 1 AND gg.hidden < $now)
                 JOIN {course_modules} cm ON gi.iteminstance=cm.instance AND c.id=cm.course AND cm.course = $courseid                 
                 JOIN {context} con ON cm.id = con.instanceid AND con.contextlevel=70
                 JOIN {modules} m ON cm.module = m.id AND gi.itemmodule = m.name AND gi.itemmodule = 'assign'
                 JOIN {assign} a ON gi.iteminstance=a.id AND a.course=gi.courseid AND gi.courseid = $courseid
                 LEFT JOIN {assign_submission} su ON a.id=su.assignment AND su.userid=$userid
                 WHERE c.visible=1 AND c.showgrades = 1 ";
        }
        if ($this->mod_is_available('quiz')) {
            $sql .= "UNION SELECT DISTINCT c.id AS cid, gi.id as tid, q.id, q.timeclose as due, gg.timecreated as sub, gi.itemmodule as type,
                    -1 AS status, -1 AS nosubmissions, cm.id AS cmid, gg.timemodified as feed_date
                 FROM {course} c
                 JOIN {grade_items} gi ON c.id=gi.courseid
                 AND itemtype='mod' AND gi.itemmodule='quiz' AND (gi.hidden != 1 AND gi.hidden < $now)
                 LEFT JOIN {grade_grades} gg ON gi.id=gg.itemid AND gg.userid = $userid
                             AND (gg.hidden != 1 AND gg.hidden < $now)
                 JOIN {course_modules} cm ON gi.iteminstance=cm.instance AND c.id=cm.course AND cm.course = $courseid
                 JOIN {context} con ON cm.id = con.instanceid AND con.contextlevel=70
                 JOIN {modules} m ON cm.module = m.id AND gi.itemmodule = m.name AND gi.itemmodule = 'quiz'
                 JOIN {quiz} q ON q.id=gi.iteminstance AND q.course=gi.courseid AND gi.courseid = $courseid
                 WHERE c.visible=1 AND c.showgrades = 1 ";
        }
        if ($this->mod_is_available('workshop')) {
            $sql .= "UNION SELECT DISTINCT c.id AS cid, gi.id as tid, w.id, w.submissionend as due, ";
            if (!$archive || ($archive && $checkdb > 1112)) {//when the new timemodified field was added to workshop_submission
                $sql .= "ws.timemodified AS sub, ";
            } else {
                $sql .= "ws.timecreated AS sub, ";
            }
            $sql .= "gi.itemmodule as type, 
                    -1 AS status, -1 AS nosubmissions, cm.id AS cmid, gg.timemodified as feed_date
                 FROM {course} c
                 JOIN {grade_items} gi ON c.id=gi.courseid
                 AND itemtype='mod' AND gi.itemmodule='workshop' AND (gi.hidden != 1 AND gi.hidden < $now)
                 LEFT JOIN {grade_grades} gg ON gi.id=gg.itemid AND gg.userid = $userid
                             AND (gg.hidden != 1 AND gg.hidden < $now)
                 JOIN {course_modules} cm ON gi.iteminstance=cm.instance AND c.id=cm.course AND cm.course = $courseid
                 JOIN {context} con ON cm.id = con.instanceid AND con.contextlevel=70
                 JOIN {modules} m ON cm.module = m.id AND gi.itemmodule = m.name AND gi.itemmodule = 'workshop'
                 JOIN {workshop} w ON w.id=gi.iteminstance AND w.course=gi.courseid AND gi.courseid = $courseid ";
            if (!$archive || ($archive && $checkdb > 1112)) {//when the new timemodified field was added to workshop_submission
                $sql .= "LEFT JOIN {workshop_submissions} ws ON w.id=ws.workshopid AND ws.example=0 AND ws.authorid = $userid ";
            } else {
                $sql .= "LEFT JOIN {workshop_submissions} ws ON w.id=ws.workshopid AND ws.example=0 AND ws.userid = $userid ";
            }
            $sql .= "WHERE c.visible=1 AND c.showgrades = 1 ";
        }
        if ($this->mod_is_available('turnitintool')) {
            $sql .= "UNION SELECT DISTINCT c.id AS cid, gi.id as tid, tp.id, tp.dtdue as due, ts.submission_modified as sub, gi.itemmodule as type, 
                    -1 AS status, -1 AS nosubmissions, cm.id AS cmid, gg.timemodified as feed_date
                 FROM {course} c
                 JOIN {grade_items} gi ON c.id=gi.courseid
                 AND itemtype='mod' AND gi.itemmodule='turnitintool' AND (gi.hidden != 1 AND gi.hidden < $now)
                 LEFT JOIN {grade_grades} gg ON gi.id=gg.itemid AND gg.userid = $userid
                             AND (gg.hidden != 1 AND gg.hidden < $now)
                 JOIN {course_modules} cm ON gi.iteminstance=cm.instance AND c.id=cm.course AND cm.course = $courseid            
                 JOIN {context} con ON cm.id = con.instanceid AND con.contextlevel=70
                 JOIN {modules} m ON cm.module = m.id AND gi.itemmodule = m.name AND gi.itemmodule = 'turnitintool'
                 JOIN {turnitintool} t ON t.id=gi.iteminstance AND t.course=gi.courseid AND gi.courseid = $courseid
                 LEFT JOIN {turnitintool_submissions} ts ON ts.turnitintoolid=t.id AND ts.userid = $userid
                 LEFT JOIN {turnitintool_parts} tp ON tp.id = ts.submission_part 
                 WHERE c.visible=1 AND c.showgrades = 1 ";
        }
        if ($this->mod_is_available('turnitintooltwo')) {
            $sql .= "UNION SELECT DISTINCT c.id AS cid, gi.id as tid, tp.id, tp.dtdue as due, ts.submission_modified as sub, gi.itemmodule as type, 
                    -1 AS status, -1 AS nosubmissions, cm.id AS cmid, gg.timemodified as feed_date
                 FROM {course} c
                 JOIN {grade_items} gi ON c.id=gi.courseid
                 AND itemtype='mod' AND gi.itemmodule='turnitintooltwo' AND (gi.hidden != 1 AND gi.hidden < $now)
                 LEFT JOIN {grade_grades} gg ON gi.id=gg.itemid AND gg.userid = $userid
                             AND (gg.hidden != 1 AND gg.hidden < $now)
                 JOIN {course_modules} cm ON gi.iteminstance=cm.instance AND c.id=cm.course AND cm.course = $courseid
                 JOIN {context} con ON cm.id = con.instanceid AND con.contextlevel=70
                 JOIN {modules} m ON cm.module = m.id AND gi.itemmodule = m.name AND gi.itemmodule = 'turnitintooltwo'
                 JOIN {turnitintooltwo} t ON t.id=gi.iteminstance AND t.course=gi.courseid AND gi.courseid = $courseid 
                 AND gi.itemmodule = 'turnitintooltwo'
                 LEFT JOIN {turnitintooltwo_submissions} ts ON ts.turnitintooltwoid=t.id AND ts.userid = $userid
                 LEFT JOIN {turnitintooltwo_parts} tp ON tp.id = ts.submission_part
                 WHERE c.visible=1 AND c.showgrades = 1";
        }
        $r = $remotedb->get_recordset_sql($sql);
        $all = array();
        if ($r->valid()) {
            foreach ($r as $rec) {//
                //Here we check id sections have any form of restrictions
                $show = 1;
                if (!$archive || ($archive && $checkdb > 1415)) {
                    if ($rec->cid) {
                        $rec->id = $rec->cid;
                        $modinfo = get_fast_modinfo($rec, $userid);
                        if ($rec->cmid >= 1) {
                            $cm = $modinfo->get_cm($rec->cmid);
                            if ($cm->uservisible) {
                                // User can access the activity.
                            } else {
                                $show = 0;
                            }
                        }
                    }
                }
                if ($show) {
                    $all[$rec->tid] = $rec;
                }
            }
            $r->close();
        }

        $result = array(0, 0, 0, 0);
        $modresult = array();
        foreach ($all as $b) {
            $modresult[$b->tid]['due'] = 0;
            $modresult[$b->tid]['nosub'] = 0;
            $modresult[$b->tid]['late'] = 0;
            $modresult[$b->tid]['feed'] = 0;
        }
        foreach ($all as $a) {
            if ($a->due) {
                if ($a->type == 'assign') {//check for extension
                    if (!$archive || ($archive && $checkdb > 1314)) {//when assign_user_flags came in
                        $extend = $this->check_assign_extension($userid, $a->id);
                        if ($extend && $extend > $a->due) {
                            $a->due = $extend;
                        }
                    }
                }
                if ($a->type == 'quiz') {//check for extension
                    $extended = $this->check_quiz_group_extension($a->id, $userid);
                    if ($extended && $extended > $a->due) {
                        $a->due = $extended;
                    }
                    $extends = $this->check_quiz_extension($a->id, $userid);
                    if ($extends && $extends > $a->due) {
                        $a->due = $extends;
                    }
                }
            }
            if (($a->due < $now) || ($a->due < 1 && $a->sub < $now)) {//Any duedate that's past or (no due date but submitted)
                $result[0] += 1;
                $modresult[$a->tid]['due'] += 1;
            }
            if ($a->nosubmissions != 1) {
                if (($a->due < $now && $a->sub < 1) || ($a->status == "new")) {//No submission and due date past
                    $result[1] += 1; //Can only be, if it is not an offline assignment
                    $modresult[$a->tid]['nosub'] += 1; //Is a non-sub if assignment status is 'new'
                }
            }
            if ($a->status != 'new') {
                if ($a->due && $a->sub > $a->due) {//Late submission
                    $result[2] += 1; //Can only be, if assignment status not 'new' which is added at override without submission
                    $modresult[$a->tid]['late'] += 1; //this is in case it updates the submission status when overridden.
                }
            }
            if ($a->status != 'new') {
                $dt = max($a->due, $a->sub);
                if ($a->feed_date && ($a->feed_date - $dt > 2419200)) {//4 weeks)
                    $result[3] += 1; //Late feedback only if status not new
                    $modresult[$a->tid]['feed'] += 1;
                }
            }
        }
        if ($modtutor) {
            return $modresult;
        } else {
            return $result;
        }
    }

    /**
     * Here we calculate the zscore of the tutee and also the module breakdown
     * @global objeect $remotedb The current DB object
     * @param int $userid The id of the tutees whos graph you are returning
     * @return string The canvas images of the overall position and the module breakdown images
     */
    public function get_dashboard_zscore($userid, $tutor = null, $coid = null, $asid = null) {
        global $remotedb;
        $eachavg = array();
        $allcourses = array();
        $alltotals = array();
        $eachmodule = array();
        $eachuser = array();
        if (!$tutor) {
            //First get all active courses the user is enrolled on
            if ($usermods = get_user_capability_course('moodle/grade:view', $userid, $doanything = false, $fields = 'shortname,visible')) {
                foreach ($usermods as $usermod) {
                    $uids = array();
                    if ($usermod->visible && $usermod->id) {
                        $allcourses[$usermod->id]['shortname'] = $usermod->shortname;
                        $eachcse_grade = $this->get_eachcourse_dashboard_grades($userid, $usermod->id);
                        $eachcse_sub = $this->get_eachcourse_dashboard_submissions($userid, $usermod->id);
                        $allcourses[$usermod->id]['due'] = $eachcse_sub[0];
                        $allcourses[$usermod->id]['non'] = $eachcse_sub[1];
                        $allcourses[$usermod->id]['late'] = $eachcse_sub[2];
                        $allcourses[$usermod->id]['feed'] = $eachcse_sub[3];
                        $allcourses[$usermod->id]['graded'] = $eachcse_grade[0];
                        $allcourses[$usermod->id]['low'] = $eachcse_grade[1];

                        //Now get all enrolled users on these courses
                        $context = context_course::instance($usermod->id);
                        $query = 'select u.id as id from mdl_role_assignments as a,
                              mdl_user as u where contextid=' . $context->id . ' and roleid=5 and a.userid=u.id;';
                        $allusers = $remotedb->get_recordset_sql($query);
                        //Get each user's id to get EACH course grades calculations
                        foreach ($allusers as $user) {
                            $uids[] = $user->id;
                        }
                        $allcourses[$usermod->id]['usercount'] = count($uids);
                        $alltotals[$usermod->id] = grade_get_course_grades($usermod->id, $uids);
                    }
                }
            }

            foreach ($alltotals as $id => $eachtotal) {
                if ($eachtotal->grademax > 0) {
                    $allcourses[$id]['grademax'] = $eachtotal->grademax;
                    foreach ($eachtotal->grades as $uid => $eachgrade) {
                        if ($eachgrade->grade != null) {
                            $eachuser[$uid][$id] = round($eachgrade->grade / $eachtotal->grademax * 100);
                            $eachmodule[$id][$uid] = round($eachgrade->grade / $eachtotal->grademax * 100);
                            if ($uid == $userid) {
                                $allcourses[$id]['currentuser'] = $eachgrade->grade;
                            }
                        }
                    }
                }
            }
        }
        //If not called from p tutor then should only calculate min, avg and max of that assessment or course
        $addavg = array();
        if ($tutor) {
            $eachuser = $tutor;
        }
        if (isset($eachuser[$userid][$asid]) && $tutor) {//Some users not from ptutor that needs it on departmental or module level
            foreach ($eachuser as $u => $eachmod) {
                foreach ($eachmod as $em => $eachassess) {
                    $eachavg[$u][$em] = $eachassess;
                    if ($em == $asid) {
                        $addavg[] = $eachassess;
                    }
                }
            }
            $usr = (isset($eachavg[$userid][$asid]) ? $eachavg[$userid][$asid] : 0);
        } else {
            foreach ($eachuser as $u => $eachmod) {
                $eachavg[$u] = array_sum($eachmod) / count($eachmod);
                $addavg[] = array_sum($eachmod) / count($eachmod);
            }
            $usr = (isset($eachavg[$userid]) ? $eachavg[$userid] : 0);
        }

        if ($usr) {
            $avg = round(array_sum($addavg) / count($addavg));
            $min = round(min($addavg));
            $max = round(max($addavg));
            $myavg = ($usr ? round($usr) : null);
            $pos_min = round($min * 1.8) + 5;
            $pos_max = round($max * 1.8) + 5;
            $pos_avg = round($avg * 1.8) + 5;
            $pos_myavg = round($myavg * 1.78) + 1;
            $pos_min_text = $pos_min - 7;
            $pos_avg_text = $pos_avg - 7;
            $pos_max_text = $pos_max - 7;
            if (!$myavg) {
                $pos_myavg = 0;
            }
            if ($pos_avg_text - $pos_min_text < 18) {
                $pos_avg_text = $pos_min_text + 18;
            }
            if ($pos_max_text - $pos_avg_text < 18) {
                $pos_max_text = $pos_avg_text + 18;
            }
            if ($pos_min_text < 1) {
                $pos_min_text = 1;
            }
            if ($min != 100 && $pos_min_text > 135) {
                $pos_min_text = 135;
            }
            if ($min == 100 && $pos_min_text > 125) {
                $pos_min_text = 125;
            }
            if ($pos_avg_text < 18) {
                $pos_avg_text = 18;
            }
            if ($avg != 100 && $pos_avg_text > 150) {
                $pos_avg_text = 150;
            }
            if ($avg == 100 && $pos_avg_text > 147) {
                $pos_avg_text = 147;
            }
            if ($pos_max_text < 32) {
                $pos_max_text = 32;
            }
            if ($pos_max_text > 168) {
                $pos_max_text = 168;
            }
            $col0 = '#8bc278';
            if ($myavg < 50) {
                $col0 = '#d69859';
            }
            if ($myavg < 40) {
                $col0 = '#c05756';
            }
            if (!$tutor) {
                $dashimage = '<div>';
            } else {
                $a = new stdClass();
                $a->minimum = $min;
                $a->mean = $avg;
                $a->maximum = $max;
                $a->studentscore = $myavg;
                $dashimage = '<div class="tutorCanvas"><canvas title="' . get_string('studentgraphdesc', 'report_myfeedback', $a) . '" rel="tooltip" id="myCanvas' . 
                        $userid . $coid . '" width="190" height="60" style="border:0px solid #d3d3d3;">'.get_string('browsersupport', 'report_myfeedback').'</canvas>
        <script>
        var c = document.getElementById("myCanvas' . $userid . $coid . '");
        var ctx = c.getContext("2d");
        var min = ' . $min . ';
        var max = ' . $max . ';
        var avg = ' . $avg . ';
        var myavg = ' . $myavg . ';
        var posmin = ' . $pos_min . ';
        var posmax = ' . $pos_max . ';
        var posavg = ' . $pos_avg . ';
        var posmintext = ' . $pos_min_text . ';
        var posmaxtext = ' . $pos_max_text . ';
        var posavgtext = ' . $pos_avg_text . ';
        var posmyavg = ' . $pos_myavg . ';
        var col = "' . $col0 . '"; 
        //ctx.scale(0.8,1);
        ctx.fillStyle = "#ddd";
        ctx.strokeStyle = "#555";
        ctx.font="12px Arial";
        ctx.strokeRect(5,17,180,18);
        ctx.fillRect(6,18,178,16);
        ctx.fillStyle = "#555";
        ctx.moveTo(posmin, 12);
        ctx.lineTo(posmin, 17);
        ctx.fillText(min,posmintext,10);

        ctx.moveTo(posmax, 12);
        ctx.lineTo(posmax, 17);
        ctx.fillText(max,posmaxtext,10);

        ctx.moveTo(77, 35);
        ctx.lineTo(77, 40);
        ctx.moveTo(95, 35);
        ctx.lineTo(95, 40);
        ctx.moveTo(113, 35);
        ctx.lineTo(113, 40);
        ctx.moveTo(131, 35);
        ctx.lineTo(131, 40);
        ctx.stroke();
        ctx.fillText(40,70,51);
        ctx.fillText(50,88,51);
        ctx.fillText(60,106,51);
        ctx.fillText(70,124,51);
        ctx.beginPath()
        ctx.font="11px Arial Black";
        ctx.fillText(myavg+"/100",144,51);

        ctx.beginPath();
        ctx.font="12px Arial";
        ctx.fillStyle = col;
        ctx.fillRect(6,18,posmyavg,16);

        ctx.beginPath();
        ctx.fillStyle = "#c05756";
        ctx.strokeStyle = "#c05756";
        ctx.moveTo(posavg, 12);
        ctx.lineTo(posavg, 17);
        ctx.stroke();
        ctx.fillText(avg,posavgtext,10);
        </script>';
            }
            if (!$tutor) {
                $dashimage .= '';
            }
            $dashimage .= '</div>';
        } else {
            $min = $max = $avg = $myavg = $pos_avg = $pos_max = $pos_min = $pos_myavg = 0;
            $dashimage = '<i style="color:#5A5A5A">' . get_string('nodata', 'report_myfeedback') . '</i>';
        }

        if ($tutor) {
            return $dashimage;
        }

        $cses = new stdClass();
        $cses->dash = '';
        $csesdue = $csesnon = $cseslate = $csesgraded = $csesfeed = $cseslow = 0;
        $cnam = $cd = $cn = $cl = $cg = $cf = $clo = $cvas = '';
        $scol1 = $scol2 = $scol3 = $scol4 = '';
        $height = 'initial';
        //Create the data for each module for the user
        foreach ($eachmodule as $z => $modavg) {
            if (count($modavg) > 1) {
                $allcourses[$z]['mean'] = array_sum($modavg) / count($modavg);
                $allcourses[$z]['lowest'] = min($modavg);
                $allcourses[$z]['highest'] = max($modavg);
            } else {
                $allcourses[$z]['mean'] = $modavg;
                $allcourses[$z]['lowest'] = $modavg;
                $allcourses[$z]['highest'] = $modavg;
            }
        }
        foreach ($allcourses as $gr_sub) {
            if (isset($gr_sub['due']) && isset($gr_sub['non']) && isset($gr_sub['late']) && isset($gr_sub['graded']) && isset($gr_sub['low']) && isset($gr_sub['feed'])) {
                $csesdue += $gr_sub['due'];
                $csesnon += $gr_sub['non'];
                $cseslate += $gr_sub['late'];
                $csesgraded += $gr_sub['graded'];
                $cseslow += $gr_sub['low'];
                $csesfeed += $gr_sub['feed'];
            }
        }

        foreach ($allcourses as $k => $cse) {
            $cdue = $cnon = $clate = $cgraded = $clow = 0;
            // if (isset($cse['currentuser']) && isset($cse['grademax']) && isset($cse['shortname'])) {
            $cdue = $cse['due'];
            $cnon = $cse['non'];
            $clate = $cse['late'];
            $cgraded = $cse['graded'];
            $clow = $cse['low'];
            $cfeed = $cse['feed'];
            $cse1 = 1; //$cse['currentuser'];
            $cse2 = 1; //$cse['grademax'];
            $cse3 = (isset($cse['shortname']) ? substr($cse['shortname'], 0, 20) : '-');
            $cse_min = 1; //number_format($cse['lowest'], 0);
            $cse_max = 1; //number_format($cse['highest'], 0);
            $cse_mean = 1; //number_format($cse['mean'], 0);
            $cse_min_pos = round($cse_min * 1.8) + 5;
            $cse_max_pos = round($cse_max * 1.8) + 5;
            $cse_mean_pos = round($cse_mean * 1.8) + 5;
            $cse_min_text = $cse_min_pos - 7;
            $cse_mean_text = $cse_mean_pos - 7;
            $cse_max_text = $cse_max_pos - 7;

            if ($cse_mean_text - $cse_min_text < 14) {
                $cse_mean_text = $cse_min_text + 14;
            }
            if ($cse_max_text - $cse_mean_text < 14) {
                $cse_max_text = $cse_mean_text + 14;
            }
            if ($cse_min_text < 1) {
                $cse_min_text = 1;
            }
            if ($cse_min_text > 138) {
                $cse_min_text = 138;
            }
            if ($cse_mean_text < 14) {
                $cse_mean_text = 14;
            }
            if ($cse_mean_text > 152) {
                $cse_mean_text = 152;
            }
            if ($cse_max_text < 28) {
                $cse_max_text = 28;
            }
            if ($cse_max_text > 166) {
                $cse_max_text = 166;
            }

            if ($cse1 && $cse2) {
                $u_avg = round($cse1 / $cse2 * 100);
                $u_pos = round($u_avg * 1.78) + 1;
                $col = '#8bc278';
                if ($u_avg < 50) {
                    $col = '#d69859';
                }
                if ($u_avg < 40) {
                    $col = '#c05756';
                }
                /* if ($cdue) {
                  if (number_format(($cnon / $cdue * 100), 0) > 25) {
                  $scol1 = 'amberlight';
                  } else if (number_format(($cnon / $cdue * 100), 0) > 50) {
                  $scol1 = 'redlight';
                  } else {
                  $scol1 = 'greenlight';
                  }
                  if (number_format(($clate / $cdue * 100), 0) > 25) {
                  $scol2 = 'amberlight';
                  } else if (number_format(($clate / $cdue * 100), 0) > 50) {
                  $scol2 = 'redlight';
                  } else {
                  $scol2 = 'greenlight';
                  }
                  }
                  if ($cgraded) {
                  if (number_format(($clow / $cgraded * 100), 0) > 25) {
                  $scol3 = 'amberlight';
                  } else if (number_format(($clow / $cgraded * 100), 0) > 50) {
                  $scol3 = 'redlight';
                  } else {
                  $scol3 = 'greenlight';
                  }
                  } */
                if (isset($eachavg[$userid])) {
                    $height = '61px';
                }

                $minortable = "<table style='display:none' class='accord' height=$height align='center'><tr><td>";
                $cd .= $minortable . "&nbsp;<br>" . $cdue . "</td></tr></table>";
                $cn .= $minortable . "&nbsp;<br><span class=$scol1>" . $cnon . "</span></td></tr></table>";
                $cl .= $minortable . "&nbsp;<br><span class=$scol2>" . $clate . "</span></td></tr></table>";
                $cg .= $minortable . "&nbsp;<br>" . $cgraded . "</td></tr></table>";
                //$cf .= $minortable . "&nbsp;<br>" . $cfeed . "</td></tr></table>";
                $clo .= $minortable . "&nbsp;<br><span class=$scol3>" . $clow . "</span></td></tr></table>";
                $cnam .= $minortable . "&nbsp;<br>" . $cse3 . "</td></tr></table>";
                $cvas .= '<table style="display:none" class="accord" align="center"><tr><td>
                <canvas id="myCanvas1' . $userid . $k . '" width="190" height="51" style="border:0px solid #d3d3d3;">'.
                get_string('browsersupport', 'report_myfeedback').'</canvas>
                <script>
                var c1 = document.getElementById("myCanvas1' . $userid . $k . '");
                var ctx1 = c1.getContext("2d");
                var min1 = ' . $cse_min . ';
                var max1 = ' . $cse_max . ';
                var avg1 = ' . $cse_mean . ';
                var myavg1 = ' . $u_avg . ';
                var posmin1 = ' . $cse_min_pos . ';
                var posmax1 = ' . $cse_max_pos . ';
                var posavg1 = ' . $cse_mean_pos . ';
                var posmintext1 = ' . $cse_min_text . ';
                var posmaxtext1 = ' . $cse_max_text . ';
                var posavgtext1 = ' . $cse_mean_text . ';
                var posmyavg1 = ' . $u_pos . ';
                var col1 = "' . $col . '";
                //ctx1.scale(0.8,1);
                ctx1.fillStyle = "#ccc";
                ctx1.strokeStyle = "#555";
                ctx1.font="12px Arial";
                ctx1.strokeRect(5,17,180,18);
                ctx1.fillRect(6,18,178,16);
                ctx1.fillStyle = "#555";
                ctx1.moveTo(posmin1, 12);
                ctx1.lineTo(posmin1, 17);
                ctx1.fillText(min1,posmintext1,10);

                ctx1.moveTo(posmax1, 12);
                ctx1.lineTo(posmax1, 17);
                ctx1.fillText(max1,posmaxtext1,10);

                ctx1.moveTo(77, 35);
                ctx1.lineTo(77, 40);
                ctx1.moveTo(95, 35);
                ctx1.lineTo(95, 40);
                ctx1.moveTo(113, 35);
                ctx1.lineTo(113, 40);
                ctx1.moveTo(131, 35);
                ctx1.lineTo(131, 40);
                ctx1.stroke();
                ctx1.fillText(40,70,51);
                ctx1.fillText(50,88,51);
                ctx1.fillText(60,106,51);
                ctx1.fillText(70,124,51);
                ctx1.fillText(myavg1+"/100",150,51);

                ctx1.beginPath();
                ctx1.fillStyle = col1;
                ctx1.fillRect(6,18,posmyavg1,16);

                ctx1.beginPath();
                ctx1.fillStyle = "#c05756";
                ctx1.strokeStyle = "#c05756";
                ctx1.moveTo(posavg1, 12);
                ctx1.lineTo(posavg1, 17);
                ctx1.stroke();
                ctx1.fillText(avg1,posavgtext1,10);
                </script></td></tr></table>';
            }
            // }
        }

        if (isset($eachavg[$userid])) {
            $height = '110px';
        }
        $br = "&nbsp;<br>";

        $maintable = "<td><table class='tutor-inner' height=$height align='center'><tr><td>";
        if (!$csesdue && !$csesgraded) {
            //$cses->dash .= "<td></td>";
            $cses->dash .= "<td></td>";
            $cses->dash .= "<td></td>";
            $cses->dash .= "<td></td>";
            $cses->dash .= "<td></td>";
            //$cses->dash .= "<td></td>";
            $cses->dash .= "<td></td>";
        } else {
            //$cses->dash .= $maintable . $dashimage . "</td></tr></table>" . $cvas . "</td>";
            $cses->dash .= "<td><table class='tutor-inner' height=$height align='center'><tr><td class=>" . $br . $csesdue . "</td></tr></table>" . $cd . "</td>";
            $cses->dash .= $maintable . $br . "<span class=$scol1>" . $csesnon . "</span></td></tr></table>" . $cn . "</td>";
            $cses->dash .= $maintable . $br . "<span class=$scol2>" . $cseslate . "</span></td></tr></table>" . $cl . "</td>";
            $cses->dash .= $maintable . $br . $csesgraded . "</td></tr></table>" . $cg . "</td>";
            //$cses->dash .= $maintable . $br . $csesfeed . "</td></tr></table>" . $cf . "</td>";
            $cses->dash .= $maintable . $br . "<span class=$scol3>" . $cseslow . "</span></td></tr></table>" . $clo . "</td>";
        }
        $allcourses['due'] = $csesdue;
        $allcourses['non'] = $csesnon;
        $allcourses['late'] = $cseslate;
        $allcourses['graded'] = $csesgraded;
        //$allcourses['feedback'] = $csesfeed;
        $allcourses['low'] = $cseslow;
        $cses->all = $allcourses;
        $cses->names = $cnam;

        return $cses;
    }

    public function get_prog_admin_dept_prog($dept_prog, $frommod = null) {
        global $CFG;
        require_once($CFG->libdir . '/coursecatlib.php');
        $cat = array();
        $prog = array();
        $tomod = array('dept' => '', 'prog' => '');
        foreach ($dept_prog as $dp) {
            $catid = ($dp->category ? $dp->category : 0);
            if ($catid) {
                $cat = coursecat::get($catid, $strictness = MUST_EXIST, $alwaysreturnhidden = true); //Use strictness so even hidden category names are shown without error
                if ($cat) {
                    $path = explode("/", $cat->path);
                    $parent = ($cat->parent ? coursecat::get($cat->parent, $strictness = MUST_EXIST, $alwaysreturnhidden = true) : $cat);
                    $progcat = array();
                    if ($cat->parent) {
                        $progcat = $cat;
                    } else {
                        $progcat = new stdClass();
                        $progcat->id = 1;
                        $progcat->name = get_string('uncategorized', 'report_myfeedback');
                    }
                    if (count($path) > 3) {
                        $parent = coursecat::get($path[1], $strictness = MUST_EXIST, $alwaysreturnhidden = true);
                        $progcat = coursecat::get($path[2], $strictness = MUST_EXIST, $alwaysreturnhidden = true);
                    }
                    $prog[$parent->id]['dept'] = $parent->name;
                    $tomod['dept'] = $parent->name;
                    $tomod['prog'] = $progcat->name;
                    $prog[$parent->id]['prog'][$progcat->id]['name'] = $progcat->name;
                    $prog[$parent->id]['prog'][$progcat->id]['mod'][$dp->id] = $dp->fullname;
                }
            }
            if ($frommod) {
                return $tomod;
            }
        }

        uasort($prog, function($a, $b) {
                    return strcasecmp($a['dept'], $b['dept']);
                });
        foreach ($prog as $pk => $prog1) {
            foreach ($prog1 as $pk1 => $prog2) {
                if ($pk1 == 'prog') {
                    uasort($prog[$pk]['prog'], function($a, $b) {
                                return strcasecmp($a['name'], $b['name']);
                            });
                    foreach ($prog2 as $ps => $progsort) {
                        foreach ($progsort as $ms => $modsort) {
                            if ($ms == 'mod') {
                                uasort($prog[$pk]['prog'][$ps]['mod'], function($a, $b) {
                                    return strcasecmp($a, $b);
                                });
                            }
                        }
                    }
                }
            }
        }
        return $prog;
    }

    /**
     * Get content to populate the feedback table     *
     * @return str The table of submission and feedback for the user referred to in the url after
     *         userid=
     */
    public function get_data($archive, $checkdb) {
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
                            gi.aggregationcoef AS weighting,
                            gi.display AS display,
                            gi.decimals AS decimals,
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
                            gi.grademax AS highestgrade,
                            -1 AS itemnumber,
                            gg.userid,
                            -1 AS groupid,
                            -1 AS assigngradeid,
                            -1 AS contextid, ";
        if (!$archive || ($archive && $checkdb > 1112)) {//when the new grading_areas table came in
            $sql .= "'' AS activemethod, ";
        }
        $sql .= "-1 AS nosubmissions,
                            '' AS status,
                            '' AS onlinetext,
                            -1 as sumgrades
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
                            gi.aggregationcoef AS weighting,
                            gi.display AS display,
                            gi.decimals AS decimals,
                            a.id AS assignid,
                            cm.id AS assignmentid,
                            a.teamsubmission as teamsubmission,
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
                            gi.grademax AS highestgrade,
                            -1 AS itemnumber,
                            gg.userid,
                            su.groupid as groupid,
                            ag.id AS assigngradeid,
                            con.id AS contextid, ";
            if (!$archive || ($archive && $checkdb > 1112)) {//when the new grading_areas table came in
                $sql .= "ga.activemethod AS activemethod, ";
            }
            $sql .= "a.nosubmissions AS nosubmissions,
                            su.status,
                            apc.value AS onlinetext,
                            -1 as sumgrades
                        FROM {course} c
                        JOIN {grade_items} gi ON c.id=gi.courseid
                             AND itemtype='mod' AND gi.itemmodule='assign' AND (gi.hidden != 1 AND gi.hidden < ?)
                        JOIN {grade_grades} gg ON gi.id=gg.itemid AND gg.userid = ?
                             AND (gg.hidden != 1 AND gg.hidden < ?)
                        JOIN {course_modules} cm ON gi.iteminstance=cm.instance AND c.id=cm.course
                        JOIN {context} con ON cm.id = con.instanceid AND con.contextlevel=70
                        JOIN {modules} m ON cm.module = m.id AND gi.itemmodule = m.name AND gi.itemmodule = 'assign'
                        JOIN {assign} a ON a.id=gi.iteminstance AND a.course=gi.courseid
                        JOIN mdl_assign_plugin_config apc on a.id = apc.assignment AND apc.name='enabled' AND plugin = 'onlinetext'
                        LEFT JOIN {assign_grades} ag ON a.id = ag.assignment AND ag.userid=$userid ";
            if (!$archive || ($archive && $checkdb > 1314)) {//when the new assign_user_flags table came in
                $sql .= "LEFT JOIN mdl_assign_user_flags auf ON a.id = auf.assignment AND auf.workflowstate = 'released'
                        AND  auf.userid = $userid OR a.markingworkflow = 0 ";
            }
            if (!$archive || ($archive && $checkdb > 1112)) {//when the new grading_areas table came in
                $sql .= "LEFT JOIN {grading_areas} ga ON con.id = ga.contextid ";
            }
            $sql .= "LEFT JOIN {assign_submission} su ON a.id = su.assignment AND su.userid = $userid
                        WHERE c.visible=1 AND c.showgrades = 1 AND cm.visible=1 ";
            array_push($params, $now, $userid, $now);
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
                               gi.aggregationcoef AS weighting,
                               gi.display AS display,
                               gi.decimals AS decimals,
                               a.id AS assignid,
                               cm.id AS assignmentid,
                               -1 AS teamsubmission,
                               gg.timecreated AS submissiondate,
                               a.timeclose AS duedate,
                               a.name AS assessmentlink, ";
            if (!$archive || ($archive && $checkdb > 1112)) {//when the new reviewattempt field was addedto the quiz table
                $sql .= "a.reviewattempt AS reviewattempt,
                         a.reviewmarks AS reviewmarks,
                         a.reviewoverallfeedback AS reviewoverallfeedback, ";
            } else {
                $sql .= "-1 AS reviewattempt,
                         -1 AS reviewmarks,
                         -1 reviewoverallfeedback, ";
            }
            $sql .= "-1 AS tiiobjid,
                               -1 AS subid,
                               -1 AS subpart,
                               -1 AS partname,
                               -1 AS usegrademark,
                               gg.feedback AS feedbacklink,
                               gi.grademax AS highestgrade,
                               -1 AS itemnumber,
                               gg.userid,
                               -1 AS groupid,
                               -1 AS assigngradeid,
                               con.id AS contextid, ";
            if (!$archive || ($archive && $checkdb > 1112)) {//when the new grading_areas table came in
                $sql .= "ga.activemethod AS activemethod, ";
            }
            $sql .= "-1 AS nosubmissions,
                               '' AS status,
                               '' AS onlinetext,
                               a.sumgrades as sumgrades
                        FROM {course} c
                        JOIN {grade_items} gi ON c.id=gi.courseid AND itemtype='mod'
                              AND gi.itemmodule = 'quiz' AND (gi.hidden != 1 AND gi.hidden < ?)
                        JOIN {grade_grades} gg ON gi.id=gg.itemid AND gg.userid = ?
                             AND (gg.hidden != 1 AND gg.hidden < ?)
                        JOIN {course_modules} cm ON gi.iteminstance=cm.instance AND c.id=cm.course
                        JOIN {context} con ON cm.id = con.instanceid AND con.contextlevel=70
                        JOIN {modules} m ON cm.module = m.id AND gi.itemmodule = m.name AND gi.itemmodule = 'quiz'
                        JOIN {quiz} a ON a.id=gi.iteminstance AND a.course=gi.courseid ";
            if (!$archive || ($archive && $checkdb > 1112)) {//when the new grading_areas table came in
                $sql .= "LEFT JOIN {grading_areas} ga ON con.id = ga.contextid ";
            }
            $sql .= "WHERE c.visible=1 AND c.showgrades = 1 AND cm.visible=1 ";
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
                               gi.aggregationcoef AS weighting,
                               gi.display AS display,
                               gi.decimals AS decimals,
                               a.id AS assignid,
                               cm.id AS assignmentid,
                               -1 AS teamsubmission, ";
            if (!$archive || ($archive && $checkdb > 1112)) {//when the new timemodified field was added to workshop_submission
                $sql .= "su.timemodified AS submissiondate, ";
            } else {
                $sql .= "su.timecreated AS submissiondate, ";
            }
            $sql .= "a.submissionend AS duedate,
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
                               gi.grademax AS highestgrade,
                               gi.itemnumber AS itemnumber,
                               gg.userid,
                               -1 AS groupid,
                               -1 AS assigngradeid,
                               con.id AS contextid, ";
            if (!$archive || ($archive && $checkdb > 1112)) {//when the new grading_areas table came in
                $sql .= "ga.activemethod AS activemethod, ";
            }
            $sql .= "a.nattachments AS nosubmissions,
                               '' AS status,
                               '' AS onlinetext,
                               -1 as sumgrades
                        FROM {course} c
                        JOIN {grade_items} gi ON c.id=gi.courseid AND itemtype='mod'
                              AND gi.itemmodule = 'workshop' AND (gi.hidden != 1 AND gi.hidden < ?)
                        JOIN {grade_grades} gg ON gi.id=gg.itemid AND gg.userid = ?
                             AND (gg.hidden != 1 AND gg.hidden < ?)
                        JOIN {course_modules} cm ON gi.iteminstance=cm.instance AND c.id=cm.course
                        JOIN {context} con ON cm.id = con.instanceid AND con.contextlevel=70
                        JOIN {modules} m ON cm.module = m.id AND gi.itemmodule = m.name AND gi.itemmodule = 'workshop'
                        JOIN {workshop} a ON gi.iteminstance = a.id AND a.course=gi.courseid ";
            if (!$archive || ($archive && $checkdb > 1112)) {//when the new timemodified field was added to workshop_submission
                $sql .= "LEFT JOIN {workshop_submissions} su ON a.id = su.workshopid AND su.example=0 AND su.authorid=$userid ";
            } else {
                $sql .= "LEFT JOIN {workshop_submissions} su ON a.id = su.workshopid AND su.example=0 AND su.userid=$userid ";
            }
            if (!$archive || ($archive && $checkdb > 1112)) {//when the new grading_areas table came in
                $sql .= "LEFT JOIN {grading_areas} ga ON con.id = ga.contextid ";
            }
            $sql .= "WHERE c.visible=1 AND c.showgrades = 1 AND cm.visible=1 ";
            array_push($params, $now, $userid, $now);
        }
        if ($this->mod_is_available("turnitintool")) {
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
                               gi.aggregationcoef AS weighting,
                               gi.display AS display,
                               gi.decimals AS decimals,
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
                               gi.grademax AS highestgrade,
                               -1 AS itemnumber,
                               gg.userid,
                               -1 AS groupid,
                               -1 AS assigngradeid,
                               con.id AS contextid, ";
            if (!$archive || ($archive && $checkdb > 1112)) {//when the new grading_areas table came in
                $sql .= "ga.activemethod AS activemethod, ";
            }
            $sql .= "t.numparts AS nosubmissions,
                               '' AS status,
                               '' AS onlinetext,
                               -1 as sumgrades
                        FROM {course} c
                        JOIN {grade_items} gi ON c.id=gi.courseid AND itemtype='mod'
                              AND gi.itemmodule = 'turnitintool' AND (gi.hidden != 1 AND gi.hidden < ?) 
                        JOIN {course_modules} cm ON gi.iteminstance=cm.instance AND c.id=cm.course
                        JOIN {context} con ON cm.id = con.instanceid AND con.contextlevel=70
                        JOIN {modules} m ON cm.module = m.id AND gi.itemmodule = m.name AND gi.itemmodule = 'turnitintool'
                        JOIN {grade_grades} gg ON gi.id=gg.itemid AND gg.userid = ? 
                             AND (gg.hidden != 1 AND gg.hidden < ?)
                        JOIN {turnitintool} t ON t.id=gi.iteminstance AND t.course=gi.courseid
                        LEFT JOIN {turnitintool_submissions} su ON t.id = su.turnitintoolid AND su.userid = $userid
                        LEFT JOIN {turnitintool_parts} tp ON su.submission_part = tp.id AND tp.dtpost < $now ";
            if (!$archive || ($archive && $checkdb > 1112)) {//when the new grading_areas table came in
                $sql .= "LEFT JOIN {grading_areas} ga ON con.id = ga.contextid ";
            }
            $sql .= "WHERE c.visible = 1 AND c.showgrades = 1 AND cm.visible=1 ";
            array_push($params, $now, $userid, $now);
        }
        if ($this->mod_is_available("turnitintooltwo")) {
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
                               gi.aggregationcoef AS weighting,
                               gi.display AS display,
                               gi.decimals AS decimals,
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
                               gi.grademax AS highestgrade,
                               -1 AS itemnumber,
                               gg.userid,
                               -1 AS groupid,
                               -1 AS assigngradeid,
                               con.id AS contextid, ";
            if (!$archive || ($archive && $checkdb > 1112)) {//when the new grading_areas table came in
                $sql .= "ga.activemethod AS activemethod, ";
            }
            $sql .= "t.numparts AS nosubmissions,
                               '' AS status,
                               '' AS onlinetext,
                               -1 as sumgrades
                        FROM {course} c
                        JOIN {grade_items} gi ON c.id=gi.courseid AND itemtype='mod'
                              AND gi.itemmodule = 'turnitintooltwo' AND (gi.hidden != 1 AND gi.hidden < ?)
                        JOIN {course_modules} cm ON gi.iteminstance=cm.instance AND c.id=cm.course
                        JOIN {context} con ON cm.id = con.instanceid AND con.contextlevel=70
                        JOIN {modules} m ON cm.module = m.id AND gi.itemmodule = m.name AND gi.itemmodule = 'turnitintooltwo'
                        JOIN {grade_grades} gg ON gi.id=gg.itemid AND gg.userid = ?
                             AND (gg.hidden != 1 AND gg.hidden < ?)
                        JOIN {turnitintooltwo} t ON t.id=gi.iteminstance AND t.course=gi.courseid
                        LEFT JOIN {turnitintooltwo_submissions} su ON t.id = su.turnitintooltwoid AND su.userid = $userid
                        LEFT JOIN {turnitintooltwo_parts} tp ON su.submission_part = tp.id AND tp.dtpost < $now ";
            if (!$archive || ($archive && $checkdb > 1112)) {//when the new grading_areas table came in
                $sql .= "LEFT JOIN {grading_areas} ga ON con.id = ga.contextid ";
            }
            $sql .= "WHERE c.visible = 1 AND c.showgrades = 1 AND cm.visible=1 ";
            array_push($params, $now, $userid, $now);
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
        $checkdb = 'current';
        $archive = false;
        if (isset($_SESSION['viewyear'])) {
            $checkdb = $_SESSION['viewyear']; // check if previous academinc year, If so change domain to archived db
        }
        if ($checkdb != 'current') {
            $archive = true;
        }

        if ($archivedomain = get_config('report_myfeedback', 'archivedomain')) {
            // we have the settings from the config database
        } else {
            $archivedomain = get_string('archivedomaindefault', 'report_myfeedback');
        }
        $archiveyear = substr_replace($checkdb, '-', 2, 0); //for building the archive link
        $x = 0;
        $exceltable = array();
        $reset_print_excel1 = null;
        $reset_print_excel = null;
        $tz = $this->user_timezone();
        $usertimezone = $this->timezone_letter($tz);
        $newwindowmsg = get_string('new_window_msg', 'report_myfeedback');
        $newwindowicon = '<img src="' . 'pix/external-link.png' . '" ' .
                ' alt="-" title="' . $newwindowmsg . '" rel="tooltip"/>';
        $relativegrademsg = get_string('relativegradedescription', 'report_myfeedback');
        $relectivenotesmsg = get_string('relectivenotesdescription', 'report_myfeedback');
        $infoicon = '<img src="' . 'pix/info.png' . '" ' .
                ' alt="-" title="' . $relectivenotesmsg . '" rel="tooltip"/>';
        $togglemsg = get_string('togglegradedescription', 'report_myfeedback');
        $infoiconrel = '<img src="' . 'pix/info.png' . '" ' .
                ' alt="-" title="' . $togglemsg . '" rel="tooltip"/>'; //Relative grade toggle
        $title = "<p>" . get_string('provisional_grades', 'report_myfeedback') . "</p>";
        // Print titles for each column: Assessment, Type, Due Date, Submission Date,
        // Submission/Feedback, Grade/Relative Grade.
        $table = "<table class=\"grades\" id=\"grades\" width=\"100%\">
                    <thead>
                            <tr class=\"tableheader\">
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
                get_string('gradetblheader_feedback', 'report_myfeedback') . "</th>
                                <th>" .
                get_string('gradetblheader_grade', 'report_myfeedback') . "</th>
                                                            <th>" .
                get_string('gradetblheader_range', 'report_myfeedback') . "</th>
                            <th><div class=\"t-rel off\">" .
                get_string('gradetblheader_bar', 'report_myfeedback') . " " ./* $infoiconrel .*/ "</div>
                <!--<div class=\"t-rel\">-->" . /* .
                  get_string('gradetblheader_relativegrade', 'report_myfeedback') . " " . $infoiconrel . "</div></th> */
                "</tr>
                        </thead><tfoot style='display: table-row-group'>
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
                                                <tbody>";
        // Setup the heading for the Comments table
        $commentstable = "<table id=\"feedbackcomments\" width=\"100%\" border=\"0\">
                <thead>
                            <tr class=\"tableheader\">
                                <th>" .
                get_string('gradetblheader_course', 'report_myfeedback') . "</th>
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
                                <th class=\"hidefromtutor\">" .
                get_string('gradetblheader_selfreflectivenotes', 'report_myfeedback') . " " . $infoicon . "</th>
                                <th>" .
                get_string('gradetblheader_feedback', 'report_myfeedback') . "</th>
                                <th>" .
                get_string('gradetblheader_viewed', 'report_myfeedback') . "</th>
                                                </tr>
                        </thead>
                                                <tbody>";

        $rs = $this->get_data($archive, $checkdb);
        if ($rs->valid()) {
            // The recordset contains records.
            foreach ($rs as $record) {
                //First we check if sections have any form of restrictions
                $show = 1;
                if (!$archive || ($archive && $checkdb > 1415)) {
                    if ($record->courseid) {
                        $record->id = $record->courseid;
                        $modinfo = get_fast_modinfo($record, $userid);
                        if ($record->assignmentid >= 1) {
                            $cm = $modinfo->get_cm($record->assignmentid);
                            if ($cm->uservisible) {
                                // User can access the activity.
                            } else {
                                $show = 0;
                            }
                        }
                    }
                }
                if ($show) {
                    // Put data into table for display here.
                    // Check permissions for course.
                    // Set the variables for each column.
                    $gradedisplay = $record->display;
                    $gradetype = $record->gradetype;
                    $cfggradetype = $CFG->grade_displaytype;
                    $feedbacktextlink = "";
                    $feedbacktext = "";
                    $submission = "-";
                    $feedbackfileicon = "";
                    $quizgrade = 'yes';
                    $assignment = $record->assessmentname;
                    $subs = 'allsubmissions';
                    $utp = 2;
		    $allparts = "";
                    $assignsingle = "&action=grade&rownum=0&userid=" . $userid;
                    if ($userid == $USER->id) {
                        $subs = 'submissions';
                        $utp = 1;
                        $assignsingle = "";
                    }
                    $assignmentname = "<a href=\"" . $CFG->wwwroot . "/mod/" . $record->assessmenttype .
                            "/view.php?id=" . $record->assignmentid . "\" title=\"" . $record->assessmentname .
                            "\" rel=\"tooltip\">" . $assignment .
                            "</a>";

                    if ($archive) {// If an archive year then change the domain
                        $assignmentname = "<a href=\"" . $archivedomain . $archiveyear . "/mod/" . $record->assessmenttype .
                                "/view.php?id=" . $record->assignmentid . "\" target=\"_blank\" title=\"" . $record->assessmentname .
                                "\" rel=\"tooltip\">" . $assignment .
                                "</a> $newwindowicon";
                    }
                    $duedate = ($record->duedate ? userdate($record->duedate) : "-");
                    $due_datesort = ($record->duedate ? $record->duedate : "-");

                    // Submission date.
                    $submissiondate = "-";
                    if ($record->submissiondate) {
                        $submissiondate = $record->submissiondate;
                    }

                    // Display information for each type of assessment activity.
                    $assessmenticon = "";
                    if ($record->assessmenttype) {
                        switch ($record->assessmenttype) {
                            case "assign":
                                $assessmenttype = get_string('moodle_assignment', 'report_myfeedback');
                                //Add the status as Moodle adds a Sub date when grades are overridden manually even without submission
                                if ($record->status == 'new') {
                                    $submissiondate = get_string('no_submission', 'report_myfeedback');
                                }
                                if ($record->teamsubmission == 1) {
                                    $assessmenttype .= " (" .
                                            get_string('groupwork', 'report_myfeedback') . ")";
                                    if (!is_numeric($submissiondate) || (!strlen($submissiondate) == 10)) {
                                        $submissiondate = $this->get_group_assign_submission_date(
                                                $userid, $record->assignid);
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
                                        $submissiondate = get_string('draft', 'report_myfeedback');
                                    }
                                    if ($submissiondate == "-") {
                                        $submissiondate = get_string('no_submission', 'report_myfeedback');
                                    }
                                } else {
                                    $assessmenttype .= " (" . get_string('offline_assignment', 'report_myfeedback') . ")";
                                    $submission = "-";
                                    $submissiondate = get_string('no_submission', 'report_myfeedback');
                                }
                                // Check whether an online PDF annotated feedback or any feedback file exists.
                                $onlinepdffeedback = false;
                                if (($record->assigngradeid && ($archive && $checkdb > 1314))
                                        || (!$archive && $record->assigngradeid)) {
                                    $onlinepdffeedback = $this->has_pdf_feedback_file(
                                            $record->gi_iteminstance, $userid, $record->assigngradeid);
                                }

                                $feedbacktext = $record->feedbacklink;
                                //implementing the rubric guide
                                if ($record->activemethod == "rubric") {
                                    if ($get_rubric = $this->rubrictext($userid, $record->courseid, $record->gi_iteminstance, 'assign')) {
                                        $feedbacktext.="<br/>&nbsp;<br/><span style=\"font-weight:bold;\"><img src=\"" .
                                                $CFG->wwwroot . "/report/myfeedback/pix/rubric.png\">" . get_string('rubrictext', 'report_myfeedback') . 
                                                "</span><br/>" . $get_rubric;
                                    }
                                }

                                //implementing the marking guide
                                if ($record->activemethod == "guide") {
                                    if ($get_guide = $this->marking_guide_text($userid, $record->courseid, $record->gi_iteminstance, 'assign')) {
                                        $feedbacktext.="<span style=\"font-weight:bold;\"><img src=\"" .
                                                $CFG->wwwroot . "/report/myfeedback/pix/guide.png\">" . get_string('markingguide', 'report_myfeedback') . 
                                                "</span><br/>" . $get_guide;
                                    }
                                }

                                // If there are any comments or other feedback (such as online PDF
                                // files, rubrics or marking guides)
                                if ($record->feedbacklink || $onlinepdffeedback || $feedbacktext) {
                                    $feedbacktextlink = "<a href=\"" . $CFG->wwwroot .
                                            "/mod/assign/view.php?id=" . $record->assignmentid . $assignsingle . "\">" .
                                            get_string('feedback', 'report_myfeedback') . "</a>";

                                    if ($archive) {// If an archive year then change the domain
                                        $feedbacktextlink = "<a href=\"" . $archivedomain . $archiveyear .
                                                "/mod/assign/view.php?id=" . $record->assignmentid . $assignsingle . "\" target=\"_blank\">" .
                                                get_string('feedback', 'report_myfeedback') . "</a> $newwindowicon";
                                    }

                                    //Add an icon if has pdf file
                                    if ($onlinepdffeedback) {
                                        $feedbackfile = get_string('hasfeedbackfile', 'report_myfeedback');
                                        $feedbackfileicon = ' <img src="' .
                                                $OUTPUT->pix_url('i/report') . '" ' .
                                                'class="icon" alt="' . $feedbackfile . '" title="' . $feedbackfile . '" rel="tooltip">';
                                    }
                                }

                                if (!$archive || ($archive && $checkdb > 1314)) {//when the new assign_user_flags table came in
                                    //Checking if the user was given an assignment extension
                                    $checkforextension = $this->check_assign_extension($userid, $record->assignid);
                                    if ($checkforextension) {
                                        $record->duedate = $checkforextension;
                                        $duedate = userdate($checkforextension);
                                        $due_datesort = $checkforextension;
                                    }
                                }
                                break;
                            case "turnitintool":
			    	$allparts = $record->grade && $record->nosubmissions > 1 ? get_string('allparts', 'report_myfeedback') : '';
                                $assignmentname .= ($record->partname) ? " (" . $record->partname . ")" : "";
                                $assessmenttype = get_string('turnitin_assignment', 'report_myfeedback');
                                if ($record->tiiobjid) {
                                    $feedbacktextlink = "<a href=\"" . $CFG->wwwroot . "/mod/" .
                                            $record->assessmenttype . "/view.php?id=" .
                                            $record->assignmentid . "&do=" . $subs . "\" target=\"_blank\">" .
                                            get_string('feedback', 'report_myfeedback') . "</a> $newwindowicon";

                                    if ($archive) {// If an archive year then change the domain
                                        $feedbacktextlink = "<a href=\"" . $archivedomain . $archiveyear . "/mod/" .
                                                $record->assessmenttype . "/view.php?id=" .
                                                $record->assignmentid . "&do=" . $subs . "\" target=\"_blank\">" .
                                                get_string('feedback', 'report_myfeedback') . "</a> $newwindowicon";
                                    }
                                }

                                // if ($record->assignmentid && $record->subpart && $record->tiiobjid &&
                                //   $record->assessmentname) {
                                // Not sure what utp is, but it seems to work when set to 2 for admin and 1
                                // for students.
                                // Link the submission to the plagiarism comparison report.
                                // If grademark marking is enabled.
                                if ($record->usegrademark == 1 && $record->tiiobjid) {
                                    // Link the submission to the gradebook.
                                    $feedbacktextlink = "<a href=\"" . $CFG->wwwroot . "/mod/" .
                                            $record->assessmenttype . "/view.php?id=" .
                                            $record->assignmentid . "&jumppage=grade&userid=" . $userid .
                                            "&utp=" . $utp . "&partid=" . $record->subpart . "&objectid=" . $record->tiiobjid .
                                            "\" target=\"_blank\">" . get_string('feedback', 'report_myfeedback') .
                                            "</a> $newwindowicon";

                                    if ($archive) {// If an archive year then change the domain
                                        $feedbacktextlink = "<a href=\"" . $archivedomain . $archiveyear . "/mod/" .
                                                $record->assessmenttype . "/view.php?id=" .
                                                $record->assignmentid . "&jumppage=grade&userid=" . $userid .
                                                "&utp=" . $utp . "&partid=" . $record->subpart . "&objectid=" . $record->tiiobjid .
                                                "\" target=\"_blank\">" . get_string('feedback', 'report_myfeedback') .
                                                "</a> $newwindowicon";
                                    }
                                }
                                break;
                            case "turnitintooltwo":
				$allparts = $record->grade && $record->nosubmissions > 1 ? get_string('allparts', 'report_myfeedback') : '';
                                $assignmentname .= ($record->partname) ? " (" . $record->partname . ")" : "";
                                $assessmenttype = get_string('turnitin_assignment', 'report_myfeedback');
                                if ($record->tiiobjid) {
                                    $feedbacktextlink = "<a href=\"" . $CFG->wwwroot . "/mod/" .
                                            $record->assessmenttype . "/view.php?id=" .
                                            $record->assignmentid . "&partid=" . $record->subpart . "\" target=\"_blank\">" .
                                            get_string('feedback', 'report_myfeedback') . "</a> $newwindowicon";

                                    if ($archive) {// If an archive year then change the domain
                                        $feedbacktextlink = "<a href=\"" . $archivedomain . $archiveyear . "/mod/" .
                                                $record->assessmenttype . "/view.php?id=" .
                                                $record->assignmentid . "&partid=" . $record->subpart . "\" target=\"_blank\">" .
                                                get_string('feedback', 'report_myfeedback') . "</a> $newwindowicon";
                                    }
                                }

                                //if ($record->assignmentid && $record->subpart && $record->tiiobjid &&
                                //      $record->assessmentname) {
                                // Not sure what utp is, but it seems to work when set to 2 for admin and 1
                                // for students.
                                // Link the submission to the plagiarism comparison report.
                                // If grademark marking is enabled.
                                if ($record->usegrademark == 1 && $record->tiiobjid) {
                                    // Link the submission to the gradebook.
                                    $feedbacktextlink = "<a href=\"" . $CFG->wwwroot . "/mod/" .
                                            $record->assessmenttype . "/view.php?id=" .
                                            $record->assignmentid . "&viewcontext=box&do=grademark&submissionid=" . $record->tiiobjid .
                                            "\" target=\"_blank\">" . get_string('feedback', 'report_myfeedback') .
                                            "</a> $newwindowicon";

                                    if ($archive) {// If an archive year then change the domain
                                        $feedbacktextlink = "<a href=\"" . $archivedomain . $archiveyear . "/mod/" .
                                                $record->assessmenttype . "/view.php?id=" .
                                                $record->assignmentid . "&viewcontext=box&do=grademark&submissionid=" . $record->tiiobjid .
                                                "\" target=\"_blank\">" . get_string('feedback', 'report_myfeedback') .
                                                "</a> $newwindowicon";
                                    }
                                }
                                break;
                            case "workshop":
                                $assessmenttype = get_string('workshop', 'report_myfeedback');

                                // Check whether an online PDF annotated feedback or any feedback file exists.
                                if ($record->subid > 0 && (!$archive || ($archive && $checkdb > 1213))) {//when the new feedbackauthorattachment field was added to workshop_submission
                                    $workshopfeedbackfile = $this->has_workshop_feedback_file($userid, $record->subid);
                                    $workshopfeedback = $this->has_workshop_feedback($userid, $record->subid, $record->assignid, $record->courseid, $record->itemnumber);
                                } else {
                                    $workshopfeedbackfile = null;
                                    $workshopfeedback = null;
                                }

                                //Add an icon if has pdf file
                                if ($workshopfeedbackfile) {
                                    $feedbackfile = get_string('hasfeedbackfile', 'report_myfeedback');
                                    $feedbackfileicon = ' <img src="' .
                                            $OUTPUT->pix_url('i/report') . '" ' .
                                            'class="icon" alt="' . $feedbackfile . '" title="' . $feedbackfile . '" rel="tooltip">';
                                }

                                $submission = "<a href=\"" . $CFG->wwwroot . "/mod/workshop/submission.php?cmid=" .
                                        $record->assignmentid . "&id=" . $record->subid . "\">" .
                                        get_string('submission', 'report_myfeedback') . "</a>";
                                if ($record->subid && (($record->feedbacklink && $record->itemnumber == 0) || $workshopfeedbackfile || $workshopfeedback)) {
                                    $feedbacktextlink = "<a href=\"" . $CFG->wwwroot . "/mod/workshop/submission.php?cmid=" .
                                            $record->assignmentid . "&id=" . $record->subid . "\">" .
                                            get_string('feedback', 'report_myfeedback') . "</a>";

                                    if ($archive) {// If an archive year then change the domain
                                        $feedbacktextlink = "<a href=\"" . $archivedomain . $archiveyear . "/mod/workshop/submission.php?cmid=" .
                                                $record->assignmentid . "&id=" . $record->subid . "\" target=\"_blank\">" .
                                                get_string('feedback', 'report_myfeedback') . "</a> $newwindowicon";
                                    }

                                    if ($record->feedbacklink && $record->itemnumber == 0) {
                                        $feedbacktext = '<b>'.get_string('tutorfeedback', 'report_myfeedback').'</b><br/>' . $record->feedbacklink;
                                    }
                                    $feedbacktext .= $workshopfeedback;
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
                                        $duedate = userdate($overrideextension);
                                    } else {
                                        $record->duedate = $overrideextensiongroup;
                                        $duedate = userdate($overrideextensiongroup);
                                        $due_datesort = $overrideextensiongroup;
                                    }
                                }
                                if ($overrideextension && !($overrideextensiongroup)) {
                                    $record->duedate = $overrideextension;
                                    $duedate = userdate($overrideextension);
                                    $due_datesort = $overrideextension;
                                }
                                if ($overrideextensiongroup && !($overrideextension)) {
                                    $record->duedate = $overrideextensiongroup;
                                    $duedate = userdate($overrideextensiongroup);
                                    $due_datesort = $overrideextensiongroup;
                                }

                                //Checking for quiz last attempt date as submission date
                                $submit = $this->get_quiz_submissiondate($record->assignid, $userid, $record->grade, $record->highestgrade, $record->sumgrades);
                                if ($submit) {
                                    $submissiondate = $submit;
                                }
                                if (!$feedbacktextlink = $this->get_quiz_attempts_link($record->assignid, $userid, $record->assignmentid, $archivedomain . $archiveyear, $archive, $newwindowicon)) {
                                    $feedbacktextlink = '';
                                }

                                //General feedback from tutor for a quiz attempt
                                $feedbacktext = $record->feedbacklink;

                                //implementing the overall feedback str in the quiz
                                $qid = intval($record->gi_iteminstance);
                                $grade2 = floatval($record->grade);
                                $feedback = $this->overallfeedback($qid, $grade2);
                                if ($feedback) {
                                    $feedbacktext.="<br/><span style=\"font-weight:bold;\">".get_string('overallfeedback', 'report_myfeedback')."</span><br/>" . $feedback;
                                }

                                $now = time();
                                $review1 = $record->reviewattempt;
                                $review2 = $record->reviewmarks;
                                $review3 = $record->reviewoverallfeedback;

                                //Show only when quiz is open
                                if ($now <= $record->duedate) {
                                    if ($review1 == 256 || $review1 == 4352 || $review1 == 65792 || $review1 == 69888) {
                                        //do nothing
                                    } else {
                                        $feedbacktextlink = '';
                                    }
                                    //Added the marks as well but if set to not show at all then this is just as hiding the grade in the gradebook and wont
                                    //show the review links or the feedback text if these are set.
                                    if ($review2 == 256 || $review2 == 4352 || $review2 == 65792 || $review2 == 69888) {
                                        $quizgrade = 'yes';
                                    } else {
                                        $quizgrade = 'noreview';
                                    }
                                    if ($review3 == 256 || $review3 == 4352 || $review3 == 65792 || $review3 == 69888) {
                                        //do nothing
                                    } else {
                                        $feedbacktext = "";
                                    }
                                } else {
                                    // When the quiz is closed what do you show
                                    if ($review1 == 16 || $review1 == 272 || $review1 == 4112 || $review1 == 4368 ||
                                            $review1 == 65552 || $review1 == 69748 || $review1 == 69904) {
                                        //do nothing
                                    } else {
                                        $feedbacktextlink = '';
                                    }
                                    if ($review2 == 16 || $review2 == 272 || $review2 == 4112 || $review2 == 4368 ||
                                            $review2 == 65552 || $review2 == 69748 || $review2 == 69904) {
                                        $quizgrade = 'yes';
                                    } else {
                                        $quizgrade = 'noreview';
                                    }
                                    if ($review3 == 16 || $review3 == 272 || $review3 == 4112 || $review3 == 4368 ||
                                            $review3 == 65552 || $review3 == 69748 || $review3 == 69904) {
                                        //do nothing
                                    } else {
                                        $feedbacktext = "";
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
                            $assignmentname = "<a href=\"" . $CFG->wwwroot . "/grade/report/user/index.php?id=" . $record->courseid .
                                    "&userid=" . $record->userid . "\" title=\"" . $record->assessmentname .
                                    "\" rel=\"tooltip\">" . $assignment . "</a>";

                            if ($archive) {// If an archive year then change the domain
                                $assignmentname = "<a href=\"" . $archivedomain . $archiveyear . "/grade/report/user/index.php?id=" . $record->courseid .
                                        "&userid=" . $record->userid . "\" target=\"_blank\" title=\"" . $record->assessmentname .
                                        "\" rel=\"tooltip\">" . $assignment . "</a> $newwindowicon";
                            }

                            $submission = "-";
                            $submissiondate = "-";
                            $duedate = "-";
                            $due_datesort = "-";
                            if ($record->feedbacklink) {
                                $feedbacktextlink = "<a href=\"" . $CFG->wwwroot .
                                        "/grade/report/user/index.php?id=" . $record->courseid . "&userid=" . $record->userid . "\">" .
                                        get_string('feedback', 'report_myfeedback') . "</a>";
                                $feedbacktext = $record->feedbacklink;

                                if ($archive) {// If an archive year then change the domain
                                    $feedbacktextlink = "<a href=\"" . $archivedomain . $archiveyear .
                                            "/grade/report/user/index.php?id=" . $record->courseid . "&userid=" . $record->userid . "\" target=\"_blank\">" .
                                            get_string('feedback', 'report_myfeedback') . "</a> $newwindowicon";
                                    $feedbacktext = $record->feedbacklink;
                                }
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
                    $submittedtime = $submissiondate;
                    // If no feedback or grade has been received don't display anything.
                    if (!($feedbacktextlink == '' && $record->grade == null)) {
                        // Mark late submissions in red.
                        $submissionmsg = "";
                        if (is_numeric($submissiondate) && (strlen($submissiondate) == 10)) {
                            $submittedtime = $submissiondate;
                            $submissiondate = userdate($submissiondate);
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

			$alerticon ='';
                        //Late message if submission late
                        if ($submissiondate != "-" && $due_datesort != "-" && $submittedtime > $record->duedate) {
                            if ($submissionmsg == "" && $submissiondate != get_string('no_submission', 'report_myfeedback') &&
                                    $submissiondate != get_string('draft', 'report_myfeedback')) {
                                $submissionmsg = get_string('late_submission_msg', 'report_myfeedback');
                                if ($record->duedate) {
                                    $a = new stdClass();
                                    $a->late = format_time($submittedtime - $record->duedate);
                                    $submissionmsg .= get_string('waslate', 'report_myfeedback', $a); 
                                    if ($record->assessmenttype == "assign") {                                        
                                        $alerticon = ($record->status == 'submitted' ? '<img class="smallicon" src="' . $OUTPUT->pix_url('i/warning', 'core') . '" ' . 'class="icon" alt="-" title="' .
                                                        $submissionmsg . '" rel="tooltip"/>' : '');
                                    } else {
                                        $alerticon = '<img class="smallicon" src="' . $OUTPUT->pix_url('i/warning', 'core') . '" ' . 'class="icon" alt="-" title="' .
                                                $submissionmsg . '" rel="tooltip"/>';
                                    }
                                }
                            }
                            $submissiondate = "<span style=\"color: #990000;\">" . $submissiondate . " $alerticon</span>";
                        }
                        if ($record->status == "draft") {
                            $submissiondate = "<span style=\"color: #990000;\">" . get_string('draft', 'report_myfeedback') . "</span>";
                        }
                        $shortname = $record->shortname;
                        //Display grades in the format defined within each Moodle course
                        $realgrade = null;
                        $mingrade = null;
                        $availablegrade = null;
                        $graderange = null;
                        if ($record->gi_itemtype == 'mod') {
                            if ($quizgrade != 'noreview') {
                                //Get the grade display type and grade type 
                                switch ($gradetype) {
                                    case GRADE_TYPE_SCALE:
                                        $realgrade = $this->get_grade_scale($record->gradeitemid, $userid, $record->courseid, $record->grade);
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
                                                    " (" . $this->get_fraction($record->grade / $record->highestgrade * 100, $record->courseid, $record->decimals) . "%)";
                                            $mingrade = $this->get_min_grade_letter($record->gradeitemid, $userid, $record->courseid) . " (0)";
                                            $graderange = $this->get_all_grade_letters($record->courseid);
                                            $availablegrade = $this->get_available_grade_letter($record->courseid, $record->grade) .
                                                    " (" . $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals) / $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals) * 100 . "%)";
                                        } else if (($gradedisplay == GRADE_DISPLAY_TYPE_LETTER_REAL) ||
                                                ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                                $cfggradetype == GRADE_DISPLAY_TYPE_LETTER_REAL)) {
                                            $realgrade = $this->get_grade_letter($record->courseid, $record->grade / $record->highestgrade * 100) .
                                                    " (" . $this->get_fraction($record->grade, $record->courseid, $record->decimals) . ")";
                                            $mingrade = $this->get_min_grade_letter($record->courseid, $record->grade) . " (0)";
                                            $graderange = $this->get_all_grade_letters($record->courseid);
                                            $availablegrade = $this->get_available_grade_letter($record->courseid, $record->grade) .
                                                    " (" . $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals) . ")";
                                        } else if (($gradedisplay == GRADE_DISPLAY_TYPE_PERCENTAGE) ||
                                                ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                                $cfggradetype == GRADE_DISPLAY_TYPE_PERCENTAGE)) {
                                            $realgrade = $this->get_fraction($record->grade / $record->highestgrade * 100, $record->courseid, $record->decimals) . "%";
                                            $mingrade = "0";
                                            $availablegrade = $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals) / $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals) * 100 . "%";
                                        } else if (($gradedisplay == GRADE_DISPLAY_TYPE_PERCENTAGE_LETTER) ||
                                                ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                                $cfggradetype == GRADE_DISPLAY_TYPE_PERCENTAGE_LETTER)) {
                                            $realgrade = $this->get_fraction($record->grade / $record->highestgrade * 100, $record->courseid, $record->decimals) . "% (" .
                                                    $this->get_grade_letter($record->courseid, $record->grade / $record->highestgrade * 100) . ")";
                                            $mingrade = "0 (" . $this->get_min_grade_letter($record->courseid, $record->grade) . ")";
                                            $graderange = $this->get_all_grade_letters($record->courseid);
                                            $availablegrade = $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals) / $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals) * 100 . "% (" .
                                                    $this->get_available_grade_letter($record->courseid, $record->grade) . ")";
                                        } else if (($gradedisplay == GRADE_DISPLAY_TYPE_PERCENTAGE_REAL) ||
                                                ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                                $cfggradetype == GRADE_DISPLAY_TYPE_PERCENTAGE_REAL)) {
                                            $realgrade = $this->get_fraction($record->grade / $record->highestgrade * 100, $record->courseid, $record->decimals) . "% (" .
                                                    $this->get_fraction($record->grade, $record->courseid, $record->decimals) . ")";
                                            $mingrade = "0 (0)";
                                            $availablegrade = $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals) / $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals) * 100 . "% (" .
                                                    $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals) . ")";
                                        } else if (($gradedisplay == GRADE_DISPLAY_TYPE_REAL_LETTER) ||
                                                ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                                $cfggradetype == GRADE_DISPLAY_TYPE_REAL_LETTER)) {
                                            $realgrade = $this->get_fraction($record->grade, $record->courseid, $record->decimals) . " (" .
                                                    $this->get_grade_letter($record->courseid, $record->grade / $record->highestgrade * 100) . ")";
                                            $mingrade = "0 (" . $this->get_min_grade_letter($record->courseid, $record->grade) . ")";
                                            $graderange = $this->get_all_grade_letters($record->courseid);
                                            $availablegrade = $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals) . " (" .
                                                    $this->get_available_grade_letter($record->courseid, $record->grade) . ")";
                                        } else if (($gradedisplay == GRADE_DISPLAY_TYPE_REAL_PERCENTAGE) ||
                                                ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                                $cfggradetype == GRADE_DISPLAY_TYPE_REAL_PERCENTAGE)) {
                                            $realgrade = $this->get_fraction($record->grade, $record->courseid, $record->decimals) . " (" .
                                                    $this->get_fraction($record->grade / $record->highestgrade * 100, $record->courseid, $record->decimals) . "%)";
                                            $mingrade = "0 (0)";
                                            $availablegrade = $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals) . " (" .
                                                    $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals) / $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals) * 100 . "%)";
                                        } else if (($gradedisplay == GRADE_DISPLAY_TYPE_REAL) ||
                                                ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                                $cfggradetype == GRADE_DISPLAY_TYPE_REAL)) {
                                            $realgrade = $this->get_fraction($record->grade, $record->courseid, $record->decimals);
                                            $mingrade = "0";
                                            $availablegrade = $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals);
                                        } else {
                                            $realgrade = $this->get_fraction($record->grade, $record->courseid, $record->decimals);
                                            $mingrade = "0";
                                            $availablegrade = $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals);
                                        }
                                        break;
                                }
                            }
                        }

                        if ($record->gi_itemtype == 'manual') {
                            switch ($gradetype) {
                                case GRADE_TYPE_SCALE:
                                    $realgrade = $this->get_grade_scale($record->gradeitemid, $userid, $record->courseid, $record->grade);
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
                                                " (" . $this->get_fraction($record->grade / $record->highestgrade * 100, $record->courseid, $record->decimals) . "%)";
                                        $mingrade = $this->get_min_grade_letter($record->courseid, $record->grade) . " (0)";
                                        $graderange = $this->get_all_grade_letters($record->courseid);
                                        $availablegrade = $this->get_available_grade_letter($record->courseid, $record->grade) .
                                                " (" . $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals) / $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals) * 100 . "%)";
                                    } else if (($gradedisplay == GRADE_DISPLAY_TYPE_LETTER_REAL) ||
                                            ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                            $cfggradetype == GRADE_DISPLAY_TYPE_LETTER_REAL)) {
                                        $realgrade = $this->get_grade_letter($record->courseid, $record->grade / $record->highestgrade * 100) .
                                                " (" . $this->get_fraction($record->grade, $record->courseid, $record->decimals) . ")";
                                        $mingrade = $this->get_min_grade_letter($record->courseid, $record->grade) . " (0)";
                                        $graderange = $this->get_all_grade_letters($record->courseid);
                                        $availablegrade = $this->get_available_grade_letter($record->courseid, $record->grade) .
                                                " (" . $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals) . ")";
                                    } else if (($gradedisplay == GRADE_DISPLAY_TYPE_PERCENTAGE) ||
                                            ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                            $cfggradetype == GRADE_DISPLAY_TYPE_PERCENTAGE)) {
                                        $realgrade = $this->get_fraction($record->grade / $record->highestgrade * 100, $record->courseid, $record->decimals) . "%";
                                        $mingrade = "0";
                                        $availablegrade = $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals) / $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals) * 100 . "%";
                                    } else if (($gradedisplay == GRADE_DISPLAY_TYPE_PERCENTAGE_LETTER) ||
                                            ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                            $cfggradetype == GRADE_DISPLAY_TYPE_PERCENTAGE_LETTER)) {
                                        $realgrade = $this->get_fraction($record->grade / $record->highestgrade * 100, $record->courseid, $record->decimals) . "% (" .
                                                $this->get_grade_letter($record->courseid, $record->grade / $record->highestgrade * 100) . ")";
                                        $mingrade = "0 (" . $this->get_min_grade_letter($record->courseid, $record->grade) . ")";
                                        $graderange = $this->get_all_grade_letters($record->courseid);
                                        $availablegrade = $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals) / $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals) * 100 . "% (" .
                                                $this->get_available_grade_letter($record->courseid, $record->grade) . ")";
                                    } else if (($gradedisplay == GRADE_DISPLAY_TYPE_PERCENTAGE_REAL) ||
                                            ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                            $cfggradetype == GRADE_DISPLAY_TYPE_PERCENTAGE_REAL)) {
                                        $realgrade = $this->get_fraction($record->grade / $record->highestgrade * 100, $record->courseid, $record->decimals) . "% (" .
                                                $this->get_fraction($record->grade, $record->courseid, $record->decimals) . ")";
                                        $mingrade = "0 (0)";
                                        $availablegrade = $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals) / $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals) * 100 . "% (" .
                                                $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals) . ")";
                                    } else if (($gradedisplay == GRADE_DISPLAY_TYPE_REAL_LETTER) ||
                                            ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                            $cfggradetype == GRADE_DISPLAY_TYPE_REAL_LETTER)) {
                                        $realgrade = $this->get_fraction($record->grade, $record->courseid, $record->decimals) . " (" .
                                                $this->get_grade_letter($record->courseid, $record->grade / $record->highestgrade * 100) . ")";
                                        $mingrade = "0 (" . $this->get_min_grade_letter($record->courseid, $record->grade) . ")";
                                        $graderange = $this->get_all_grade_letters($record->courseid);
                                        $availablegrade = $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals) . " (" .
                                                $this->get_available_grade_letter($record->courseid, $record->grade) . ")";
                                    } else if (($gradedisplay == GRADE_DISPLAY_TYPE_REAL_PERCENTAGE) ||
                                            ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                            $cfggradetype == GRADE_DISPLAY_TYPE_REAL_PERCENTAGE)) {
                                        $realgrade = $this->get_fraction($record->grade, $record->courseid, $record->decimals) . " (" .
                                                $this->get_fraction($record->grade / $record->highestgrade * 100, $record->courseid, $record->decimals) . "%)";
                                        $mingrade = "0 (0)";
                                        $availablegrade = $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals) . " (" .
                                                $this->get_fraction($record->highestgrade / $record->highestgrade * 100, $record->courseid, $record->decimals) . "%)";
                                    } else if (($gradedisplay == GRADE_DISPLAY_TYPE_REAL) ||
                                            ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                            $cfggradetype == GRADE_DISPLAY_TYPE_REAL)) {
                                        $realgrade = $this->get_fraction($record->grade, $record->courseid, $record->decimals);
                                        $mingrade = "0";
                                        $availablegrade = $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals);
                                    } else {
                                        $realgrade = $this->get_fraction($record->grade, $record->courseid, $record->decimals);
                                        $mingrade = "0";
                                        $availablegrade = $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals);
                                    }
                            }
                        }

                        //horizontal bar only needed if grade type is value and not letter or scale
                        if ($gradetype == GRADE_TYPE_VALUE && $gradedisplay != GRADE_DISPLAY_TYPE_LETTER) {
                            //$rel_graph = $this->get_activity_min_and_max_grade($record->gradeitemid, $record->grade); //relative grade toggle
                            $grade_percent = ($record->highestgrade ? $record->grade / $record->highestgrade * 100 : $record->grade);
                            if ($grade_percent > 100) {
                                $grade_percent = 100;
                            }

                            $horbar = "<td style=\"width:150px\"><div class=\"horizontal-bar t-rel off\"><div style=\"width:" . $grade_percent .
                                    "%\" class=\"grade-bar\">&nbsp;</div></div>"
                                    . "<div class=\"available-grade t-rel off\">"
                                    . $this->get_fraction($record->grade, $record->courseid, $record->decimals) . "/" .
                                    $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals) . "</div>
                                <!--<div class=\"t-rel\"><div>-->"/* . $rel_graph */ . "<!--</div></div>--></td>"; //relative grade toggle
                        }
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

                        $grade_tbl2 = $realgrade;
                        if ($gradetype != GRADE_TYPE_SCALE) {
                            if (($gradedisplay == GRADE_DISPLAY_TYPE_REAL) || ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT && $cfggradetype == GRADE_DISPLAY_TYPE_REAL)) {
                                $grade_tbl2 = $realgrade . '/' . $availablegrade;
                            }
                        }

                        //If no grade is given then don't display 0
                        if ($record->grade == NULL) {
                            $grade_tbl2 = "-";
                            $realgrade = "-";
                        }

                        //implement weighting
                        $weighting = '-';
                        if ($record->weighting != 0 && $gradetype == 1) {
                            $weighting = number_format(($record->weighting * 100), 0, '.', ',') . '%';
                        }

                        //Data for the viewed feedback
                        $viewed = "<span style=\"color:#c05756;\"> &#10006;</span>";
                        $viewexport = 'no';
                        if (!$archive || ($archive && $checkdb > 1415)) {//when the new log store table came in
                            $check = $this->check_viewed_gradereport($record->contextid, $record->assignmentid, $userid, $record->courseid, $record->assessmentname);
                            if ($check != 'no') {
                                $viewed = "<span style=\"color:#8bc278;\">&#10004;</span> " . $check;
                                $viewexport = $check;
                            }
                            if ($record->gi_itemtype == 'manual') {

                                $checkmanual = $this->check_viewed_manualitem($userid, $record->courseid, $record->gradeitemid);
                                if ($checkmanual != 'no') {
                                    $viewed = "<span style=\"color:#8bc278;\">&#10004;</span> " . $checkmanual;
                                    $viewexport = $checkmanual;
                                }
                            }
                        }

                        //Only show if Module tutor or Departmental admin has their access to the specific course
                        //Personal tutor would only get this far if they had that access so no need to set condition for them
                        $libcoursecontext = context_course::instance($record->courseid);
                        $usercontext = context_user::instance($record->userid);

                        $modulename = ($record->coursename && $record->courseid ? "<a href=\"" . $CFG->wwwroot .
                                        "/course/view.php?id=" . $record->courseid . "\" title=\"" . $record->coursename .
                                        "\" rel=\"tooltip\">" . $shortname . "</a>" : "&nbsp;");
                        if ($archive) {// If an archive year then change the domain
                            $modulename = ($record->coursename && $record->courseid ? "<a href=\"" . $archivedomain . $archiveyear .
                                            "/course/view.php?id=" . $record->courseid . "\" target=\"_blank\" title=\"" . $record->coursename .
                                            "\" rel=\"tooltip\">" . $shortname . "</a> $newwindowicon" : "&nbsp;");
                        }

                        if ($show && ($userid == $USER->id || $USER->id == 2 ||
                                has_capability('moodle/user:viewdetails', $usercontext) ||
                                has_capability('report/myfeedback:progadmin', $libcoursecontext, $USER->id, $doanything = false) ||
                                has_capability('report/myfeedback:modtutor', $libcoursecontext, $USER->id, $doanything = false))) {
                            $table .= "<tr>";
                            $table .= "<td class=\"ellip\">" . $modulename . "</td>";
                            $table .= "<td>" . $assessmenticon . $assignmentname . "</td>";
                            $table .= "<td>" . $assessmenttype . "</td>";
                            $table .= "<td data-sort=$due_datesort>" . $duedate . "</td>";
                            $table .= "<td data-sort=$sub_datesort>" . $submissiondate . "</td>";
                            $table .= "<td>" . $feedbacktextlink . "</td>"; // Including links to marking guide or rubric.
                            $table .= "<td>" . $realgrade . $allparts . "</td>";
                            $table .= "<td title=\"$graderange\" rel=\"tooltip\">" . $mingrade . " - " . $availablegrade . "</td>";
                            $table .= $horbar;
                            $table .= "</tr>";

                            // The full excel downloadable table
                            $exceltable[$x]['Module'] = $record->shortname;
                            $exceltable[$x]['Assessment'] = $record->assessmentname;
                            $exceltable[$x]['Type'] = $assessmenttype;
                            $exceltable[$x]['Due date'] = $duedate;
                            $exceltable[$x]['Submission date'] = $submissiondate;
                            $exceltable[$x]['Grade'] = $realgrade . $allparts;
                            $exceltable[$x]['Grade range'] = $mingrade . " - " . $availablegrade;
                            $exceltable[$x]['Full feedback'] = $feedbacktext;
                            $exceltable[$x]['Viewed'] = $viewexport;
                            ++$x;

                            $fileicon = ' <img src="' . $OUTPUT->pix_url('i/edit', 'core') . '" ' . 'class="icon" alt="edit">';
                            //The reflective notes and turnitin feedback
                            $tdid = $record->gradeitemid;
                            if (!$instn = $record->subpart) {
                                $instn = null;
                            }
                            $tutorhidden = 'nottutor';
                            $notes = '';
                            $noteslink = '<a href="#" class="addnote" data-toggle="modal" title="' . get_string('addnotestitle', 'report_myfeedback') . '" rel="tooltip" data-uid="' .
                                    $userid . '" data-gid="' . $tdid . '" data-inst="' . $instn . '">' . get_string('addnotes', 'report_myfeedback') . '</a>';

                            if (!$archive || ($archive && $checkdb > 1415)) {
                                if ($usernotes = $this->get_notes($userid, $record->gradeitemid, $instn)) {
                                    $notes = nl2br($usernotes);
                                    $noteslink = '<a href="#" class="addnote" data-toggle="modal" title="' . get_string('editnotestitle', 'report_myfeedback') . '" rel="tooltip" data-uid="' .
                                            $userid . '" data-gid="' . $tdid . '" data-inst="' . $instn . '">' . get_string('editnotes', 'report_myfeedback') . '</a>';
                                }
                            }
                            //Only the user can add/edit their own self-reflective notes
                            if ($USER->id != $userid) {
                                $noteslink = '';
                            }
                            //Module tutor who are not personal tutor for the student can't see the notes
                            if (has_capability('report/myfeedback:modtutor', $libcoursecontext, $USER->id, $doanything = false) && !has_capability('moodle/user:viewdetails', $usercontext)) {
                                $notes = '';
                                $tutorhidden = 'hidefromtutor';
                                echo '<style> .hidefromtutor{display:none}</style>';
                            }

                            $feedbacklink = '<i>' . get_string('addfeedback', 'report_myfeedback') . '<br><a href="#" class="addfeedback" data-toggle="modal" title="' . get_string('addfeedbacktitle', 'report_myfeedback') . '" rel="tooltip" data-uid="' .
                                    $userid . '" data-gid="' . $tdid . '" data-inst="' . $instn . '">' . get_string('copyfeedback', 'report_myfeedback') . '</a>.</i>';
                            $selfadded = 0;
                            $studentcopy = '';
                            $studentadded = 'notstudent';
                            if ($archive && $checkdb <= 1415) {//Have to be done here so feedback text don't add the link to the text
                                $noteslink = '';
                                $feedbacklink = '';
                            }
                            if (!$archive || ($archive && $checkdb > 1415)) {
                                if ($record->assessmenttype == 'turnitintool' || $record->assessmenttype == 'turnitintooltwo') {
                                    if ($nonmoodlefeedback = $this->get_turnitin_feedback($userid, $record->gradeitemid, $instn)) {
                                        if ($nonmoodlefeedback->feedback) {
                                            if ($nonmoodlefeedback->modifierid) {
                                                $selfadded = $nonmoodlefeedback->modifierid;
                                            }
                                            if ($userid == $selfadded) {
                                                $studentadded = 'student';
                                                $studentcopy = '<b><u>' . get_string('studentaddedfeedback', 'report_myfeedback') . '</u></b><br>';
                                                if ($USER->id == $userid) {
                                                    $studentcopy = '<b><u>' . get_string('selfaddedfeedback', 'report_myfeedback') . '</u></b><br>';
                                                }
                                            }

                                            $feedbacklink = '<a href="#" class="addfeedback" data-toggle="modal" title="' . get_string('editfeedbacktitle', 'report_myfeedback') . '" rel="tooltip" data-uid="' .
                                                    $userid . '" data-gid="' . $tdid . '" data-inst="' . $instn . '">' . $fileicon . '</a>';
                                            if ($archive) {//Have to be done here again so feedback text don't add the link to the text
                                                $feedbacklink = '';
                                            }
                                            $feedbacktext = $studentcopy . "<div id=\"feed-val" . $tdid . $instn . "\">" . nl2br($nonmoodlefeedback->feedback) . "</div><div style=\"float:right;\">" . $feedbacklink . "</div>";
                                        } else {
                                            $feedbacktext = $feedbacklink;
                                        }
                                    } else {
                                        $feedbacktext = $feedbacklink;
                                    }
                                }
                            }

                            if (!$archive || ($archive && $checkdb > 1415)) {

                                //The self-reflective notes bootstrap modal
                                echo '<div style="height: 0; width: 0;" class="container">
                            <div style="display: none;" id="Abs2" class="modal hide fade">
                            <div class="modal-header"><a class="close" data-dismiss="modal"> X </a>
                            <h3>' . get_string("addeditnotes", "report_myfeedback") . '</h3>
                            </div>
                            <div class="modal-body">
                            <form  method="POST"  id="notesform" action="reflectivenotes.php" >
                            <textarea id="notename" class="autoexpand" name="notename" wrap="hard" rows="4" data-min-rows="4" cols="60" value=""></textarea>
                            <input type="hidden" name="grade_id" id="grade_id" value="" />                            
                            <input type="hidden" name="instance1" id="instance1" value="" />
                            <input type="hidden" name="userid" id="user_id" value="" /> 
                            <input type="submit" id="submitnotes" value="' . get_string("savenotes", "report_myfeedback") . '" />
                            </form>
                            
                            </div>
                            <div class="modal-footer"><a class="btn" href="#" data-dismiss="modal">Close</a></div>
                            </div>
                            </div>';

                                //The non-moodle(turnitin) feedback bootstrap modal
                                echo '<div style="height: 0; width: 0;" class="container">
                            <div style="display: none;" id="Abs1" class="modal hide fade">
                            <div class="modal-header"><a class="close" data-dismiss="modal"> X </a>
                            <h3>' . get_string("addeditfeedback", "report_myfeedback") . '</h3>
                            </div>
                            <div class="modal-body">
                            <form  method="POST"  id="feedform" action="nonmoodlefeedback.php" >
                            <textarea id="feedname" class="autoexpand" name="feedname" wrap="hard" rows="4" data-min-rows="4" cols="60" value=""></textarea>
                            <input type="hidden" name="grade_id2" id="grade_id2" value="" />
                            <input type="hidden" name="instance" id="instance" value="" />
                            <input type="hidden" name="userid2" id="user_id2" value="" /> 
                            <input type="submit" id="submitfeed" value="' . get_string("savefeedback", "report_myfeedback") . '" />
                            </form>
                            </div>
                            <div class="modal-footer"><a class="btn" href="#" data-dismiss="modal">' . get_string("closebuttontitle") . '</a></div>
                            </div>
                            </div>';
                            }

                            //The feedback comments table
                            if (trim($feedbacktext) != '' || trim($feedbackfileicon) != '' || trim($feedbacktextlink) != '') {
                                $commentstable .= "<tr>";
                                $commentstable .= "<td class=\"ellip\">" . $modulename . "</td>";
                                $commentstable .= "<td style=\"text-align:left;\">" . $assessmenticon . $assignmentname . "</td>";
                                $commentstable .= "<td style=\"text-align:left;\">" . $assessmenttype . "</td>";
                                $commentstable .= "<td data-sort=$sub_datesort>" . $submissiondate . "</td>";
                                $commentstable .= "<td>" . $grade_tbl2 . $allparts . "</td>";
                                $commentstable .= "<td class=\"feed-val-2 $studentadded\" width=\"400\" style=\"border:3px solid #ccc; text-align:left;\">" . $feedbacktext . "</td>";
                                $commentstable .= "<td class=\"note-val-2 $tutorhidden \"><div id=\"note-val" . $tdid . $instn . "\">" . $notes . "</div><div>" . $noteslink . "</div></td>";
                                $commentstable .= "<td>" . $feedbacktextlink . $feedbackfileicon . "</td>";
                                $commentstable .= "<td>" . $viewed . "</td>";
                                $commentstable .= "</tr>";
                            }
                        }
                    }
                }
            }
            $rs->close(); // Close the recordset!
        }
        $table .= "</tbody>                
                    </table>";
        $commentstable.="</tbody></table>";

        // Buttons for filter reset, download and print
        $reset_print_excel = "<div style=\"float:right;\" class=\"buttonswrapper\"><input id=\"tableDestroy\" type=\"button\" value=\"" .
                get_string('reset_table', 'report_myfeedback') . "\">
                <input id=\"exportexcel\" type=\"button\" value=\"" . get_string('export_to_excel', 'report_myfeedback') . "\">
                <input id=\"reportPrint\" type=\"button\" value=\"" . get_string('print_report', 'report_myfeedback') . "\"></div>";
        //<input id=\"toggle-grade\" type=\"button\" value=\"" . get_string('togglegrade', 'report_myfeedback') . "\"> //relative grade toggle
        $reset_print_excel1 = "<div style=\"float:right;\" class=\"buttonswrapper\"><input id=\"ftableDestroy\" type=\"button\" value=\"" .
                get_string('reset_table', 'report_myfeedback') . "\">
                <input id=\"exportexcel\" type=\"button\" value=\"" . get_string('export_to_excel', 'report_myfeedback') . "\">
                <input id=\"reportPrint\" type=\"button\" value=\"" . get_string('print_report', 'report_myfeedback') . "\"></div>";

        $_SESSION['exp_sess'] = $exceltable;
        $_SESSION['myfeedback_userid'] = $userid;
        $_SESSION['tutor'] = 'no';
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
                    "<h2>" . get_string('tutor_messages', 'report_myfeedback') . "</h2>";
            $this->content->text = $personaltutor;
        }
        return $this->content;
    }

}
