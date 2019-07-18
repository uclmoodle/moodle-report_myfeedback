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
 *            Prof John Mitchell <j.mitchell@ucl.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

/**
 * This function extends the navigation with the My feedback report.
 *
 * @param global_navigation $navigation The navigation node to extend
 */
function report_myfeedback_extend_navigation(global_navigation $navigation) {
    // TODO: Segun Babalola. Where does $course come from?
    // TODO: Check that pix_icon is not deprecated.
    $url = new moodle_url('/report/myfeedback/index.php', array('course' => $course->id));
    $navigation->add(get_string('pluginname', 'report_myfeedback'), $url, null, null, null, new pix_icon('i/report', ''));
}

/**
 * This function extends the My settings >> activity with the My feedback report.
 *
 * @param global_navigation $navigation The navigation node to extend
 * @param stdClass $course The course object
 * @param stdClass $user The user object
 */
function report_myfeedback_extend_navigation_user($navigation, $user, $course) {//backward compatibility to v2.8 and earlier versions
    $context = context_user::instance($user->id, MUST_EXIST);
    $url = new moodle_url('/report/myfeedback/index.php', array('userid' => $user->id));
    $navigation->add(get_string('pluginname', 'report_myfeedback'), $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
}

/**
 * This function extends the navigation with the My feedback report for users you have access to.
 *
 * @param global_navigation $navigation The navigation node to extend
 * @param stdClass $user The user object
 * @param stdClass $context The context
 * @param stdClass $course The course object
 * @param stdClass $coursecontext The context of the course
 */
function report_myfeedback_extend_navigation_user_settings($navigation, $user, $context, $course, $coursecontext) {
    $url = new moodle_url('/report/myfeedback/index.php', array('userid' => $user->id));
    $navigation->add(get_string('pluginname', 'report_myfeedback'), $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
}

/**
 * This function extends the navigation in course admin >> reports with the My feedback report.
 *
 * @param global_navigation $navigation The navigation node to extend
 * @param stdClass $course The course object
 * @param stdClass $context The context of the course
 */
function report_myfeedback_extend_navigation_course($navigation, $course, $context) {
    global $USER;
    $url = has_capability('report/myfeedback:modtutor', $context) ? new moodle_url('/report/myfeedback/index.php', array('userid' => $USER->id, 'currenttab' => 'mymodules')) :
            new moodle_url('/report/myfeedback/index.php', array('userid' => $USER->id));
    $navigation->add(get_string('pluginname', 'report_myfeedback'), $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
}

/**
 * This function extends the navigation with the My feedback report to the user's profile.
 *
 * @param core_user\output\myprofile\tree $tree, The node to add to
 * @param stdClass $user The user object
 * @param bool $iscurrentuser Whether the logged-in user is current user
 * @param stdClass $course The course object
 */
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
     * @global stdClass $remotedb The global remote moodle_database instance.
     * @return void|bool Returns true when finished setting up $DB or $remotedb. Returns void when $DB has already been set.
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
     * @param int $userid The user id
     * @param int $subid The workshop submission id
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

    /**
     * Gets and returns any workshop feedback
     * 
     * @global stdClass $remotedb The database object
     * @global stdClass $CFG The global config
     * @param int $userid The user id
     * @param int $subid The workshop submission id
     * @param int $assignid The workshop id
     * @param int $cid The course id
     * @param int $itemnumber The grade_item itemnumber
     * @return string All the feedback information
     */
    public function has_workshop_feedback($userid, $subid, $assignid, $cid, $itemnumber) {
        global $remotedb, $CFG;
        $feedback = '';

        //Get the other feedback that comes when graded so will have a grade id otherwise it is not unique
        $peer = "SELECT DISTINCT wg.id, wg.peercomment, wa.reviewerid, wa.feedbackreviewer, w.conclusion
        FROM {workshop} w
        JOIN {workshop_submissions} ws ON ws.workshopid=w.id AND w.course=? AND w.useexamples=0      
        JOIN {workshop_assessments} wa ON wa.submissionid=ws.id AND ws.authorid=?
        AND ws.workshopid=? AND ws.example=0 AND wa.submissionid=?
        LEFT JOIN {workshop_grades} wg ON wg.assessmentid=wa.id AND wa.submissionid=?";
        $arr = array($cid, $userid, $assignid, $subid, $subid);
		//TODO: fix this! If won't work here, use: if ($rs->valid()) {}
        if ($assess = $remotedb->get_recordset_sql($peer, $arr)) {
            if ($itemnumber == 1) {
                foreach ($assess as $a) {
                    if ($a->feedbackreviewer && strlen($a->feedbackreviewer) > 0) {
                        $feedback = (strip_tags($a->feedbackreviewer) ? "<b>" . get_string('tutorfeedback', 'report_myfeedback') . "</b><br/>" . strip_tags($a->feedbackreviewer) : '');
                    }
                }
				$assess->close();
                return $feedback;
            }
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
                    $feedback .= strip_tags($feedback) ? '<br/>' : '';
                    $feedback .= '<strong>' . get_string('peerfeedback', 'report_myfeedback') . '</strong>';
                }
                foreach ($asse as $as) {
                    if ($as->feedbackauthor && $as->reviewerid != $userid) {
                        $feedback .= (strip_tags($as->feedbackauthor) ? '<br/>' . strip_tags($as->feedbackauthor) : '');
                    }
                }
                foreach ($asse as $cub1) {
                    if ($cub1->feedbackauthor && $cub1->reviewerid == $userid) {
                        $self = true;
                    }
                }
                if ($self) {
                    $feedback .= strip_tags($feedback) ? '<br/>' : '';
                    $feedback .= '<strong>' . get_string('selfassessment', 'report_myfeedback') . '</strong>';
                }
                foreach ($asse as $as1) {
                    if ($as1->feedbackauthor && $as1->reviewerid == $userid) {
                        $feedback .= (strip_tags($as1->feedbackauthor) ? '<br/>' . strip_tags($as1->feedbackauthor) : '');
                    }
                }
            }
        }

        //get comments strategy type
        $sql_c = "SELECT wg.id as gradeid, wa.reviewerid, a.description, peercomment
          FROM {workshopform_accumulative} a
          JOIN {workshop_grades} wg ON wg.dimensionid=a.id AND wg.strategy='comments'
          JOIN {workshop_assessments} wa ON wg.assessmentid=wa.id AND wa.submissionid=?
          JOIN {workshop_submissions} ws ON wa.submissionid=ws.id
          AND ws.workshopid=? AND ws.example=0 AND ws.authorid = ?
          ORDER BY wa.reviewerid";
        $params_c = array($subid, $assignid, $userid);
        $c = 0;
        if ($commentscheck = $remotedb->get_records_sql($sql_c, $params_c)) {
            foreach ($commentscheck as $com) {
                if (strip_tags($com->description)) {
                    $c = 1;
                }
            }
            if ($c) {
                $feedback .= strip_tags($feedback) ? '<br/>' : '';
                $feedback .= "<br/><strong>" . get_string('comments', 'report_myfeedback') . "</strong>";
            }
            foreach ($commentscheck as $ts) {
                $feedback .= strip_tags($ts->description) ? "<br/><b>" . $ts->description : '';
                $feedback .= strip_tags($ts->description) ? "<br/><strong>" . get_string('comment', 'report_myfeedback') . "</strong>: " . strip_tags($ts->peercomment) . "<br/>" : '';
            }
        }

        //get accumulative strategy type
        $sql_a = "SELECT wg.id as gradeid, wa.reviewerid, a.description, wg.grade as score, a.grade, peercomment
          FROM {workshopform_accumulative} a
          JOIN {workshop_grades} wg ON wg.dimensionid=a.id AND wg.strategy='accumulative'
          JOIN {workshop_assessments} wa ON wg.assessmentid=wa.id AND wa.submissionid=?
          JOIN {workshop_submissions} ws ON wa.submissionid=ws.id
          AND ws.workshopid=? AND ws.example=0 AND ws.authorid = ?
          ORDER BY wa.reviewerid";
        $params_a = array($subid, $assignid, $userid);
        $a = 0;
        if ($accumulativecheck = $remotedb->get_records_sql($sql_a, $params_a)) {
            foreach ($accumulativecheck as $acc) {
                if (strip_tags($acc->description && $acc->score)) {
                    $a = 1;
                }
            }
            if ($a) {
                $feedback .= strip_tags($feedback) ? '<br/>' : '';
                $feedback .= "<br/><b>" . get_string('accumulativetitle', 'report_myfeedback') . "</b>";
            }
            foreach ($accumulativecheck as $tiv) {
                $feedback .= strip_tags($acc->description && $acc->score) ? "<br/><b>" . strip_tags($tiv->description) . "</b>: " . get_string('grade', 'report_myfeedback') . round($tiv->score) . "/" . round($tiv->grade) : '';
                $feedback .= strip_tags($acc->description && $acc->score) ? "<br/><b>" . get_string('comment', 'report_myfeedback') . "</b>: " . strip_tags($tiv->peercomment) . "<br/>" : '';
            }
        }

        //get the rubrics strategy type
        $sql = "SELECT wg.id as gradeid, wa.reviewerid, r.description, l.definition, peercomment
          FROM {workshopform_rubric} r
          LEFT JOIN {workshopform_rubric_levels} l ON (l.dimensionid = r.id) AND r.workshopid=?
          JOIN {workshop_grades} wg ON wg.dimensionid=r.id AND l.grade=wg.grade and wg.strategy='rubric'
          JOIN {workshop_assessments} wa ON wg.assessmentid=wa.id AND wa.submissionid=?
          JOIN {workshop_submissions} ws ON wa.submissionid=ws.id
          AND ws.workshopid=? AND ws.example=0 AND ws.authorid = ?
          ORDER BY wa.reviewerid";
        $params = array($assignid, $subid, $assignid, $userid);
        $r = 0;
        if ($rubriccheck = $remotedb->get_records_sql($sql, $params)) {
            foreach ($rubriccheck as $rub) {
                if (strip_tags($rub->description && $rub->definition)) {
                    $r = 1;
                }
            }
            if ($r) {
                $feedback .= strip_tags($feedback) ? '<br/>' : '';
                $feedback .= "<br/><span style=\"font-weight:bold;\"><img src=\"" .
                        $CFG->wwwroot . "/report/myfeedback/pix/rubric.png\">" . get_string('rubrictext', 'report_myfeedback') . "</span>";
            }
            foreach ($rubriccheck as $rec) {
                $feedback .= strip_tags($rec->description && $rec->definition) ? "<br/><b>" . strip_tags($rec->description) . "</b>: " . strip_tags($rec->definition) : '';
                $feedback .= strip_tags($rec->peercomment) ? "<br/><b>" . get_string('comment', 'report_myfeedback') . "</b>: " . strip_tags($rec->peercomment) . "<br/>" : '';
            }
        }

        //get the numerrors strategy type
        $sql_n = "SELECT wg.id as gradeid, wa.reviewerid, n.description, wg.grade, n.grade0, n.grade1, peercomment
          FROM {workshopform_numerrors} n
          JOIN {workshop_grades} wg ON wg.dimensionid=n.id AND wg.strategy='numerrors'
          JOIN {workshop_assessments} wa ON wg.assessmentid=wa.id AND wa.submissionid=?
          JOIN {workshop_submissions} ws ON wa.submissionid=ws.id
          AND ws.workshopid=? AND ws.example=0 AND ws.authorid = ?
          ORDER BY wa.reviewerid";
        $params_n = array($subid, $assignid, $userid);
        $n = 0;
        if ($numerrorcheck = $remotedb->get_records_sql($sql_n, $params_n)) {
            foreach ($numerrorcheck as $num) {
                if ($num->gradeid) {
                    $n = 1;
                }
            }
            if ($n) {
                $feedback .= strip_tags($feedback) ? '<br/>' : '';
                $feedback .= "<br/><b>" . get_string('numerrortitle', 'report_myfeedback') . "</b>";
            }
            foreach ($numerrorcheck as $err) {
                $feedback .= $err->gradeid ? "<br/><b>" . strip_tags($err->description) . "</b>: " . ($err->grade < 1.0 ? strip_tags($err->grade0) : strip_tags($err->grade1)) : '';
                $feedback .= $err->gradeid ? "<br/><b>" . get_string('comment', 'report_myfeedback') . "</b>: " . strip_tags($err->peercomment) . "<br/>" : '';
            }
        }

        return $feedback;
    }

    /**
     * Check whether the user has been granted an assignment extension
     * 
     * @param int $userid The user id
     * @param int $assignment The assignment id
     * @return int Due date of the extension or false if no extension
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
     * Check if the user got an override to extend completion date/time
     * 
     * @global stdClass $remotedb The database object
     * @param int $assignid The quiz id
     * @param int $userid The user id
     * @return int Datetime of the override or false if no override
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
     * @global stdClass $remotedb The database object
     * @param int $assignid The quiz id
     * @param int $userid The user id
     * @return int extension date or false if no extension
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
     * @global stdClass $remotedb The database object
     * @param int $assignid The assignment id
     * @param int $userid The user id
     * @param int $grade The quiz grade of the user
     * @param int $availablegrade The highest grade the user could get for that quiz
     * @param int $sumgrades The amount of questions marked out of
     * @return int The date of the attempt or false if not attempted.
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
     * @param str $archivedomain_year The academic year eg (14-15)
     * @param bool $archive Whether it's an archived academic year
     * @param str  $newwindowicon An icon image with tooltip showing that it opens in another window
     * @param bool $reviewattempt whetehr the user can review the quiz attempt
     * @param bool $sameuser Whether the logged-in user is the user quiz being referred to
     * @return str Any comments left by a marker on a Turnitin Assignment via the Moodle Comments
     *         feature (not in Turnitin), each on a new line
     */
    public function get_quiz_attempts_link($quizid, $userid, $quizurlid, $archivedomain_year, $archive, $newwindowicon, $reviewattempt, $sameuser) {
        global $CFG, $remotedb;
        $sqlcount = "SELECT count(attempt) as attempts, max(id) as id
                        FROM {quiz_attempts} qa
                        WHERE quiz=? and userid=?";
        $params = array($quizid, $userid);
        $attemptcount = $remotedb->get_records_sql($sqlcount, $params);
        $out = array();
        $url = '';
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
                    $attemptstext = ($a > 1) ? get_string('reviewlastof', 'report_myfeedback', $a) . $newicon : get_string('reviewaattempt', 'report_myfeedback', $a) . $newicon;
                    $out[] = html_writer::link($url, $attemptstext, $attr);
                }
            }
            if (!$reviewattempt) {
                if ($sameuser) {//Student can only see the attempt if it is set in review options so link to result page instead.
                    return "<a href=" . $CFG->wwwroot . "/mod/quiz/view.php?id=" . $quizurlid . ">" . get_string('feedback', 'report_myfeedback') . "</a>";
                } else {//Tutor can still see the attempt if review is off.
                    return "<a href=" . $url . ">" . get_string('feedback', 'report_myfeedback') . "</a>";
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
	 *
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
		$comments->close();
        return implode($br, $out);
    }

    /**
     * Check if the grades and feedback have been viewed in the gradebook
     * or results page since the last grade or feedback has been released
     *
     * @param int $contextid The context id of the course module
     * @param int $assignmentid The course module id
     * @param int $userid The id of the user
     * @param int $courseid The id of the course
     * @param str $itemname The name of the assessment/course module
     * @return str date viewed or no if not viewed
     */
    public function check_viewed_gradereport($contextid, $assignmentid, $userid, $courseid, $itemname) {
        global $remotedb;
        $sql = "SELECT min(timecreated) as timecreated
                FROM {logstore_standard_log}
                WHERE contextid=? AND contextinstanceid=? AND userid=? AND courseid=?
                AND timecreated > ?";
        $sqlone = "SELECT min(timecreated) as timecreated
                FROM {logstore_standard_log}
                WHERE component=? AND action=? AND userid=? AND courseid=?
                AND timecreated > ?";
        $sqltwo = "SELECT max(g.timemodified) as timemodified
                FROM {grade_grades} g 
                JOIN {grade_items} gi ON g.itemid=gi.id AND g.userid=? 
                AND gi.courseid=? AND gi.itemname=?
                JOIN {course_modules} cm ON gi.iteminstance=cm.instance AND cm.course=?
                AND cm.id=?";
        $paramstwo = array($userid, $courseid, $itemname, $courseid, $assignmentid);
        $gradeadded = $remotedb->get_record_sql($sqltwo, $paramstwo);
        if ($gradeadded) {
            $params = array($contextid, $assignmentid, $userid, $courseid, $gradeadded->timemodified);
            $viewreport = $remotedb->get_record_sql($sql, $params);
            if ($viewreport && $viewreport->timecreated > $gradeadded->timemodified) {
                return date('d-m-Y H:i', $viewreport->timecreated);
            }

            $paramsone = array('gradereport_user', 'viewed', $userid, $courseid, $gradeadded->timemodified);
            $userreport = $remotedb->get_record_sql($sqlone, $paramsone);
            if ($userreport && $userreport->timecreated > $gradeadded->timemodified) {
                return date('d-m-Y H:i', $userreport->timecreated);
            }
        }
        return 'no';
    }

    /**
     * Check if the grades and feedback have been viewed in the gradebook
     * since the last grade or feedback has been released but for manual items 
	 *
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
                $dateviewed = date('d-m-Y', $userreport->timecreated);
                if ($gradeadded->timemodified < $userreport->timecreated) {
                    return $dateviewed;
                }
            }
        }
        return 'no';
    }

    /**
     * Get the overall feedback for quiz for the user based on the grade
     * they currently have in the gradebook for their best attempt
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
     * @param int $itemmodule The module currently being queried
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
            AND gi.courseid=? AND gg.userid=? AND gi.iteminstance=? AND status=?";
        $params = array($userid, $itemmodule, $courseid, $userid, $iteminstance, 1);
        $rubrics = $remotedb->get_recordset_sql($sql, $params);
        $out = '';
        if ($rubrics) {
            foreach ($rubrics as $rubric) {
                if ($rubric->description || $rubric->definition) {
                    $out .= "<strong>" . $rubric->description . ": </strong>" . $rubric->definition . "<br/>";
                }
            }
        }
		$rubrics->close();
        return $out;
    }

    /**
     * Get the Marking guide feedback
     * 
     * @param int $userid The id of the user who's feedback being viewed
     * @param int $courseid The course the Marking guide is being checked for
     * @param int $iteminstance The instance of the module item 
     * @param int $itemmodule The module currently being queried
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
		$guides->close();
        return $out;
    }

    /**
     * Get the scales for a manual item 
     * 
     * @global stdClass $remotedb DB object
     * @param int $itemid The grade item id
     * @param int $userid The user id
     * @param int $courseid The course id
     * @param int $grade The user's grade for that manual item
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
	 *
     * @global stdClass $remotedb The DB object
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
	 *
     * @global stdClass $remotedb The DB object
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
	 *
     * @global stdClass $remotedb The DB object
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
	 *
     * @global stdClass $remotedb DB object
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
	 *
     * @global stdClass $remotedb The DB object
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
	 *
     * @global stdClass $remotedb The DB object
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
	 *
     * @global stdClass $remotedb The DB object
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
	 *
     * @param float $grade The grade 
     * @param int $cid The Course id
     * @param int $decimals The number of decimals if set in grade items 
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
	 *
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
	 *
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
	 *
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
	 *
     * @global stdClass $remotedb DB object
     * @return int Personal tutor role id
     */
    public function get_personal_tutor_id() {
        global $remotedb;
        $sql = "SELECT roleid FROM {role_context_levels} WHERE contextlevel = ? limit 1";
        $params = array(30);
        $tutor = $remotedb->get_record_sql($sql, $params);
        return $tutor ? $tutor->roleid : 0;
    }

    /**
     * Return whether the user has the capability in any context
	 *
     * @global stdClass $remotedb DB object
     * @param uid The user id
     * @param cap The capability to check for
     * @return true or false
     */
    public function get_dashboard_capability($uid, $cap, $context = NULL) {
        global $remotedb;
        $sql = "SELECT DISTINCT min(c.id) as id, r.roleid FROM {role_capabilities} c
            JOIN {role_assignments} r ON r.roleid=c.roleid ";
        if ($context) {
            $sql .= "AND r.contextid = $context ";
        }
        $sql .= "AND userid = ? AND capability = ? GROUP BY c.id, r.roleid";
        $params = array($uid, $cap);
        $capy = $remotedb->get_record_sql($sql, $params);
        return $capy ? $capy->roleid : 0;
    }
	
    /**
     * Return the export to excel and print buttons
	 *
     * @return the HTML to display the export to excel and print buttons
     */	
	public function export_print_buttons(){
		$printmsg = get_string('print_msg', 'report_myfeedback');
		return "<div class=\"buttonswrapper\"><input class=\"x_port\" id=\"exportexcel\" type=\"button\" value=\"" . get_string('export_to_excel', 'report_myfeedback') . "\">
                <input class=\"reportPrint\" id=\"reportPrint\" type=\"button\" value=\"" . get_string('print_report', 'report_myfeedback') . "\" title=\"" . $printmsg . "\" rel=\"tooltip\"></div>";
	}
	
	/**
    * Return all categories relevant to the search category name
	*
    * @global stdClass $remotedb The DB object.
    * @global stdClass $CFG The global configuration instance.
    * @param str $search The user input entered to search on.
    * @param str $reporttype The report type where the search is being used
    * @return string Table with the list of categories with names containing the search term.
    */
	public function search_all_categories($search, $reporttype, $hideor = false){
		global $remotedb, $CFG;
		
		$mycategories = array();
		$table = "";		
        //The search form
		if($hideor == false){
        	echo '<span class="searchusage"> ' . get_string("or", "report_myfeedback");
		}
		echo' </span><form method="POST" id="reportsearchform" class="report_form" action="">
                            <input type="text" id="searchu" name="searchusage" class="searchusage" value="' . get_string("searchcategory", "report_myfeedback") . '" />
                            <input type="submit" id="submitsearch" value="' . get_string("search", "report_myfeedback") . '" />
							<input type="hidden" name="categoryid" id="categoryid" value="-1" /> 
                            </form>';
        //We first trim the search to remove leading and trailing spaces
        $search = trim($search);
        //If there is a search input and it's not the text that tells the user to enter search input
        if ($search !="" && $search != get_string("searchcategory", "report_myfeedback")) {
            $searchu = addslashes(strip_tags($search));//we escape the quotes etc and strip all html tags
            $result = array();
            $result = $remotedb->get_records_sql("SELECT id, name, parent FROM {course_categories}
                    WHERE visible = 1 AND name LIKE '%". $searchu ."%'");
			if ($result) {
				foreach ($result as $a) {
					if ($a->id) {
						$mycategories[$a->id][0] = "<a href=\"" . $CFG->wwwroot . "/report/myfeedback/index.php?currenttab=usage&reporttype=$reporttype&categoryid=" . $a->id . "\" title=\"" .
                                                    $a->email . "\" rel=\"tooltip\">" . $a->name . "</a>";
						if ($a->parent){
							$categoryname = $this->get_category_name($a->parent);
						}
						$mycategories[$a->id][1] = "<a href=\"" . $CFG->wwwroot . "/report/myfeedback/index.php?currenttab=usage&reporttype=$reporttype&categoryid=" . $a->id . "\" title=\"" .
                                                    $a->email . "\" rel=\"tooltip\">" . $categoryname . "</a>";								
					}
				}
			}

		$help_icon_cat = ' <img src="' . 'pix/info.png' . '" ' .
                ' alt="-" title="' . get_string('usagesrchheader_catname_info', 'report_myfeedback') . '" rel="tooltip"/>';
		$help_icon_pcat = ' <img src="' . 'pix/info.png' . '" ' .
                ' alt="-" title="' . get_string('usagesrchheader_pcatname_info', 'report_myfeedback') . '" rel="tooltip"/>';
		$table = "<table class=\"userstable\" id=\"userstable\">
                    <thead>
                            <tr class=\"tableheader\">
                                <th class=\"tblname\">" . get_string('name') .  $help_icon_cat .
                "</th><th class=\"tbldepartment\">" . get_string('parent', 'report_myfeedback') . " " . get_string('category', 'report_myfeedback') .  $help_icon_pcat . "</th></tr>
                        </thead><tbody>";
		//Add the records to the table here. These records were stored in the myusers array.
        foreach ($mycategories as $result) {
            if (isset($result[0])) {
                $table.= "<tr>";
                $table.="<td>" . $result[0] . "</td>";
				$table.="<td>" . $result[1] . "</td>";
                $table.="</tr>";
            }
        }
		$table.="</tbody><tfoot><tr><td></td><td></td></tr></tfoot></table><br />";
		}
		//blank the search form when you click in it
		echo "<script>$('#searchu').on('click', function() {
          if ($(this).val() == '" . get_string('searchcategory', 'report_myfeedback') . "') {
             $(this).val('');
          }
        })</script>";
        return $table;
	}
	
	/**
    * Return all courses within a category and its subcategories
	*
    * @global stdClass $remotedb The DB object.
	* @param int $catid The category id.
    * @return array of courses within that category and its subcategories
    */
	public function get_category_courses($catid){
		global $remotedb;
		$sql = "SELECT distinct c.id, c.shortname, c.fullname, c.summary, c.visible FROM {course} c, {course_categories} cat ";
		if($catid > 0){
			$sql .= "WHERE c.category = cat.id AND cat.path LIKE '%/".$catid."' OR cat.path LIKE '%/".$catid."/%' ";
		}
		$sql .= "ORDER BY cat.sortorder, c.sortorder";
		return $remotedb->get_records_sql($sql);
	}
	
	/**
    * Return all courses relevant to the search name / email address (max 10)
	*
    * @global stdClass $remotedb The DB object.
    * @global stdClass $CFG The global configuration instance.
    * @param str $search The user input entered to search on.
    * @param str $reporttype The report type where the search is being used
    * @return string Table with the list of course shortnames and fullnames containing the search term.
    */
	public function search_all_courses($search, $reporttype){
		global $remotedb, $CFG;
		
		$mycourses = array();
						
        //The search form
        echo ' <form method="POST" id="reportsearchform" class="report_form" action="">
                            <input type="text" id="searchu" name="searchusage" class="searchusage" value="' . get_string("searchcourses", "report_myfeedback") . '" />
                            <input type="submit" id="submitsearch" value="' . get_string("search", "report_myfeedback") . '" />
							<input type="hidden" name="courseid" id="courseid" value="0" /> 
                            </form>';
        //We first trim the search to remove leading and trailing spaces

        $search = trim($search);
        //If there is a search input and it's not the text that tells the user to enter search input
        if ($search != "" && $search != get_string("searchcourses", "report_myfeedback")) {
            $searchu = addslashes(strip_tags($search));//we escape the quotes etc and strip all html tags
            $result = array();
             $result = $remotedb->get_records_sql("SELECT id,shortname, fullname, category FROM {course}
                    WHERE shortname LIKE '%". $searchu ."%' or fullname LIKE '%". $searchu ."%'");
			if ($result) {
				foreach ($result as $a) {
					if ($a->id) {
						$mycourses[$a->id][0] = "<a href=\"" . $CFG->wwwroot . "/report/myfeedback/index.php?currenttab=usage&reporttype=$reporttype&courseid=" . $a->id . "\" title=\"" .
                                                    $a->email . "\" rel=\"tooltip\">" . $a->fullname . " (" . $a->shortname . ")</a>";
						if ($a->category){
							$categoryname = $this->get_category_name($a->category);
						}
						$categoryreporttype = str_replace("course", "category", $reporttype);
						$mycourses[$a->id][1] = "<a href=\"" . $CFG->wwwroot . "/report/myfeedback/index.php?currenttab=usage&reporttype=$categoryreporttype&categoryid=" . $a->category . "\" title=\"" .
                                                    $a->email . "\" rel=\"tooltip\">" . $categoryname . "</a>";								
					}
				}
			}
			$help_icon_course = ' <img src="' . 'pix/info.png' . '" ' .
                ' alt="-" title="' . get_string('usagesrchheader_coursename_info', 'report_myfeedback') . '" rel="tooltip"/>';
			$help_icon_coursecat = ' <img src="' . 'pix/info.png' . '" ' .
                ' alt="-" title="' . get_string('usagesrchheader_coursecatname_info', 'report_myfeedback') . '" rel="tooltip"/>';
			$table = "<table class=\"userstable\" id=\"userstable\">
						<thead>
								<tr class=\"tableheader\">
									<th class=\"tblname\">" . get_string('name') . $help_icon_course .
					"</th><th class=\"tbldepartment\">" . get_string('category', 'report_myfeedback') . $help_icon_coursecat . "</th></tr>
							</thead><tbody>";
			//Add the records to the table here. These records were stored in the myusers array.
			foreach ($mycourses as $result) {
				if (isset($result[0])) {
					$table.= "<tr>";
					$table.="<td>" . $result[0] . "</td>";
					$table.="<td>" . $result[1] . "</td>";
					$table.="</tr>";
				}
			}
			$table.="</tbody><tfoot><tr><td></td><td></td></tr></tfoot></table><br />";
		}
        echo "<script>$('#searchu').on('click', function() {
          if ($(this).val() == '" . get_string('searchcourses', 'report_myfeedback') . "') {
             $(this).val('');
          }
        })</script>";
        return $table;
		
	}
	
	/**
    * Return all users relevant to the search name / email address (max 10)
	*
    * @global stdClass $remotedb The DB object.
    * @global stdClass $CFG The global configuration instance.
    * @param str $search The user input entered to search on.
    * @param str $reporttype The report type where the search is being used
    * @return string Table with the list of users matching the search term.
    */
	public function search_all_users($search, $reporttype = "student"){
		global $remotedb, $CFG;
		
		$myusers = array();
						
        //The search form
        echo ' <form method="POST" id="reportsearchform" class="report_form" action="">
                            <input type="text" id="searchu" name="searchusage" class="searchusage" value="' . get_string("searchusers", "report_myfeedback") . '" />
                            <input type="submit" id="submitsearch" value="' . get_string("search", "report_myfeedback") . '" />
							<input type="hidden" name="reportuserid" id="reportuserid" value="0" /> 
                            </form>';
        //We first trim the search to remove leading and trailing spaces

        $search = trim($search);
        //If there is a search input and it's not the text that tells the user to enter search input
        if ($search != "" && $search != get_string("searchusers", "report_myfeedback")) {
            $searchu = addslashes(strip_tags($search));//we escape the quotes etc and strip all html tags
            $userresult = array();
            if (strpos($searchu, '@')) {//If it is an email address search for the full input
                $userresult = $remotedb->get_records_sql("SELECT id,firstname,lastname,email,department FROM {user}
                    WHERE deleted = 0 AND email = ?", array($searchu));
			} else {//if not an emal address then search on first word or last word
                $namef = explode(" ", $searchu);//make string into array if multiple words
                $namel = array_reverse($namef);//reverse the array to get the last word
				//suggest this checks to see how many words are entered in the search box
				//suggest the query then CHANGE from OR to AND for 2 werd entries, as otherwise you get back a lot of innacurate results.
                $userresult = $remotedb->get_records_sql("SELECT id,firstname,lastname,email, department FROM {user}
                    WHERE deleted = 0 AND (firstname LIKE ('$namef[0]%') OR lastname LIKE ('$namel[0]%')) limit 10", array());
            }
			if ($userresult) {
				foreach ($userresult as $a) {
                    if ($a->id && ($a->firstname || $a->lastname)) {
						$myusers[$a->id][0] = "<a href=\"" . $CFG->wwwroot . "/report/myfeedback/index.php?currenttab=usage&reporttype=$reporttype&reportuserid=" . $a->id . "\" title=\"" .
                                                    $a->email . "\" rel=\"tooltip\">" . $a->firstname . " " . $a->lastname . "</a>";
						$myusers[$a->id][1] = $a->department;								
					}
				}
			}
			$help_icon_username = ' <img src="' . 'pix/info.png' . '" ' .
                ' alt="-" title="' . get_string('usagesrchheader_username_info', 'report_myfeedback') . '" rel="tooltip"/>';
			$help_icon_userdept = ' <img src="' . 'pix/info.png' . '" ' .
                ' alt="-" title="' . get_string('usagesrchheader_userdept_info', 'report_myfeedback') . '" rel="tooltip"/>';
			
			$usertable = "<table class=\"userstable\" id=\"userstable\">
				<thead>
						<tr class=\"tableheader\">
							<th class=\"tblname\">" . get_string('name') . $help_icon_username .
			"</th><th class=\"tbldepartment\">" . get_string('department', 'report_myfeedback')  . $help_icon_userdept . "</th></tr>
					</thead><tbody>";
			//Add the records to the table here. These records were stored in the myusers array.
			foreach ($myusers as $result) {
				if (isset($result[0])) {
					$usertable.= "<tr>";
					$usertable.="<td>" . $result[0] . "</td>";
					$usertable.="<td>" . $result[1] . "</td>";
					$usertable.="</tr>";
				}
			}
			$usertable.="</tbody><tfoot><tr><td></td><td></td></tr></tfoot></table><br />";
		}
        echo "<script>$('#searchu').on('click', function() {
          if ($(this).val() == '" . get_string('searchusers', 'report_myfeedback') . "') {
             $(this).val('');
          }
        })</script>";
        return $usertable;
		
	}

    /**
     * Return all users the current user (tutor/admin) has access to
	 *
     * @global stdClass $remotedb The DB object
     * @global stdClass $COURSE The course object
     * @global stdClass $USER The user object
     * @param bool $ptutor If user is a personal tutor
     * @param str $search The user input entered to search on
     * @param bool $modt Is the user a module tutor
     * @param bool $proga Is the user a Dept admin
     * @return string Table with the list of users they have access to
     */
    public function get_all_accessible_users($ptutor, $search = null, $modt, $proga) {
        global $remotedb, $USER, $CFG;
		
		$help_icon_username = ' <img src="' . 'pix/info.png' . '" ' .
                ' alt="-" title="' . get_string('mystudentssrch_username_info', 'report_myfeedback') . '" rel="tooltip"/>';
		$help_icon_relationship = ' <img src="' . 'pix/info.png' . '" ' .
                ' alt="-" title="' . get_string('mystudentssrch_relationship_info', 'report_myfeedback') . '" rel="tooltip"/>';
				
        $usertable = "<table style=\"float:left;\" class=\"userstable\" id=\"userstable\">
                    <thead>
                            <tr class=\"tableheader\">
                                <th class=\"tblname\">" . get_string('name') . $help_icon_username .
                "</th><th class=\"tblrelationship\">" . get_string('relationship', 'report_myfeedback') . $help_icon_relationship . "</th>
                            </tr>
                        </thead><tbody>";

        $myusers = array();
        //The search form
        echo '<form  method="POST"  id="searchform" action="" >
                            <input type="text" id="searchu" name="searchuser" value="' . get_string("searchusers", "report_myfeedback") . '" />
                            <input type="hidden" name="mytick" value="checked"/>
                            <input type="submit" id="submitsearch" value="' . get_string("search", "report_myfeedback") . '" />
                            </form>';
        //We first trim the search to remove leading and trailing spaces
        $search = trim($search);
        //If there is a search input and it's not the text that tells the user to enter search input
        if ($search && $search != get_string("searchusers", "report_myfeedback")) {
            $searchu = addslashes(strip_tags($search));//we escape the quotes etc and strip all html tags
            $userresult = array();
            if (strpos($searchu, '@')) {//If it is an email address search for the full input
                $userresult = $remotedb->get_records_sql("SELECT id,firstname,lastname,email FROM {user}
                    WHERE deleted = 0 AND email = ?", array($searchu));
            } else {//if not an emal address then search on first word or last word
                $namef = explode(" ", $searchu);//make string into array if multiple words
                $namel = array_reverse($namef);//reverse the array to get the last word
				//suggest this checks to see how many words are entered in the search box
				//suggest the query then CHANGE from OR to AND for 2 werd entries, as otherwise you get back a lot of innacurate results.
                $userresult = $remotedb->get_records_sql("SELECT id,firstname,lastname,email FROM {user}
                    WHERE deleted = 0 AND (firstname LIKE ('$namef[0]%') OR lastname LIKE ('$namel[0]%')) limit 10", array());
            }
            $progs = array();
            $my_mods = array();
            if ($userresult) {
                if ($proga) {//get all courses the logged in user is dept admin in but only return the listed fields, then save in array
                    if ($ptadmin = get_user_capability_course('report/myfeedback:progadmin', $USER->id, false, $field = 'shortname,visible')) {
                        foreach ($ptadmin as $valu) {
                            $progs[] = $valu;
                        }
                    }
                }
                if ($modt) {//get all courses the logged in user is modfule tutor in but only return the listed fields, then save in array
                    if ($mtadmin = get_user_capability_course('report/myfeedback:modtutor', $USER->id, false, $field = 'shortname,visible')) {
                        foreach ($mtadmin as $valm) {
                            $my_mods[] = $valm;
                        }
                    }
                }
                //For all users (currently 10 due to performance)returned in the search
                //If they have the name and id fields in their records, check all courses they are student in
                foreach ($userresult as $a) {
                    if ($a->id && ($a->firstname || $a->lastname)) {
                        if ($admin = get_user_capability_course('report/myfeedback:student', $a->id, false, $field = 'shortname,visible')) {
                            foreach ($admin as $mod) {
                                if ($mod->visible && $mod->id && $mod->shortname) {//courses should be active and have shortname
                                    foreach ($progs as $dept) {//Iterate through the user an dept admin courses and if they match then add that record to be added to table
                                        if ($dept->id == $mod->id) {
                                            $myusers[$a->id][0] = "<a href=\"" . $CFG->wwwroot . "/report/myfeedback/index.php?userid=" . $a->id . "\" title=\"" .
                                                    $a->email . "\" rel=\"tooltip\">" . $a->firstname . " " . $a->lastname . "</a>";
                                            $myusers[$a->id][1] = ''; //get_string('othertutee', 'report_myfeedback');
                                            $myusers[$a->id][2] = 1;
                                            $myusers[$a->id][3] = '';
                                        }
                                    }
                                    foreach ($my_mods as $tutmod) {//Now iterate through the module tutor courses and if they match the student the add that record to be added to table
                                        if ($tutmod->id == $mod->id) {
                                            $myusers[$a->id][0] = "<a href=\"" . $CFG->wwwroot . "/report/myfeedback/index.php?userid=" . $a->id . "\" title=\"" .
                                                    $a->email . "\" rel=\"tooltip\">" . $a->firstname . " " . $a->lastname . "</a>";
                                            $myusers[$a->id][1] = ''; //get_string('othertutee', 'report_myfeedback');
                                            $myusers[$a->id][2] = 2;
                                            foreach ($admin as $mod1) {//Here we add the course shortname that the module tutor has a capability in for that student
                                                if ($mod1->visible && $mod1->id && $mod1->shortname) {
                                                    foreach ($my_mods as $tutmod1) {
                                                        if ($mod1->id == $tutmod1->id) {
                                                            $myusers[$a->id][1] .= $mod1->shortname . ", ";//add a comma if multiple course shortnames
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    $myusers[$a->id][1] = isset($myusers[$a->id][1]) ? rtrim($myusers[$a->id][1], ", ") : '';
                                    $myusers[$a->id][3] = isset($myusers[$a->id][1]) ? $myusers[$a->id][1] : '';
                                }
                            }
                        }
                    }
                }
            }
        }

        // get all the mentees, i.e. users you have a direct assignment to and add them to the table if you are a personal tutor
        if ($ptutor) {
            if ($usercontexts = $remotedb->get_records_sql("SELECT c.instanceid, u.id as id, firstname, lastname, email
                                                    FROM {role_assignments} ra, {context} c, {user} u
                                                   WHERE ra.userid = ?
                                                         AND ra.contextid = c.id
                                                         AND c.instanceid = u.id
                                                         AND c.contextlevel = " . CONTEXT_USER, array($USER->id))) {
                foreach ($usercontexts as $u) {
                    if ($u->id && ($u->firstname || $u->lastname)) {
                        $myusers[$u->id][0] = "<a href=\"" . $CFG->wwwroot . "/report/myfeedback/index.php?userid=" . $u->id . "\" title=\"" .
                                $u->email . "\" rel=\"tooltip\">" . $u->firstname . " " . $u->lastname . "</a>";
                        $myusers[$u->id][1] = get_string('personaltutee', 'report_myfeedback');
                        $myusers[$u->id][2] = 3;
                        $myusers[$u->id][3] = '';
                    }
                }
                unset($myusers[$USER->id]);
            }
        }

        //Add the records to the table here. These records were stored in the myusers array.
        foreach ($myusers as $result) {
            if (isset($result[0])) {
                $usertable.= "<tr>";
                $usertable.="<td>" . $result[0] . "</td>";
                $usertable.="<td class=\"ellip\" data-sort=$result[2] title=\"$result[3]\" rel=\"tooltip\">" . $result[1] . "</td>";
                $usertable.="</tr>";
            }
        }
        $usertable.="</tbody><tfoot><tr><td></td><td></td></tr></tfoot></table>";
        echo "<script>$('#searchu').on('click', function() {
          if ($(this).val() == '" . get_string('searchusers', 'report_myfeedback') . "') {
             $(this).val('');
          }
        })</script>";
        return $usertable;
    }
	
	

    /**
     * Return all users for the dept admin personal tutors
	 *
     * @global stdClass $remotedb The DB object
     * @global stdClass $CFG The global config
     * @param int $uid The user id
     * @return string Table with the list of users
     */
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
        $inner_table = '<table class="innertable" width="100%" style="text-align:center; display:none"><thead><tr><th>' . get_string('tutee', 'report_myfeedback') . '
            </th><th>' . get_string('p_tut_programme', 'report_myfeedback') . '</th><th>' . get_string('yearlevel', 'report_myfeedback') . '
                </th><th>' . get_string('my_feedback', 'report_myfeedback') . '</th></tr></thead><tbody>';
        foreach ($myusers as $res) {
            $inner_table .= "<tr>";
            $inner_table .= "<td>" . $res['name'] . "</td><td>" . $res['prog'] . "</td><td>" . $res['year'] . "</td><td>" . $res['here'] . "</td></tr>";
        }
        $inner_table .= "</tbody></table>";
        return $inner_table;
    }

    /**
     * Return all users for the dept admin mod tutor groups
	 *
     * @global stdClass $remotedb The DB object
     * @param int $uid The user id
     * @param int $cid The course id
     * @param array $tutgroup An array of members in the tutors group
     * @return string Table with the list of users
     */
    public function get_tutees_for_prog_tutor_groups($uid, $cid, $tutgroup) {
        global $remotedb;
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
            //Iterate through both arrays and if members in the course are similar to those in the 
            //tutors group then add them to list to be added to the table
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
                        //$myusers[$u->id]['feed'] += $tutgroup[$u->userid]['feed'];
                        $myusers[$u->id]['count'] += count($sum);
                    }
                }
            }
            //$myusers[$u->id]['here'] = "<a href=\"" . $CFG->wwwroot . "/report/myfeedback/index.php?userid=" . $u->id . "\">Click here</a>";
        }

        $stu = $this->get_user_analytics($myusers, $tgid = 't' . $cid, $display = 'stuRec', $style = '', $breakdodwn = null, $fromassess = false, $fromtut = true);
        $inner_table = "<table class=\"innertable\" width=\"100%\" style=\"text-align:center; display:none\">
            <thead><tr><th>" . get_string('groupname', 'report_myfeedback') . "</th><th>" . get_string('tutortblheader_assessment', 'report_myfeedback') . "</th><th>" .
                get_string('tutortblheader_nonsubmissions', 'report_myfeedback') . "</th><th>" . get_string('tutortblheader_latesubmissions', 'report_myfeedback') . "</th><th>" .
                get_string('tutortblheader_graded', 'report_myfeedback') . "</th><th>" .
                get_string('tutortblheader_lowgrades', 'report_myfeedback') . "</th></tr></thead><tbody>";

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

        $inner_table .= "</tbody></table>";
        return $inner_table;
    }

    /**
     * This returns the id of the personal tutor 
	 *
     * @global stdClass $remotedb DB object
     * @param int $p_tutor_roleid Personal tutor role id
     * @param int $contextid The context id
     * @return int The id of the personal tutor or 0 if none
     */
    public function get_my_personal_tutor($p_tutor_roleid, $contextid) {
        global $remotedb;
        $sql = "SELECT userid FROM {role_assignments}
                WHERE roleid = ? AND contextid = ?
                ORDER BY timemodified DESC limit 1";
        $params = array($p_tutor_roleid, $contextid);
        $tutor = $remotedb->get_record_sql($sql, $params);
        return $tutor ? $tutor->userid : 0;
    }

    /**
     * Returns a course id given the course shortname
	 *
     * @global stdClass $remotedb DB object
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
     * Returns all assessments in the course
	 *
     * @global stdClass $remotedb DB object
     * @param int $cid The course id
     * @return array The assmessments id, name, type and module
     */
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
        $sql = "SELECT id, itemname, itemtype, itemmodule
                FROM {grade_items} gi 
                WHERE (hidden != 1 AND hidden < ?) AND courseid = ?
                AND (itemmodule IN ($items) OR (itemtype = 'manual'))";
        $params = array($now, $cid);
        $assess = $remotedb->get_records_sql($sql, $params);

        return $assess;
    }

    /**
     * Returns a canvas graph of stats
	 *
     * @param int $cid The course id
     * @param array $grades_totals Grades totals
     * @param int $enrolled The number of students enrolled in the course
     * @return Canvas image The graph as an image on canvas
     */
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
                    '" width="380" height="70" style="border:0px solid #d3d3d3;">' . get_string("browsersupport", "report_myfeedback") . '</canvas>
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

    /**
     * Returns a constructed link for the assessment
	 *
     * @param str $type The type of assemssment
     * @param int $cid The course id
     * @param int $gid The grade_item id
     * @return link The link to the assessment
     */
    public function get_assessment_link_from_type($type, $cid, $gid = null) {
        global $CFG, $remotedb;
        $sql = "SELECT cm.id FROM {course_modules} cm
            JOIN {grade_items} gi ON gi.iteminstance=cm.instance 
            JOIN {modules} m ON cm.module = m.id AND gi.itemmodule = m.name
            AND cm.course = ? AND gi.id = ? AND gi.itemmodule = ?";
        $params = array($cid, $gid, $type);
        $link = $remotedb->get_record_sql($sql, $params);
        switch ($type) {
            case 'quiz':
                $link = $CFG->wwwroot . "/mod/quiz/view.php?id=$link->id";
                break;
            case 'assign':
                $link = $CFG->wwwroot . "/mod/assign/view.php?id=$link->id";
                break;
            case 'turnitintooltwo':
                $link = $CFG->wwwroot . "/mod/turnitintooltwo/view.php?id=$link->id";
                break;
            case 'workshop':
                $link = $CFG->wwwroot . "/mod/workshop/view.php?id=$link->id";
                break;
            case 'manual':
                $link = $CFG->wwwroot . "/grade/report/grader/index.php?id=$cid";
                break;
        }
        return $link;
    }

    /**
     * Returns a table with the analytics for all assessments of the user
	 *
     * @global stdClass $OUTPUT The global output object
     * @param array $assess The array with the assessments and their stats
     * @param int $uidnum The id for the table
     * @param str $display The CSS class that is added
     * @param str $style The CSS style to be added
     * @param str $breakdown Whether to add the breakdown text
     * @param array $users The array of users in this assessment and some stats
     * @param bool $pmod Whether the function is called from module dashboard
     * @return str A table with the assessments and their stats
     */
    public function get_assessment_analytics($assess, $uidnum, $display = null, $style = null, $breakdown = null, $users = null, $pmod = false) {
        global $OUTPUT;
        //Sort the assessment by name aplabetically but case insentsitive
        uasort($assess, function($a11, $b11) {
                    return strcasecmp($a11['name'], $b11['name']);
                });

        $aname = $ad = $an = $al = $ag = $af = $alo = $a_vas = '';
        foreach ($assess as $aid => $a) {
            $scol1 = $scol2 = $scol3 = $scol4 = '';
            $a_due = $a_non = $a_graded = $a_late = $a_feed = $a_low = 0;
            $assessmenticon = $a_name = '';
            $assess_graph = '<i style="color:#5A5A5A">' . get_string('nographtodisplay', 'report_myfeedback') . '</i>';
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
            $item = $a['icon'];
            if ($item && $item != 'manual') {
                $assessmenticon = '<img src="' .
                        $OUTPUT->image_url('icon', $item) . '" ' .
                        'class="icon" alt="' . $item . '" title="' . $item . '" rel="tooltip">';
            }
            if ($item && $item == 'manual') {
                $assessmenticon = '<img src="' .
                        $OUTPUT->image_url('i/manual_item') . '" ' .
                        'class="icon" alt="' . $item . '" title="' . $item . '" rel="tooltip">';
            }

            $link = $item == 'manual' ? $this->get_assessment_link_from_type($item, $a['cid']) : $this->get_assessment_link_from_type($item, $a['cid'], $a['aid']);
            $a_name = '<a href="' . $link . '" title="' . $a['name'] . '" rel="tooltip">' . $a['name'] . '</a>';
            $a_due = $a['due'];
            $a_non = $a['non'];
            $a_late = $a['late'];
            //$a_feed = $a['feed'];
            $a_graded = $a['graded'];
            $a_low = $a['low'];

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
            $minortable = "<div style='height:81px' class='settableheight'><table data-aid='a" . $aid . "' style='" . $style . " ' class='tutor-inner " . $display . "' height='" . $height . "' align='center'><tr class='accord'>";
            $ad .= $minortable . "<td style = 'text-align: center'>" . $a_due . "</td></tr></table></div>" . $ud;
            $an .= $minortable . "<td>" . "<span class=$scol1>" . $a_non . "</span></td></tr></table></div>" . $un;
            $al .= $minortable . "<td>" . "<span class=$scol2>" . $a_late . "</span></td></tr></table></div>" . $ul;
            $ag .= $minortable . "<td>" . $a_graded . "</td></tr></table></div>" . $ug;
            //$af .= $minortable . "<td>" . $a_feed . "</td></tr></table></div>" . $uf;
            $alo .= $minortable . "<td>" . "<span class=$scol3>" . $a_low . "</span></td></tr></table></div>" . $ulo;
            $a_vas .= $minortable . "<td class='overallgrade'>" . $assess_graph . "</td></tr></table></div>" . $u_vas;
            $aname .= $minortable . "<td id='assess-name'>" . $assessmenticon . $a_name . $breakdown . "</td></tr></table></div>" . $uname;
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
	

    /**
     * Returns a table with the analytics for all users in the given subset requested
	 *
     * @global stdClass $remotedb The DB object
     * @global stdClass $CFG The global config object
     * @param array $users The array with the users and their stats
     * @param int $cid The course id
     * @param str $display The CSS class that is added
     * @param str $style The CSS style to be added
     * @param str $breakdown Whether to add the breakdown text
     * @param bool $fromassess Whether the function call is from user assessment
     * @param bool $tutgroup Whether these are users for the tutor group
     * @return str A table with the users and their stats
     */
    public function get_user_analytics($users, $cid, $display = null, $style = null, $breakdodwn = null, $fromassess = null, $tutgroup = false) {
        global $remotedb, $CFG;
        $userassess = array();
        $assessuser = array(); //if adding user for each assessment

        $u_name = $uname = $ud = $un = $ul = $ug = $uf = $ulo = $u_vas = $fname = $lname = '';
        $u_due = $u_non = $u_graded = $u_late = $u_feed = $u_low = 0;

        $user_graph = '<i style="color:#5A5A5A">' . get_string('nographtodisplay', 'report_myfeedback') . '</i>';
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
                    $studenttable = "<table style='" . $style . "' class='tutor-inner " . $display . "' height='" . $height . "' align='center'><tr class='st-accord'>";
                    $uname .= $studenttable . "<td>" . $u_name . $breakdodwn . '</td></tr></table>';
                    $ud .= $studenttable . "<td class='assessdue'>" . $u_due . "</td></tr></table>";
                    $un .= $studenttable . "<td>" . "<span class=$scol1>" . $u_non . "</span></td></tr></table>";
                    $ul .= $studenttable . "<td>" . "<span class=$scol2>" . $u_late . "</span></td></tr></table>";
                    $ug .= $studenttable . "<td>" . $u_graded . "</td></tr></table>";
                    //$uf .= $studenttable . "<td>" . $u_feed . "</td></tr></table>";
                    $ulo .= $studenttable . "<td>" . "<span class=$scol3>" . $u_low . "</span></td></tr></table>";
                    $u_vas .= $studenttable . "<td class='overallgrade'>" . $user_graph . '</td></tr></table>';
                    //}
                }
            }
        }
        //sort the users alphabetically but case insensitive
        uasort($users, function($a12, $b12) {
                    return strcasecmp($a12['name'], $b12['name']);
                });

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
    * Returns the head of the table, with all the titles for each column
	*
    * @param array $headertitles The list of header titles as strings.
    * @return string Table head, with titles above each column.
    */
	public function get_table_headers($headertitles, $headerhelptext){
		$header = "<thead><tr class=\"usagetableheader\">";
		for($i = 0; $i < sizeOf($headertitles); $i++){
        	$help_icon = '<img src="' . 'pix/info.png' . '" ' .
                ' alt="-" title="' . $headerhelptext[$i] . '" rel="tooltip"/>';
			$header .= 	"<th>" . $headertitles[$i] . " " . $help_icon . "</th>";
		} 
		$header .= 	"</tr></thead>";
		return $header;
	}
	
	/**
    * Returns subcategories of a parent category only 1 level deep
	*
    * @global stdClass $remotedb The DB object.
    * @param int $parentcatid The parent category id
    * @return array Subcategory objects containing their ids
    */
	public function get_subcategories($parentcatid){
		global $remotedb;
		//get 1 level of subcategories
		return $remotedb->get_records_sql("SELECT id, visible
                                                    FROM {course_categories}
                                                   WHERE parent = ? ORDER BY visible desc, sortorder", array($parentcatid));
	}
	
	
	/**
    * Returns a selectable form menu of subcategories of a parent category only 1 level deep
	*
    * @param int $parentcatid The parent category id
	* @param int $categoryid The currently selected category id
    * @return array Form menu of subcategories of a parent category
    */
	public function get_subcategory_menu($parentcatid=0, $categoryid=0, $parent=true){
		global $SITE;
		
		$subcategories = $this->get_subcategories($parentcatid);
		
		//Start of menu form
		$menu = "<form method=\"POST\" id=\"report_category_select\" class=\"report_form\" action=\"\">".get_string('category', 'report_myfeedback').": <select id=\"categorySelect\" value=\"\" name=\"categoryid\"><option id=\"selectcat\">".get_string('choosedots')."</option>";
		if($parent == true){
			//add a top level category option
			$menu .= "<option id=\"selectcat\"";
			if($categoryid == $parentcatid){
					$menu .= " selected";
			}
			$menu .= ">" . $SITE->fullname . "</option>";
		}
		foreach($subcategories as $subcat){
			$menu .= "<option value=\"$subcat->id\"";
			if($categoryid == $subcat->id){
				$menu .= " selected";
			}
			$menu .= ">".$this->get_category_name($subcat->id)."</option>";
		}
		$menu .= "</select></form> ";
		return $menu;
	}
	
	/**
    * Returns unique list of users within a category and its subcategories
	*
    * @global stdClass $remotedb The DB object.
    * @param int $catid The parent category id
	* @param str $capability The capability that defines the role of the returned users. E.g. student or modtutor.
    * @return array Subcategory objects containing their ids
    */
	public function get_unique_category_users($catid, $capability = 'report/myfeedback:student'){
		global $remotedb;
				
		//get all roles that are assigned the My Feedback capability passed to the function
		$roleids = $this->get_roles($capability);
		
		//if there are no roles with a my feedback student capability then return null
		if(sizeof($roleids) < 1){
			return null;
		}
		//get all the courses in the category, then iterate through the below
		//CONTINUE HERE - get courses in a category and iterate though each enrolled user on that course
		//$courseids = $this->get_courses_in_category($catid);
		//get the contextid of the category
		$contextid = $this->get_categorycontextid($catid);
		$params = array();

		$sql = 'SELECT DISTINCT ra.userid FROM {role_assignments} ra JOIN {user} u on u.id = ra.userid JOIN {context} con ON ra.contextid = con.id AND con.contextlevel = 50 WHERE u.deleted = 0 AND u.suspended = 0';
		if($catid > 0){
			$params = array("%/".$contextid, "%/".$contextid."/%");
			$sql .= ' AND (con.path LIKE ? OR con.path LIKE ? )';
		}
		$sql .= ' AND (roleid = ?';
		//add all the roleid code into the where query
		$params[] = $roleids[0];
		$i = 1; //start at 1, because the first role is already in the query

        // Segun Babalola, 2019-07-18.
        // The following line of code is causing warnings in the UI because $i exceeds array size.
        // This is unrelated to the issue I'm trying to fix (i.e. https://tracker.moodle.org/browse/CONTRIB-6841),
        // however I will guard execution with an existence check for now. Hopefully the root cause of
        // the issue will be addressed in future.
		while(isset($roleids[$i]) && $roleids[$i] != null){
			$sql .= '  OR roleid = ?';
			$params[] = $roleids[$i];
			$i++;
		}
		$sql .= ')';

		$rs = $remotedb->get_recordset_sql($sql, $params);

		// The recordset contains records.
		if ($rs->valid()) {
			$users = array();
			foreach ($rs as $user) {
				$users[] = $user->userid;
			}
			$rs->close();
		}
		return $users;
	}
	
	/**
    * Returns unique list of users within a course
	*
    * @global stdClass $remotedb The DB object.
    * @param int $catid The courseid
	* @param str $capability The capability that defines the role of the returned users. E.g. student or modtutor.
    * @return array User objects containing their ids
    */	
	public function get_unique_course_users($catid, $capability = 'report/myfeedback:student'){
		global $remotedb;
				
		//get all roles that are assigned the My Feedback capability passed to the function
		$roleids = $this->get_roles($capability);

		//if there are no roles with a my feedback student capability then return null
		if(sizeof($roleids) < 1){
			return null;
		}
		// get courses in a category and iterate though each enrolled user on that course
		$courseparams = array("%/".$catid, "%/".$catid."/%");

		$coursesql = "SELECT DISTINCT c.id FROM {course} c, {course_categories} cat WHERE c.category = cat.id AND cat.path LIKE ? OR cat.path LIKE ?";
		$courses = $remotedb->get_recordset_sql($coursesql, $courseparams);

		// The recordset contains records.
		if ($courses->valid()) {

			foreach ($courses as $course) {
				//for entire Moodle get all students on courses within each faculty, then total together so you show each faculty 
				//with total moodle stats on top
				$sql = 'SELECT DISTINCT ra.userid FROM {role_assignments} ra JOIN {user} u on u.id = ra.userid JOIN {context} con ON ra.contextid = con.id AND con.contextlevel = 50 WHERE u.deleted = 0 AND u.suspended = 0 AND con.instanceid = ?  AND (roleid = ?';
				$i = 1; //start at 1, because the first role is already in the query
				//add all the roleid code into the where query
				while($roleids[$i] != null){
					$sql .= '  OR roleid = ?';
					$i++;
				}
				$sql .= ')';
	
				$params = $roleids;
				//prepend the course id to the front of the role ids already in the array
				array_unshift($params, $course->id);
				
				$rs = $remotedb->get_recordset_sql($sql, $params);
				
				$userids = array();
				if ($rs->valid()) {
					// The recordset contains records.
					foreach ($rs as $record) {
						$userids[] = $record->userid;
					}
					$rs->close();
					
				}
				return $userids;
			}
			$rs->close();
		}
	}

	/**
    * Returns the roles that have a particular capability
	*
    * @global stdClass $remotedb The DB object.
	* @param str $capability The capability that defines the role of the returned users. E.g. student or modtutor.
    * @return array An array of roleids.
    */	
	public function get_roles($capability){
		global $remotedb;
		$params = array($capability); 
		//SELECT roleid FROM mdl_role_capabilities c where capability = 'report/myfeedback:student';
		return $remotedb->get_fieldset_select('role_capabilities', 'roleid', 'capability = ?', $params);
	}
	
	/**
    * Returns the first name and surname of a particular user
	*
    * @global stdClass $remotedb The DB object.
	* @param int $ui The user id
    * @return str The firstname and lastname of the user, separated by a space.
    */	
	public function get_names($ui){
		global $remotedb;
		$params = array($ui);
		$unames = $remotedb->get_record_sql("SELECT firstname,lastname FROM {user} where id=?", $params);
		return $unames->firstname . " " . $unames->lastname;
	}
	
	/**
    * Returns the course fullname or shortname
	*
    * @global stdClass $remotedb The DB object.
	* @param int $id The course id
	* @param bool $fullname Whether to return the fullname or the shortname.
    * @return str The firstname and lastname of the user, separated by a space.
    */	
	public function get_course_name($id, $fullname = true){
		global $remotedb;
		
        $coursename =  $remotedb->get_record_sql("SELECT c.fullname, c.shortname
                                                    FROM {course} c
                                                   WHERE c.id = ?", array($id));
		if($fullname == true){
			return $coursename->fullname;
		}else{
			return $coursename->shortname;
		}
	}
		
	/**
    * Returns the category name
	*
    * @global stdClass $remotedb The DB object.
	* @param int $id The category id
    * @return str The category name.
    */	
	public function get_category_name($id){
		global $remotedb;
		
        $catname = $remotedb->get_record_sql("SELECT cat.name 
                                                    FROM {course_categories} cat
                                                   WHERE cat.id = ?", array($id));
		return $catname->name;
	}
	
	/**
    * Returns the up a category button
	*
    * @global stdClass $remotedb The DB object.
	* @global stdClass $CFG The global configuration instance.
	* @param int $categoryid The category id
	* @param str $url The type of report for inserting into the URL.
    * @return str The go up a category button as html.
    */
	public function get_parent_category_link($categoryid, $reporttype){
		global $remotedb, $CFG;
        $category = $remotedb->get_record_sql("SELECT cat.parent 
                                                    FROM {course_categories} cat
                                                   WHERE cat.id = ?", array($categoryid));
		if($category->parent > 0){		
		return " <a href=\"".$CFG->wwwroot ."/report/myfeedback/index.php?currenttab=usage&reporttype=".$reporttype."&categoryid=".$category->parent."\" title=\"Up to parent category\"><img class=\"uparrow\" src=\"".
			$CFG->wwwroot . "/report/myfeedback/pix/return.png\" alt=\"\" /></a>";						   
		}else{
			return "";
		}
	}
	
	/**
    * Returns the up to category button for the course reports
	*
    * @global stdClass $remotedb The DB object.
	* @global stdClass $CFG The global configuration instance.
	* @param int $courseid The course id.
	* @param str $url The type of report for inserting into the URL.
    * @return str The go up to category button as html.
    */
	public function get_course_category_link($courseid, $reporttype){
		global $remotedb, $CFG;
        $course = $remotedb->get_record_sql("SELECT c.category 
                                                    FROM {course} c
                                                   WHERE c.id = ?", array($courseid));
		if($course->category > 0){		
		return " <a href=\"".$CFG->wwwroot ."/report/myfeedback/index.php?currenttab=usage&reporttype=".$reporttype."&categoryid=".$course->category."\" title=\"Up to parent category\"><img class=\"uparrow\" src=\"".
			$CFG->wwwroot . "/report/myfeedback/pix/return.png\" alt=\"\" /></a>";						   
		}else{
			return "";
		}
	}
	
	/**
    * Returns whether a user is suspended and deleted or not
	*
    * @global stdClass $remotedb The DB object.
	* @param int $id The user id
    * @return int True if the user is active (not suspended or deleted), otherwise false.
    */	
	public function is_active_user($id){
		global $remotedb;
		
        $user = $remotedb->get_record_sql("SELECT deleted, suspended
                                                   FROM {user}
                                                   WHERE id = ?", array($id));
		if($user->suspended == 0 && $user->deleted == 0){
			return true;
		}
		return false;
		
	}
	
	
	
	/**
    * Returns the context id of the category
	*
    * @global stdClass $remotedb The DB object.
	* @param int $id The category id
    * @return int The context id of the category.
    */	
	public function get_categorycontextid($id){
		global $remotedb;
		
        $contextid = $remotedb->get_record_sql("SELECT id
                                                    FROM {context}
                                                   WHERE contextlevel = 40 AND instanceid = ?", array($id));
		return $contextid->id;
	}
	
	/**
    * Returns the personal tutees of a personal tutor
	*
    * @global stdClass $remotedb The DB object.
	* @param int $personaltutorid The user id of the personal tutor
    * @return array The list of user objects containing the ids of personal tutees.
    */	
	public function get_personal_tutees($personaltutorid){
	 	global $remotedb;
		// get all the mentees, i.e. users you have a direct assignment to as their personal tutor
        return $remotedb->get_records_sql("SELECT u.id 
                                                    FROM {role_assignments} ra, {context} c, {user} u
                                                   WHERE ra.userid = ? 
												   		 AND u.deleted = 0 
														 AND u.suspended = 0 
                                                         AND ra.contextid = c.id
                                                         AND c.instanceid = u.id
                                                         AND c.contextlevel = " . CONTEXT_USER, array($personaltutorid));
	}
	
	
	/**
     * Returns the usage statistics for the staff passed to the function
	 *
     * @global stdClass $remotedb The DB object.
     * @param array $users The array of user ids.
     * @return array The list of staff and their usage statistics.	 
     */
	public function get_overall_staff_usage_statistics($uids){
	 	global $remotedb;
		
        $users = array();
		$usertotalviews = array();
		//the array of studentids viewed by any staff members
			
		foreach ($uids as $ui) {
			$users[$ui]['userid'] = $ui;
			$users[$ui]['name'] = $this->get_names($ui);
			$users[$ui]['totalviews'] = 0;
			$users[$ui]['ownreportviews'] = 0;
			$users[$ui]['mystudentstabviews'] = 0;
			//the array of studentids viewed by this staff member - can do a count later to get the number veiwed
			$users[$ui]['studentsviewed'] = array(); 		
			$users[$ui]['studentreportviews'] = 0;		
			$users[$ui]['modtutordashboardviews'] = 0;	
			$users[$ui]['ptutordashboardviews'] = 0;
			$users[$ui]['deptadmindashboardviews'] = 0;
			$users[$ui]['downloads'] = 0;
			$users[$ui]['lastaccess'] = 0;
			$users[$ui]['ptutees'] = sizeOf($this->get_personal_tutees($ui));
			
			
			foreach($this->get_user_usage_logs($ui) as $reportevent){
				switch($reportevent->eventname){
					//check for ownreport views and student report views
					case '\report_myfeedback\event\myfeedbackreport_viewed':
						$users[$ui]['totalviews'] += 1;
						//check if it was their own report, or a students' report they viewed
						if($reportevent->userid == $reportevent->relateduserid){
							$users[$ui]['ownreportviews'] += 1;
						}else{
							$users[$ui]['studentreportviews'] += 1;
							$users[$ui]['studentsviewed'][$reportevent->relateduserid] += 1;
						}	
						if($reportevent->timecreated > $users[$ui]['lastaccess']){
							$users[$ui]['lastaccess'] = $reportevent->timecreated;
						}
						break;
					
					case '\report_myfeedback\event\myfeedbackreport_download':
						$users[$ui]['downloads'] += 1;
						if($reportevent->timecreated > $users[$ui]['lastaccess']){
							$users[$ui]['lastaccess'] = $reportevent->timecreated;
						}
						break;
						
					case '\report_myfeedback\event\myfeedbackreport_downloadmtutor':
						$users[$ui]['downloads'] += 1;
						if($reportevent->timecreated > $users[$ui]['lastaccess']){
							$users[$ui]['lastaccess'] = $reportevent->timecreated;
						}
						break;
					case '\report_myfeedback\event\myfeedbackreport_downloaddeptadmin':
						$users[$ui]['downloads'] += 1;
						if($reportevent->timecreated > $users[$ui]['lastaccess']){
							$users[$ui]['lastaccess'] = $reportevent->timecreated;
						}
						break;
					case '\report_myfeedback\event\myfeedbackreport_viewed_mystudents':
						$users[$ui]['totalviews'] += 1;
						$users[$ui]['mystudentstabviews'] += 1;
						if($reportevent->timecreated > $users[$ui]['lastaccess']){
							$users[$ui]['lastaccess'] = $reportevent->timecreated;
						}
						break;
					case '\report_myfeedback\event\myfeedbackreport_viewed_ptutordash':
						$users[$ui]['totalviews'] += 1;
						$users[$ui]['ptutordashboardviews'] += 1;
						if($reportevent->timecreated > $users[$ui]['lastaccess']){
							$users[$ui]['lastaccess'] = $reportevent->timecreated;
						}
						break;
					case '\report_myfeedback\event\myfeedbackreport_viewed_mtutordash':
						$users[$ui]['totalviews'] += 1;
						$users[$ui]['modtutordashboardviews'] += 1;
						if($reportevent->timecreated > $users[$ui]['lastaccess']){
							$users[$ui]['lastaccess'] = $reportevent->timecreated;
						}
						break;
					case '\report_myfeedback\event\myfeedbackreport_viewed_deptdash':
						$users[$ui]['totalviews'] += 1;
						$users[$ui]['deptadmindashboardviews'] += 1;
						if($reportevent->timecreated > $users[$ui]['lastaccess']){
							$users[$ui]['lastaccess'] = $reportevent->timecreated;
						}
						break;
				}
			}
			//Note: consider whether we want to order by total activity, rather than total views. This would also capture downloads etc then too. 
			//If so we could increment the count each time the foreach loop is initiated instead.
			$usertotalviews[] = $users[$ui]['totalviews'];
			//Instead of overriding the keys (like in array_merge), the array_merge_recursive() function makes the value as an array.
			//E.g. Array ( [12345] => 3 [67890] => Array ( [0] => 1 [1] => 6 ) [54365] => 3 ) - the second student was viewed by 2 staff 1 and 6 times.
			
		} //end foreach for this staff member
		
		//sort in descending order - use array_multisort as it's better with large arrays than usort
		//usort($users, function ($a, $b) {  return strcmp($b['totalviews'], $a['totalviews']); });
		array_multisort($usertotalviews, SORT_DESC, SORT_NUMERIC, $users);
		
		return $users; 
	}
	
	/**
     * Returns usage statistics for the students passed to the function
	 *
     * @global stdClass $remotedb The DB object
     * @param array $users The array of user ids
     * @return array The list of students and their usage statistics.	 	 
     */
	public function get_overall_student_usage_statistics($uids){
	 	global $remotedb;
		
        $users = array();
		$usertotalviews = array();
		foreach ($uids as $ui) {
			$users[$ui]['userid'] = $ui;
			$users[$ui]['name'] = $this->get_names($ui);
			$users[$ui]['totalviews'] = 0;
			$users[$ui]['notes'] = 0;
			$users[$ui]['feedback'] = 0;
			$users[$ui]['downloads'] = 0;		
			$users[$ui]['lastaccess'] = 0;
			//Get the personal tutor details of the user
			$p_tutor_roleid = $this->get_personal_tutor_id();
			$usercontext = context_user::instance($ui);
			$users[$ui]['personaltutor'] = "";
			$users[$ui]['personaltutorid'] = -1;
			if ($mytutorid = $this->get_my_personal_tutor($p_tutor_roleid, $usercontext->id)){
				$users[$ui]['personaltutorid'] = $mytutorid;
				$users[$ui]['personaltutor'] = $this->get_names($mytutorid);
			}
			
			foreach($this->get_user_usage_logs($ui) as $reportevent){

				switch($reportevent->eventname){

					case '\report_myfeedback\event\myfeedbackreport_viewed':
						$users[$ui]['totalviews'] += 1;
						if($reportevent->timecreated > $users[$ui]['lastaccess']){
							$users[$ui]['lastaccess'] = $reportevent->timecreated;
						}
						break;
					
					case '\report_myfeedback\event\myfeedbackreport_download':
						$users[$ui]['downloads'] += 1;
						if($reportevent->timecreated > $users[$ui]['lastaccess']){
							$users[$ui]['lastaccess'] = $reportevent->timecreated;
						}
						break;
						
				}
			}
			
			//not used in the above case, as we look directly in the notes and feedback table for these.
			//\report_myfeedback\event\myfeedbackreport_addnotes
			//\report_myfeedback\event\myfeedbackreport_updatenotes
			//\report_myfeedback\event\myfeedbackreport_addfeedback
			//\report_myfeedback\event\myfeedbackreport_updatefeedback
			
			foreach($this->get_notes_and_feedback($ui) as $studentinput){
				if($studentinput->notes){
					$users[$ui]['notes'] += 1;
				}
				if($studentinput->feedback){
					$users[$ui]['feedback'] += 1;
				}
			}
			$usertotalviews[] = $users[$ui]['totalviews'];
		}

		//sort in descending order - use array_multisort as it's better with large arrays than usort
		//usort($users, function ($a, $b) {  return strcmp($b['totalviews'], $a['totalviews']); });
		array_multisort($usertotalviews, SORT_DESC, SORT_NUMERIC, $users);
		
		return $users; 
	}
	
	/**
     * Returns a table with the usage statistics for the staff passed to the function
	 *
     * @global stdClass $CFG The global configuration instance.
     * @param array $uids The array of staff user ids.
	 * @param bool $showptutees Whether or not to display personal tutees.
	 * @param bool $overview Whether or not to display just an overview with aggregated data only and no user data or closing table tags.
	 * @param str $overviewname The name of the overview (e.g. category or course).
	 * @param str $overviewlink The link for the overview name (so users can navigate to subcategories).
	 * @param bool $printheader Whether to print the headers or not, so overviews can show aggregated data for subcategories in a single table.
     * @return str Table containing the staff statistics (missing the </tbody></table> tag if an overview).
     */
	public function get_staff_statistics_table($uids, $showptutees=false, $overview=false, $overviewname = "", $overviewlink = "", $printheader=true){
		global $CFG;
		$i = 0;	
		$exceltable = array();
		
		//populate the $exceltable array with the previous data, if it's not the first row of an overview
		if($overview == true && $printheader == false){
			$exceltable = $_SESSION["exp_sess"];
			$i = count($exceltable);	
		}
				
		if($printheader==true){
			$usagetable = "<table id=\"usagetable\" class=\"table table-striped table-bordered table-hover\" style=\"text-align:center\">";
			//print the table headings
				$headers = array(get_string('tutortblheader_name', 'report_myfeedback'));
				$headerhelptext = array(get_string('usagetblheader_name_info', 'report_myfeedback'));
				
				if($overview == true){
					$headers[] = ucfirst(get_string('staff', 'report_myfeedback'));
					$headerhelptext[] = get_string('usagetblheader_staff_info', 'report_myfeedback');
				}
				array_push($headers, 
					get_string('usagetblheader_totalviews', 'report_myfeedback'),
					get_string('usagetblheader_ownreportviews', 'report_myfeedback'),
					get_string('usagetblheader_mystudenttabviews', 'report_myfeedback'),
					get_string('usagetblheader_studentsviewed', 'report_myfeedback'),
					get_string('usagetblheader_studentreportviews', 'report_myfeedback'),
					get_string('tabs_tutor', 'report_myfeedback') . " " . get_string('views', 'report_myfeedback'),
					get_string('tabs_mtutor', 'report_myfeedback') . " " . get_string('views', 'report_myfeedback'),
					get_string('progadmin_dashboard', 'report_myfeedback') . " " . get_string('views', 'report_myfeedback'),
					get_string('usagetblheader_downloads', 'report_myfeedback'),
					get_string('usagetblheader_lastaccessed', 'report_myfeedback'));
					
				array_push($headerhelptext, 
					get_string('usagetblheader_totalviews_info', 'report_myfeedback'),
					get_string('usagetblheader_ownreportviews_info', 'report_myfeedback'),
					get_string('usagetblheader_mystudenttabviews_info', 'report_myfeedback'),
					get_string('usagetblheader_studentsviewed_info', 'report_myfeedback'),
					get_string('usagetblheader_studentreportviews_info', 'report_myfeedback'),
					get_string('usagetblheader_personaltutorviews_info', 'report_myfeedback'),
					get_string('usagetblheader_modtutorviews_info', 'report_myfeedback'),
					get_string('usagetblheader_progadminviews_info', 'report_myfeedback'),
					get_string('usagetblheader_downloads_info', 'report_myfeedback'),
					get_string('usagetblheader_lastaccessed_info', 'report_myfeedback'));
					
				if($showptutees){
					$headers[] = get_string('personaltutees', 'report_myfeedback');
					$headerhelptext[] = get_string('usagetblheader_personaltutees_info', 'report_myfeedback');
				}
				$usagetable .= $this->get_table_headers($headers, $headerhelptext);
				//print the table data
				$usagetable .= "<tbody>";
			}
							
			//get the table data
			if(sizeOf($uids) > 0){
				//get all the staff stats
				$all_staff_stats = $this->get_overall_staff_usage_statistics($uids);	
			
				//print stats for each staff member
				//TODO: make the drop down button toggle to show/hide student data for the category. Currently it shows and can't be toggled off
				$staffusagetable = "";
				$studentsviewedbyanystaff = array();
				
				$overallstats = array();
				$overallstats['totalviews'] = 0;				
				$overallstats['ownreportviews'] = 0;				
				$overallstats['mystudentstabviews'] = 0;							
				$overallstats['studentreportviews'] = 0;				
				$overallstats['ptutordashboardviews'] = 0;				
				$overallstats['modtutordashboardviews'] = 0;				
				$overallstats['deptadmindashboardviews'] = 0;				
				$overallstats['downloads'] = 0;
				$overallstats['lastaccess'] = 0;
				
				foreach($all_staff_stats as $one_staff_stats){
					$staffusagetable .= "<tr>";					
						if(sizeOf($uids) > 1){
							$staffusagetable .= "<td>" . "<a href=\"" . $CFG->wwwroot . "/report/myfeedback/index.php?currenttab=usage&reporttype=staffmember&reportuserid=" . $one_staff_stats['userid'] . "\" title=\"" . ucfirst(get_string('view', 'report_myfeedback')) . " " . $one_staff_stats['name'] . get_string('apostrophe_s', 'report_myfeedback') . strtolower(get_string('usagereport', 'report_myfeedback')) . "\" rel=\"tooltip\">" . $one_staff_stats['name']."</a></td>";
						}else{
							$staffusagetable .= "<td>" . "<a href=\"" . $CFG->wwwroot . "/report/myfeedback/index.php?currenttab=usage&reporttype=staffmember&reportuserid=" . $one_staff_stats['userid'] . "\" title=\"" . ucfirst(get_string('view', 'report_myfeedback')) . " " . $one_staff_stats['name'] . get_string('apostrophe_s', 'report_myfeedback') . " " .  get_string('usagereport', 'report_myfeedback') . "\" rel=\"tooltip\">" . $one_staff_stats['name']."</a></td>";
						}
						$staffusagetable .= "<td>" . $one_staff_stats['totalviews'] . "</td>";
						$staffusagetable .= "<td>" . $one_staff_stats['ownreportviews'] . "</td>";
						//$staffusagetable .= "<td>" . "&nbsp;" . "</td>"; //roles?
						$staffusagetable .= "<td>" . $one_staff_stats['mystudentstabviews'] . "</td>";
						$staffusagetable .= "<td>" . count($one_staff_stats['studentsviewed']) . "</td>";
						$staffusagetable .= "<td>" . $one_staff_stats['studentreportviews'] . "</td>";
						$staffusagetable .= "<td>" . $one_staff_stats['ptutordashboardviews'] . "</td>";
						$staffusagetable .= "<td>" . $one_staff_stats['modtutordashboardviews'] . "</td>";
						$staffusagetable .= "<td>" . $one_staff_stats['deptadmindashboardviews'] . "</td>";
						$staffusagetable .= "<td>" . $one_staff_stats['downloads'] . "</td>";
						
						//only print the excel data if it's not an overview
						if($overview == false){
							//store the excel export data
							$exceltable[$i]['name'] = $one_staff_stats['name'];
							$exceltable[$i]['totalviews'] = $one_staff_stats['totalviews'];
							$exceltable[$i]['ownreportviews'] = $one_staff_stats['ownreportviews'];
							$exceltable[$i]['mystudentstabviews'] = $one_staff_stats['mystudentstabviews'];
							$exceltable[$i]['studentsviewed'] = count($one_staff_stats['studentsviewed']);
							$exceltable[$i]['studentreportviews'] = $one_staff_stats['studentreportviews'];
							$exceltable[$i]['ptutordashboardviews'] = $one_staff_stats['ptutordashboardviews'];
							$exceltable[$i]['modtutordashboardviews'] = $one_staff_stats['modtutordashboardviews'];
							$exceltable[$i]['deptadmindashboardviews'] = $one_staff_stats['deptadmindashboardviews'];
							$exceltable[$i]['downloads'] = $one_staff_stats['downloads'];
						}
						
						//display last accessed
						$staffusagetable .= "<td>";
						//check if the user has ever accessed the report. If not lastaccess will be 0
						if($one_staff_stats['lastaccess'] > 0){
							$staffusagetable .= date('d-m-Y H:i', $one_staff_stats['lastaccess']);
							//only print the excel data if it's not an overview
							if($overview == false){
								$exceltable[$i]['lastaccess'] = date('d-m-Y H:i', $one_staff_stats['lastaccess']);
							}
						}else{
							$staffusagetable .= "-";
							//only print the excel data if it's not an overview
							if($overview == false){
								$exceltable[$i]['lastaccess'] = "";
							}
						}
						$staffusagetable .= "</td>";
						if($showptutees){
							if($one_staff_stats['ptutees'] > 0){
							$staffusagetable .= "<td><a href=\"" . $CFG->wwwroot . "/report/myfeedback/index.php?currenttab=usage&reporttype=personaltutorstudents&reportuserid=" . $one_staff_stats['userid'] . "\" title=\"" . ucfirst(get_string('view', 'report_myfeedback')) . " " . $one_staff_stats['name'] . get_string('apostrophe_s', 'report_myfeedback') . strtolower(get_string('personaltutees', 'report_myfeedback')) . "\" rel=\"tooltip\">" . $one_staff_stats['ptutees'] . " " .strtolower(get_string('personaltutees', 'report_myfeedback')) . "</a></td>";
							//only print the excel data if it's not an overview
							if($overview == false){
								$exceltable[$i]['ptutees'] = $one_staff_stats['ptutees'];
							}
							}else{
								$staffusagetable .= "<td>&nbsp;</td>";
							}
						}
					
					$staffusagetable .= "</tr>";
				
					//we only need overall stats if we are looking at more than one staff member
					if(sizeOf($uids) > 1){
						//add the staff stats to the overall stats
						if($one_staff_stats['totalviews'] > 0){
							$overallstats['viewedby'] += 1;
						}
						$overallstats['totalviews'] += $one_staff_stats['totalviews'];
						$overallstats['ownreportviews'] += $one_staff_stats['ownreportviews'];
						$overallstats['mystudentstabviews'] += $one_staff_stats['mystudentstabviews'];
						//students viewed
						foreach($one_staff_stats['studentsviewed'] as $studentid => $numberviews){
							$studentsviewedbyanystaff[$studentid] = $numberviews;
						}
						$overallstats['studentreportviews'] += $one_staff_stats['studentreportviews'];
						
						$overallstats['ptutordashboardviews'] += $one_staff_stats['ptutordashboardviews'];
						$overallstats['modtutordashboardviews'] += $one_staff_stats['modtutordashboardviews'];
						$overallstats['deptadmindashboardviews'] += $one_staff_stats['deptadmindashboardviews'];
						$overallstats['downloads'] += $one_staff_stats['downloads'];
						if($one_staff_stats['lastaccess'] > $overallstats['lastaccess']){
							$overallstats['lastaccess'] = $one_staff_stats['lastaccess'];
						}
						$overallstats['ptutees'] += $one_staff_stats['ptutees'];
					}
					//only iterate i if it's not an overview - i.e. the results will be included in the export file
					if($overview == false){
						$i++;
					}
				} //end for loop
				
				//first print overall category stats
				
				//Only show overall stats if there's more than one staff member
				if(sizeOf($uids) > 1){
					$usagetable .= '<tr';
					//Apply a darker sub-heading style to the overall category stats
					if($overview == false){
						$usagetable .= 'class="highlight"';
					}
					$usagetable .= '>';
					//get the course / category name
					$usagetable .= '<td id="long-name">';
					//if it's not an overview, then print the count in brackets in the same column
					if($overview == false){
						//show an arrow if there are items underneath (staff)
						$usagetable .= "<span class=\"assess-br modangle\">&#9660;</span>&nbsp;";
						$usagetable .= $overviewname . " (" . sizeOf($uids) . " " . get_string('staff', 'report_myfeedback') .")";
					}else{
						//if it is an overview
						//get the course/category name and display a link if it's a subcategory
						if($overviewlink != "" && $printheader == false){
							$usagetable .=  "<a href=\"" . $CFG->wwwroot . $overviewlink . "\">" . $overviewname .  "</a>";
						}else{
							if($printheader == true){
								//show an arrow if there are items underneath (sub categories)
								$usagetable .= "<span class=\"assess-br modangle\">&#9660;</span>&nbsp;";
							}
							$usagetable .=  $overviewname;
						}
						//else if it is an overview make a new column and print just the number there and link it if there's a link
						$usagetable .=  "</td><td>";
						$usagetable .=  "<a href=\"" . $CFG->wwwroot . str_replace("overview", "", $overviewlink) . "\">".sizeOf($uids)."</a>";
						
					}
					$usagetable .= "</td>";
					$usagetable .= "<td>" . $overallstats['totalviews'] . "</td>";
					$usagetable .= "<td>" . $overallstats['ownreportviews'] . "</td>";
					$usagetable .= "<td>" . $overallstats['mystudentstabviews'] . "</td>";
					$usagetable .= "<td>" . count($studentsviewedbyanystaff) . "</td>";
					$usagetable .= "<td>" . $overallstats['studentreportviews'] . "</td>";
					
					$usagetable .= "<td>" . $overallstats['ptutordashboardviews'] . "</td>";
					$usagetable .= "<td>" . $overallstats['modtutordashboardviews'] . "</td>";
					$usagetable .= "<td>" . $overallstats['deptadmindashboardviews'] . "</td>";
					$usagetable .= "<td>" . $overallstats['downloads'] . "</td>";
					if($overallstats['lastaccess'] > 0){
						$usagetable .= "<td>" . date('d-m-Y H:i', $overallstats['lastaccess']) . "</td>";
					}else{
						$usagetable .= "<td>&nbsp;</td>";
					}
					$usagetable .= "<td>" . $overallstats['ptutees'] . "</td>";
					$usagetable .= "</tr>";
					
						//store the excel export data
						//indicate the parent category in the excel export (there's no arrow to show this like the on screen version)
						if($printheader == true){
							$exceltable[$i]['name'] = $overviewname . " (" . get_string('parent', 'report_myfeedback') . ")";
						}else{
							$exceltable[$i]['name'] = $overviewname;
						}
						if($overview == true){
							$exceltable[$i]['staff'] = count($uids);
						}
						$exceltable[$i]['totalviews'] = $overallstats['totalviews'];
						$exceltable[$i]['ownreportviews'] = $overallstats['ownreportviews'];
						$exceltable[$i]['mystudentstabviews'] = $overallstats['mystudentstabviews'];
						$exceltable[$i]['studentsviewed'] = count($studentsviewedbyanystaff);
						$exceltable[$i]['studentreportviews'] = $overallstats['studentreportviews'];
						$exceltable[$i]['ptutordashboardviews'] = $overallstats['ptutordashboardviews'];
						$exceltable[$i]['modtutordashboardviews'] = $overallstats['modtutordashboardviews'];
						$exceltable[$i]['deptadmindashboardviews'] = $overallstats['deptadmindashboardviews'];
						$exceltable[$i]['downloads'] = $overallstats['downloads'];
						if($overallstats['lastaccess'] > 0){
							$exceltable[$i]['lastaccess'] = date('d-m-Y H:i', $overallstats['lastaccess']);
						}else{
							$exceltable[$i]['lastaccess'] = "";
						}
						$exceltable[$i]['ptutees'] = $overallstats['ptutees'];
				}
				//then print the staff usage stats, if it's not an overview
				if($overview == false){
					//swap the tds for trs and remove the end thead tag, as the first row is a summary
					$usagetable = str_replace("</thead>", "", $usagetable);
					$usagetable = str_replace("<tbody>", "", $usagetable);
					$usagetable = str_replace("<td", "<th", $usagetable);
					$usagetable = str_replace("</td>", "</th>", $usagetable);
					$usagetable .= '</thead></tbody>';
					$usagetable .= $staffusagetable;
					$usagetable .= '</tbody></table>'; 
				}else{
					if($printheader == true){
						//swap the tds for trs and remove the end thead tag, as the first row is a summary
						$usagetable = str_replace("</thead>", "", $usagetable);
						$usagetable = str_replace("<tbody>", "", $usagetable);
						$usagetable = str_replace("<td", "<th", $usagetable);
						$usagetable = str_replace("</td>", "</th>", $usagetable);
						$usagetable .= '</thead></tbody>';
					}
				}
			}elseif(sizeOf($uids) == 0){
			//if there are no users in the category show the category name and that there are 0 staff
				if($overview == false){
					$usagetable .= "<tr><td>".$overviewname." (0 ".lcfirst (get_string('staff', 'report_myfeedback')).")</td>";
				}else{
					$usagetable .= "<tr><td>".$overviewname."</td><td>0</td>";
					$exceltable[$i]['name'] = $overviewname;
					$exceltable[$i]['staff'] = 0;
				}
				$headercount = 10;
				if($showptutees == true){
					$headercount++;
				}
				for($i=0;$i<$headercount;$i++){
					$usagetable .= "<td>&nbsp;</td>";
				}
				$usagetable .= "</tr>";
			}
			//set the excel table export data
			$_SESSION['exp_sess'] = $exceltable;
			return $usagetable;
	}

	/**
     * Returns a table with the usage statistics for the students passed to the function
	 *
     * @global stdClass $CFG The global configuration instance.
     * @param array $uids The array of students user ids.
	 * @param bool $overview Whether or not to display just an overview with aggregated data only and no user data or closing table tags.
	 * @param str $overviewname The name of the overview (e.g. category or course).
	 * @param str $overviewlink The link for the overview name (so users can navigate to subcategories).
	 * @param bool $printheader Whether to print the headers or not, so overviews can show aggregated data for subcategories in a single table.
     * @return str Table containing the student statistics (missing the </tbody></table> tag if an overview).
     */
    public function get_student_statistics_table($uids, $reporttype, $overview=false, $overviewname = "", $overviewlink = "", $printheader=true){
		global $CFG;
		$i = 0;	
		$exceltable = array();
		
		//populate the $exceltable array with the previous data, if it's not the first row of an overview
		if($overview == true && $printheader == false){
			$exceltable = $_SESSION["exp_sess"];
			$i = count($exceltable);
		}
		if($printheader==true){
			$usagetable = "<table id=\"usagetable\" class=\"table table-striped table-bordered table-hover\" style=\"text-align:center\">";
			//print the table headings
			$headers = array(get_string('tutortblheader_name', 'report_myfeedback'));
			$headerhelptext = array(get_string('usagetblheader_name_info', 'report_myfeedback'));
			
			if($overview == true){
				$headers[] = ucfirst(get_string('dashboard_students', 'report_myfeedback'));
				$headerhelptext[] = get_string('usagetblheader_students_info', 'report_myfeedback');
			}
			array_push($headers, 
				get_string('usagetblheader_viewed', 'report_myfeedback'),
				get_string('usagetblheader_totalviews', 'report_myfeedback'),
				get_string('usagetblheader_notes', 'report_myfeedback'),
				get_string('usagetblheader_tiifeedback', 'report_myfeedback'),
				get_string('usagetblheader_downloads', 'report_myfeedback'),
				get_string('tabs_ptutor', 'report_myfeedback'),
				get_string('usagetblheader_lastaccessed', 'report_myfeedback'));
			
			
			array_push($headerhelptext, 
				get_string('usagetblheader_viewed_info', 'report_myfeedback'),
				get_string('usagetblheader_totalviews_info', 'report_myfeedback'),
				get_string('usagetblheader_notes_info', 'report_myfeedback'),
				get_string('usagetblheader_tiifeedback_info', 'report_myfeedback'),
				get_string('usagetblheader_downloads_info', 'report_myfeedback'),
				get_string('usagetblheader_personaltutor_info', 'report_myfeedback'),
				get_string('usagetblheader_lastaccessed_info', 'report_myfeedback'));
				
			$usagetable .= $this->get_table_headers($headers, $headerhelptext);
			//print the table data
			$usagetable .= "<tbody>";
		}	
		//get the table data
		if(sizeOf($uids) > 0){
			//get all the student stats
			$all_students_stats = $this->get_overall_student_usage_statistics($uids);

			//print stats for each student
			//TODO: make the drop down button toggle to show/hide student data for the category. Currently it shows and can't be toggled off
			$studentusagetable = "";
			$overallstats = array();
			$overallstats['totalviews'] = 0;
			$overallstats['notes'] = 0;
			$overallstats['feedback'] = 0;
			$overallstats['downloads'] = 0;
			$overallstats['lastaccess'] = 0;
			$overallstats['viewedby'] = 0;
			$personaltutors = array();
			
			foreach($all_students_stats as $one_student_stats){
				$studentusagetable .= "<tr>";
				if(sizeOf($uids) > 1){					
					$studentusagetable .= "<td>" . "<a href=\"" . $CFG->wwwroot . "/report/myfeedback/index.php?currenttab=usage&reporttype=student&reportuserid=" . $one_student_stats['userid'] . "\" title=\"" . ucfirst(get_string('view', 'report_myfeedback')) . " " .  $one_student_stats['name'] . get_string('apostrophe_s', 'report_myfeedback') . " " .  get_string('usagereport', 'report_myfeedback') . "\" rel=\"tooltip\">" . $one_student_stats['name']."</a></td>";
				}else{
					$studentusagetable .= "<td>" . "<a href=\"" . $CFG->wwwroot . "/report/myfeedback/index.php?userid=" . $one_student_stats['userid'] . "\" title=\"" . ucfirst(get_string('view', 'report_myfeedback')) . " " .  $one_student_stats['name'] . get_string('apostrophe_s', 'report_myfeedback') . " " . get_string('dashboard', 'report_myfeedback') . "\" rel=\"tooltip\">" . $one_student_stats['name']."</a></td>";
				}
					//TODO: get the number of staff who have viewed the report for that student and display it under viewed by followed by " staff"
					$studentusagetable .= "<td>";
					$studentusagetable .= ($one_student_stats['totalviews'] > 0) ? "yes" : "&nbsp;";
					$studentusagetable .= "</td>";
					$studentusagetable .= "<td>" . $one_student_stats['totalviews'] . "</td>";
					$studentusagetable .= "<td>" . $one_student_stats['notes'] . "</td>";
					$studentusagetable .= "<td>" . $one_student_stats['feedback'] . "</td>";
					$studentusagetable .= "<td>" . $one_student_stats['downloads'] . "</td>";
					$studentusagetable .= "<td>";
					
					//only print the excel data if it's not an overview
					if($overview == false){
						//store the excel export data
						$exceltable[$i]['name'] = $one_student_stats['name'];
						$exceltable[$i]['viewed'] = ($one_student_stats['totalviews'] > 0) ? "yes" : "";
						$exceltable[$i]['views'] = $one_student_stats['totalviews'];
						$exceltable[$i]['notes'] = $one_student_stats['notes'];
						$exceltable[$i]['feedback'] = $one_student_stats['feedback'];
						$exceltable[$i]['downloads'] = $one_student_stats['downloads'];
					}
				
					//link to the personal tutor usage report
					if($one_student_stats['personaltutorid'] > 0){
						$studentusagetable .= "<a href=\"" . $CFG->wwwroot . "/report/myfeedback/index.php?reportuserid=" . $one_student_stats['personaltutorid'] . "&currenttab=usage&reporttype=staffmember\" title=\"" . ucfirst(get_string('view', 'report_myfeedback')) . " " .  trim($one_student_stats['personaltutor']) . get_string('apostrophe_s', 'report_myfeedback') . " " . get_string('usagereport', 'report_myfeedback') . "\" rel=\"tooltip\">" . $one_student_stats['personaltutor'] . "</a>";
						//only print the excel data if it's not an overview
						if($overview == false){
							$exceltable[$i]['personaltutor'] = $one_student_stats['personaltutor'];
						}
						$personaltutors[$one_student_stats['personaltutorid']] = $one_student_stats['personaltutor'];
					}else{
						$studentusagetable .= "&nbsp;";
						//only print the excel data if it's not an overview
						if($overview == false){
							$exceltable[$i]['personaltutor'] = "";
						}
					}
					$studentusagetable .= "</td>";
					$studentusagetable .= "<td>";
					//check if the user has ever accessed the report. If not lastaccess will be 0
					if($one_student_stats['lastaccess'] > 0){
						$studentusagetable .= date('d-m-Y H:i', $one_student_stats['lastaccess']);
						//only print the excel data if it's not an overview
						if($overview == false){
							$exceltable[$i]['lastaccess'] = date('d-m-Y H:i', $one_student_stats['lastaccess']);
						}
					}else{
						$studentusagetable .= "-";
						//only print the excel data if it's not an overview
						if($overview == false){
							$exceltable[$i]['lastaccess'] = "";
						}
					}
					$studentusagetable .= "</td>";
				
				$studentusagetable .= "</tr>";
				
				//add this students stats to the overall stats
				if($one_student_stats['totalviews'] > 0){
					$overallstats['viewedby'] += 1;
				}
				$overallstats['totalviews'] += $one_student_stats['totalviews'];
				$overallstats['notes'] += $one_student_stats['notes'];
				$overallstats['feedback'] += $one_student_stats['feedback'];
				$overallstats['downloads'] += $one_student_stats['downloads'];
				if($one_student_stats['lastaccess'] > $overallstats['lastaccess']){
					$overallstats['lastaccess'] = $one_student_stats['lastaccess'];
				}
				//only iterate i if it's not an overview - i.e. the results will be included in the export file
				if($overview == false){
					$i++;
				}
			} //end for loop
			
			//first print overall category stats
			if(sizeOf($uids) > 1){
				$usagetable .= '<tr';
					//Apply a darker sub-heading style to the overall category stats
					if($overview == false){
						$usagetable .= 'class="highlight"';
					}
					$usagetable .= '>';
				//get the course / category name
				$usagetable .= '<td id="long-name">';
				//if it's not an overview, then print the count in brackets in the same column
				if($overview == false){
					//show an arrow if there are items underneath (students)
					$usagetable .= "<span class=\"assess-br modangle\">&#9660;</span>&nbsp;";
					if($reporttype == "personaltutorstudents"){
						$usagetable .= $overviewname . " (" . sizeOf($uids) . " " . strtolower(get_string('personaltutees', 'report_myfeedback')) .")";
					}else{
						$usagetable .= $overviewname . " (" . sizeOf($uids) . " " . lcfirst(get_string('dashboard_students', 'report_myfeedback')) .")";
					}
				}else{
					//get the course/category name and display a link if it's a subcategory
					if($overviewlink != "" && $printheader == false){
						$usagetable .=  "<a href=\"" . $CFG->wwwroot . $overviewlink . "\">" . $overviewname .  "</a>";
					}else{
						//show an arrow if there are items underneath (sub categories)
						if($printheader == true){
							//show an arrow if there are items underneath (sub categories)
							$usagetable .= "<span class=\"assess-br modangle\">&#9660;</span>&nbsp;";
						}
						$usagetable .=  $overviewname;
					}
					//else if it is an overview make a new column and print just the number there and link it if there's a link
					$usagetable .=  "</td><td>";
					$usagetable .=  "<a href=\"" . $CFG->wwwroot . str_replace("overview", "", $overviewlink) . "\">".sizeOf($uids)."</a>";
				}
				$usagetable .= "</td>";
				$usagetable .= "<td>";
				$usagetable .= (sizeOf($uids) > 1) ? $overallstats['viewedby'] : "&nbsp;";
				$usagetable .= "</td>";
				$usagetable .= "<td>" . $overallstats['totalviews'] . "</td>";
				$usagetable .= "<td>" . $overallstats['notes'] . "</td>";
				$usagetable .= "<td>" . $overallstats['feedback'] . "</td>";
				$usagetable .= "<td>" . $overallstats['downloads'] . "</td>";
				$usagetable .= "<td>".sizeof($personaltutors)."</td>";
				if($overallstats['lastaccess'] > 0){
					$usagetable .= "<td>".date('d-m-Y H:i', $overallstats['lastaccess'])."</td>";
				}else{
					$usagetable .= "<td>&nbsp;</td>";
				}
				$usagetable .= "</tr>";

				//store the overview excel export data
				//indicate the parent category in the excel export (there's no arrow to show this like the on screen version)
				if($printheader == true){
					if($reporttype == "personaltutorstudents"){
						$exceltable[$i]['name'] = $overviewname . " (" . get_string('tabs_ptutor', 'report_myfeedback') . ")";
					}else{
						$exceltable[$i]['name'] = $overviewname . " (" . get_string('parent', 'report_myfeedback') . ")";
					}
				}else{
					$exceltable[$i]['name'] = $overviewname;
				}
				if($overview == true){
					$exceltable[$i]['students'] = count($uids);
				}
				$exceltable[$i]['viewed'] = (sizeof($uids) > 1) ? $overallstats['viewedby'] : "";
				$exceltable[$i]['views'] = $overallstats['totalviews'];
				$exceltable[$i]['notes'] = $overallstats['notes'];
				$exceltable[$i]['feedback'] = $overallstats['feedback'];
				$exceltable[$i]['downloads'] = $overallstats['downloads'];
				$exceltable[$i]['personaltutors'] = sizeof($personaltutors);
				if($overallstats['lastaccess'] > 0){
					$exceltable[$i]['lastaccess'] = date('d-m-Y H:i', $overallstats['lastaccess']);
				}else{
					$exceltable[$i]['lastaccess'] = "";
				}
			}
			//then print the student usage stats, if it's not an overview
			if($overview == false){
				//swap the tds for trs and remove the end thead tag, as the first row is a summary
				$usagetable = str_replace("</thead>", "", $usagetable);
				$usagetable = str_replace("<tbody>", "", $usagetable);
				$usagetable = str_replace("<td", "<th", $usagetable);
				$usagetable = str_replace("</td>", "</th>", $usagetable);
				$usagetable .= '</thead></tbody>';
				$usagetable .= $studentusagetable;
				$usagetable .= '</tbody></table>'; 
			}else{
				if($printheader == true){
					//swap the tds for trs and remove the end thead tag, as the first row is a summary
					$usagetable = str_replace("</thead>", "", $usagetable);
					$usagetable = str_replace("<tbody>", "", $usagetable);
					$usagetable = str_replace("<td", "<th", $usagetable);
					$usagetable = str_replace("</td>", "</th>", $usagetable);
					$usagetable .= '</thead></tbody>';
				}
			}	
			
		}elseif(sizeOf($uids) == 0){
		//if there are no users in the category show the category name and that there are 0 students
			if($overview == false){
				$usagetable .= "<tr><td>".$overviewname." (0 ".lcfirst(get_string('dashboard_students', 'report_myfeedback')).")</td>";
			}else{
				$usagetable .= "<tr><td>".$overviewname."</td><td>0</td>";
				$exceltable[$i]['name'] = $overviewname;
				$exceltable[$i]['students'] = 0;
			}
			$headercount = 7;
			for($i=0;$i<$headercount;$i++){
				$usagetable .= "<td>&nbsp;</td>";
			}
			$usagetable .= "</tr>";
		}
		//set the excel table export data
		$_SESSION['exp_sess'] = $exceltable;
		return $usagetable;
	}


	
	/**
     * Return the array of My feedback usage logs
	 *
	 * @global stdClass $remotedb DB object
     * @param array $dept_prog Arry with users dept courses user has progadmin capability in
     * @return bool $frommod Whether the call is from Mod tutor dashboard
    */ 
	public function get_user_usage_logs($userid){
		global $remotedb;
		/*$sql = "select 
    			l.eventname,
    			l.contextlevel,
				l.component,
				l.action,
				l.userid,
				l.relateduserid,
				l.timecreated
			from
				{logstore_standard_log} l
			where
				l.component = 'report_myfeedback' and l.userid = ?";*/
		$params = array($userid);
		$usagelogs = $remotedb->get_records('logstore_standard_log', array('component'=>'report_myfeedback','userid'=>$userid));
		//the below doesn't return all of the results. Perhaps because they have to be unique values when you define which fields to return.
        //$usagelogs = $remotedb->get_records('logstore_standard_log', array('component'=>'report_myfeedback','userid'=>$userid), null, 'eventname, contextlevel, component, action, userid, relateduserid, timecreated');
		return $usagelogs;
	}
	
	/**
     * Return the array of My feedback usage logs
	 *
	 * @global stdClass $remotedb DB object
     * @param array $dept_prog Arry with users dept courses user has progadmin capability in
     * @return bool $frommod Whether the call is from Mod tutor dashboard
    */
	public function get_notes_and_feedback($userid){
		global $remotedb;
		/*$sql = " select * from {report_myfeedback} where userid = ?";*/
		$params = array($userid);
		$usagelogs = $remotedb->get_records('report_myfeedback', array('userid'=>$userid));
		//i'm not confident the below will return all the results, so I'm going to return all fields (above) instead
        //$usagelogs = $remotedb->get_records('report_myfeedback', array('userid'=>$userid), null, 'notes, feedback');
		return $usagelogs;
	}

    /**
     * Returns the graph with the z-score with the lowest, highest and median grade
     * also the number who got between 0-39, 40-49, 50-59, 60-69 and 70-100 %
     * 
     * @param int $cid The course id
     * @param array $uids The array of user ids
     * @param bool $pmod Whether this graph is is for mod or dept dashboards
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
                $assess[$k]['icon'] = ($a->itemmodule ? $a->itemmodule : $a->itemtype);
                $assess[$k]['cid'] = $cid;
                $assess[$k]['aid'] = $a->id;
                $assess[$k]['due'] = 0;
                $assess[$k]['non'] = 0;
                $assess[$k]['late'] = 0;
                $assess[$k]['feed'] = 0;
                $assess[$k]['graded'] = 0;
                $assess[$k]['low'] = 0;
                $assess[$k]['feed'] = 0;
                $assess[$k]['score'][$ui] = null;
                $users[$ui]['assess'][$k]['name'] = $a->itemname;
                $users[$ui]['assess'][$k]['icon'] = ($a->itemmodule ? $a->itemmodule : $a->itemtype);
                $users[$ui]['assess'][$k]['cid'] = 0;
                $users[$ui]['assess'][$k]['aid'] = $cid;
                $users[$ui]['assess'][$k]['due'] = $a->id;
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

        $mainassess = $this->get_assessment_analytics($assess, $uidnum, $display = 'assRec', $style = '', $breakdown = '<p style="margin-top:10px"><span class="assess-br">' . get_string('studentbreakdown', 'report_myfeedback') .
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

    /**
     * This function is not currently in use
     */
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

        $tutor = ($modtut ? '<th>' . get_string('moduletutors', 'report_myfeedback') . '</th><th>' . get_string('tutorgroups', 'report_myfeedback') .
                        '</th><th></a><input title="' . get_string('selectallforemail', 'report_myfeedback') . '" rel ="tooltip" type="checkbox" id="selectall1"/>' . get_string('selectall', 'report_myfeedback') .
                        ' <a class="btn" id="mail1"> ' . get_string('sendmail', 'report_myfeedback') . '</th>' : '<th>' . get_string('personaltutors', 'report_myfeedback') . '</th>
                            <th colspan="4">' . get_string('tutees_plus_minus', 'report_myfeedback') . '</th>');
        $usertable = '<form method="POST" id="emailform1" action=""><table id="ptutor" class="ptutor" width="100%" style="display:none" border="1"><thead><tr class="tableheader">' . $tutor . '</tr></thead><tbody>';

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
	 *
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
                ' . get_string("browsersupport", "report_myfeedback") . '</canvas>
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
	 *
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

    /**
     * Return the course limit on Dept admin dashboard so that if the second level category they are trying
     * to view has more than this amount of courses it asks the user to select individual courses instead as there would be
     * too many courses on that evel to run the stats. This is set in settings or default to 200
	 *
     * @return int The The limit for the number of courses
     */
    public function get_course_limit() {
        $cl = get_config('report_myfeedback');
        return (isset($cl->courselimit) && $cl->courselimit ? $cl->courselimit : 200);
    }

    /**
     * Return the academic years based on the current month 
	 *
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
	 *
     * @global stdClass $remotedb The current DB object
     * @global stdClass $CFG The global config settings
     * @global stdClass $USER The logged in user global properties
     * @global stdClass $OUTPUT The global output properties
     * @return string Return th ename and other details of the personal tutees
     */
    public function get_dashboard_tutees($personaltutorid = 0) {
        global $remotedb, $CFG, $USER, $OUTPUT;
		if($personaltutorid == 0){
			$personaltutorid = $USER->id;
		}
        $myusers = array();
        // get all the mentees, i.e. users you have a direct assignment to as their personal tutor
        if ($usercontexts = $remotedb->get_records_sql("SELECT c.instanceid, c.instanceid, u.id as id, firstname, lastname, email, department
                                                    FROM {role_assignments} ra, {context} c, {user} u
                                                   WHERE ra.userid = ?
                                                         AND ra.contextid = c.id
                                                         AND c.instanceid = u.id
                                                         AND c.contextlevel = " . CONTEXT_USER, array($personaltutorid))) {
            foreach ($usercontexts as $u) {
                $user = $remotedb->get_record('user', array('id' => $u->id, 'deleted' => 0));
                $year = null;
                profile_load_data($user);
                // Segun Babalola, July 11, 2019
                // Adding check for field existence to avoid errors being thrown in UI.
                // The body of the if statement is empty, so not sure whst the purpose is, but leaving in place.
                if (isset($user->profile_field_courseyear) && !$year = $user->profile_field_courseyear) {
                    
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
	 *
     * @global stdClass $remotedb The current DB object
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
                //Also check instance of Moodle as only from 1516 this funtions below work
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
	 *
     * @global stdClass $remotedb The current DB object
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
                 WHERE c.visible=1 AND c.showgrades = 1 AND tp.dtpost < $now ";
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
                 WHERE c.visible=1 AND c.showgrades = 1 AND tp.dtpost < $now ";
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
        }
		$r->close();
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
            $lt = get_config('report_myfeedback');
            $lte = isset($lt->latefeedback) ? $lt->latefeedback : 28;
            $latedays = 86400 * $lte; //86400 is the number of seconds in a day
            if ($a->status != 'new') {
                $dt = max($a->due, $a->sub);
                if ($a->feed_date && ($a->feed_date - $dt > $latedays)) {
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
	 *
     * @global stdClass $remotedb The current DB object
     * @param int $userid The id of the tutees whos graph you are returning
     * @param array $tutor stats from mod tutor tab 
     * @param str An id for the canvas image
     * @param str An id for the assessment table
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
            if ($usermods = get_user_capability_course('report/myfeedback:student', $userid, $doanything = false, $fields = 'shortname,visible')) {
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
                        $userid . $coid . '" width="190" height="60" style="border:0px solid #d3d3d3;">' . get_string('browsersupport', 'report_myfeedback') . '</canvas>
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
        $height = '61px';
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
            $cdue = $cnon = $clate = $cgraded = $cfeed = $clow = 0;
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
                $cnam .= $minortable . $cse3 . "</td></tr></table>";
                $cvas .= '<table style="display:none" class="accord" align="center"><tr><td>
                <canvas id="myCanvas1' . $userid . $k . '" width="190" height="51" style="border:0px solid #d3d3d3;">' .
                        get_string('browsersupport', 'report_myfeedback') . '</canvas>
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
	

	/**
     * Return the arry of dept admin categories and courses 
	 *
     * @param array $dept_prog Arry with users dept courses user has progadmin capability in
     * @return bool $frommod Whether the call is from Mod tutor dashboard
     */
    public function get_prog_admin_dept_prog($dept_prog, $frommod = null) {
        global $CFG;
        // Segun Babalola, July 10, 2019.
        // Commenting out require statement for coursecatlib.php, to avoid deprecation messages.
        // require_once($CFG->libdir . '/coursecatlib.php');
        $cat = array();
        $prog = array();
        $tomod = array('dept' => '', 'prog' => '');
        foreach ($dept_prog as $dp) {
            $catid = ($dp->category ? $dp->category : 0);
            if ($catid) {
                // Segun Babalola, July 10, 2019.
                // Replacing coursecat::get with core_course_category::get to avoid deprecation messages.
                $cat = core_course_category::get($catid, $strictness = MUST_EXIST, $alwaysreturnhidden = true); //Use strictness so even hidden category names are shown without error
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
        //Sort the categories and courses in alphabetic order but case insentive
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
     * Return the oerridden grade if it is overriden else false 
	 *
     * @param int $itemid The grade_item id
     * @param int $userid The userid
     * @return mixed The overridden grade or false
     */
    public function is_grade_overridden($itemid, $userid) {
        global $remotedb;
        $sql = "SELECT DISTINCT id, finalgrade, overridden
                FROM {grade_grades}
                WHERE itemid=? AND userid=?";
        $params = array($itemid, $userid);
        $overridden = $remotedb->get_record_sql($sql, $params);
        if ($overridden && $overridden->overridden > 0) {
            return $overridden->finalgrade;
        }
        return false;
    }
    
    /**
     * Return the display type for the course grade
	 *
     * @param int $cid The course id
     * @return int The display type numeric value for letter, percentage, number etc.
     */
    public function get_course_grade_displaytype($cid) {
        global $remotedb;
        $sql = "SELECT DISTINCT id, value
                FROM {grade_settings}
                WHERE courseid=? AND name='displaytype'";
        $param = array($cid);
        $displaytype = $remotedb->get_record_sql($sql, $param);
        return $displaytype ? $displaytype->value : '';
    }

    /**
     * Get data from database to populate the overview and feedback table 
	 *
     * @param bool $archive Whether it's an archive year
     * @param int $checkdb The academic year value eg. 1415
     * @return str The DB/remotedb results of submission and feedback for the user referred to in the url after
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
                            '' AS partname,
                            -1 AS usegrademark,
                            gg.feedback AS feedbacklink,
                            gi.grademax AS highestgrade,
                            -1 AS highestmarks,
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
                            '' AS partname,
                            -1 AS usegrademark,
                            gg.feedback AS feedbacklink,
                            gi.grademax AS highestgrade,
							-1 AS highestmarks,
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
                        JOIN {assign_plugin_config} apc on a.id = apc.assignment AND apc.name='enabled' AND plugin = 'onlinetext'
                        LEFT JOIN {assign_grades} ag ON a.id = ag.assignment AND ag.userid=$userid ";
            if (!$archive || ($archive && $checkdb > 1314)) {//when the new assign_user_flags table came in
                $sql .= "LEFT JOIN {assign_user_flags} auf ON a.id = auf.assignment AND auf.workflowstate = 'released'
                        AND  (auf.userid = $userid OR a.markingworkflow = 0) ";
            }
            if (!$archive || ($archive && $checkdb > 1112)) {//when the new grading_areas table came in
                $sql .= "LEFT JOIN {grading_areas} ga ON con.id = ga.contextid ";
            }
            $sql .= "LEFT JOIN {assign_submission} su ON a.id = su.assignment AND su.userid = $userid
                        WHERE c.visible=1 AND c.showgrades = 1 AND cm.visible=1 ";
            array_push($params, $now, $userid, $now);
        }
		if ($this->mod_is_available("coursework")) {
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
                               '' AS partname,
                               -1 AS usegrademark,
                               gg.feedback AS feedbacklink,
                               gi.grademax AS highestgrade,
							   -1 AS highestmarks,
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
                              AND gi.itemmodule = 'quiz'
                        JOIN {grade_grades} gg ON gi.id=gg.itemid AND gg.userid = ?
                        JOIN {course_modules} cm ON gi.iteminstance=cm.instance AND c.id=cm.course
                        JOIN {context} con ON cm.id = con.instanceid AND con.contextlevel=70
                        JOIN {modules} m ON cm.module = m.id AND gi.itemmodule = m.name AND gi.itemmodule = 'quiz'
                        JOIN {quiz} a ON a.id=gi.iteminstance AND a.course=gi.courseid ";
            if (!$archive || ($archive && $checkdb > 1112)) {//when the new grading_areas table came in
                $sql .= "LEFT JOIN {grading_areas} ga ON con.id = ga.contextid ";
            }
            $sql .= "WHERE c.visible=1 AND c.showgrades = 1 AND cm.visible=1 ";
            array_push($params, $userid);
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
                               '' AS partname,
                               -1 AS usegrademark,
                               gg.feedback AS feedbacklink,
                               gi.grademax AS highestgrade,
							   -1 AS highestmarks,
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
                               su.submission_grade AS grade,
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
                               -1 AS highestmarks,
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
                        LEFT JOIN {turnitintool_parts} tp ON su.submission_part = tp.id ";
            if (!$archive || ($archive && $checkdb > 1112)) {//when the new grading_areas table came in
                $sql .= "LEFT JOIN {grading_areas} ga ON con.id = ga.contextid ";
            }
            $sql .= "WHERE c.visible = 1 AND c.showgrades = 1 AND cm.visible=1 AND tp.dtpost < $now ";
            array_push($params, $now, $userid, $now);
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
                               tp.maxmarks AS highestmarks,
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
                        LEFT JOIN {turnitintooltwo_parts} tp ON su.submission_part = tp.id ";
            if (!$archive || ($archive && $checkdb > 1112)) {//when the new grading_areas table came in
                $sql .= "LEFT JOIN {grading_areas} ga ON con.id = ga.contextid ";
            }
            $sql .= "WHERE c.visible = 1 AND c.showgrades = 1 AND cm.visible=1 AND tp.dtpost < $now ";
            array_push($params, $now, $userid, $now);
        }
        //$sql .= " ORDER BY duedate";
        // Get a number of records as a moodle_recordset using a SQL statement.
        $rs = $remotedb->get_recordset_sql($sql, $params, $limitfrom = 0, $limitnum = 0);
        return $rs;
    }

    /**
     * Return the overview and feedback comments tab tables with user information
	 *
     * @global stdClass $CFG The global config object 
     * @global stdClass $OUTPUT The global OUTPUT object
     * @global stdClass $USER The logged-in user object
     * @param str $tab The current tab/dashboard we are viewing
     * @param bool $ptutor Whether the user is a personal atutor
     * @param bool $padmin Whether the user is a Dept admin
     * @param in $arch The academic year eg. 1516
     * @return str A table with the content of submission and feedback for the user referred to in the url after
     *         userid=
     */
    public function get_content($tab = NULL, $ptutor = NULL, $padmin = NULL, $arch = NULL) {
        global $CFG, $OUTPUT, $USER;

        $userid = optional_param('userid', 0, PARAM_INT); // User id.
        if (empty($userid)) {
            $userid = $USER->id;
        }
        $checkdb = $arch ? $arch : 'current';
        $archive = false;
        if (isset($_SESSION['viewyear']) && $_SESSION['viewyear'] != 'current') {
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
        $course_h_msg = get_string('gradetblheader_course_info', 'report_myfeedback');
        $course_h_icon = '<img src="' . 'pix/info.png' . '" ' .
                ' alt="-" title="' . $course_h_msg . '" rel="tooltip"/>';
        $assessment_h_msg = get_string('gradetblheader_assessment_info', 'report_myfeedback');
        $assessment_h_icon = '<img src="' . 'pix/info.png' . '" ' .
                ' alt="-" title="' . $assessment_h_msg . '" rel="tooltip"/>';
        $type_h_msg = get_string('gradetblheader_type_info', 'report_myfeedback');
        $type_h_icon = '<img src="' . 'pix/info.png' . '" ' .
                ' alt="-" title="' . $type_h_msg . '" rel="tooltip"/>';
        $duedate_h_msg = get_string('gradetblheader_duedate_info', 'report_myfeedback');
        $duedate_h_icon = '<img src="' . 'pix/info.png' . '" ' .
                ' alt="-" title="' . $duedate_h_msg . '" rel="tooltip"/>';
        $submission_h_msg = get_string('gradetblheader_submissiondate_info', 'report_myfeedback');
        $submission_h_icon = '<img src="' . 'pix/info.png' . '" ' .
                ' alt="-" title="' . $submission_h_msg . '" rel="tooltip"/>';
        $fullfeedback_h_msg = get_string('gradetblheader_feedback_info', 'report_myfeedback');
        $fullfeedback_h_icon = '<img src="' . 'pix/info.png' . '" ' .
                ' alt="-" title="' . $fullfeedback_h_msg . '" rel="tooltip"/>';
        $genfeedback_h_msg = get_string('gradetblheader_generalfeedback_info', 'report_myfeedback');
        $genfeedback_h_icon = '<img src="' . 'pix/info.png' . '" ' .
                ' alt="-" title="' . $genfeedback_h_msg . '" rel="tooltip"/>';
        $grade_h_msg = get_string('gradetblheader_grade_info', 'report_myfeedback');
        $grade_h_icon = '<img src="' . 'pix/info.png' . '" ' .
                ' alt="-" title="' . $grade_h_msg . '" rel="tooltip"/>';
        $range_h_msg = get_string('gradetblheader_range_info', 'report_myfeedback');
        $range_h_icon = '<img src="' . 'pix/info.png' . '" ' .
                ' alt="-" title="' . $range_h_msg . '" rel="tooltip"/>';
        $bar_h_msg = get_string('gradetblheader_bar_info', 'report_myfeedback');
        $bar_h_icon = '<img src="' . 'pix/info.png' . '" ' .
                ' alt="-" title="' . $bar_h_msg . '" rel="tooltip"/>';
        $reflect_h_msg = get_string('gradetblheader_selfreflectivenotes_info', 'report_myfeedback');
        $reflect_h_icon = '<img src="' . 'pix/info.png' . '" ' .
                ' alt="-" title="' . $reflect_h_msg . '" rel="tooltip"/>';
        $viewed_h_msg = get_string('gradetblheader_viewed_info', 'report_myfeedback');
        $viewed_h_icon = '<img src="' . 'pix/info.png' . '" ' .
                ' alt="-" title="' . $viewed_h_msg . '" rel="tooltip"/>';

        $title = "<p>" . get_string('provisional_grades', 'report_myfeedback') . "</p>";
        // Print titles for each column: Assessment, Type, Due Date, Submission Date,
        // Submission/Feedback, Grade/Relative Grade.
        $table = "<table id=\"grades\" class=\"grades\" width=\"100%\">
                    <thead>
                            <tr class=\"tableheader\">
                                <th>" .
                get_string('gradetblheader_course', 'report_myfeedback') . " $course_h_icon</th>
                                <th>" .
                get_string('gradetblheader_assessment', 'report_myfeedback') . " $assessment_h_icon</th>
                                <th>" .
                get_string('gradetblheader_type', 'report_myfeedback') . " $type_h_icon</th>
                                <th>" .
                get_string('gradetblheader_duedate', 'report_myfeedback') . " $duedate_h_icon</th>
                                <th>" .
                get_string('gradetblheader_submissiondate', 'report_myfeedback') . " $submission_h_icon</th>
                                <th>" .
                get_string('gradetblheader_feedback', 'report_myfeedback') . " $fullfeedback_h_icon</th>
                                <th>" .
                get_string('gradetblheader_grade', 'report_myfeedback') . " $grade_h_icon</th>
                                                            <th>" .
                get_string('gradetblheader_range', 'report_myfeedback') . " $range_h_icon</th>
                            <th>" .
                get_string('gradetblheader_bar', 'report_myfeedback') . " $bar_h_icon
                </tr>
                        <!--<tabfoot class=\"tabf\" style=\"display: table-header-group\">-->
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
                    <!--</tabfoot>--></thead>
                                                <tbody>";
        // Setup the heading for the Comments table
        $usercontext = context_user::instance($userid);
        $commentstable = "<table id=\"feedbackcomments\" width=\"100%\" border=\"0\">
                <thead>
                            <tr class=\"tableheader\">
                                <th>" .
                get_string('gradetblheader_course', 'report_myfeedback') . " $course_h_icon</th>
                                <th>" .
                get_string('gradetblheader_assessment', 'report_myfeedback') . " $assessment_h_icon</th>
                                <th>" .
                get_string('gradetblheader_type', 'report_myfeedback') . " $type_h_icon</th>
                                <th>" .
                get_string('gradetblheader_submissiondate', 'report_myfeedback') . " $submission_h_icon</th>
                                <th>" .
                get_string('gradetblheader_grade', 'report_myfeedback') . " $grade_h_icon</th>                                
                                <th>" .
                get_string('gradetblheader_generalfeedback', 'report_myfeedback') . " $genfeedback_h_icon</th>";
        if ($USER->id == $userid || has_capability('moodle/user:viewdetails', $usercontext)) {
            $commentstable .= "<th>" .
                    get_string('gradetblheader_selfreflectivenotes', 'report_myfeedback') . " $reflect_h_icon</th>";
        }
        $commentstable .= "<th>" .
                get_string('gradetblheader_feedback', 'report_myfeedback') . " $fullfeedback_h_icon</th>
                                <th>" .
                get_string('gradetblheader_viewed', 'report_myfeedback') . " $viewed_h_icon</th>
                                                </tr>
              
                        <!--<tabfoot class=\"tabf\" style=\"display: table-row-group\">-->
                        <tr class=\"tablefooter\">
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>";
        if ($USER->id == $userid || has_capability('moodle/user:viewdetails', $usercontext)) {
            $commentstable .= "<td></td>";
        }
        $commentstable .= "<td></td>
                            <td></td>
                        </tr>
                    <!--</tabfoot>-->  </thead>
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
                    $cfggradetype = $this->get_course_grade_displaytype($record->courseid);                    
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
                                                $OUTPUT->image_url('i/report') . '" ' .
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
                                //If grade is overriden then show the overridden grade and put (all parts) if multiple parts otherwise show each part grade
                                $overridden = $this->is_grade_overridden($record->gradeitemid, $userid);
                                $record->grade = $overridden ? $overridden : $record->grade;
                                $allparts = ($record->grade && $record->nosubmissions > 1 && $overridden) ? get_string('allparts', 'report_myfeedback') : '';
                                $assignmentname .= ($record->partname && $record->nosubmissions > 1) ? " (" . $record->partname . ")" : "";
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
                                //If grade is overriden then show the overridden grade and put (all parts) if multiple parts otherwise show each part grade
                                $overridden = $this->is_grade_overridden($record->gradeitemid, $userid);
                                $record->grade = $overridden ? $overridden : $record->grade;
                                $allparts = ($record->grade && $record->nosubmissions > 1 && $overridden) ? get_string('allparts', 'report_myfeedback') : '';
                                $assignmentname .= ($record->partname && $record->nosubmissions > 1) ? " (" . $record->partname . ")" : "";
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
                                if ($record->highestmarks >= 0) {
                                    $record->highestgrade = $record->highestmarks;
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
                                            $OUTPUT->image_url('i/report') . '" ' .
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
                                        $feedbacktext = '<b>' . get_string('tutorfeedback', 'report_myfeedback') . '</b><br/>' . $record->feedbacklink;
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

                                $feedbacktext = $record->feedbacklink ? $record->feedbacklink . "<br/>" : "";

                                $now = time();
                                $review1 = $record->reviewattempt;
                                $review2 = $record->reviewmarks;
                                $review3 = $record->reviewoverallfeedback;
                                $reviewattempt = true;
                                $reviewfeedback = true;

                                //Show only when quiz is still openopen
                                if (!$record->duedate || ($record->duedate && $record->duedate > $now)) {
                                    if ($review1 == 256 || $review1 == 272 || $review1 == 4352 || $review1 == 4368 ||
                                            $review1 == 65792 || $review1 == 65808 || $review1 == 69888 || $review1 == 69904) {
                                        $reviewattempt = true;
                                    } else {
                                        $reviewattempt = false;
                                    }
                                    //Added the marks as well but if set to not show at all then this is just as hiding the grade in the gradebook 
                                    //but on the result page it will allow the review links or the overall feedback text if these are set.
                                    //Will also show the overridden feedback though on result page so independent of gradereview
                                    if ($review2 == 256 || $review2 == 272 || $review2 == 4352 || $review2 == 4368 ||
                                            $review2 == 65792 || $review2 == 65808 || $review2 == 69888 || $review2 == 69904) {
                                        $quizgrade = 'yes';
                                    } else {
                                        $quizgrade = 'noreview';
                                    }
                                    if ($review3 == 256 || $review3 == 272 || $review3 == 4352 || $review3 == 4368 ||
                                            $review3 == 65792 || $review3 == 65808 || $review3 == 69888 || $review3 == 69904) {
                                        $reviewfeedback = true;
                                    } else {
                                        $reviewfeedback = false;
                                    }
                                } else {
                                    // When the quiz is closed what do you show
                                    if ($review1 == 16 || $review1 == 272 || $review1 == 4112 || $review1 == 4368 ||
                                            $review1 == 65552 || $review1 == 65808 || $review1 == 69648 || $review1 == 69904) {
                                        $reviewattempt = true;
                                    } else {
                                        $reviewattempt = false;
                                    }
                                    if ($review2 == 16 || $review2 == 272 || $review2 == 4112 || $review2 == 4368 ||
                                            $review2 == 65552 || $review2 == 65808 || $review2 == 69648 || $review2 == 69904) {
                                        $quizgrade = 'yes';
                                    } else {
                                        $quizgrade = 'noreview';
                                    }
                                    if ($review3 == 16 || $review3 == 272 || $review3 == 4112 || $review3 == 4368 ||
                                            $review3 == 65552 || $review3 == 65808 || $review3 == 69648 || $review3 == 69904) {
                                        $reviewfeedback = true;
                                    } else {
                                        $reviewfeedback = false;
                                    }
                                }

                                //General feedback from tutor for a quiz attempt
                                //If there is no attempt and we have feedback from override then we set feedback link to gradebook (singleview/index for tutor)
                                //If reviewmark is set then user will link to gradebook as grades don't show on results page unless there is a submission (attempt)
                                //If there is feedback the only time user link to gradebook (user/index) is if reviewmark is not set
                                if ($feedbacktext) {
                                    if ($USER->id == $userid) {
                                        if ($quizgrade == 'yes' && $submissiondate == '-') {
                                            $thislink = "<a href=\"" . $CFG->wwwroot . "/grade/report/user/index.php?id=" . $record->courseid .
                                                    "\">" . get_string('feedback', 'report_myfeedback') . "</a>";
                                        } else {
                                            $thislink = "<a href=\"" . $CFG->wwwroot . "/mod/quiz/view.php?id=" . $record->assignmentid .
                                                    "\">" . get_string('feedback', 'report_myfeedback') . "</a>";
                                        }
                                    } else {
                                        $thislink = "<a href=\"" . $CFG->wwwroot . "/grade/report/singleview/index.php?id=" . $record->courseid .
                                                "&item=user&itemid=" . $record->userid . "\">" . get_string('feedback', 'report_myfeedback') . "</a>";
                                    }
                                } else {
                                    $thislink = '';
                                }
                                $sameuser = ($USER->id == $userid) ? true : false;
                                $feedbacktextlink = $this->get_quiz_attempts_link($record->assignid, $userid, $record->assignmentid, $archivedomain . $archiveyear, $archive, $newwindowicon, $reviewattempt, $sameuser);

                                //implementing the overall feedback str in the quiz
                                //If review overall feedback and there is an attempt ($submissiondate) then get overall feedback
                                $qid = intval($record->gi_iteminstance);
                                $grade2 = floatval($record->grade);
                                $feedback = ($reviewfeedback && $submissiondate != '-') ? $this->overallfeedback($qid, $grade2) : '';

                                //If no attempts(submissiondate) and overridden feedback then use that as the link otherwise use the attempt link
                                $feedbacktextlink = ($feedbacktextlink) ? $feedbacktextlink : $thislink;
                                $feedbacktextlink = ($feedback || $feedbacktext) ? $feedbacktextlink : '';

                                //Only if review grade will the overall feedback be shown.
                                //Overriden feedback is always show as user can see it on quiz results page.
                                if ($reviewfeedback && $feedback) {
                                    $feedbacktext.="<span style=\"font-weight:bold;\">" . get_string('overallfeedback', 'report_myfeedback') . "</span><br/>" . $feedback;
                                }
                                break;
                        }
                    } else {
                        //The manual item is assumed to be assignment - not sure why yet 
                        $itemtype = $record->gi_itemtype;
                        if ($itemtype === "manual") {
                            $assessmenttype = get_string('manual_gradeitem', 'report_myfeedback');
                            $assessmenticon = '<img src="' .
                                    $OUTPUT->image_url('i/manual_item') . '" ' .
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
                                $OUTPUT->image_url('icon', $record->assessmenttype) . '" ' .
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
                        $alerticon = '';
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
                                        $alerticon = ($record->status == 'submitted' ? '<img class="smallicon" src="' . $OUTPUT->image_url('i/warning', 'core') . '" ' . 'class="icon" alt="-" title="' .
                                                        $submissionmsg . '" rel="tooltip"/>' : '');
                                    } else {
                                        $alerticon = '<img class="smallicon" src="' . $OUTPUT->image_url('i/warning', 'core') . '" ' . 'class="icon" alt="-" title="' .
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
                                //TODO: Change the functionality to display grades to just (if grade_item display is 0 then 
                                //      check if course grade display type is set and if so take that value.
                                //      I've added that function to get_course_grade_displaytype(course id);
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
                        if ($record->grade == NULL || $grade_tbl2 == '/') {
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

                            $fileicon = ' <img src="' . $OUTPUT->image_url('i/edit', 'core') . '" ' . 'class="icon" alt="edit">';
                            //The reflective notes and turnitin feedback
                            $tdid = $record->gradeitemid;
                            if (!$instn = $record->subpart) {
                                $instn = null;
                            }
                            //$tutorhidden = 'nottutor';
                            $notes = '';
                            $noteslink = '<a href="#" class="addnote" data-toggle="modal" title="' . get_string('addnotestitle', 'report_myfeedback') . '" rel="tooltip" data-uid="' .
                                    $userid . '" data-gid="' . $tdid . '" data-inst="' . $instn . '">' . get_string('addnotes', 'report_myfeedback') . '</a>';

                            if ($usernotes = $this->get_notes($userid, $record->gradeitemid, $instn)) {
                                $notes = nl2br($usernotes);
                                $noteslink = '<a href="#" class="addnote" data-toggle="modal" title="' . get_string('editnotestitle', 'report_myfeedback') . '" rel="tooltip" data-uid="' .
                                        $userid . '" data-gid="' . $tdid . '" data-inst="' . $instn . '">' . get_string('editnotes', 'report_myfeedback') . '</a>';
                            }

                            //Only the user can add/edit their own self-reflective notes
                            if ($USER->id != $userid) {
                                $noteslink = '';
                            }

                            $feedbacklink = '<i>' . get_string('addfeedback', 'report_myfeedback') . '<br><a href="#" class="addfeedback" data-toggle="modal" title="' . get_string('addfeedbacktitle', 'report_myfeedback') . '" rel="tooltip" data-uid="' .
                                    $userid . '" data-gid="' . $tdid . '" data-inst="' . $instn . '">' . get_string('copyfeedback', 'report_myfeedback') . '</a>.</i>';
                            $selfadded = 0;
                            $studentcopy = '';
                            $studentadded = 'notstudent';
                            if ($archive) {//Have to be done here so feedback text don't add the link to the text
                                $noteslink = '';
                                $feedbacklink = '';
                            }

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

                            if (!$archive) {

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
                                if ($USER->id == $userid || has_capability('moodle/user:viewdetails', $usercontext)) {
                                    $commentstable .= "<td class=\"note-val-2 \"><div id=\"note-val" . $tdid . $instn . "\">" . $notes . "</div><div>" . $noteslink . "</div></td>";
                                }
                                $commentstable .= "<td>" . $feedbacktextlink . $feedbackfileicon . "</td>";
                                $commentstable .= "<td>" . $viewed . "</td>";
                                $commentstable .= "</tr>";
                            }
                        }
                    }
                }
            }
        }
		$rs->close(); // Close the recordset!
        $table .= "</tbody>                
                    </table>";
        $commentstable.="</tbody></table>";
        $printmsg = get_string('print_msg', 'report_myfeedback');
        // Buttons for filter reset, download and print
        $reset_print_excel = "<div style=\"float:right;\" class=\"buttonswrapper\"><input id=\"tableDestroy\" type=\"button\" value=\"" .
                get_string('reset_table', 'report_myfeedback') . "\">
                <input id=\"exportexcel\" type=\"button\" value=\"" . get_string('export_to_excel', 'report_myfeedback') . "\">
                <input id=\"reportPrint\" type=\"button\" value=\"" . get_string('print_report', 'report_myfeedback') . "\" title=\"" . $printmsg . "\" rel=\"tooltip\"></div>";
        //<input id=\"toggle-grade\" type=\"button\" value=\"" . get_string('togglegrade', 'report_myfeedback') . "\"> //relative grade toggle
        $reset_print_excel1 = "<div style=\"float:right;\" class=\"buttonswrapper\"><input id=\"ftableDestroy\" type=\"button\" value=\"" .
                get_string('reset_table', 'report_myfeedback') . "\">
                <input id=\"exportexcel\" type=\"button\" value=\"" . get_string('export_to_excel', 'report_myfeedback') . "\">
                <input id=\"reportPrint\" type=\"button\" value=\"" . get_string('print_report', 'report_myfeedback') . "\" title=\"" . $printmsg . "\" rel=\"tooltip\"></div>";

        $_SESSION['exp_sess'] = $exceltable;
        $_SESSION['myfeedback_userid'] = $userid;
        $_SESSION['tutor'] = 'no';
        $this->content->text = $title . $reset_print_excel . $table;
        if ($tab == 'feedback') {
            $feedbackcomments = "<p>" . get_string('tabs_feedback_text', 'report_myfeedback') . "</p>";
            $this->content->text = $feedbackcomments . $reset_print_excel1 . $commentstable;
        }
        return $this->content;
    }

}