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
 * Class for feedback report.
 *
 * @package    report_myfeedback
 * @copyright  2015 onwards Delvon Forrester <delvon@live.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_myfeedback\local;

use context;
use context_course;
use context_user;
use core_course_category;
use core_plugin_manager;
use dml_exception;
use html_writer;
use moodle_database;
use moodle_exception;
use moodle_url;
use stdClass;

/**
 * Class for feedback report.
 *
 * @package    report_myfeedback
 * @copyright  2015 onwards Delvon Forrester <delvon@live.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report {

    /**
     * Initialises the report and sets the title.
     */
    public function init(): void {
        $this->title = get_string('my_feedback', 'report_myfeedback');
    }

    /**
     * Sets up global $DB moodle_database instance.
     *
     * @param string|null $extdb
     * @return bool Returns true when finished setting up $DB or $currentdb. Returns void when $DB has already been set.
     * @throws \coding_exception
     * @throws \dml_connection_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function setup_external_db($extdb = null): bool {
        global $CFG, $DB, $currentdb;

        // Use a custom $currentdb (and not current system's $DB) if set - code sourced from configurable
        // Reports plugin.
        $currentdbhost = get_config('report_myfeedback', 'dbhost');
        $currentdbname = get_config('report_myfeedback', 'dbname');
        $currentdbuser = get_config('report_myfeedback', 'dbuser');
        $currentdbpass = get_config('report_myfeedback', 'dbpass');

        if (empty($currentdbhost) || empty($currentdbname) || empty($currentdbuser)) {
            $currentdb = $DB;
            setup_DB();
        } else {
            if (!isset($CFG->dblibrary)) {
                $CFG->dblibrary = 'native';
                // Use new drivers instead of the old adodb driver names.
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
                $CFG->dboptions = [];
            }

            if (isset($CFG->dbpersist)) {
                $CFG->dboptions['dbpersist'] = $CFG->dbpersist;
            }

            if (!$currentdb = moodle_database::get_driver_instance($CFG->dbtype, $CFG->dblibrary)) {
                throw new dml_exception('dbdriverproblem', "Unknown driver $CFG->dblibrary/$CFG->dbtype");
            }

            try {
                $currentdb->connect($currentdbhost, $currentdbuser, $currentdbpass, $currentdbname, $CFG->prefix, $CFG->dboptions);
            } catch (moodle_exception $e) {
                if (empty($CFG->noemailever) && !empty($CFG->emailconnectionerrorsto)) {
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
                            // Email directly rather than using messaging.
                            @mail($CFG->emailconnectionerrorsto, 'WARNING: Database connection error: ' . $CFG->wwwroot, $body);
                            $fp = @fopen($CFG->dataroot . '/emailcount', 'w');
                            @fwrite($fp, time());
                        }
                    } else {
                        // Email directly rather than using messaging.
                        @mail($CFG->emailconnectionerrorsto, 'WARNING: Database connection error: ' . $CFG->wwwroot, $body);
                        $fp = @fopen($CFG->dataroot . '/emailcount', 'w');
                        @fwrite($fp, time());
                    }
                }
                // Rethrow the exception.
                if ($CFG->debug != 0) {
                    throw $e;
                } else {
                    echo get_string('archivedbnotexist', 'report_myfeedback');
                }
            }
            $DB = $currentdb;
            $CFG->dbfamily = $currentdb->get_dbfamily(); // TODO: BC only for now.

            return true;
        }
        return false;
    }

    /**
     * Gets whether or not the module is installed and visible.
     *
     * @param string $modname The name of the module
     * @return bool true if the module exists and is not hidden in the site admin settings,
     *         otherwise false
     */
    public function mod_is_available($modname) {
        global $currentdb;
        $installedplugins = core_plugin_manager::instance()->get_plugins_of_type('mod');
        // Is the module installed?
        if (array_key_exists($modname, $installedplugins)) {
            // Is the module visible?
            if ($currentdb->get_field('modules', 'visible', array('name' => $modname
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
     * @param string $iteminstance The assign id
     * @param string $userid The user id
     * @param string $gradeid The gradeid
     * @return bool true if there's a pdf feedback annotated file
     * or any feedback filed for the submission, otherwise false
     */
    public function has_pdf_feedback_file($iteminstance, $userid, $gradeid) {
        global $currentdb;
        // Is there any online pdf annotation feedback or any feedback file?
        if ($currentdb->get_record('assignfeedback_editpdf_annot', array('gradeid' => $gradeid), 'id', IGNORE_MULTIPLE)) {
            return true;
        }
        if ($currentdb->get_record('assignfeedback_editpdf_cmnt', array('gradeid' => $gradeid), 'id', IGNORE_MULTIPLE)) {
            return true;
        }

        $sql = "SELECT af.numfiles
                  FROM {assign_grades} ag
                  JOIN {assignfeedback_file} af on ag.id=af.grade
                   AND ag.id=? AND ag.userid=? AND af.assignment=?";
        $params = array($gradeid, $userid, $iteminstance);
        $feedbackfile = $currentdb->get_record_sql($sql, $params);
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
        global $currentdb;
        // Is there any feedback file?
        $sql = "SELECT DISTINCT max(wa.id) as id, wa.feedbackauthorattachment
                  FROM {workshop_assessments} wa
                  JOIN {workshop_submissions} ws ON wa.submissionid=ws.id
                   AND ws.authorid=? AND ws.id=? and ws.example = 0";
        $params = array($userid, $subid);
        $feedbackfile = $currentdb->get_record_sql($sql, $params);
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
     * @param int $userid The user id
     * @param int $subid The workshop submission id
     * @param int $assignid The workshop id
     * @param int $cid The course id
     * @param int $itemnumber The grade_item itemnumber
     * @return string All the feedback information
     */
    public function has_workshop_feedback($userid, $subid, $assignid, $cid, $itemnumber) {
        global $currentdb, $CFG;
        $feedback = '';

        // Get the other feedback that comes when graded so will have a grade id otherwise it is not unique.
        $peer = "SELECT DISTINCT wg.id, wg.peercomment, wa.reviewerid, wa.feedbackreviewer, w.conclusion
                   FROM {workshop} w
                   JOIN {workshop_submissions} ws ON ws.workshopid=w.id AND w.course=? AND w.useexamples=0
                   JOIN {workshop_assessments} wa ON wa.submissionid=ws.id AND ws.authorid=?
                    AND ws.workshopid=? AND ws.example=0 AND wa.submissionid=?
              LEFT JOIN {workshop_grades} wg ON wg.assessmentid=wa.id AND wa.submissionid=?";
        $arr = array($cid, $userid, $assignid, $subid, $subid);
        // TODO: fix this! If won't work here, use: if ($rs->valid()) {}.
        if ($assess = $currentdb->get_recordset_sql($peer, $arr)) {
            if ($itemnumber == 1) {
                foreach ($assess as $a) {
                    if ($a->feedbackreviewer && strlen($a->feedbackreviewer) > 0) {
                        $feedback = (strip_tags($a->feedbackreviewer) ? "<b>" . get_string('tutorfeedback', 'report_myfeedback') .
                            "</b><br/>" . strip_tags($a->feedbackreviewer) : '');
                    }
                }
                $assess->close();
                return $feedback;
            }
        }

        if ($itemnumber != 1) {
            // Get the feedback from author as this does not necessarily mean they are graded.
            $auth = "SELECT DISTINCT wa.id, wa.feedbackauthor, wa.reviewerid
                       FROM {workshop} w
                       JOIN {workshop_submissions} ws ON ws.workshopid=w.id AND w.course=? AND w.useexamples=0
                       JOIN {workshop_assessments} wa ON wa.submissionid=ws.id AND ws.authorid=?
                        AND ws.workshopid=? AND ws.example=0 AND wa.submissionid=?";
            $par = array($cid, $userid, $assignid, $subid);
            $self = $pfeed = false;
            if ($asse = $currentdb->get_records_sql($auth, $par)) {
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

        // Get comments strategy type.
        $sqlc = "SELECT wg.id as gradeid, wa.reviewerid, a.description, peercomment
                    FROM {workshopform_accumulative} a
                    JOIN {workshop_grades} wg ON wg.dimensionid=a.id AND wg.strategy='comments'
                    JOIN {workshop_assessments} wa ON wg.assessmentid=wa.id AND wa.submissionid=?
                    JOIN {workshop_submissions} ws ON wa.submissionid=ws.id
                     AND ws.workshopid=? AND ws.example=0 AND ws.authorid = ?
                ORDER BY wa.reviewerid";
        $paramsc = array($subid, $assignid, $userid);
        $c = 0;
        if ($commentscheck = $currentdb->get_records_sql($sqlc, $paramsc)) {
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
                $feedback .= strip_tags($ts->description) ? "<br/><b>" . strip_tags($ts->description) : '';
                $feedback .= strip_tags($ts->description) ? "<br/><strong>" . get_string('comment', 'report_myfeedback') .
                    "</strong>: " . strip_tags($ts->peercomment) . "<br/>" : '';
            }
        }

        // Get accumulative strategy type.
        $sqla = "SELECT wg.id as gradeid, wa.reviewerid, a.description, wg.grade as score, a.grade, peercomment
          FROM {workshopform_accumulative} a
          JOIN {workshop_grades} wg ON wg.dimensionid=a.id AND wg.strategy='accumulative'
          JOIN {workshop_assessments} wa ON wg.assessmentid=wa.id AND wa.submissionid=?
          JOIN {workshop_submissions} ws ON wa.submissionid=ws.id
          AND ws.workshopid=? AND ws.example=0 AND ws.authorid = ?
          ORDER BY wa.reviewerid";
        $paramsa = array($subid, $assignid, $userid);
        $a = 0;
        if ($accumulativecheck = $currentdb->get_records_sql($sqla, $paramsa)) {
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
                $feedback .= strip_tags($acc->description && $acc->score) ? "<br/><b>" . strip_tags($tiv->description) . "</b>: " .
                    get_string('grade', 'report_myfeedback') . round($tiv->score) . "/" . round($tiv->grade) : '';
                $feedback .= strip_tags($acc->description && $acc->score) ? "<br/><b>" .
                    get_string('comment', 'report_myfeedback') . "</b>: " . strip_tags($tiv->peercomment) . "<br/>" : '';
            }
        }

        // Get the rubrics strategy type.
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
        if ($rubriccheck = $currentdb->get_records_sql($sql, $params)) {
            foreach ($rubriccheck as $rub) {
                if (strip_tags($rub->description && $rub->definition)) {
                    $r = 1;
                }
            }
            if ($r) {
                $feedback .= strip_tags($feedback) ? '<br/>' : '';
                $feedback .= "<br/><span style=\"font-weight:bold;\"><img src=\"" .
                    $CFG->wwwroot . "/report/myfeedback/pix/rubric.png\">" .
                    get_string('rubrictext', 'report_myfeedback') . "</span>";
            }
            foreach ($rubriccheck as $rec) {
                $feedback .= strip_tags($rec->description && $rec->definition) ? "<br/><b>" .
                    strip_tags($rec->description) ."</b>: " . strip_tags($rec->definition) : '';
                $feedback .= strip_tags($rec->peercomment) ? "<br/><b>" .
                    get_string('comment', 'report_myfeedback') . "</b>: " . strip_tags($rec->peercomment) . "<br/>" : '';
            }
        }

        // Get the numerrors strategy type.
        $sqln = "SELECT wg.id as gradeid, wa.reviewerid, n.description, wg.grade, n.grade0, n.grade1, peercomment
          FROM {workshopform_numerrors} n
          JOIN {workshop_grades} wg ON wg.dimensionid=n.id AND wg.strategy='numerrors'
          JOIN {workshop_assessments} wa ON wg.assessmentid=wa.id AND wa.submissionid=?
          JOIN {workshop_submissions} ws ON wa.submissionid=ws.id
          AND ws.workshopid=? AND ws.example=0 AND ws.authorid = ?
          ORDER BY wa.reviewerid";
        $paramsn = array($subid, $assignid, $userid);
        $n = 0;
        if ($numerrorcheck = $currentdb->get_records_sql($sqln, $paramsn)) {
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
                $feedback .= $err->gradeid ? "<br/><b>" . strip_tags($err->description) . "</b>: " .
                    ($err->grade < 1.0 ? strip_tags($err->grade0) : strip_tags($err->grade1)) : '';
                $feedback .= $err->gradeid ? "<br/><b>" . get_string('comment', 'report_myfeedback') . "</b>: " .
                    strip_tags($err->peercomment) . "<br/>" : '';
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
        global $currentdb;
        $sql = "SELECT max(extensionduedate) as extensionduedate
                FROM {assign_user_flags}
                WHERE userid=? AND assignment=?";
        $params = array($userid, $assignment);
        $extension = $currentdb->get_record_sql($sql, $params);
        if ($extension) {
            return $extension->extensionduedate;
        }
        return false;
    }

    /**
     * Check if the user got an override to extend completion date/time
     *
     * @param int $assignid The quiz id
     * @param int $userid The user id
     * @return int Datetime of the override or false if no override
     */
    public function check_quiz_extension($assignid, $userid) {
        global $currentdb;
        $sql = "SELECT max(timeclose) as timeclose
                FROM {quiz_overrides}
                WHERE quiz=? AND userid=?";
        $params = array($assignid, $userid);
        $override = $currentdb->get_record_sql($sql, $params);
        if ($override) {
            return $override->timeclose;
        }
        return false;
    }

    /**
     * Check if user got an override to extend completion date/time
     * as part of a group
     *
     * @param int $assignid The quiz id
     * @param int $userid The user id
     * @return int extension date or false if no extension
     */
    public function check_quiz_group_extension($assignid, $userid) {
        global $currentdb;
        $sql = "SELECT max(qo.timeclose) as timeclose
                FROM {quiz_overrides} qo
                JOIN {groups_members} gm ON qo.groupid=gm.groupid
                AND qo.quiz=? AND gm.userid=?";
        $params = array($assignid, $userid);
        $override = $currentdb->get_record_sql($sql, $params);
        if ($override) {
            return $override->timeclose;
        }
        return false;
    }

    /**
     * Get the submitted date of the last attempt
     *
     * @param int $assignid The assignment id
     * @param int $userid The user id
     * @param int $grade The quiz grade of the user
     * @param int $availablegrade The highest grade the user could get for that quiz
     * @param int $sumgrades The amount of questions marked out of
     * @return int The date of the attempt or false if not attempted.
     */
    public function get_quiz_submissiondate($assignid, $userid, $grade, $availablegrade, $sumgrades) {
        global $currentdb;
        $a = $grade / $availablegrade * $sumgrades;
        $sql = "SELECT id, timefinish, sumgrades
                FROM {quiz_attempts}
                WHERE quiz=? AND userid=?";
        $params = array($assignid, $userid);
        $attempts = $currentdb->get_records_sql($sql, $params);
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
     * @param string $archivedomainyear The academic year eg (14-15)
     * @param bool $archive Whether it's an archived academic year
     * @param string  $newwindowicon An icon image with tooltip showing that it opens in another window
     * @param bool $reviewattempt whetehr the user can review the quiz attempt
     * @param bool $sameuser Whether the logged-in user is the user quiz being referred to
     * @return string Any comments left by a marker on a Turnitin Assignment via the Moodle Comments
     *         feature (not in Turnitin), each on a new line
     * @throws \coding_exception
     */
    public function get_quiz_attempts_link($quizid, $userid, $quizurlid, $archivedomainyear, $archive, $newwindowicon,
                                           $reviewattempt, $sameuser): string {
        global $CFG, $currentdb;
        $sqlcount = "SELECT count(attempt) as attempts, max(id) as id
                        FROM {quiz_attempts} qa
                        WHERE quiz=? and userid=?";
        $params = array($quizid, $userid);
        $attemptcount = $currentdb->get_records_sql($sqlcount, $params);
        $out = [];
        $url = '';
        if ($attemptcount) {
            foreach ($attemptcount as $attempt) {
                $a = $attempt->attempts;
                if ($a > 0) {
                    $attr = [];
                    $newicon = '';
                    $url = $CFG->wwwroot . "/mod/quiz/review.php?attempt=" . $attempt->id;
                    if ($archive) {
                        // If an archive year then change the domain.
                        $url = $archivedomainyear . "/mod/quiz/review.php?attempt=" . $attempt->id;
                        $attr = array("target" => "_blank");
                        $newicon = $newwindowicon;
                    }

                    $attemptstext = ($a > 1) ? get_string('reviewlastof', 'report_myfeedback', $a) .
                        $newicon : get_string('reviewaattempt', 'report_myfeedback', $a) . $newicon;
                    $out[] = html_writer::link($url, $attemptstext, $attr);
                }
            }
            if (!$reviewattempt) {
                if ($sameuser) {
                    // Student can only see the attempt if it is set in review options so link to result page instead.
                    return "<a href=" . $CFG->wwwroot . "/mod/quiz/view.php?id=" . $quizurlid . ">" .
                        get_string('feedback', 'report_myfeedback') . "</a>";
                } else {
                    // Tutor can still see the attempt if review is off.
                    return "<a href=" . $url . ">" . get_string('feedback', 'report_myfeedback') . "</a>";
                }
            }
        }
        $br = html_writer::empty_tag('br');
        return implode($br, $out);
    }

    /**
     * Get group assignment submission date - since it won't come through for a user in a group
     * unless they were the one's to upload the file.
     *
     * @param int $userid The id of the user
     * @param int $assignid The id of the assignment
     * @return string submission dates, each on a new line if there are multiple
     */
    public function get_group_assign_submission_date($userid, $assignid): string {
        global $currentdb;
        // Group submissions.
        $sql = "SELECT max(su.timemodified) as subdate
                FROM {assign_submission} su
                JOIN {groups_members} gm ON su.groupid = gm.groupid AND gm.userid = ?
                AND su.assignment=?";
        $params = array($userid, $assignid);
        $files = $currentdb->get_record_sql($sql, $params);
        if (isset($files->subdate)) {
            return $files->subdate;
        }
        return get_string('no_submission', 'report_myfeedback');
    }

    /**
     * Get the peercoments for workshop.
     *
     * @param int $userid The id of the user
     * @return string the comments made on all parts of the work
     */
    public function get_workshop_comments($userid): string {
        global $currentdb;
        // Group submissions.
        $sql = "SELECT wg.peercomment
                FROM {workshop_grades} wg
                    LEFT JOIN {workshop_submissions} su ON wg.assessmentid = su.id and su.authorid=?";
        $params = array($userid);
        $comments = $currentdb->get_recordset_sql($sql, $params, $limitfrom = 0, $limitnum = 0);
        $out = [];
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
     * @param string $itemname The name of the assessment/course module
     * @return string date viewed or no if not viewed
     */
    public function check_viewed_gradereport($contextid, $assignmentid, $userid, $courseid, $itemname): string {
        global $currentdb;
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
        $gradeadded = $currentdb->get_record_sql($sqltwo, $paramstwo);
        if ($gradeadded) {
            $params = array($contextid, $assignmentid, $userid, $courseid, $gradeadded->timemodified);
            $viewreport = $currentdb->get_record_sql($sql, $params);
            if ($viewreport && $viewreport->timecreated > $gradeadded->timemodified) {
                return date('d-m-Y H:i', $viewreport->timecreated);
            }

            $paramsone = array('gradereport_user', 'viewed', $userid, $courseid, $gradeadded->timemodified);
            $userreport = $currentdb->get_record_sql($sqlone, $paramsone);
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
        global $currentdb;
        $sqlone = "SELECT max(timecreated) as timecreated
                FROM {logstore_standard_log}
                WHERE component=? AND action=? AND userid=? AND courseid=?";
        $sqltwo = "SELECT max(g.timemodified) as timemodified
                FROM {grade_grades} g
                JOIN {grade_items} gi ON g.itemid=gi.id AND g.userid=?
                AND gi.courseid=? AND gi.id=?";
        $paramsone = array('gradereport_user', 'viewed', $userid, $courseid);
        $paramstwo = array($userid, $courseid, $gradeitemid);
        $userreport = $currentdb->get_record_sql($sqlone, $paramsone);
        $gradeadded = $currentdb->get_record_sql($sqltwo, $paramstwo);
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
        global $currentdb;
        $sql = "SELECT feedbacktext
                FROM {quiz_feedback}
                WHERE quizid=? and mingrade<=? and maxgrade>=?
                limit 1";
        $params = array($quizid, $grade, $grade);
        $feedback = $currentdb->get_record_sql($sql, $params);
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
        global $currentdb;
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
        $rubrics = $currentdb->get_recordset_sql($sql, $params);
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
        global $currentdb;
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
        $guides = $currentdb->get_recordset_sql($sql, $params);
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
     * @param int $itemid The grade item id
     * @param int $userid The user id
     * @param int $courseid The course id
     * @param int $grade The user's grade for that manual item
     * @return str The scale grade for the user
     */
    public function get_grade_scale($itemid, $userid, $courseid, $grade) {
        global $currentdb;
        $sql = "SELECT DISTINCT gg.finalgrade, s.scale
            FROM {grade_grades} gg
            JOIN {grade_items} gi ON gg.itemid=gi.id AND gi.id=?
            AND gg.userid=? AND gi.courseid=? AND gi.gradetype = 2
            JOIN {scale} s ON gi.scaleid=s.id limit 1";
        $params = array($itemid, $userid, $courseid);
        $scales = $currentdb->get_record_sql($sql, $params);
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
     * @param int $itemid The grade item id
     * @param int $userid The user id
     * @param int $courseid The course id
     * @return str The lowest scale grade available
     */
    public function get_min_grade_scale($itemid, $userid, $courseid) {
        global $currentdb;
        $sql = "SELECT DISTINCT s.scale
            FROM {grade_grades} gg
            JOIN {grade_items} gi ON gg.itemid=gi.id AND gi.id=?
            AND gg.userid=? AND gi.courseid=? AND gi.gradetype = 2
            JOIN {scale} s ON gi.scaleid=s.id limit 1";
        $params = array($itemid, $userid, $courseid);
        $scales = $currentdb->get_record_sql($sql, $params);
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
     * @param int $itemid The grade item id
     * @param int $userid The user id
     * @param int $courseid The course id
     * @return str The highest scale grade available
     */
    public function get_available_grade_scale($itemid, $userid, $courseid) {
        global $currentdb;
        $sql = "SELECT DISTINCT s.scale
            FROM {grade_grades} gg
            JOIN {grade_items} gi ON gg.itemid=gi.id AND gi.id=?
            AND gg.userid=? AND gi.courseid=? AND gi.gradetype = 2
            JOIN {scale} s ON gi.scaleid=s.id limit 1";
        $params = array($itemid, $userid, $courseid);
        $scales = $currentdb->get_record_sql($sql, $params);
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
     * @param int $itemid The grade item id
     * @param int $userid The user id
     * @param int $courseid The course id
     * @return str All scale grade in the scale
     */
    public function get_all_grade_scale($itemid, $userid, $courseid) {
        global $currentdb;
        $sql = "SELECT s.scale
            FROM {grade_grades} gg
            JOIN {grade_items} gi ON gg.itemid=gi.id AND gi.id=?
            AND gg.userid=? AND gi.courseid=? AND gi.gradetype = 2
            JOIN {scale} s ON gi.scaleid=s.id";
        $params = array($itemid, $userid, $courseid);
        $scales = $currentdb->get_records_sql($sql, $params);
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
     * @param int $courseid The course id
     * @param int $grade The final grade the user got
     * @return str The letter grade or word for the user
     */
    public function get_grade_letter($courseid, $grade) {
        global $currentdb;
        $sql = "SELECT l.letter, con.id
            FROM {grade_letters} l
            JOIN {context} con ON l.contextid = con.id AND con.contextlevel=50
            AND con.instanceid=? AND l.lowerboundary <=?
            ORDER BY l.lowerboundary DESC limit 1";
        $params = array($courseid, $grade);
        $letters = $currentdb->get_record_sql($sql, $params);
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
     * @param int $courseid The course id
     * @return string The lowest letter grade available
     */
    public function get_min_grade_letter($courseid): string {
        global $currentdb;
        $sql = "SELECT l.letter, con.id
            FROM {grade_letters} l
            JOIN {context} con ON l.contextid = con.id AND con.contextlevel=50
            AND con.instanceid=? ORDER BY l.lowerboundary ASC limit 1";
        $params = array($courseid);
        $letters = $currentdb->get_record_sql($sql, $params);
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
     * @param int $courseid The course id
     * @return str The highest letter grade available
     */
    public function get_available_grade_letter($courseid) {
        global $currentdb;
        $sql = "SELECT l.letter, con.id
            FROM {grade_letters} l
            JOIN {context} con ON l.contextid = con.id AND con.contextlevel=50
            AND con.instanceid=? ORDER BY l.lowerboundary DESC limit 1";
        $params = array($courseid);
        $letters = $currentdb->get_record_sql($sql, $params);
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
     * @param int $courseid The course id
     * @return str All grade letters
     */
    public function get_all_grade_letters($courseid) {
        global $currentdb;
        $sql = "SELECT l.letter, con.id
            FROM {grade_letters} l
            JOIN {context} con ON l.contextid = con.id AND con.contextlevel=50
            AND con.instanceid=?";
        $params = array($courseid);
        $letters = $currentdb->get_records_sql($sql, $params);
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
        // TODO: use my own function other than grade_get_setting because when we start using multiple DBs
        // then $DB would be the incorrect database or we need to set $DB to the database being used.
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
     * @return mixed The GMT/UTC+-offset
     */
    public function user_timezone() {
        global $USER, $currentdb;
        $sql = "SELECT timezone FROM {user} WHERE id = ?";
        $params = array($USER->id);
        $timezone = $currentdb->get_record_sql($sql, $params);
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
     * @param date $date Date to check for timezone name
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
     * @return int Personal tutor role id
     */
    public function get_personal_tutor_id() {
        global $currentdb;
        $sql = "SELECT roleid FROM {role_context_levels} WHERE contextlevel = ? limit 1";
        $params = array(30);
        $tutor = $currentdb->get_record_sql($sql, $params);
        return $tutor ? $tutor->roleid : 0;
    }

    /**
     * Return whether the user has the capability in any context
     *
     * @param int $uid The user id
     * @param string $cap  The capability to check for
     * @param context $context
     * @return bool
     */
    public function get_dashboard_capability($uid, $cap, $context = null): bool {
        global $currentdb;
        $params = [$uid, $cap];
        $sql = "SELECT DISTINCT min(c.id) as id, r.roleid FROM {role_capabilities} c
            JOIN {role_assignments} r ON r.roleid=c.roleid ";
        if ($context) {
            $sql .= "AND r.contextid = ? ";
            array_unshift($params, $context);
        }
        $sql .= "AND userid = ? AND capability = ? GROUP BY c.id, r.roleid";
        $capy = $currentdb->get_records_sql($sql, $params);
        return count($capy) ? true : false;
    }

    /**
     * Return the export to excel and print buttons.
     *
     * @return string the HTML to display the export to excel and print buttons.
     */
    public function export_print_buttons(): string {
        $printmsg = get_string('print_msg', 'report_myfeedback');
        return "<div class=\"buttonswrapper\"><input class=\"x_port\" id=\"exportexcel\" type=\"button\" value=\""
            . get_string('export_to_excel', 'report_myfeedback') . "\">
                <input class=\"reportPrint\" id=\"reportPrint\" type=\"button\" value=\""
            . get_string('print_report', 'report_myfeedback') . "\" title=\"" . $printmsg . "\" rel=\"tooltip\"></div>";
    }

    /**
     * Return all categories relevant to the search category name
     *
     * @param string $search The user input entered to search on.
     * @param string $reporttype The report type where the search is being used
     * @param bool $hideor
     * @return string Table with the list of categories with names containing the search term.
     * @throws \coding_exception
     */
    public function search_all_categories($search, $reporttype, $hideor = false): string {
        global $currentdb, $CFG;
        $sesskeyqs = '&sesskey=' . sesskey();

        $mycategories = [];
        $table = "";
        // The search form.
        if ($hideor == false) {
            echo '<span class="searchusage"> ' . get_string("or", "report_myfeedback");
        }
        echo' </span>
            <form method="POST" id="reportsearchform" class="report_form" action="">
                <input type="hidden" name="sesskey" value="' . sesskey() . '" />
                <input type="text" id="searchu" name="searchusage" class="searchusage"
                    value="' . get_string("searchcategory", "report_myfeedback") . '"
                 />
                <input type="submit" id="submitsearch" value="' . get_string("search", "report_myfeedback") . '" />
                <input type="hidden" name="categoryid" id="categoryid" value="-1" />
            </form>';
        // We first trim the search to remove leading and trailing spaces.
        $search = trim($search);
        // If there is a search input and it's not the text that tells the user to enter search input.
        if ($search != "" && $search != get_string("searchcategory", "report_myfeedback")) {
            $searchu = addslashes(strip_tags($search)); // We escape the quotes etc and strip all html tags.

            $sqllike = $currentdb->sql_like('name', '?');
            $sql = "SELECT id, name, parent FROM {course_categories}
                     WHERE visible = 1
                           AND " . $sqllike;
            $result = $currentdb->get_records_sql($sql, array('%' . $searchu . '%'));
            if ($result) {
                foreach ($result as $a) {
                    if ($a->id) {
                        $mycategories[$a->id][0] = "<a href=\"" . $CFG->wwwroot . "/report/myfeedback/index.php?currenttab=usage"
                            . "&reporttype=$reporttype"
                            . '&categoryid=' . $a->id
                            . $sesskeyqs . '" title="'
                            . $a->email . "\" rel=\"tooltip\">" . $a->name . "</a>";
                        if ($a->parent) {
                            $categoryname = $this->get_category_name($a->parent);
                        }
                        $mycategories[$a->id][1] = "<a href=\"" . $CFG->wwwroot . "/report/myfeedback/index.php?currenttab=usage"
                            . "&reporttype=$reporttype&categoryid=" . $a->id
                            . $sesskeyqs . '" title="'
                            . $a->email . "\" rel=\"tooltip\">" . $categoryname . "</a>";
                    }
                }
            }

            $helpiconcat = ' <img src="' . 'pix/info.png' . '" ' .
                ' alt="-" title="' . get_string('usagesrchheader_catname_info', 'report_myfeedback') . '" rel="tooltip"/>';
            $helpiconpcat = ' <img src="' . 'pix/info.png' . '" ' .
                ' alt="-" title="' . get_string('usagesrchheader_pcatname_info', 'report_myfeedback') . '" rel="tooltip"/>';
            $table = "<table class=\"userstable\" id=\"userstable\">
                    <thead>
                            <tr class=\"tableheader\">
                                <th class=\"tblname\">" . get_string('name') .  $helpiconcat .
                "</th><th class=\"tbldepartment\">" .
                get_string('parent', 'report_myfeedback') . " " . get_string('category', 'report_myfeedback') .  $helpiconpcat .
                "</th></tr>
                        </thead><tbody>";
            // Add the records to the table here. These records were stored in the myusers array.
            foreach ($mycategories as $result) {
                if (isset($result[0])) {
                    $table .= "<tr>";
                    $table .= "<td>" . $result[0] . "</td>";
                    $table .= "<td>" . $result[1] . "</td>";
                    $table .= "</tr>";
                }
            }
            $table .= "</tbody><tfoot><tr><td></td><td></td></tr></tfoot></table><br />";
        }
        // Blank the search form when you click in it.
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
     * @param int $catid The category id.
     * @return array of courses within that category and its subcategories
     */
    public function get_category_courses($catid) {
        global $currentdb;
        $sql = "SELECT DISTINCT c.id, c.shortname, c.fullname, c.summary, c.visible, c.sortorder, cat.sortorder
                  FROM {course} c, {course_categories} cat ";
        if ($catid > 0) {
            $sql .= "WHERE c.category = cat.id AND cat.path LIKE '%/".$catid."' OR cat.path LIKE '%/".$catid."/%' ";
        }
        $sql .= "ORDER BY cat.sortorder, c.sortorder";
        return $currentdb->get_records_sql($sql);
    }

    /**
     * Return all courses relevant to the search name / email address (max 10)
     *
     * @param string $search The user input entered to search on.
     * @param string $reporttype The report type where the search is being used
     * @return string Table with the list of course shortnames and fullnames containing the search term.
     */
    public function search_all_courses($search, $reporttype) {
        global $currentdb, $CFG;
        $sesskeyqs = '&sesskey=' . sesskey();

        $mycourses = [];

        // The search form.
        echo ' <form method="POST" id="reportsearchform" class="report_form" action="">
                <input type="hidden" name="sesskey" value="' . sesskey() . '" />
                            <input type="text" id="searchu" name="searchusage" class="searchusage" value="'
                                . get_string("searchcourses", "report_myfeedback") . '" />
                            <input type="submit" id="submitsearch" value="' . get_string("search", "report_myfeedback") . '" />
							<input type="hidden" name="courseid" id="courseid" value="0" />
                            </form>';
        // We first trim the search to remove leading and trailing spaces.

        $search = trim($search);
        $table = '';
        // If there is a search input and it's not the text that tells the user to enter search input.
        if ($search != "" && $search != get_string("searchcourses", "report_myfeedback")) {
            $searchu = addslashes(strip_tags($search)); // We escape the quotes etc and strip all html tags.
            $result = [];
            $sqllikefullname = $currentdb->sql_like('fullname', '?');
            $sqllikeshortname = $currentdb->sql_like('shortname', '?');
            $sql = "SELECT id,shortname, fullname, category
                      FROM {course}
                     WHERE " . $sqllikefullname . "
                        OR " . $sqllikeshortname;
            $result = $currentdb->get_records_sql($sql, array(
                '%' . $searchu . '%',
                '%' . $searchu . '%'
            ));
            if ($result) {
                foreach ($result as $a) {
                    if ($a->id) {
                        $mycourses[$a->id][0] = "<a href=\"" . $CFG->wwwroot
                            . "/report/myfeedback/index.php?currenttab=usage&reporttype=$reporttype"
                            .'&courseid=' . $a->id
                            . $sesskeyqs
                            . '" title="'
                            . $a->email . "\" rel=\"tooltip\">" . $a->fullname . " (" . $a->shortname . ")</a>";
                        if ($a->category) {
                            $categoryname = $this->get_category_name($a->category);
                        }
                        $categoryreporttype = str_replace("course", "category", $reporttype);
                        $mycourses[$a->id][1] = "<a href=\"" . $CFG->wwwroot
                            . "/report/myfeedback/index.php?currenttab=usage&reporttype=$categoryreporttype"
                            .'&categoryid=' . $a->category
                            . $sesskeyqs
                            . '" title="'
                            . $a->email . "\" rel=\"tooltip\">" . $categoryname . "</a>";
                    }
                }
            }
            $helpiconcourse = ' <img src="' . 'pix/info.png' . '" ' .
                ' alt="-" title="' . get_string('usagesrchheader_coursename_info', 'report_myfeedback') . '" rel="tooltip"/>';
            $helpiconcoursecat = ' <img src="' . 'pix/info.png' . '" ' .
                ' alt="-" title="' . get_string('usagesrchheader_coursecatname_info', 'report_myfeedback') . '" rel="tooltip"/>';
            $table = "<table class=\"userstable\" id=\"userstable\">
						<thead>
								<tr class=\"tableheader\">
									<th class=\"tblname\">" . get_string('name') . $helpiconcourse .
                "</th><th class=\"tbldepartment\">" . get_string('category', 'report_myfeedback') . $helpiconcoursecat . "</th></tr>
							</thead><tbody>";
            // Add the records to the table here. These records were stored in the myusers array.
            foreach ($mycourses as $result) {
                if (isset($result[0])) {
                    $table .= "<tr>";
                    $table .= "<td>" . $result[0] . "</td>";
                    $table .= "<td>" . $result[1] . "</td>";
                    $table .= "</tr>";
                }
            }
            $table .= "</tbody><tfoot><tr><td></td><td></td></tr></tfoot></table><br />";
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
     * @param string $search The user input entered to search on.
     * @param string $reporttype The report type where the search is being used
     * @return string Table with the list of users matching the search term.
     */
    public function search_all_users($search, $reporttype = "student") {
        global $currentdb, $CFG;
        $sesskeyqs = '&sesskey=' . sesskey();

        $myusers = [];
        $usertable = '';

        // The search form.
        echo ' <form method="POST" id="reportsearchform" class="report_form" action="">
                <input type="hidden" name="sesskey" value="' . sesskey() . '" />
                    <input type="text" id="searchu" name="searchusage" class="searchusage"
                        value="' . get_string("searchusers", "report_myfeedback") . '"
                    />
                    <input type="submit" id="submitsearch" value="' . get_string("search", "report_myfeedback") . '" />
                    <input type="hidden" name="reportuserid" id="reportuserid" value="0" />
                </form>';
        // We first trim the search to remove leading and trailing spaces.

        $search = trim($search);
        // If there is a search input and it's not the text that tells the user to enter search input.
        if ($search != "" && $search != get_string("searchusers", "report_myfeedback")) {
            $searchu = addslashes(strip_tags($search)); // We escape the quotes etc and strip all html tags.
            $userresult = [];
            if (strpos($searchu, '@')) {
                // If it is an email address search for the full input.
                $userresult = $currentdb->get_records_sql("SELECT id,firstname,lastname,email,department FROM {user}
                    WHERE deleted = 0 AND email = ?", array($searchu));
            } else {
                // If not an email address then search on first word or last word.
                $namef = explode(" ", $searchu); // Make string into array if multiple words.
                $namel = array_reverse($namef); // Reverse the array to get the last word.
                // Suggest this checks to see how many words are entered in the search box.
                // Suggest the query then CHANGE from OR to AND for 2 word entries,
                // as otherwise you get back a lot of inaccurate results.
                $userresult = $currentdb->get_records_sql("SELECT id,firstname,lastname,email, department FROM {user}
                    WHERE deleted = 0 AND (firstname LIKE ('$namef[0]%') OR lastname LIKE ('$namel[0]%')) limit 10", []);
            }
            if ($userresult) {
                foreach ($userresult as $a) {
                    if ($a->id && ($a->firstname || $a->lastname)) {
                        $myusers[$a->id][0] = "<a href=\"" . $CFG->wwwroot . '/report/myfeedback/index.php?currenttab=usage'
                            . $sesskeyqs
                            . "&reporttype=$reporttype&reportuserid=" . $a->id . "\" title=\"" .
                            $a->email . "\" rel=\"tooltip\">" . $a->firstname . " " . $a->lastname . "</a>";
                        $myusers[$a->id][1] = $a->department;
                    }
                }
            }
            $helpiconusername = ' <img src="' . 'pix/info.png' . '" ' .
                ' alt="-" title="' . get_string('usagesrchheader_username_info', 'report_myfeedback') . '" rel="tooltip"/>';
            $helpiconuserdept = ' <img src="' . 'pix/info.png' . '" ' .
                ' alt="-" title="' . get_string('usagesrchheader_userdept_info', 'report_myfeedback') . '" rel="tooltip"/>';

            $usertable = "
                <table class=\"userstable\" id=\"userstable\">
				    <thead>
						<tr class=\"tableheader\">
							<th class=\"tblname\">" . get_string('name') . $helpiconusername . "</th>
							<th class=\"tbldepartment\">"
                                . get_string('department', 'report_myfeedback')  . $helpiconuserdept . "
							</th>
                        </tr>
					</thead>
                <tbody>";
            // Add the records to the table here. These records were stored in the myusers array.
            foreach ($myusers as $result) {
                if (isset($result[0])) {
                    $usertable .= "<tr>";
                    $usertable .= "<td>" . $result[0] . "</td>";
                    $usertable .= "<td>" . $result[1] . "</td>";
                    $usertable .= "</tr>";
                }
            }
            $usertable .= "</tbody><tfoot><tr><td></td><td></td></tr></tfoot></table><br />";
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
     * @param bool $ptutor If user is a personal tutor
     * @param bool $modt Is the user a module tutor
     * @param bool $proga Is the user a Dept admin
     * @param string $search The user input entered to search on
     * @return string Table with the list of users they have access to
     */
    public function get_all_accessible_users($ptutor, $modt, $proga, $search = null) {
        global $currentdb, $USER, $CFG;
        $sesskeyqs = '&sesskey=' . sesskey();

        $helpiconusername = ' <img src="' . 'pix/info.png' . '" ' .
            ' alt="-" title="' . get_string('mystudentssrch_username_info', 'report_myfeedback') . '" rel="tooltip"/>';
        $helpiconrelationship = ' <img src="' . 'pix/info.png' . '" ' .
            ' alt="-" title="' . get_string('mystudentssrch_relationship_info', 'report_myfeedback') . '" rel="tooltip"/>';

        $usertable = "<table style=\"float:left;\" class=\"userstable\" id=\"userstable\">
                          <thead>
                              <tr class=\"tableheader\">
                                  <th class=\"tblname\">" . get_string('name') . $helpiconusername . "</th>
                                  <th class=\"tblrelationship\">"
                                      . get_string('relationship', 'report_myfeedback') . $helpiconrelationship .
                                  "</th>
                              </tr>
                          </thead><tbody>";

        $myusers = [];
        // The search form.
        echo '<form  method="POST"  id="searchform" action="" >
                <input type="hidden" name="sesskey" value="' . sesskey() . '" />
                            <input type="text" id="searchu" name="searchuser" placeholder="'
                                . get_string("searchusers", "report_myfeedback") . '"
                            />
                            <input type="hidden" name="mytick" value="checked"/>
                            <input type="submit" id="submitsearch" value="' . get_string("search", "report_myfeedback") . '" />
                            </form>';
        // We first trim the search to remove leading and trailing spaces.
        $search = trim($search);
        // If there is a search input and it's not the text that tells the user to enter search input.
        if ($search && $search != get_string("searchusers", "report_myfeedback")) {
            $searchu = addslashes(strip_tags($search)); // We escape the quotes etc and strip all html tags.

            if (strpos($searchu, '@')) {
                // If it is an email address search for the full input.
                $userresult = $currentdb->get_records_sql("SELECT id,firstname,lastname,email FROM {user}
                    WHERE deleted = 0 AND email = ?", array($searchu));
            } else {
                // If not an email address then search on first word or last word.
                $namef = explode(" ", $searchu); // Make string into array if multiple words.
                $namel = array_reverse($namef); // Reverse the array to get the last word.
                // Suggest this checks to see how many words are entered in the search box.
                // Suggest the query then CHANGE from OR to AND for 2 word entries,
                // as otherwise you get back a lot of inaccurate results.
                $userresult = $currentdb->get_records_sql("SELECT id,firstname,lastname,email FROM {user}
                    WHERE deleted = 0 AND (firstname LIKE ('$namef[0]%') OR lastname LIKE ('$namel[0]%')) limit 10", []);
            }
            $progs = [];
            $mymods = [];
            if ($userresult) {
                if ($proga) {
                    // Get all courses the logged in user is dept admin in but only return the listed fields, then save in array.
                    $ptadmin = get_user_capability_course('report/myfeedback:progadmin', $USER->id, false, 'shortname,visible');
                    if ($ptadmin) {
                        foreach ($ptadmin as $valu) {
                            $progs[] = $valu;
                        }
                    }
                }
                if ($modt) {
                    // Get all courses the logged in user is modfule tutor in but only return the listed fields, then save in array.
                    $mtadmin = get_user_capability_course('report/myfeedback:modtutor', $USER->id, false, 'shortname,visible');
                    if ($mtadmin) {
                        foreach ($mtadmin as $valm) {
                            $mymods[] = $valm;
                        }
                    }
                }
                // For all users (currently 10 due to performance)returned in the search.
                // If they have the name and id fields in their records, check all courses they are student in.
                foreach ($userresult as $a) {
                    if ($a->id && ($a->firstname || $a->lastname)) {
                        if ($admin = get_user_capability_course('report/myfeedback:student', $a->id, false, 'shortname,visible')) {
                            foreach ($admin as $mod) {
                                if ($mod->visible && $mod->id && $mod->shortname) {
                                    // Courses should be active and have shortname.
                                    foreach ($progs as $dept) {
                                        // Iterate through the user and dept admin courses and if they match then add
                                        // that record to be added to table.
                                        if ($dept->id == $mod->id) {
                                            $myusers[$a->id][0] = "<a href=\"" . $CFG->wwwroot
                                                . "/report/myfeedback/index.php?userid=" . $a->id
                                                . $sesskeyqs
                                                . "\" title=\"" .
                                                $a->email . "\" rel=\"tooltip\">" . $a->firstname . " " . $a->lastname . "</a>";
                                            $myusers[$a->id][1] = '';
                                            $myusers[$a->id][2] = 1;
                                            $myusers[$a->id][3] = '';
                                        }
                                    }
                                    foreach ($mymods as $tutmod) {
                                        // Now iterate through the module tutor courses and if they match the student
                                        // then add that record to be added to table.
                                        if ($tutmod->id == $mod->id) {
                                            $myusers[$a->id][0] = "<a href=\"" . $CFG->wwwroot
                                                . "/report/myfeedback/index.php?userid=" . $a->id
                                                . $sesskeyqs
                                                . "\" title=\"" .
                                                $a->email . "\" rel=\"tooltip\">" . $a->firstname . " " . $a->lastname . "</a>";
                                            $myusers[$a->id][1] = '';
                                            $myusers[$a->id][2] = 2;
                                            foreach ($admin as $mod1) {
                                                // Here we add the course shortname that the module tutor has a capability
                                                // in for that student.
                                                if ($mod1->visible && $mod1->id && $mod1->shortname) {
                                                    foreach ($mymods as $tutmod1) {
                                                        if ($mod1->id == $tutmod1->id) {
                                                            // Add a comma if multiple course shortnames.
                                                            $myusers[$a->id][1] .= $mod1->shortname . ", ";
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

        // Get all the mentees, i.e. users you have a direct assignment to and add them to the table if you are a personal tutor.
        if ($ptutor) {
            if ($usercontexts = $currentdb->get_records_sql("SELECT c.instanceid, u.id as id, firstname, lastname, email
                                                    FROM {role_assignments} ra, {context} c, {user} u
                                                   WHERE ra.userid = ?
                                                         AND ra.contextid = c.id
                                                         AND c.instanceid = u.id
                                                         AND c.contextlevel = " . CONTEXT_USER, array($USER->id))) {
                foreach ($usercontexts as $u) {
                    if ($u->id && ($u->firstname || $u->lastname)) {
                        $myusers[$u->id][0] = "<a href=\"" . $CFG->wwwroot . "/report/myfeedback/index.php?userid=" . $u->id
                            . $sesskeyqs
                            . "\" title=\"" .
                            $u->email . "\" rel=\"tooltip\">" . $u->firstname . " " . $u->lastname . "</a>";
                        $myusers[$u->id][1] = get_string('personaltutee', 'report_myfeedback');
                        $myusers[$u->id][2] = 3;
                        $myusers[$u->id][3] = '';
                    }
                }
                unset($myusers[$USER->id]);
            }
        }

        // Add the records to the table here. These records were stored in the myusers array.
        foreach ($myusers as $result) {
            if (isset($result[0])) {
                $usertable .= "<tr>";
                $usertable .= "<td>" . $result[0] . "</td>";
                $usertable .= "<td class=\"ellip\" data-sort=$result[2] title=\"$result[3]\" rel=\"tooltip\">"
                    . $result[1] . "</td>";
                $usertable .= "</tr>";
            }
        }
        $usertable .= "</tbody><tfoot><tr><td></td><td></td></tr></tfoot></table>";
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
     * @param int $uid The user id
     * @return string Table with the list of users
     */
    public function get_tutees_for_prog_ptutors($uid) {
        global $currentdb, $CFG;
        $sesskeyqs = '&sesskey=' . sesskey();

        $myusers = [];
        // Get all the mentees, i.e. users you have a direct assignment to.
        if ($usercontexts = $currentdb->get_records_sql("SELECT u.id as id, firstname, lastname, email
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
                $myusers[$u->id]['here'] = "<a href=\"" . $CFG->wwwroot . "/report/myfeedback/index.php?userid=" . $u->id
                    . $sesskeyqs
                    . "\">Click here</a>";
                if (isset($u->profile_field_courseyear)) {
                    $myusers[$u->id]['year'] = $u->profile_field_courseyear;
                }
                if (isset($u->profile_field_programmename)) {
                    $myusers[$u->id]['prog'] = $u->profile_field_programmename;
                }
            }
        }
        $innertable = '<table class="innertable" width="100%" style="text-align:center; display:none"><thead><tr><th>'
                . get_string('tutee', 'report_myfeedback') .
                '</th><th>' . get_string('p_tut_programme', 'report_myfeedback') .
                '</th><th>' . get_string('yearlevel', 'report_myfeedback') .
                '</th><th>' . get_string('my_feedback', 'report_myfeedback') .
                '</th></tr></thead><tbody>';
        foreach ($myusers as $res) {
            $innertable .= "<tr>";
            $innertable .= "<td>" . $res['name'] . "</td><td>" . $res['prog'] . "</td><td>" .
                $res['year'] . "</td><td>" . $res['here'] . "</td></tr>";
        }
        $innertable .= "</tbody></table>";
        return $innertable;
    }

    /**
     * Return all users for the dept admin mod tutor groups
     *
     * @param int $uid The user id
     * @param int $cid The course id
     * @param array $tutgroup An array of members in the tutors group
     * @return string Table with the list of users
     */
    public function get_tutees_for_prog_tutor_groups($uid, $cid, $tutgroup) {
        global $currentdb;
        $myusers = [];

        // Get all the users in each tutor group and add their stats.
        if ($tutorgroups = $currentdb->get_records_sql("SELECT distinct u.id as userid, g.name, g.id as id
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
                $myusers[$tgroup->id]['count'] = 0;
            }
            // Iterate through both arrays and if members in the course are similar to those in the
            // tutors group then add them to list to be added to the table.
            foreach ($tutorgroups as $u) {
                foreach ($tutgroup as $gp => $val) {
                    if ($gp == $u->userid) {
                        $myusers[$u->id]['name'] = $u->name;
                        $sum = [];
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
                        $myusers[$u->id]['count'] += count($sum);
                    }
                }
            }
        }

        $stu = $this->get_user_analytics(
            $myusers, 't' . $cid,
            'stuRec',
            '',
            null,
            false,
            true
        );
        $innertable = "<table class=\"innertable\" width=\"100%\" style=\"text-align:center; display:none\">
            <thead><tr><th>" . get_string('groupname', 'report_myfeedback') . "</th><th>" .
            get_string('tutortblheader_assessment', 'report_myfeedback') . "</th><th>" .
            get_string('tutortblheader_nonsubmissions', 'report_myfeedback') . "</th><th>" .
            get_string('tutortblheader_latesubmissions', 'report_myfeedback') . "</th><th>" .
            get_string('tutortblheader_graded', 'report_myfeedback') . "</th><th>" .
            get_string('tutortblheader_lowgrades', 'report_myfeedback') . "</th></tr></thead><tbody>";

        $innertable .= "<tr>";
        $innertable .= "<td>" . $stu->uname . "</td>";
        $innertable .= "<td>" . $stu->ud . "</td>";
        $innertable .= "<td>" . $stu->un . "</td>";
        $innertable .= "<td>" . $stu->ul . "</td>";
        $innertable .= "<td>" . $stu->ug . "</td>";
        $innertable .= "<td>" . $stu->ulo . "</td>";

        $innertable .= "</tbody></table>";
        return $innertable;
    }

    /**
     * This returns the id of the personal tutor
     *
     * @param int $ptutorroleid Personal tutor role id
     * @param int $contextid The context id
     * @return int The id of the personal tutor or 0 if none
     */
    public function get_my_personal_tutor($ptutorroleid, $contextid) {
        global $currentdb;
        $sql = "SELECT userid FROM {role_assignments}
                WHERE roleid = ? AND contextid = ?
                ORDER BY timemodified DESC limit 1";
        $params = array($ptutorroleid, $contextid);
        $tutor = $currentdb->get_record_sql($sql, $params);
        return $tutor ? $tutor->userid : 0;
    }

    /**
     * Returns a course id given the course shortname
     *
     * @param text $shortname Course shortname
     * @return int The course id
     */
    public function get_course_id_from_shortname($shortname) {
        global $currentdb;
        $sql = "SELECT max(id) as id, fullname FROM {course}
                WHERE shortname = ?";
        $params = array($shortname);
        $cid = $currentdb->get_record_sql($sql, $params);
        return $cid ?: 0;
    }

    /**
     * Returns all assessments in the course
     *
     * @param int $cid The course id
     * @return array The assmessments id, name, type and module
     */
    public function get_all_assessments($cid) {
        global $currentdb;
        $now = time();
        $items = array('turnitintool', 'turnitintooltwo', 'workshop', 'quiz', 'assign');
        foreach ($items as $key => $item) {
            if (!$this->mod_is_available($item)) {
                unset($items[$key]);
            }
        }
        $items = "'" . implode("','", $items) . "'";
        $sql = "SELECT id, itemname, itemtype, itemmodule
                FROM {grade_items} gi
                WHERE (hidden != 1 AND hidden < ?) AND courseid = ?
                AND (itemmodule IN ($items) OR (itemtype = 'manual'))";
        $params = array($now, $cid);
        $assess = $currentdb->get_records_sql($sql, $params);

        return $assess;
    }

    /**
     * Returns a canvas graph of stats
     *
     * @param int $cid The course id
     * @param array $gradestotals Grades totals
     * @param int $enrolled The number of students enrolled in the course
     * @return string Canvas image The graph as an image on canvas
     * @throws \coding_exception
     */
    public function get_module_graph($cid, $gradestotals, $enrolled = null) {
        $ctotal = 0;
        $mean = 0;
        $grade40 = 0;
        $grade50 = 0;
        $grade60 = 0;
        $grade70 = 0;
        $grade100 = 0;

        foreach ($gradestotals as $totalgrade) {
            $ptotal = $totalgrade;
            $ctotal += $ptotal;
            ++$mean;
            if ($ptotal < 40) {
                ++$grade40;
            }
            if ($ptotal >= 40 && $ptotal < 50) {
                ++$grade50;
            }
            if ($ptotal >= 50 && $ptotal < 60) {
                ++$grade60;
            }
            if ($ptotal >= 60 && $ptotal < 70) {
                ++$grade70;
            }
            if ($ptotal >= 70) {
                ++$grade100;
            }
        }
        $meangrade = ($mean ? round($ctotal / $mean) : 0);
        $maxgrade = ($ctotal ? round(max($gradestotals)) : 0);
        $mingrade = ($ctotal ? round(min($gradestotals)) : 0);
        $posmin = round($mingrade * 3.7) + 5;
        $posmax = round($maxgrade * 3.7) + 5;
        $posavg = round($meangrade * 3.7) + 5;
        $posmintext = $posmin - 7;
        $posavgtext = $posavg - 7;
        $posmaxtext = $posmax - 7;
        $enrl = '';

        if ($posavgtext - $posmintext < 18) {
            $posavgtext = $posmintext + 18;
        }
        if ($posmaxtext - $posavgtext < 18) {
            $posmaxtext = $posavgtext + 18;
        }
        if ($posmintext < 1) {
            $posmintext = 1;
        }
        if ($mingrade != 100 && $posmintext > 312) {
            $posmintext = 312;
        }
        if ($mingrade == 100 && $posmintext > 306) {
            $posmintext = 306;
        }
        if ($posavgtext < 18) {
            $posavgtext = 18;
        }
        if ($meangrade != 100 && $posavgtext > 338) {
            $posavgtext = 340;
        }
        if ($meangrade == 100 && $posavgtext > 338) {
            $posavgtext = 330;
        }
        if ($posmaxtext < 34) {
            $posmaxtext = 34;
        }
        if ($posmaxtext > 354) {
            $posmaxtext = 354;
        }
        $result = '<i style="color:#5A5A5A">' . get_string('nodata', 'report_myfeedback') . '</i>';
        if ($ctotal) {
            $a = new stdClass();
            $a->minimum = $mingrade;
            $a->mean = $meangrade;
            $a->maximum = $maxgrade;
            $result = '<div title="' . get_string('bargraphdesc', 'report_myfeedback', $a) . '" rel="tooltip" class="modCanvas">
                        <canvas id="modCanvas' . $cid . '" width="380" height="70" style="border:0px solid #d3d3d3;">'
                        . get_string("browsersupport", "report_myfeedback") . '</canvas>
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
        var posmin = ' . $posmin . ';
        var posmax = ' . $posmax . ';
        var posavg = ' . $posavg . ';
        var posmintext = ' . $posmintext . ';
        var posmaxtext = ' . $posmaxtext . ';
        var posavgtext = ' . $posavgtext . ';

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
     * @param string $type The type of assemssment
     * @param int $cid The course id
     * @param int $gid The grade_item id
     * @return link The link to the assessment
     */
    public function get_assessment_link_from_type($type, $cid, $gid = null) {
        global $CFG, $currentdb;
        $sql = "SELECT cm.id FROM {course_modules} cm
            JOIN {grade_items} gi ON gi.iteminstance=cm.instance
            JOIN {modules} m ON cm.module = m.id AND gi.itemmodule = m.name
            AND cm.course = ? AND gi.id = ? AND gi.itemmodule = ?";
        $params = array($cid, $gid, $type);
        $link = $currentdb->get_record_sql($sql, $params);
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
     * @param array $assess The array with the assessments and their stats
     * @param int $uidnum The id for the table
     * @param string $display The CSS class that is added
     * @param string $style The CSS style to be added
     * @param string $breakdown Whether to add the breakdown text
     * @param array $users The array of users in this assessment and some stats
     * @param bool $pmod Whether the function is called from module dashboard
     * @return stdClass A table with the assessments and their stats
     */
    public function get_assessment_analytics($assess, $uidnum, $display = null, $style = null,
                                             $breakdown = null, $users = null, $pmod = false): stdClass {
        global $OUTPUT;
        // Sort the assessment by name alphabetically but case insensitive.
        uasort($assess, function($a11, $b11) {
            return strcasecmp($a11['name'], $b11['name']);
        });

        $anamecell = $ad = $an = $al = $ag = $af = $alo = $avas = '';
        foreach ($assess as $aid => $a) {
            $scol1 = $scol2 = $scol3 = $scol4 = '';
            $adue = $anon = $agraded = $alate = $afeed = $alow = 0;
            $assessmenticon = $aname = '';
            $assessgraph = '<i style="color:#5A5A5A">' . get_string('nographtodisplay', 'report_myfeedback') . '</i>';
            $uname = $ud = $un = $ul = $ug = $uf = $ulo = $uvas = '';
            $assesstotals = [];
            if (array_sum($a['score']) != null) {
                foreach ($a['score'] as $e) {
                    if ($e != null) {
                        $assesstotals[] = $e;
                    }
                }
                $assessgraph = $this->get_module_graph($aid, $assesstotals);
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

            $link = $item == 'manual' ? $this->get_assessment_link_from_type($item, $a['cid'])
                : $this->get_assessment_link_from_type($item, $a['cid'], $a['aid']);
            $aname = '<a href="' . $link . '" title="' . $a['name'] . '" rel="tooltip">' . $a['name'] . '</a>';
            $adue = $a['due'];
            $anon = $a['non'];
            $alate = $a['late'];
            $agraded = $a['graded'];
            $alow = $a['low'];

            // Get the inner users per assessment.
            if ($users) {
                $au = $this->get_user_analytics(
                    $users,
                    $aid,
                    ($pmod ? 'stuRecP a' . $aid : 'stuRec a' . $aid),
                    'display:none',
                    null,
                    true
                );
                $uname = $au->uname;
                $ud = $au->ud;
                $un = $au->un;
                $ul = $au->ul;
                $ug = $au->ug;
                $ulo = $au->ulo;
                $uvas = $au->u_vas;
            }
            $height = '80px';
            $minortable = "<div style='height:81px' class='settableheight'>
                <table data-aid='a" . $aid . "' style='" . $style . " ' class='tutor-inner " . $display . "'
                    height='" . $height . "' align='center'><tr class='accord'>";
            $ad .= $minortable . "<td style = 'text-align: center'>" . $adue . "</td></tr></table></div>" . $ud;
            $an .= $minortable . "<td>" . "<span class=$scol1>" . $anon . "</span></td></tr></table></div>" . $un;
            $al .= $minortable . "<td>" . "<span class=$scol2>" . $alate . "</span></td></tr></table></div>" . $ul;
            $ag .= $minortable . "<td>" . $agraded . "</td></tr></table></div>" . $ug;
            $alo .= $minortable . "<td>" . "<span class=$scol3>" . $alow . "</span></td></tr></table></div>" . $ulo;
            $avas .= $minortable . "<td class='overallgrade'>" . $assessgraph . "</td></tr></table></div>" . $uvas;
            $anamecell .= $minortable . "<td id='assess-name'>" . $assessmenticon . $aname
                . $breakdown . "</td></tr></table></div>" . $uname;
        }
        $as = new stdClass();
        $as->ad = $ad;
        $as->an = $an;
        $as->al = $al;
        $as->ag = $ag;
        $as->alo = $alo;
        $as->a_vas = $avas;
        $as->aname = $anamecell;
        return $as;
    }

    /**
     * Returns a table with the analytics for all users in the given subset requested
     *
     * @param array $users The array with the users and their stats
     * @param int $cid The course id
     * @param string $display The CSS class that is added
     * @param string $style The CSS style to be added
     * @param string $breakdown Whether to add the breakdown text
     * @param bool $fromassess Whether the function call is from user assessment
     * @param bool $tutgroup Whether these are users for the tutor group
     * @return stdClass A table with the users and their stats
     * @throws \coding_exception
     */
    /**
     * Returns a table with the analytics for all users in the given subset requested
     *
     * @param array $users The array with the users and their stats
     * @param int $cid The course id
     * @param string $display The CSS class that is added
     * @param string $style The CSS style to be added
     * @param string $breakdodwn Whether to add the breakdown text
     * @param bool $fromassess Whether the function call is from user assessment
     * @param bool $tutgroup Whether these are users for the tutor group
     * @return stdClass A table with the users and their stats
     * @throws \coding_exception
     */
    public function get_user_analytics($users, $cid, $display = null, $style = null, $breakdodwn = null,
                                       $fromassess = null, $tutgroup = false): stdClass {
        global $currentdb, $CFG;
        $sesskeyqs = '&sesskey=' . sesskey();

        $userassess = [];
        $assessuser = []; // If adding user for each assessment.

        $usercell = $uname = $ud = $un = $ul = $ug = $uf = $ulo = $uvas = $fname = $lname = '';

        $usergraph = '<i style="color:#5A5A5A">' . get_string('nographtodisplay', 'report_myfeedback') . '</i>';
        if ($users) {
            // Need to run through the entire array to create the arrays needed otherwise it will omit
            // elements below what the current array element it's working on.
            foreach ($users as $usid1 => $usr1) {
                if ($usr1['count'] > 0) {
                    foreach ($usr1['assess'] as $asid => $usrval) {
                        if ($usrval['score'] > 0) {
                            $userassess[$usid1][$asid] = $usrval['score'];
                            if ($asid == $cid && $fromassess) {
                                $assessuser[$usid1][$asid] = $usrval['score'];
                            }
                        }
                    }
                }
            }

            // Iterate through array again to get the correct amount of elements for each user.
            foreach ($users as $usid => $usr) {
                $scol1 = $scol2 = $scol3 = '';
                if (!isset($usr['name']) || !$usr['name']) {
                    $getname = $currentdb->get_record('user', array('id' => $usid), $list = 'firstname,lastname,email');
                    if ($getname) {
                        $uname = "<a href=\"" . $CFG->wwwroot . "/report/myfeedback/index.php?userid=" . $usid
                            . $sesskeyqs
                            . "\" title=\"" .
                            $getname->email . "\" rel=\"tooltip\">" . $getname->firstname . " " . $getname->lastname . "</a>";
                        $fname = $getname->firstname;
                        $lname = $getname->lastname;
                    }
                } else {
                    $uname = $usr['name'];
                }
                $users[$usid]['name'] = $uname;
                $users[$usid]['fname'] = $fname;
                $users[$usid]['lname'] = $lname;

                if ($fromassess) {
                    $userassess = $assessuser;
                    $udue = $usr['assess'][$cid]['due'];
                    $unon = $usr['assess'][$cid]['non'];
                    $ulate = $usr['assess'][$cid]['late'];
                    $ugraded = $usr['assess'][$cid]['graded'];
                    $ulow = $usr['assess'][$cid]['low'];
                } else {
                    $udue = $usr['due'];
                    $unon = $usr['non'];
                    $ulate = $usr['late'];
                    $ugraded = $usr['graded'];
                    $ulow = $usr['low'];
                }
                if (count($userassess) > 0) {
                    if ($tutgroup) {
                        foreach ($users[$usid]['assess'] as $uas) {
                            $a = [];
                            foreach ($uas as $tgtot) {
                                if ($tgtot != null) {
                                    $a[] = $tgtot;
                                }
                            }
                        }
                        $usergraph = $this->get_module_graph('tg' . $usid, $a, count($a));
                    } else {
                        $usergraph = $this->get_dashboard_zscore($usid, $userassess, $usid . $cid, $cid);
                    }
                }
                if (!$fromassess || ($fromassess && isset($usr['assess'][$cid]['yes']))) {
                    $height = '80px';
                    $studenttable = "<table style='" . $style . "' class='tutor-inner " . $display . "' height='"
                        . $height . "' align='center'><tr class='st-accord'>";
                    $usercell .= $studenttable . "<td>" . $uname . $breakdodwn . '</td></tr></table>';
                    $ud .= $studenttable . "<td class='assessdue'>" . $udue . "</td></tr></table>";
                    $un .= $studenttable . "<td>" . "<span class=$scol1>" . $unon . "</span></td></tr></table>";
                    $ul .= $studenttable . "<td>" . "<span class=$scol2>" . $ulate . "</span></td></tr></table>";
                    $ug .= $studenttable . "<td>" . $ugraded . "</td></tr></table>";
                    $ulo .= $studenttable . "<td>" . "<span class=$scol3>" . $ulow . "</span></td></tr></table>";
                    $uvas .= $studenttable . "<td class='overallgrade'>" . $usergraph . '</td></tr></table>';
                }
            }
        }
        // Sort the users alphabetically but case insensitive.
        uasort($users, function($a12, $b12) {
            return strcasecmp($a12['name'], $b12['name']);
        });

        $us = new stdClass();
        $us->ud = $ud;
        $us->un = $un;
        $us->ul = $ul;
        $us->ug = $ug;
        $us->ulo = $ulo;
        $us->u_vas = $uvas;
        $us->uname = $usercell;
        $us->newusers = ($fromassess ? '' : $users);
        return $us;
    }

    /**
     * Returns the head of the table, with all the titles for each column
     *
     * @param array $headertitles The list of header titles as strings.
     * @param array $headerhelptext
     * @return string Table head, with titles above each column.
     */
    public function get_table_headers($headertitles, $headerhelptext) {
        $header = "<thead><tr class=\"usagetableheader\">";
        for ($i = 0; $i < count($headertitles); $i++) {
            $helpicon = '<img src="' . 'pix/info.png' . '" ' .
                ' alt="-" title="' . $headerhelptext[$i] . '" rel="tooltip"/>';
            $header .= "<th>" . $headertitles[$i] . " " . $helpicon . "</th>";
        }
        $header .= "</tr></thead>";
        return $header;
    }

    /**
     * Returns subcategories of a parent category only 1 level deep.
     *
     * @param int $parentcatid The parent category id
     * @return array Subcategory objects containing their ids
     */
    public function get_subcategories($parentcatid) {
        global $currentdb;

        return $currentdb->get_records_sql("SELECT id, visible
                                                    FROM {course_categories}
                                                   WHERE parent = ? ORDER BY visible desc, sortorder", array($parentcatid));
    }


    /**
     * Returns a selectable form menu of subcategories of a parent category only 1 level deep.
     *
     * @param int $parentcatid The parent category id
     * @param int $categoryid The currently selected category id
     * @param bool $parent
     * @return string  Form menu of subcategories of a parent category
     * @throws \coding_exception
     */
    public function get_subcategory_menu($parentcatid=0, $categoryid=0, $parent=true) {
        global $SITE;

        $subcategories = $this->get_subcategories($parentcatid);

        // Start of menu form.
        $menu = "<form method=\"POST\" id=\"report_category_select\" class=\"report_form\" action=\"\">"
            . '<input type="hidden" name="sesskey" value="' . sesskey() . '" />'
            . get_string('category', 'report_myfeedback')
            . ": <select id=\"categorySelect\" value=\"\" name=\"categoryid\"><option id=\"selectcat\">"
            . get_string('choosedots')."</option>";
        if ($parent == true) {
            // Add a top level category option.
            $menu .= "<option id=\"selectcat\"";
            if ($categoryid == $parentcatid) {
                $menu .= " selected";
            }
            $menu .= ">" . $SITE->fullname . "</option>";
        }
        foreach ($subcategories as $subcat) {
            $menu .= "<option value=\"$subcat->id\"";
            if ($categoryid == $subcat->id) {
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
     * @param int $catid The parent category id
     * @param string $capability The capability that defines the role of the returned users. E.g. student or modtutor.
     * @return array Subcategory objects containing their ids
     */
    public function get_unique_category_users($catid, $capability = 'report/myfeedback:student') {
        global $currentdb;

        // Get all roles that are assigned the My Feedback capability passed to the function.
        $roleids = $this->get_roles($capability);

        // If there are no roles with a my feedback student capability then return null.
        if (count($roleids) < 1) {
            return null;
        }
        // Get all the courses in the category, then iterate through the below
        // CONTINUE HERE - get courses in a category and iterate though each enrolled user on that course.
        $params = [];

        $sql = 'SELECT DISTINCT ra.userid
                           FROM {role_assignments} ra
                           JOIN {user} u ON u.id = ra.userid
                           JOIN {context} con ON ra.contextid = con.id AND con.contextlevel = 50
                          WHERE u.deleted = 0 AND u.suspended = 0';
        if ($catid > 0) {
            // If this category isn't the root, get its contextid.
            $contextid = $this->get_categorycontextid($catid);
            $params = array("%/".$contextid, "%/".$contextid."/%");
            $sql .= ' AND (con.path LIKE ? OR con.path LIKE ? )';
        }
        $sql .= ' AND (roleid = ?';
        // Add all the roleid code into the where query.
        $params[] = $roleids[0];
        $i = 1; // Start at 1, because the first role is already in the query.

        // The following line of code is causing warnings in the UI because $i exceeds array size.
        // This is unrelated to the issue I'm trying to fix (i.e. https://tracker.moodle.org/browse/CONTRIB-6841),
        // however I will guard execution with an existence check for now. Hopefully the root cause of
        // the issue will be addressed in future.
        while (isset($roleids[$i]) && $roleids[$i] != null) {
            $sql .= '  OR roleid = ?';
            $params[] = $roleids[$i];
            $i++;
        }
        $sql .= ')';

        $rs = $currentdb->get_recordset_sql($sql, $params);

        // The recordset contains records.
        if ($rs->valid()) {
            $users = [];
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
     * @param int $catid The courseid
     * @param string $capability The capability that defines the role of the returned users. E.g. student or modtutor.
     * @return array User objects containing their ids
     */
    public function get_unique_course_users($catid, $capability = 'report/myfeedback:student') {
        global $currentdb;

        // Get all roles that are assigned the My Feedback capability passed to the function.
        $roleids = $this->get_roles($capability);

        // If there are no roles with a my feedback student capability then return null.
        if (count($roleids) < 1) {
            return null;
        }
        // Get courses in a category and iterate though each enrolled user on that course.
        $courseparams = array("%/".$catid, "%/".$catid."/%");

        $coursesql = "SELECT DISTINCT c.id
                                 FROM {course} c, {course_categories} cat
                                WHERE c.category = cat.id AND cat.path LIKE ? OR cat.path LIKE ?";
        $courses = $currentdb->get_recordset_sql($coursesql, $courseparams);

        // The recordset contains records.
        if ($courses->valid()) {

            foreach ($courses as $course) {
                // For entire Moodle get all students on courses within each faculty, then total together so you show each faculty
                // with total moodle stats on top.
                $sql = 'SELECT DISTINCT ra.userid
                                   FROM {role_assignments} ra
                                   JOIN {user} u ON u.id = ra.userid
                                   JOIN {context} con ON ra.contextid = con.id AND con.contextlevel = 50
                                  WHERE u.deleted = 0 AND u.suspended = 0 AND con.instanceid = ?  AND (roleid = ?';
                $i = 1; // Start at 1, because the first role is already in the query.
                // Add all the roleid code into the where query.
                while ($roleids[$i] != null) {
                    $sql .= '  OR roleid = ?';
                    $i++;
                }
                $sql .= ')';

                $params = $roleids;
                // Prepend the course id to the front of the role ids already in the array.
                array_unshift($params, $course->id);

                $rs = $currentdb->get_recordset_sql($sql, $params);

                $userids = [];
                if ($rs->valid()) {
                    // The recordset contains records.
                    foreach ($rs as $record) {
                        $userids[] = $record->userid;
                    }
                    $rs->close();
                }
                return $userids;
                $rs->close();
            }
        }
    }

    /**
     * Returns the roles that have a particular capability
     *
     * @param string $capability The capability that defines the role of the returned users. E.g. student or modtutor.
     * @return array An array of roleids.
     */
    public function get_roles($capability): array {
        global $currentdb;

        return $currentdb->get_fieldset_select('role_capabilities', 'roleid', 'capability = ?', [$capability]);
    }

    /**
     * Returns the first name and surname of a particular user
     *
     * @param int $ui The user id
     * @return string The firstname and lastname of the user, separated by a space.
     */
    public function get_names($ui): string {
        global $currentdb;

        $unames = $currentdb->get_record_sql("SELECT firstname,lastname FROM {user} where id=?", [$ui]);
        return $unames->firstname . " " . $unames->lastname;
    }

    /**
     * Returns the course fullname or shortname
     *
     * @param int $id The course id
     * @param bool $fullname Whether to return the fullname or the shortname.
     * @return string The firstname and lastname of the user, separated by a space.
     */
    public function get_course_name($id, $fullname = true): string {
        global $currentdb;

        $coursename = $currentdb->get_record_sql("SELECT c.fullname, c.shortname
                                                    FROM {course} c
                                                   WHERE c.id = ?", array($id));
        if ($fullname == true) {
            return $coursename->fullname;
        } else {
            return $coursename->shortname;
        }
    }

    /**
     * Returns the category name.
     *
     * @param int $id The category id
     * @return string|null The category name.
     */
    public function get_category_name($id): ?string {
        global $currentdb;

        $catname = $currentdb->get_record_sql("SELECT cat.name
                                                    FROM {course_categories} cat
                                                   WHERE cat.id = ?", array($id));
        if (!empty($catname->name)) {
            return $catname->name;
        }

        return null;
    }

    /**
     * Returns the up a category button
     *
     * @param int $categoryid The category id
     * @param string $reporttype The type of report for inserting into the URL.
     * @return string The go up a category button as html.
     */
    public function get_parent_category_link($categoryid, $reporttype): string {
        global $currentdb, $CFG;
        $sesskeyqs = '&sesskey=' . sesskey();

        $category = $currentdb->get_record_sql("SELECT cat.parent
                                                    FROM {course_categories} cat
                                                   WHERE cat.id = ?", array($categoryid));
        if (!empty($category->parent) && $category->parent > 0) {
            return " <a href=\"".$CFG->wwwroot ."/report/myfeedback/index.php?currenttab=usage&reporttype=" . $reporttype
                . $sesskeyqs
                . "&categoryid=".$category->parent."\" title=\"Up to parent category\"><img class=\"uparrow\" src=\"".
                $CFG->wwwroot . "/report/myfeedback/pix/return.png\" alt=\"\" /></a>";
        } else {
            return "";
        }
    }

    /**
     * Returns the up to category button for the course reports
     *
     * @param int $courseid The course id.
     * @param string $reporttype The type of report for inserting into the URL.
     * @return string The go up to category button as html.
     */
    public function get_course_category_link($courseid, $reporttype): string {
        global $currentdb, $CFG;
        $sesskeyqs = '&sesskey=' . sesskey();

        $course = $currentdb->get_record_sql("SELECT c.category
                                                    FROM {course} c
                                                   WHERE c.id = ?", array($courseid));
        if (!empty($course->category) && $course->category > 0) {
            return " <a href=\"".$CFG->wwwroot ."/report/myfeedback/index.php?currenttab=usage&reporttype=" . $reporttype
                . $sesskeyqs
                . "&categoryid=".$course->category."\" title=\"Up to parent category\"><img class=\"uparrow\" src=\"".
                $CFG->wwwroot . "/report/myfeedback/pix/return.png\" alt=\"\" /></a>";
        } else {
            return "";
        }
    }

    /**
     * Returns whether a user is suspended and deleted or not
     *
     * @param int $id The user id
     * @return bool True if the user is active (not suspended or deleted), otherwise false.
     */
    public function is_active_user($id): bool {
        global $currentdb;

        $user = $currentdb->get_record_sql("SELECT deleted, suspended
                                                   FROM {user}
                                                   WHERE id = ?", array($id));
        if ($user->suspended == 0 && $user->deleted == 0) {
            return true;
        }
        return false;

    }



    /**
     * Returns the context id of the category
     *
     * @param int $id The category id
     * @return int The context id of the category.
     */
    public function get_categorycontextid($id): int {
        global $currentdb;

        $contextid = $currentdb->get_record_sql("SELECT id
                                                    FROM {context}
                                                   WHERE contextlevel = 40 AND instanceid = ?", array($id));
        return $contextid->id;
    }

    /**
     * Returns the personal tutees of a personal tutor
     *
     * @param int $personaltutorid The user id of the personal tutor
     * @return array The list of user objects containing the ids of personal tutees.
     */
    public function get_personal_tutees($personaltutorid) {
        global $currentdb;
        // Get all the mentees, i.e. users you have a direct assignment to as their personal tutor.
        return $currentdb->get_records_sql("SELECT u.id
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
     * @param array $uids The array of user ids.
     * @return array The list of staff and their usage statistics.
     */
    public function get_overall_staff_usage_statistics($uids): array {
        global $currentdb;

        $users = [];
        $usertotalviews = [];
        // The array of studentids viewed by any staff members.

        foreach ($uids as $ui) {
            $users[$ui]['userid'] = $ui;
            $users[$ui]['name'] = $this->get_names($ui);
            $users[$ui]['totalviews'] = 0;
            $users[$ui]['ownreportviews'] = 0;
            $users[$ui]['mystudentstabviews'] = 0;
            // The array of studentids viewed by this staff member - can do a count later to get the number viewed.
            $users[$ui]['studentsviewed'] = [];
            $users[$ui]['studentreportviews'] = 0;
            $users[$ui]['modtutordashboardviews'] = 0;
            $users[$ui]['ptutordashboardviews'] = 0;
            $users[$ui]['deptadmindashboardviews'] = 0;
            $users[$ui]['downloads'] = 0;
            $users[$ui]['lastaccess'] = 0;
            $users[$ui]['ptutees'] = count($this->get_personal_tutees($ui));

            foreach ($this->get_user_usage_logs($ui) as $reportevent) {
                switch($reportevent->eventname) {
                    // Check for ownreport views and student report views.
                    case '\report_myfeedback\event\myfeedbackreport_viewed':
                        $users[$ui]['totalviews'] += 1;
                        // Check if it was their own report, or a students' report they viewed.
                        if ($reportevent->userid == $reportevent->relateduserid) {
                            $users[$ui]['ownreportviews'] += 1;
                        } else {
                            $users[$ui]['studentreportviews'] += 1;
                            $users[$ui]['studentsviewed'][$reportevent->relateduserid] += 1;
                        }
                        if ($reportevent->timecreated > $users[$ui]['lastaccess']) {
                            $users[$ui]['lastaccess'] = $reportevent->timecreated;
                        }
                        break;

                    case '\report_myfeedback\event\myfeedbackreport_download':
                    case '\report_myfeedback\event\myfeedbackreport_downloadmtutor':
                    case '\report_myfeedback\event\myfeedbackreport_downloaddeptadmin':
                        $users[$ui]['downloads'] += 1;
                        if ($reportevent->timecreated > $users[$ui]['lastaccess']) {
                            $users[$ui]['lastaccess'] = $reportevent->timecreated;
                        }
                        break;
                    case '\report_myfeedback\event\myfeedbackreport_viewed_mystudents':
                        $users[$ui]['totalviews'] += 1;
                        $users[$ui]['mystudentstabviews'] += 1;
                        if ($reportevent->timecreated > $users[$ui]['lastaccess']) {
                            $users[$ui]['lastaccess'] = $reportevent->timecreated;
                        }
                        break;
                    case '\report_myfeedback\event\myfeedbackreport_viewed_ptutordash':
                        $users[$ui]['totalviews'] += 1;
                        $users[$ui]['ptutordashboardviews'] += 1;
                        if ($reportevent->timecreated > $users[$ui]['lastaccess']) {
                            $users[$ui]['lastaccess'] = $reportevent->timecreated;
                        }
                        break;
                    case '\report_myfeedback\event\myfeedbackreport_viewed_mtutordash':
                        $users[$ui]['totalviews'] += 1;
                        $users[$ui]['modtutordashboardviews'] += 1;
                        if ($reportevent->timecreated > $users[$ui]['lastaccess']) {
                            $users[$ui]['lastaccess'] = $reportevent->timecreated;
                        }
                        break;
                    case '\report_myfeedback\event\myfeedbackreport_viewed_deptdash':
                        $users[$ui]['totalviews'] += 1;
                        $users[$ui]['deptadmindashboardviews'] += 1;
                        if ($reportevent->timecreated > $users[$ui]['lastaccess']) {
                            $users[$ui]['lastaccess'] = $reportevent->timecreated;
                        }
                        break;
                }
            }
            // Note: consider whether we want to order by total activity, rather than total views.
            // This would also capture downloads etc then too.
            // If so we could increment the count each time the foreach loop is initiated instead.
            $usertotalviews[] = $users[$ui]['totalviews'];
            // Instead of overriding the keys (like in array_merge), the array_merge_recursive()
            // function makes the value as an array.
            // E.g. Array ( [12345] => 3 [67890] => Array ( [0] => 1 [1] => 6 ) [54365] => 3 ) -
            // the second student was viewed by 2 staff 1 and 6 times.

        }

        // Sort in descending order - use array_multisort as it's better with large arrays than usort.
        array_multisort($usertotalviews, SORT_DESC, SORT_NUMERIC, $users);

        return $users;
    }

    /**
     * Returns usage statistics for the students passed to the function
     *
     * @param array $uids The array of user ids
     * @return array The list of students and their usage statistics.
     */
    public function get_overall_student_usage_statistics($uids): array {

        $users = [];
        $usertotalviews = [];
        foreach ($uids as $ui) {
            $users[$ui]['userid'] = $ui;
            $users[$ui]['name'] = $this->get_names($ui);
            $users[$ui]['totalviews'] = 0;
            $users[$ui]['notes'] = 0;
            $users[$ui]['feedback'] = 0;
            $users[$ui]['downloads'] = 0;
            $users[$ui]['lastaccess'] = 0;
            // Get the personal tutor details of the user.
            $ptutorroleid = $this->get_personal_tutor_id();
            $usercontext = context_user::instance($ui);
            $users[$ui]['personaltutor'] = "";
            $users[$ui]['personaltutorid'] = -1;
            if ($mytutorid = $this->get_my_personal_tutor($ptutorroleid, $usercontext->id)) {
                $users[$ui]['personaltutorid'] = $mytutorid;
                $users[$ui]['personaltutor'] = $this->get_names($mytutorid);
            }

            foreach ($this->get_user_usage_logs($ui) as $reportevent) {

                switch($reportevent->eventname) {

                    case '\report_myfeedback\event\myfeedbackreport_viewed':
                        $users[$ui]['totalviews'] += 1;
                        if ($reportevent->timecreated > $users[$ui]['lastaccess']) {
                            $users[$ui]['lastaccess'] = $reportevent->timecreated;
                        }
                        break;

                    case '\report_myfeedback\event\myfeedbackreport_download':
                        $users[$ui]['downloads'] += 1;
                        if ($reportevent->timecreated > $users[$ui]['lastaccess']) {
                            $users[$ui]['lastaccess'] = $reportevent->timecreated;
                        }
                        break;

                }
            }

            foreach ($this->get_notes_and_feedback($ui) as $studentinput) {
                if ($studentinput->notes) {
                    $users[$ui]['notes'] += 1;
                }
                if ($studentinput->feedback) {
                    $users[$ui]['feedback'] += 1;
                }
            }
            $usertotalviews[] = $users[$ui]['totalviews'];
        }

        // Sort in descending order - use array_multisort as it's better with large arrays than usort.
        array_multisort($usertotalviews, SORT_DESC, SORT_NUMERIC, $users);

        return $users;
    }

    /**
     * Returns a table with the usage statistics for the staff passed to the function
     *
     * @param array $uids The array of staff user ids.
     * @param bool $showptutees Whether or not to display personal tutees.
     * @param bool $overview Whether or not to display just an overview with aggregated data only and no
     *                       user data or closing table tags.
     * @param string $overviewname The name of the overview (e.g. category or course).
     * @param string $overviewlink The link for the overview name (so users can navigate to subcategories).
     * @param bool $printheader Whether to print the headers or not, so overviews can show aggregated data
     *                          for subcategories in a single table.
     * @return str Table containing the staff statistics (missing the </tbody></table> tag if an overview).
     */
    public function get_staff_statistics_table($uids, $showptutees=false, $overview=false, $overviewname = "",
                                               $overviewlink = "", $printheader=true) {
        global $CFG;
        $sesskeyqs = '&sesskey=' . sesskey();

        $i = 0;
        $exceltable = [];

        // Populate the $exceltable array with the previous data, if it's not the first row of an overview.
        if ($overview == true && $printheader == false) {
            $exceltable = $_SESSION["exp_sess"];
            $i = count($exceltable);
        }

        if ($printheader == true) {
            $usagetable =
                "<table id=\"usagetable\" class=\"table table-striped table-bordered table-hover\" style=\"text-align:center\">";
            // Print the table headings.
            $headers = array(get_string('tutortblheader_name', 'report_myfeedback'));
            $headerhelptext = array(get_string('usagetblheader_name_info', 'report_myfeedback'));

            if ($overview == true) {
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

            if ($showptutees) {
                $headers[] = get_string('personaltutees', 'report_myfeedback');
                $headerhelptext[] = get_string('usagetblheader_personaltutees_info', 'report_myfeedback');
            }
            $usagetable .= $this->get_table_headers($headers, $headerhelptext);
            // Print the table data.
            $usagetable .= "<tbody>";
        }

        // Get the table data.
        if (count($uids) > 0) {
            // Get all the staff stats.
            $allstaffstats = $this->get_overall_staff_usage_statistics($uids);

            // Print stats for each staff member.
            // TODO: make the drop down button toggle to show/hide student data for the category.
            // Currently it shows and can't be toggled off.
            $staffusagetable = "";
            $studentsviewedbyanystaff = [];

            $overallstats = [];
            $overallstats['totalviews'] = 0;
            $overallstats['ownreportviews'] = 0;
            $overallstats['mystudentstabviews'] = 0;
            $overallstats['studentreportviews'] = 0;
            $overallstats['ptutordashboardviews'] = 0;
            $overallstats['modtutordashboardviews'] = 0;
            $overallstats['deptadmindashboardviews'] = 0;
            $overallstats['downloads'] = 0;
            $overallstats['lastaccess'] = 0;

            foreach ($allstaffstats as $onestaffstats) {
                $staffusagetable .= "<tr>";
                if (count($uids) > 1) {
                    $staffusagetable .= "<td>" . "<a href=\"" . $CFG->wwwroot . "/report/myfeedback/index.php?currenttab=usage"
                        . $sesskeyqs
                        . "&reporttype=staffmember&reportuserid=" . $onestaffstats['userid']
                        . "\" title=\"" . ucfirst(get_string('view', 'report_myfeedback')) . " "
                        . $onestaffstats['name'] . get_string('apostrophe_s', 'report_myfeedback')
                        . strtolower(get_string('usagereport', 'report_myfeedback'))
                        . "\" rel=\"tooltip\">" . $onestaffstats['name']."</a></td>";
                } else {
                    $staffusagetable .= "<td>" . "<a href=\"" . $CFG->wwwroot . "/report/myfeedback/index.php?currenttab=usage"
                        . $sesskeyqs
                        . "&reporttype=staffmember&reportuserid=" . $onestaffstats['userid']
                        . "\" title=\"" . ucfirst(get_string('view', 'report_myfeedback')) . " "
                        . $onestaffstats['name'] . get_string('apostrophe_s', 'report_myfeedback') . " "
                        .  get_string('usagereport', 'report_myfeedback')
                        . "\" rel=\"tooltip\">" . $onestaffstats['name']."</a></td>";
                }
                $staffusagetable .= "<td>" . $onestaffstats['totalviews'] . "</td>";
                $staffusagetable .= "<td>" . $onestaffstats['ownreportviews'] . "</td>";
                $staffusagetable .= "<td>" . $onestaffstats['mystudentstabviews'] . "</td>";
                $staffusagetable .= "<td>" . count($onestaffstats['studentsviewed']) . "</td>";
                $staffusagetable .= "<td>" . $onestaffstats['studentreportviews'] . "</td>";
                $staffusagetable .= "<td>" . $onestaffstats['ptutordashboardviews'] . "</td>";
                $staffusagetable .= "<td>" . $onestaffstats['modtutordashboardviews'] . "</td>";
                $staffusagetable .= "<td>" . $onestaffstats['deptadmindashboardviews'] . "</td>";
                $staffusagetable .= "<td>" . $onestaffstats['downloads'] . "</td>";

                // Only print the excel data if it's not an overview.
                if ($overview == false) {
                    // Store the excel export data.
                    $exceltable[$i]['name'] = $onestaffstats['name'];
                    $exceltable[$i]['totalviews'] = $onestaffstats['totalviews'];
                    $exceltable[$i]['ownreportviews'] = $onestaffstats['ownreportviews'];
                    $exceltable[$i]['mystudentstabviews'] = $onestaffstats['mystudentstabviews'];
                    $exceltable[$i]['studentsviewed'] = count($onestaffstats['studentsviewed']);
                    $exceltable[$i]['studentreportviews'] = $onestaffstats['studentreportviews'];
                    $exceltable[$i]['ptutordashboardviews'] = $onestaffstats['ptutordashboardviews'];
                    $exceltable[$i]['modtutordashboardviews'] = $onestaffstats['modtutordashboardviews'];
                    $exceltable[$i]['deptadmindashboardviews'] = $onestaffstats['deptadmindashboardviews'];
                    $exceltable[$i]['downloads'] = $onestaffstats['downloads'];
                }

                // Display last accessed.
                $staffusagetable .= "<td>";
                // Check if the user has ever accessed the report. If not lastaccess will be 0.
                if ($onestaffstats['lastaccess'] > 0) {
                    $staffusagetable .= date('d-m-Y H:i', $onestaffstats['lastaccess']);
                    // Only print the excel data if it's not an overview.
                    if ($overview == false) {
                        $exceltable[$i]['lastaccess'] = date('d-m-Y H:i', $onestaffstats['lastaccess']);
                    }
                } else {
                    $staffusagetable .= "-";
                    // Only print the excel data if it's not an overview.
                    if ($overview == false) {
                        $exceltable[$i]['lastaccess'] = "";
                    }
                }
                $staffusagetable .= "</td>";
                if ($showptutees) {
                    if ($onestaffstats['ptutees'] > 0) {
                        $staffusagetable .= "<td><a href=\"" . $CFG->wwwroot
                            . "/report/myfeedback/index.php?currenttab=usage"
                            . $sesskeyqs
                            . "&reporttype=personaltutorstudents&reportuserid=" . $onestaffstats['userid']
                            . "\" title=\"" . ucfirst(get_string('view', 'report_myfeedback')) . " "
                            . $onestaffstats['name'] . get_string('apostrophe_s', 'report_myfeedback')
                            . strtolower(get_string('personaltutees', 'report_myfeedback')) .
                            "\" rel=\"tooltip\">" . $onestaffstats['ptutees'] . " "
                            . strtolower(get_string('personaltutees', 'report_myfeedback')) . "</a></td>";
                        // Only print the excel data if it's not an overview.
                        if ($overview == false) {
                            $exceltable[$i]['ptutees'] = $onestaffstats['ptutees'];
                        }
                    } else {
                        $staffusagetable .= "<td>&nbsp;</td>";
                    }
                }

                $staffusagetable .= "</tr>";

                // We only need overall stats if we are looking at more than one staff member.
                if (count($uids) > 1) {
                    // Add the staff stats to the overall stats.
                    if ($onestaffstats['totalviews'] > 0) {
                        $overallstats['viewedby'] += 1;
                    }
                    $overallstats['totalviews'] += $onestaffstats['totalviews'];
                    $overallstats['ownreportviews'] += $onestaffstats['ownreportviews'];
                    $overallstats['mystudentstabviews'] += $onestaffstats['mystudentstabviews'];
                    // Students viewed.
                    foreach ($onestaffstats['studentsviewed'] as $studentid => $numberviews) {
                        $studentsviewedbyanystaff[$studentid] = $numberviews;
                    }
                    $overallstats['studentreportviews'] += $onestaffstats['studentreportviews'];

                    $overallstats['ptutordashboardviews'] += $onestaffstats['ptutordashboardviews'];
                    $overallstats['modtutordashboardviews'] += $onestaffstats['modtutordashboardviews'];
                    $overallstats['deptadmindashboardviews'] += $onestaffstats['deptadmindashboardviews'];
                    $overallstats['downloads'] += $onestaffstats['downloads'];
                    if ($onestaffstats['lastaccess'] > $overallstats['lastaccess']) {
                        $overallstats['lastaccess'] = $onestaffstats['lastaccess'];
                    }
                    $overallstats['ptutees'] += $onestaffstats['ptutees'];
                }
                // Only iterate i if it's not an overview - i.e. the results will be included in the export file.
                if ($overview == false) {
                    $i++;
                }
            }

            // First print overall category stats.

            // Only show overall stats if there's more than one staff member.
            if (count($uids) > 1) {
                $usagetable .= '<tr';
                // Apply a darker sub-heading style to the overall category stats.
                if ($overview == false) {
                    $usagetable .= 'class="highlight"';
                }
                $usagetable .= '>';
                // Get the course / category name.
                $usagetable .= '<td id="long-name">';
                // If it's not an overview, then print the count in brackets in the same column.
                if ($overview == false) {
                    // Show an arrow if there are items underneath (staff).
                    $usagetable .= "<span class=\"assess-br modangle\">&#9660;</span>&nbsp;";
                    $usagetable .= $overviewname . " (" . count($uids) . " " . get_string('staff', 'report_myfeedback') .")";
                } else {
                    // If it is an overview get the course/category name and display a link if it's a subcategory.
                    if ($overviewlink != "" && $printheader == false) {
                        $usagetable .= "<a href=\"" . $CFG->wwwroot . $overviewlink . "\">" . $overviewname .  "</a>";
                    } else {
                        if ($printheader == true) {
                            // Show an arrow if there are items underneath (sub categories).
                            $usagetable .= "<span class=\"assess-br modangle\">&#9660;</span>&nbsp;";
                        }
                        $usagetable .= $overviewname;
                    }
                    // Else if it is an overview make a new column and print just the number there and link it if there's a link.
                    $usagetable .= "</td><td>";
                    $usagetable .= "<a href=\"" . $CFG->wwwroot . str_replace("overview", "", $overviewlink) . "\">"
                        .count($uids)."</a>";

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
                if ($overallstats['lastaccess'] > 0) {
                    $usagetable .= "<td>" . date('d-m-Y H:i', $overallstats['lastaccess']) . "</td>";
                } else {
                    $usagetable .= "<td>&nbsp;</td>";
                }
                $usagetable .= "<td>" . $overallstats['ptutees'] . "</td>";
                $usagetable .= "</tr>";

                // Store the excel export data
                // indicate the parent category in the excel export (there's no arrow to show this like the on screen version).
                if ($printheader == true) {
                    $exceltable[$i]['name'] = $overviewname . " (" . get_string('parent', 'report_myfeedback') . ")";
                } else {
                    $exceltable[$i]['name'] = $overviewname;
                }
                if ($overview == true) {
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
                if ($overallstats['lastaccess'] > 0) {
                    $exceltable[$i]['lastaccess'] = date('d-m-Y H:i', $overallstats['lastaccess']);
                } else {
                    $exceltable[$i]['lastaccess'] = "";
                }
                $exceltable[$i]['ptutees'] = $overallstats['ptutees'];
            }
            // Then print the staff usage stats, if it's not an overview.
            if ($overview == false) {
                // Swap the tds for trs and remove the end thead tag, as the first row is a summary.
                $usagetable = str_replace("</thead>", "", $usagetable);
                $usagetable = str_replace("<tbody>", "", $usagetable);
                $usagetable = str_replace("<td", "<th", $usagetable);
                $usagetable = str_replace("</td>", "</th>", $usagetable);
                $usagetable .= '</thead></tbody>';
                $usagetable .= $staffusagetable;
                $usagetable .= '</tbody></table>';
            } else {
                if ($printheader == true) {
                    // Swap the tds for trs and remove the end thead tag, as the first row is a summary.
                    $usagetable = str_replace("</thead>", "", $usagetable);
                    $usagetable = str_replace("<tbody>", "", $usagetable);
                    $usagetable = str_replace("<td", "<th", $usagetable);
                    $usagetable = str_replace("</td>", "</th>", $usagetable);
                    $usagetable .= '</thead></tbody>';
                }
            }
        } else if (count($uids) == 0) {
            // If there are no users in the category show the category name and that there are 0 staff.
            if ($overview == false) {
                $usagetable .= "<tr><td>".$overviewname." (0 ".lcfirst (get_string('staff', 'report_myfeedback')).")</td>";
            } else {
                $usagetable .= "<tr><td>".$overviewname."</td><td>0</td>";
                $exceltable[$i]['name'] = $overviewname;
                $exceltable[$i]['staff'] = 0;
            }
            $headercount = 10;
            if ($showptutees == true) {
                $headercount++;
            }
            for ($i = 0; $i < $headercount; $i++) {
                $usagetable .= "<td>&nbsp;</td>";
            }
            $usagetable .= "</tr>";
        }
        // Set the excel table export data.
        $_SESSION['exp_sess'] = $exceltable;
        return $usagetable;
    }

    /**
     * Returns a table with the usage statistics for the students passed to the function.
     *
     * @param array $uids The array of students user ids.
     * @param string $reporttype
     * @param bool $overview Whether or not to display just an overview with aggregated data only and no user
     *                       data or closing table tags.
     * @param string $overviewname The name of the overview (e.g. category or course).
     * @param string $overviewlink The link for the overview name (so users can navigate to subcategories).
     * @param bool $printheader Whether to print the headers or not, so overviews can show aggregated data for
     *                          subcategories in a single table.
     * @return string Table containing the student statistics (missing the </tbody></table> tag if an overview).
     * @throws \coding_exception
     */
    public function get_student_statistics_table($uids, $reporttype, $overview=false, $overviewname = "",
                                                 $overviewlink = "", $printheader=true): string {
        global $CFG;
        $sesskeyqs = '&sesskey=' . sesskey();

        $i = 0;
        $exceltable = [];

        // Populate the $exceltable array with the previous data, if it's not the first row of an overview.
        if ($overview == true && $printheader == false) {
            $exceltable = $_SESSION["exp_sess"];
            $i = count($exceltable);
        }
        $usagetable = '';
        if ($printheader == true) {
            $usagetable =
                "<table id=\"usagetable\" class=\"table table-striped table-bordered table-hover\" style=\"text-align:center\">";
            // Print the table headings.
            $headers = array(get_string('tutortblheader_name', 'report_myfeedback'));
            $headerhelptext = array(get_string('usagetblheader_name_info', 'report_myfeedback'));

            if ($overview == true) {
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
            // Print the table data.
            $usagetable .= "<tbody>";
        }
        // Get the table data.
        if (count($uids) > 0) {
            // Get all the student stats.
            $allstudentsstats = $this->get_overall_student_usage_statistics($uids);

            // Print stats for each student.
            // TODO: make the drop down button toggle to show/hide student data for the category.
            // Currently it shows and can't be toggled off.
            $studentusagetable = "";
            $overallstats = [];
            $overallstats['totalviews'] = 0;
            $overallstats['notes'] = 0;
            $overallstats['feedback'] = 0;
            $overallstats['downloads'] = 0;
            $overallstats['lastaccess'] = 0;
            $overallstats['viewedby'] = 0;
            $personaltutors = [];

            foreach ($allstudentsstats as $onestudentstats) {
                $studentusagetable .= "<tr>";
                if (count($uids) > 1) {
                    $studentusagetable .= "<td>" . "<a href=\"" . $CFG->wwwroot
                        . "/report/myfeedback/index.php?currenttab=usage&reporttype=student&reportuserid="
                        . $onestudentstats['userid']
                        . $sesskeyqs
                        . "\" title=\"" . ucfirst(get_string('view', 'report_myfeedback')) . " "
                        . $onestudentstats['name'] . get_string('apostrophe_s', 'report_myfeedback') . " "
                        .  get_string('usagereport', 'report_myfeedback') . "\" rel=\"tooltip\">"
                        . $onestudentstats['name']."</a></td>";
                } else {
                    $studentusagetable .= "<td>" . "<a href=\"" . $CFG->wwwroot
                        . "/report/myfeedback/index.php?userid=" . $onestudentstats['userid']
                        . $sesskeyqs
                        . "\" title=\"" . ucfirst(get_string('view', 'report_myfeedback')) . " "
                        .  $onestudentstats['name'] . get_string('apostrophe_s', 'report_myfeedback') . " "
                        . get_string('dashboard', 'report_myfeedback') . "\" rel=\"tooltip\">"
                        . $onestudentstats['name']."</a></td>";
                }
                // TODO: get the number of staff who have viewed the report for that student and display it under
                // viewed by followed by " staff".
                $studentusagetable .= "<td>";
                $studentusagetable .= ($onestudentstats['totalviews'] > 0) ? "yes" : "&nbsp;";
                $studentusagetable .= "</td>";
                $studentusagetable .= "<td>" . $onestudentstats['totalviews'] . "</td>";
                $studentusagetable .= "<td>" . $onestudentstats['notes'] . "</td>";
                $studentusagetable .= "<td>" . $onestudentstats['feedback'] . "</td>";
                $studentusagetable .= "<td>" . $onestudentstats['downloads'] . "</td>";
                $studentusagetable .= "<td>";

                // Only print the excel data if it's not an overview.
                if ($overview == false) {
                    // Store the excel export data.
                    $exceltable[$i]['name'] = $onestudentstats['name'];
                    $exceltable[$i]['viewed'] = ($onestudentstats['totalviews'] > 0) ? "yes" : "";
                    $exceltable[$i]['views'] = $onestudentstats['totalviews'];
                    $exceltable[$i]['notes'] = $onestudentstats['notes'];
                    $exceltable[$i]['feedback'] = $onestudentstats['feedback'];
                    $exceltable[$i]['downloads'] = $onestudentstats['downloads'];
                }

                // Link to the personal tutor usage report.
                if ($onestudentstats['personaltutorid'] > 0) {
                    $studentusagetable .= "<a href=\"" . $CFG->wwwroot . "/report/myfeedback/index.php"
                        . "?reportuserid=" . $onestudentstats['personaltutorid']
                        . $sesskeyqs
                        . "&currenttab=usage&reporttype=staffmember\" title=\""
                        . ucfirst(get_string('view', 'report_myfeedback')) . " "
                        . trim($onestudentstats['personaltutor'])
                        . get_string('apostrophe_s', 'report_myfeedback') . " "
                        . get_string('usagereport', 'report_myfeedback') . "\" rel=\"tooltip\">"
                        . $onestudentstats['personaltutor'] . "</a>";
                    // Only print the excel data if it's not an overview.
                    if ($overview == false) {
                        $exceltable[$i]['personaltutor'] = $onestudentstats['personaltutor'];
                    }
                    $personaltutors[$onestudentstats['personaltutorid']] = $onestudentstats['personaltutor'];
                } else {
                    $studentusagetable .= "&nbsp;";
                    // Only print the excel data if it's not an overview.
                    if ($overview == false) {
                        $exceltable[$i]['personaltutor'] = "";
                    }
                }
                $studentusagetable .= "</td>";
                $studentusagetable .= "<td>";
                // Check if the user has ever accessed the report. If not lastaccess will be 0.
                if ($onestudentstats['lastaccess'] > 0) {
                    $studentusagetable .= date('d-m-Y H:i', $onestudentstats['lastaccess']);
                    // Only print the excel data if it's not an overview.
                    if ($overview == false) {
                        $exceltable[$i]['lastaccess'] = date('d-m-Y H:i', $onestudentstats['lastaccess']);
                    }
                } else {
                    $studentusagetable .= "-";
                    // Only print the excel data if it's not an overview.
                    if ($overview == false) {
                        $exceltable[$i]['lastaccess'] = "";
                    }
                }
                $studentusagetable .= "</td>";

                $studentusagetable .= "</tr>";

                // Add this students stats to the overall stats.
                if ($onestudentstats['totalviews'] > 0) {
                    $overallstats['viewedby'] += 1;
                }
                $overallstats['totalviews'] += $onestudentstats['totalviews'];
                $overallstats['notes'] += $onestudentstats['notes'];
                $overallstats['feedback'] += $onestudentstats['feedback'];
                $overallstats['downloads'] += $onestudentstats['downloads'];
                if ($onestudentstats['lastaccess'] > $overallstats['lastaccess']) {
                    $overallstats['lastaccess'] = $onestudentstats['lastaccess'];
                }
                // Only iterate i if it's not an overview - i.e. the results will be included in the export file.
                if ($overview == false) {
                    $i++;
                }
            }

            // First print overall category stats.
            if (count($uids) > 1) {
                $usagetable .= '<tr';
                // Apply a darker sub-heading style to the overall category stats.
                if ($overview == false) {
                    $usagetable .= 'class="highlight"';
                }
                $usagetable .= '>';
                // Get the course / category name.
                $usagetable .= '<td id="long-name">';
                // If it's not an overview, then print the count in brackets in the same column.
                if ($overview == false) {
                    // Show an arrow if there are items underneath (students).
                    $usagetable .= "<span class=\"assess-br modangle\">&#9660;</span>&nbsp;";
                    if ($reporttype == "personaltutorstudents") {
                        $usagetable .= $overviewname . " (" . count($uids) . " " .
                            strtolower(get_string('personaltutees', 'report_myfeedback')) .")";
                    } else {
                        $usagetable .= $overviewname . " (" . count($uids) . " " .
                            lcfirst(get_string('dashboard_students', 'report_myfeedback')) .")";
                    }
                } else {
                    // Get the course/category name and display a link if it's a subcategory.
                    if ($overviewlink != "" && $printheader == false) {
                        $usagetable .= "<a href=\"" . $CFG->wwwroot . $overviewlink . "\">" . $overviewname .  "</a>";
                    } else {
                        // Show an arrow if there are items underneath (sub categories).
                        if ($printheader == true) {
                            // Show an arrow if there are items underneath (sub categories).
                            $usagetable .= "<span class=\"assess-br modangle\">&#9660;</span>&nbsp;";
                        }
                        $usagetable .= $overviewname;
                    }
                    // Else if it is an overview make a new column and print just the number there and link it if there's a link.
                    $usagetable .= "</td><td>";
                    $usagetable .= "<a href=\"" . $CFG->wwwroot . str_replace("overview", "", $overviewlink) . "\">"
                        . count($uids) . "</a>";
                }
                $usagetable .= "</td>";
                $usagetable .= "<td>";
                $usagetable .= (count($uids) > 1) ? $overallstats['viewedby'] : "&nbsp;";
                $usagetable .= "</td>";
                $usagetable .= "<td>" . $overallstats['totalviews'] . "</td>";
                $usagetable .= "<td>" . $overallstats['notes'] . "</td>";
                $usagetable .= "<td>" . $overallstats['feedback'] . "</td>";
                $usagetable .= "<td>" . $overallstats['downloads'] . "</td>";
                $usagetable .= "<td>".count($personaltutors)."</td>";
                if ($overallstats['lastaccess'] > 0) {
                    $usagetable .= "<td>".date('d-m-Y H:i', $overallstats['lastaccess'])."</td>";
                } else {
                    $usagetable .= "<td>&nbsp;</td>";
                }
                $usagetable .= "</tr>";

                // Store the overview excel export data
                // indicate the parent category in the excel export (there's no arrow to show this like the on screen version).
                if ($printheader == true) {
                    if ($reporttype == "personaltutorstudents") {
                        $exceltable[$i]['name'] = $overviewname . " (" . get_string('tabs_ptutor', 'report_myfeedback') . ")";
                    } else {
                        $exceltable[$i]['name'] = $overviewname . " (" . get_string('parent', 'report_myfeedback') . ")";
                    }
                } else {
                    $exceltable[$i]['name'] = $overviewname;
                }
                if ($overview == true) {
                    $exceltable[$i]['students'] = count($uids);
                }
                $exceltable[$i]['viewed'] = (count($uids) > 1) ? $overallstats['viewedby'] : "";
                $exceltable[$i]['views'] = $overallstats['totalviews'];
                $exceltable[$i]['notes'] = $overallstats['notes'];
                $exceltable[$i]['feedback'] = $overallstats['feedback'];
                $exceltable[$i]['downloads'] = $overallstats['downloads'];
                $exceltable[$i]['personaltutors'] = count($personaltutors);
                if ($overallstats['lastaccess'] > 0) {
                    $exceltable[$i]['lastaccess'] = date('d-m-Y H:i', $overallstats['lastaccess']);
                } else {
                    $exceltable[$i]['lastaccess'] = "";
                }
            }
            // Then print the student usage stats, if it's not an overview.
            if ($overview == false) {
                // Swap the tds for trs and remove the end thead tag, as the first row is a summary.
                $usagetable = str_replace("</thead>", "", $usagetable);
                $usagetable = str_replace("<tbody>", "", $usagetable);
                $usagetable = str_replace("<td", "<th", $usagetable);
                $usagetable = str_replace("</td>", "</th>", $usagetable);
                $usagetable .= '</thead></tbody>';
                $usagetable .= $studentusagetable;
                $usagetable .= '</tbody></table>';
            } else {
                if ($printheader == true) {
                    // Swap the tds for trs and remove the end thead tag, as the first row is a summary.
                    $usagetable = str_replace("</thead>", "", $usagetable);
                    $usagetable = str_replace("<tbody>", "", $usagetable);
                    $usagetable = str_replace("<td", "<th", $usagetable);
                    $usagetable = str_replace("</td>", "</th>", $usagetable);
                    $usagetable .= '</thead></tbody>';
                }
            }

        } else if (count($uids) == 0) {
            // If there are no users in the category show the category name and that there are 0 students.
            if ($overview == false) {
                $usagetable .= "<tr><td>"
                    . $overviewname . " (0 ".lcfirst(get_string('dashboard_students', 'report_myfeedback')).")</td>";
            } else {
                $usagetable .= "<tr><td>".$overviewname."</td><td>0</td>";
                $exceltable[$i]['name'] = $overviewname;
                $exceltable[$i]['students'] = 0;
            }
            $headercount = 7;
            for ($i = 0; $i < $headercount; $i++) {
                $usagetable .= "<td>&nbsp;</td>";
            }
            $usagetable .= "</tr>";
        }
        // Set the excel table export data.
        $_SESSION['exp_sess'] = $exceltable;
        return $usagetable;
    }

    /**
     * Return the array of My feedback usage logs.
     *
     * @param int $userid
     * @return array
     */
    public function get_user_usage_logs($userid): array {
        global $currentdb;

        return $currentdb->get_records('logstore_standard_log', ['component' => 'report_myfeedback', 'userid' => $userid]);
    }

    /**
     * Return the array of My feedback usage logs
     *
     * @param int $userid
     * @return array
     */
    public function get_notes_and_feedback($userid): array {
        global $currentdb;

        return $currentdb->get_records('report_myfeedback', ['userid' => $userid]);
    }

    /**
     * Returns the graph with the z-score with the lowest, highest and median grade
     * also the number who got between 0-39, 40-49, 50-59, 60-69 and 70-100 %
     *
     * @param int $cid The course id
     * @param array $uids The array of user ids
     * @param bool $pmod Whether this graph is is for mod or dept dashboards
     * @return stdClass The bar graph depicting the z-score
     */
    public function get_module_z_score($cid, $uids, $pmod = false): stdClass {
        $users = [];
        $assess = [];
        $userassess = [];
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
            $eachcsegrade = $this->get_eachcourse_dashboard_grades($uid, $cid, $mod = 'Y');
            $eachcsesub = $this->get_eachcourse_dashboard_submissions($uid, $cid, $mod = 'Y');

            foreach ($eachcsesub as $sid => $sub) {
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
            foreach ($eachcsegrade as $gid => $gr) {
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

        $graphtotals = [];
        $ct = 1;

        $due = $nonsub = $graded = $latesub = $latefeedback = $lowgrades = 0;

        foreach ($users as $u) {
            if ($u['count']) {
                $ct = $u['count'];
            }
            $graphtotals[] = $u['score'] / $ct;
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

        $mainassess = $this->get_assessment_analytics(
            $assess,
            $uidnum,
            'assRec',
            '',
            '<p style="margin-top:10px"><span class="assess-br">' . get_string('studentbreakdown', 'report_myfeedback') .
                '</span><br><span class="assess-br modangle">&#9660;</span></p>',
            $users,
            $pmod
        );
        $mainuser = $this->get_user_analytics($users, $cid, ($pmod ? $stmod : $st), 'display:none');
        $users = $mainuser->newusers;

        $result = new stdClass();
        $result->graph = '';
        $maingraph = $this->get_module_graph($cid, $graphtotals, $uidnum);

        if ($pmod) {
            if (!$due && !$graded) {
                $result->graph .= "<tr><td></td>";
                $result->graph .= "<td></td>";
                $result->graph .= "<td></td>";
                $result->graph .= "<td></td>";
                $result->graph .= "<td></td>";
                $result->graph .= "<td></td>";
                $result->graph .= "<td></td></tr>";
            } else {
                $result->graph .= "<tr class='recordRow' ><td>" . $mainassess->aname . $mainuser->uname . "</td>";
                $result->graph .= "<td class='overallgrade'>" . $mainassess->a_vas . $mainuser->u_vas . "</td>";
                $result->graph .= "<td>" . $mainassess->ad . $mainuser->ud . "</td>";
                $result->graph .= "<td>" . $mainassess->an . $mainuser->un . "</td>";
                $result->graph .= "<td>" . $mainassess->al . $mainuser->ul . "</td>";
                $result->graph .= "<td>" . $mainassess->ag . $mainuser->ug . "</td>";
                $result->graph .= "<td>" . $mainassess->alo . $mainuser->ulo . "</td></tr>";
            }
        }
        $result->modgraph = $maingraph;
        $result->due = $due;
        $result->nonsub = $nonsub;
        $result->latesub = $latesub;
        $result->graded = $graded;
        $result->lowgrades = $lowgrades;
        $result->users = $users;
        $result->assess = $assess;
        $result->totals = $graphtotals;

        return $result;
    }

    /**
     * Get table for progadmin page.
     *
     * @param object $ptutors
     * @param int $ptutorid
     * @param object $modtut
     * @param object $tutgroup
     * @param object $currentmod
     * @return string
     * @throws \coding_exception
     */
    public function get_progadmin_ptutor($ptutors, $ptutorid, $modtut = null, $tutgroup = null, $currentmod = null) {

        $myusers = [];
        if ($modtut) {
            if ($csectexts = context::instance_by_id($modtut, IGNORE_MISSING)) {
                $ptutors = get_users_by_capability(
                    $csectexts,
                    'report/myfeedback:modtutor',
                    'u.id, u.firstname, u.lastname, u.email'
                );
                foreach ($ptutors as $u) {
                    $myusers[$u->id][0] = $u->firstname . " " . $u->lastname;
                    $myusers[$u->id][1] = $u->email;
                    $myusers[$u->id][2] = ($modtut ? $this->get_tutees_for_prog_tutor_groups($u->id, $currentmod, $tutgroup)
                        : $this->get_tutees_for_prog_ptutors($u->id));
                    $myusers[$u->id][3] = $u->id;
                }
            }
        }

        $tutor = ($modtut ? '<th>' . get_string('moduletutors', 'report_myfeedback') . '</th><th>'
            . get_string('tutorgroups', 'report_myfeedback') . '</th><th></a>
            <input title="'. get_string('selectallforemail', 'report_myfeedback') .
                '" rel="tooltip" type="checkbox" id="selectall1"
            />' . get_string('selectall', 'report_myfeedback') .
            ' <a class="btn" id="mail1"> ' . get_string('sendmail', 'report_myfeedback') . '</th>'
                : '<th>' . get_string('personaltutors', 'report_myfeedback') . '</th>
                            <th colspan="4">' . get_string('tutees_plus_minus', 'report_myfeedback') . '</th>');

        $usertable = '<form method="POST" id="emailform1" action="">'
            . '<input type="hidden" name="sesskey" value="' . sesskey() . '" />'
            . '<table id="ptutor" class="ptutor" width="100%" style="display:none" border="1"><thead><tr class="tableheader">'
            . $tutor . '</tr></thead><tbody>';

        foreach ($myusers as $result) {
            $usertable .= "<tr>";
            $usertable .= "<td>" . $result[0] . "</td>";
            $usertable .= "<td class='maintable'><u class='hidetable'>" . get_string('show') . "</u><br>" . $result[2] . "</td>";
            $usertable .= "<td><input class=\"chk2\" type=\"checkbox\" name=\"email" . $result[3] . "\" value=\"" .
                $result[1] . "\"> " . $result[1] . "</td>";
            $usertable .= "</tr>";
        }

        $usertable .= "</tbody></table></form>";
        return $usertable;
    }

    /**
     * Return the position the user's grade falls into for the bar graph
     *
     * @param int $itemid Grade item id
     * @param int $grade The grade
     * @return string  The relative graph with the sections that should be shaded
     */
    public function get_activity_min_and_max_grade($itemid, $grade): string {
        global $currentdb;
        $sql = "SELECT min(finalgrade) as min, max(finalgrade) as max FROM {grade_grades}
                WHERE itemid = ?";
        $params = array($itemid);
        $activity = $currentdb->get_record_sql($sql, $params);
        $loc = 100;
        if ($activity) {
            $res = (int) $activity->max - (int) $activity->min;
            if ($res > 0) {
                $pres = $res / 5;
                $pos1 = $activity->min + $pres;
                $pos2 = $activity->min + $pres * 2;
                $pos3 = $activity->min + $pres * 3;
                $pos4 = $activity->min + $pres * 4;
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
     * Get the self-reflective notes for each grade item added by the user
     *
     * @param int $userid The user id who the notes relate to
     * @param int $gradeitemid The id of the specific grade item
     * @param object $instn
     * @return string The self-reflective notes
     */
    public function get_notes($userid, $gradeitemid, $instn): string {
        global $currentdb;

        $sql = "SELECT DISTINCT notes
                 FROM {report_myfeedback}
                 WHERE userid=? AND gradeitemid=? AND iteminstance=?";
        $params = array($userid, $gradeitemid, $instn);
        $usernotes = $currentdb->get_record_sql($sql, $params);
        $displaynotes = '';
        if ($usernotes) {
            $displaynotes = $usernotes->notes;
        }
        return $displaynotes;
    }

    /**
     * get the non-Moodle feedback for each grade item added by the user
     *
     * @param int $userid The user id who the notes relate to
     * @param int $gradeitemid The id of the specific grade item
     * @param object $inst
     * @return string The non-Moodle feedback
     */
    public function get_turnitin_feedback($userid, $gradeitemid, $inst): string {
        global $currentdb;

        $sql = "SELECT DISTINCT modifierid, feedback
                 FROM {report_myfeedback}
                 WHERE userid=? AND gradeitemid=? AND iteminstance=?";
        $params = array($userid, $gradeitemid, $inst);
        $turnitinfeedback = $currentdb->get_record_sql($sql, $params);
        if ($turnitinfeedback) {
            return $turnitinfeedback;
        }
        return "";
    }

    /**
     * Get the mount of archived years set in the report settings and return the
     * years (1415) as an array including the current year ('current')
     *
     * @return array mixed The archived years
     */
    public function get_archived_dbs(): array {
        $dbs = get_config('report_myfeedback');
        $acyears = array('current');
        $archivedyears = 0;
        if (isset($dbs->archivedyears)) {
            if ($dbs->archivedyears) {
                $archivedyears = $dbs->archivedyears;
            }
        }
        if (isset($dbs->dbhostarchive)) {
            if ($dbs->dbhostarchive) {
                for ($i = 1; $i <= $archivedyears; $i++) {
                    $acyears[] = $this->get_academic_years($i);
                }
            }
        }
        return $acyears;
    }

    /**
     * Get the number of archived years set in the report settings and return the
     * years (1415) as an array including the current year ('current')
     *
     * @return array mixed The archived years
     */
    public function get_archived_years(): array {
        $dbs = get_config('report_myfeedback');
        $acyears = array('current');
        $archivedyears = 0;
        if (isset($dbs->archivedyears)) {
            if ($dbs->archivedyears) {
                $archivedyears = $dbs->archivedyears;
            }
        }
        for ($i = 1; $i <= $archivedyears; $i++) {
            $acyears[] = $this->get_academic_years($i);
        }
        return $acyears;
    }

    /**
     * Return the course limit on Dept admin dashboard so that if the second level category they are trying
     * to view has more than this amount of courses it asks the user to select individual courses instead as there would be
     * too many courses on that evel to run the stats. This is set in settings or default to 200
     *
     * @return int The limit for the number of courses
     */
    public function get_course_limit(): int {
        $cl = get_config('report_myfeedback');
        return (isset($cl->courselimit) && $cl->courselimit ? $cl->courselimit : 200);
    }

    /**
     * Return the academic years based on the current month
     *
     * @param int $acyear How many years from today's date
     * @return string The academic year in the form of 1415, 1314
     */
    public function get_academic_years($acyear = null): string {
        $month = date("m");
        $year = date("Y-m-d", strtotime("- {$acyear} Years"));
        $academicyear = date("y", strtotime($year)) . date("y", strtotime($year . "+ 1 Years"));
        if ($month < 9) {
            $academicyear = date("y", strtotime($year . "- 1 Years")) . date("y", strtotime($year));
        }
        return $academicyear;
    }

    /**
     * Return the personal tutees and their stats
     *
     * @param int $personaltutorid
     * @return array Return the name and other details of the personal tutees
     * @throws \coding_exception
     */
    public function get_dashboard_tutees($personaltutorid = 0): array {
        global $currentdb, $CFG, $USER, $OUTPUT;
        $sesskeyqs = '&sesskey=' . sesskey();

        if ($personaltutorid == 0) {
            $personaltutorid = $USER->id;
        }
        $myusers = [];
        // Get all the mentees, i.e. users you have a direct assignment to as their personal tutor.
        $sql = "SELECT c.instanceid, c.instanceid, u.id as id, firstname, lastname, email, department
                  FROM {role_assignments} ra, {context} c, {user} u
                 WHERE ra.userid = ?
                   AND ra.contextid = c.id
                   AND c.instanceid = u.id
                   AND c.contextlevel = " . CONTEXT_USER;
        if ($usercontexts = $currentdb->get_records_sql($sql, [$personaltutorid])) {
            foreach ($usercontexts as $u) {
                $user = $currentdb->get_record('user', array('id' => $u->id, 'deleted' => 0));
                $year = null;
                profile_load_data($user);

                // Adding check for field existence to avoid errors being thrown in UI.
                if (isset($user->profile_field_courseyear)) {
                    $year = $user->profile_field_courseyear;
                }
                $myusers[$u->id][0] = "<div><div style=\"float:left;margin-right:5px;\">" .
                    $OUTPUT->user_picture($user, array('size' => 40)) . "</div><div style=\"float:left;\"><a href=\""
                    . $CFG->wwwroot . "/report/myfeedback/index.php?userid=" . $u->id
                    . $sesskeyqs
                    . "\">" . $u->firstname . " " . $u->lastname . "</a><br>" .
                    ($year ? ' ' . get_string('year', 'report_myfeedback') . $year . ', ' : '') . $u->department . "</div></div>";
                $myusers[$u->id][1] = $u->firstname . '+' . $u->lastname; // What we want to sort by (without spaces).
                $myusers[$u->id][2] = $u->email;
                $myusers[$u->id][3] = $u->firstname . ' ' . $u->lastname; // Display name with spaces.
                $myusers[$u->id][4] = $u->firstname; // Display name with spaces.
                $myusers[$u->id][5] = $u->lastname; // Display name with spaces.
            }
        }
        return $myusers;
    }

    /**
     * Get the number of graded assessments and low grades of the tutees
     *
     * @param int $userid The id of the user who's details are being retrieved
     * @param int $courseid The id of the course
     * @param object $modtutor
     * @return array|int[] Retun the int value fo the graded assessments and low grades.
     * @throws moodle_exception
     */
    public function get_eachcourse_dashboard_grades($userid, $courseid, $modtutor = null) {
        global $currentdb;
        $checkdb = 'current';
        $archive = false;
        if (isset($_SESSION['viewyear'])) {
            $checkdb = $_SESSION['viewyear']; // Check if previous academic year.
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
        list($itemsql, $params) = $currentdb->get_in_or_equal($items, SQL_PARAMS_NAMED);
        $sql = "SELECT DISTINCT c.id AS cid, gg.id as tid, finalgrade, gg.timemodified as feed_date, gi.id as gid, grademax,
                    cm.id AS cmid
                FROM {course} c
                JOIN {grade_items} gi ON c.id = gi.courseid AND (gi.hidden != 1 AND gi.hidden < :now1)
                JOIN {grade_grades} gg ON gi.id = gg.itemid AND gg.userid = :userid AND (gi.hidden != 1 AND gi.hidden < :now2)
                AND (gg.hidden != 1 AND gg.hidden < :now3) AND gi.courseid = :courseid1
                AND gg.finalgrade IS NOT NULL AND (gi.itemmodule $itemsql OR (gi.itemtype = 'manual'))
                LEFT JOIN {course_modules} cm ON gi.iteminstance = cm.instance AND c.id = cm.course AND cm.course = :courseid2
                LEFT JOIN {context} con ON cm.id = con.instanceid AND con.contextlevel=70
                LEFT JOIN {modules} m ON cm.module = m.id AND gi.itemmodule = m.name
                WHERE c.visible=1 AND c.showgrades = 1";
        $params['now1'] = $now;
        $params['now2'] = $now;
        $params['now3'] = $now;
        $params['userid'] = $userid;
        $params['courseid1'] = $courseid;
        $params['courseid2'] = $courseid;
        $gr = $currentdb->get_recordset_sql($sql, $params);
        $grades = [];
        if ($gr->valid()) {
            foreach ($gr as $rec) {
                // Here we check id sections have any form of restrictions.
                // Also check instance of Moodle as only from 1516 this funtions below work.
                $show = 1;
                if (!$archive || ($archive && $checkdb > 1415)) {
                    if ($rec->cid) {
                        $rec->id = $rec->cid;
                        $modinfo = get_fast_modinfo($rec, $userid);
                        if ($rec->cmid >= 1) {
                            $cm = $modinfo->get_cm($rec->cmid);
                            if (!$cm->uservisible) {
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
        $modresult = [];
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
            $modresult[$grade->gid]['score'][$userid] =
                ($grade->grademax > 0 ? round($grade->finalgrade) / $grade->grademax * 100 : round($grade->finalgrade));
        }
        if ($modtutor) {
            return $modresult;
        } else {
            return $result;
        }
    }

    /**
     * Get all the due assessments, non-submissions and late submission
     *
     * from the assign mod type, quiz, workshop and turnitin v1 and v2
     *
     * @param int $userid The id of the user you getting the details for
     * @param int $courseid The id of the course
     * @param object $modtutor
     * @return array|int[] Return the int value for the assessment due, non and late submissions
     * @throws dml_exception
     * @throws moodle_exception
     *
     */
    public function get_eachcourse_dashboard_submissions($userid, $courseid, $modtutor = null) {
        global $currentdb;
        $checkdb = 'current';
        $archive = false;
        if (isset($_SESSION['viewyear'])) {
            $checkdb = $_SESSION['viewyear']; // Check if previous academic year.
        }
        if ($checkdb != 'current') {
            $archive = true;
        }
        $now = time();
        $sql = "SELECT DISTINCT c.id AS cid, gi.id as tid, gg.id, gg.timemodified as due, gg.timemodified as sub,
                    gi.itemtype as type, '-1' AS status, -1 AS nosubmissions, -1 AS cmid, gg.timemodified as feed_date
                 FROM {course} c
                 JOIN {grade_items} gi ON c.id=gi.courseid AND gi.itemtype = 'manual' AND (gi.hidden != 1 AND gi.hidden < ?)
                 JOIN {grade_grades} gg ON gi.id=gg.itemid AND gg.userid = ? AND gi.courseid = ?
                         AND (gg.hidden != 1 AND gg.hidden < ?)
                 WHERE c.visible=1 AND c.showgrades = 1 ";
        $params = array($now, $userid, $courseid, $now);
        if ($this->mod_is_available('assign')) {
            $sql .= "UNION SELECT DISTINCT c.id AS cid, gi.id as tid, a.id, a.duedate as due, su.timemodified as sub,
                    gi.itemmodule as type, su.status AS status, a.nosubmissions AS nosubmissions, cm.id AS cmid,
                    gg.timemodified as feed_date
                 FROM {course} c
                 JOIN {grade_items} gi ON c.id=gi.courseid
                 AND itemtype='mod' AND gi.itemmodule = 'assign' AND (gi.hidden != 1 AND gi.hidden < ?)
                 LEFT JOIN {grade_grades} gg ON gi.id=gg.itemid AND gg.userid = ?
                             AND (gg.hidden != 1 AND gg.hidden < ?)
                 JOIN {course_modules} cm ON gi.iteminstance=cm.instance AND c.id=cm.course AND cm.course = ?
                 JOIN {context} con ON cm.id = con.instanceid AND con.contextlevel=70
                 JOIN {modules} m ON cm.module = m.id AND gi.itemmodule = m.name AND gi.itemmodule = 'assign'
                 JOIN {assign} a ON gi.iteminstance=a.id AND a.course=gi.courseid AND gi.courseid = ?
                 LEFT JOIN {assign_submission} su ON a.id = su.assignment AND su.userid = ?
                 WHERE c.visible=1 AND c.showgrades = 1 ";
            array_push($params, $now, $userid, $now, $courseid, $courseid, $userid);
        }
        if ($this->mod_is_available('quiz')) {
            $sql .= "UNION SELECT DISTINCT c.id AS cid, gi.id as tid, q.id, q.timeclose as due, gg.timecreated as sub,
                    gi.itemmodule as type, '-1' AS status, -1 AS nosubmissions, cm.id AS cmid, gg.timemodified as feed_date
                 FROM {course} c
                 JOIN {grade_items} gi ON c.id=gi.courseid
                 AND itemtype='mod' AND gi.itemmodule='quiz' AND (gi.hidden != 1 AND gi.hidden < ?)
                 LEFT JOIN {grade_grades} gg ON gi.id = gg.itemid AND gg.userid = ?
                             AND (gg.hidden != 1 AND gg.hidden < ?)
                 JOIN {course_modules} cm ON gi.iteminstance=cm.instance AND c.id=cm.course AND cm.course = ?
                 JOIN {context} con ON cm.id = con.instanceid AND con.contextlevel=70
                 JOIN {modules} m ON cm.module = m.id AND gi.itemmodule = m.name AND gi.itemmodule = 'quiz'
                 JOIN {quiz} q ON q.id = gi.iteminstance AND q.course = gi.courseid AND gi.courseid = ?
                 WHERE c.visible=1 AND c.showgrades = 1 ";
            array_push($params, $now, $userid, $now, $courseid, $courseid);
        }
        if ($this->mod_is_available('workshop')) {
            $sql .= "UNION SELECT DISTINCT c.id AS cid, gi.id as tid, w.id, w.submissionend as due, ";
            if (!$archive || ($archive && $checkdb > 1112)) { // When the new timemodified field was added to workshop_submission.
                $sql .= "ws.timemodified AS sub, ";
            } else {
                $sql .= "ws.timecreated AS sub, ";
            }
            $sql .= "gi.itemmodule as type,
                    '-1' AS status, -1 AS nosubmissions, cm.id AS cmid, gg.timemodified as feed_date
                 FROM {course} c
                 JOIN {grade_items} gi ON c.id=gi.courseid
                 AND itemtype='mod' AND gi.itemmodule = 'workshop' AND (gi.hidden != 1 AND gi.hidden < ?)
                 LEFT JOIN {grade_grades} gg ON gi.id = gg.itemid AND gg.userid = ?
                             AND (gg.hidden != 1 AND gg.hidden < ?)
                 JOIN {course_modules} cm ON gi.iteminstance=cm.instance AND c.id=cm.course AND cm.course = ?
                 JOIN {context} con ON cm.id = con.instanceid AND con.contextlevel=70
                 JOIN {modules} m ON cm.module = m.id AND gi.itemmodule = m.name AND gi.itemmodule = 'workshop'
                 JOIN {workshop} w ON w.id = gi.iteminstance AND w.course = gi.courseid AND gi.courseid = ? ";
            if (!$archive || ($archive && $checkdb > 1112)) {// When the new timemodified field was added to workshop_submission.
                $sql .= "LEFT JOIN {workshop_submissions} ws ON w.id = ws.workshopid AND ws.example = 0 AND ws.authorid = ? ";
            } else {
                $sql .= "LEFT JOIN {workshop_submissions} ws ON w.id = ws.workshopid AND ws.example = 0 AND ws.userid = ? ";
            }
            array_push($params, $now, $userid, $now, $courseid, $courseid, $userid);
            $sql .= "WHERE c.visible=1 AND c.showgrades = 1 ";
        }
        if ($this->mod_is_available('turnitintool')) {
            $sql .= "UNION SELECT DISTINCT c.id AS cid, gi.id as tid, tp.id, tp.dtdue as due, ts.submission_modified as sub,
                    gi.itemmodule as type, '-1' AS status, -1 AS nosubmissions, cm.id AS cmid, gg.timemodified as feed_date
                 FROM {course} c
                 JOIN {grade_items} gi ON c.id=gi.courseid
                 AND itemtype='mod' AND gi.itemmodule = 'turnitintool' AND (gi.hidden != 1 AND gi.hidden < ?)
                 LEFT JOIN {grade_grades} gg ON gi.id = gg.itemid AND gg.userid = ?
                             AND (gg.hidden != 1 AND gg.hidden < ?)
                 JOIN {course_modules} cm ON gi.iteminstance = cm.instance AND c.id = cm.course AND cm.course = ?
                 JOIN {context} con ON cm.id = con.instanceid AND con.contextlevel=70
                 JOIN {modules} m ON cm.module = m.id AND gi.itemmodule = m.name AND gi.itemmodule = 'turnitintool'
                 JOIN {turnitintool} t ON t.id = gi.iteminstance AND t.course = gi.courseid AND gi.courseid = ?
                 LEFT JOIN {turnitintool_submissions} ts ON ts.turnitintoolid = t.id AND ts.userid = ?
                 LEFT JOIN {turnitintool_parts} tp ON tp.id = ts.submission_part
                 WHERE c.visible=1 AND c.showgrades = 1 AND tp.dtpost < ? ";
            array_push($params, $now, $userid, $now, $courseid, $courseid, $userid, $now);
        }
        if ($this->mod_is_available('turnitintooltwo')) {
            $sql .= "UNION SELECT DISTINCT c.id AS cid, gi.id as tid, tp.id, tp.dtdue as due, ts.submission_modified as sub,
                    gi.itemmodule as type, '-1' AS status, -1 AS nosubmissions, cm.id AS cmid, gg.timemodified as feed_date
                 FROM {course} c
                 JOIN {grade_items} gi ON c.id=gi.courseid
                 AND itemtype='mod' AND gi.itemmodule = 'turnitintooltwo' AND (gi.hidden != 1 AND gi.hidden < ?)
                 LEFT JOIN {grade_grades} gg ON gi.id = gg.itemid AND gg.userid = ?
                             AND (gg.hidden != 1 AND gg.hidden < ?)
                 JOIN {course_modules} cm ON gi.iteminstance = cm.instance AND c.id = cm.course AND cm.course = ?
                 JOIN {context} con ON cm.id = con.instanceid AND con.contextlevel=70
                 JOIN {modules} m ON cm.module = m.id AND gi.itemmodule = m.name AND gi.itemmodule = 'turnitintooltwo'
                 JOIN {turnitintooltwo} t ON t.id = gi.iteminstance AND t.course = gi.courseid AND gi.courseid = ?
                 AND gi.itemmodule = 'turnitintooltwo'
                 LEFT JOIN {turnitintooltwo_submissions} ts ON ts.turnitintooltwoid = t.id AND ts.userid = ?
                 LEFT JOIN {turnitintooltwo_parts} tp ON tp.id = ts.submission_part
                 WHERE c.visible=1 AND c.showgrades = 1 AND tp.dtpost < ? ";
            array_push($params, $now, $userid, $now, $courseid, $courseid, $userid, $now);
        }
        $r = $currentdb->get_recordset_sql($sql, $params);
        $all = [];
        if ($r->valid()) {
            foreach ($r as $rec) {//
                // Here we check id sections have any form of restrictions.
                $show = 1;
                if (!$archive || ($archive && $checkdb > 1415)) {
                    if ($rec->cid) {
                        $rec->id = $rec->cid;
                        $modinfo = get_fast_modinfo($rec, $userid);
                        if ($rec->cmid >= 1) {
                            $cm = $modinfo->get_cm($rec->cmid);
                            if (!$cm->uservisible) {
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
        $modresult = [];
        foreach ($all as $b) {
            $modresult[$b->tid]['due'] = 0;
            $modresult[$b->tid]['nosub'] = 0;
            $modresult[$b->tid]['late'] = 0;
            $modresult[$b->tid]['feed'] = 0;
        }
        foreach ($all as $a) {
            if ($a->due) {
                if ($a->type == 'assign') { // Check for extension.
                    if (!$archive || ($archive && $checkdb > 1314)) {
                        // When assign_user_flags came in.
                        $extend = $this->check_assign_extension($userid, $a->id);
                        if ($extend && $extend > $a->due) {
                            $a->due = $extend;
                        }
                    }
                }
                if ($a->type == 'quiz') {
                    // Check for extension.
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
            if (($a->due < $now) || ($a->due < 1 && $a->sub < $now)) {
                // Any duedate that's past or (no due date but submitted).
                $result[0] += 1;
                $modresult[$a->tid]['due'] += 1;
            }
            if ($a->nosubmissions != 1) {
                if (($a->due < $now && $a->sub < 1) || ($a->status == "new")) {
                    // No submission and due date past.
                    $result[1] += 1; // Can only be, if it is not an offline assignment.
                    $modresult[$a->tid]['nosub'] += 1; // Is a non-sub if assignment status is 'new'.
                }
            }
            if ($a->status != 'new') {
                if ($a->due && $a->sub > $a->due) {
                    // Late submission.
                    $result[2] += 1; // Can only be, if assignment status not 'new' which is added at override without submission.
                    $modresult[$a->tid]['late'] += 1; // This is in case it updates the submission status when overridden.
                }
            }
            $lt = get_config('report_myfeedback');
            $lte = isset($lt->latefeedback) ? $lt->latefeedback : 28;
            $latedays = 86400 * $lte; // 86400 is the number of seconds in a day.
            if ($a->status != 'new') {
                $dt = max($a->due, $a->sub);
                if ($a->feed_date && ($a->feed_date - $dt > $latedays)) {
                    $result[3] += 1; // Late feedback only if status not new.
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
     * @param int $userid The id of the tutees whos graph you are returning
     * @param array $tutor stats from mod tutor tab
     * @param string $coid An id for the canvas image
     * @param string $asid An id for the assessment table
     * @return stdClass|string The canvas images of the overall position and the module breakdown images
     * @throws \coding_exception
     */
    public function get_dashboard_zscore($userid, $tutor = null, $coid = null, $asid = null) {
        global $currentdb;
        $eachavg = [];
        $allcourses = [];
        $eachmodule = [];
        $eachuser = [];
        if (!$tutor) {
            // First get all active courses the user is enrolled on.
            if ($usermods = get_user_capability_course('report/myfeedback:student', $userid, false, 'shortname,visible')) {
                foreach ($usermods as $usermod) {
                    $uids = [];
                    if ($usermod->visible && $usermod->id) {
                        $allcourses[$usermod->id]['shortname'] = $usermod->shortname;
                        $eachcsegrade = $this->get_eachcourse_dashboard_grades($userid, $usermod->id);
                        $eachcsesub = $this->get_eachcourse_dashboard_submissions($userid, $usermod->id);
                        $allcourses[$usermod->id]['due'] = $eachcsesub[0];
                        $allcourses[$usermod->id]['non'] = $eachcsesub[1];
                        $allcourses[$usermod->id]['late'] = $eachcsesub[2];
                        $allcourses[$usermod->id]['feed'] = $eachcsesub[3];
                        $allcourses[$usermod->id]['graded'] = $eachcsegrade[0];
                        $allcourses[$usermod->id]['low'] = $eachcsegrade[1];
                    }
                }
            }
        }
        // If not called from p tutor then should only calculate min, avg and max of that assessment or course.
        $addavg = [];
        if ($tutor) {
            $eachuser = $tutor;
        }
        if (isset($eachuser[$userid][$asid]) && $tutor) {
            // Some users not from ptutor that needs it on departmental or module level.
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
            $posmin = round($min * 1.8) + 5;
            $posmax = round($max * 1.8) + 5;
            $posavg = round($avg * 1.8) + 5;
            $posmyavg = round($myavg * 1.78) + 1;
            $posmintext = $posmin - 7;
            $posavgtext = $posavg - 7;
            $posmaxtext = $posmax - 7;
            if (!$myavg) {
                $posmyavg = 0;
            }
            if ($posavgtext - $posmintext < 18) {
                $posavgtext = $posmintext + 18;
            }
            if ($posmaxtext - $posavgtext < 18) {
                $posmaxtext = $posavgtext + 18;
            }
            if ($posmintext < 1) {
                $posmintext = 1;
            }
            if ($min != 100 && $posmintext > 135) {
                $posmintext = 135;
            }
            if ($min == 100 && $posmintext > 125) {
                $posmintext = 125;
            }
            if ($posavgtext < 18) {
                $posavgtext = 18;
            }
            if ($avg != 100 && $posavgtext > 150) {
                $posavgtext = 150;
            }
            if ($avg == 100 && $posavgtext > 147) {
                $posavgtext = 147;
            }
            if ($posmaxtext < 32) {
                $posmaxtext = 32;
            }
            if ($posmaxtext > 168) {
                $posmaxtext = 168;
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
                $dashimage = '<div class="tutorCanvas"><canvas title="' . get_string('studentgraphdesc', 'report_myfeedback', $a)
                    . '" rel="tooltip" id="myCanvas' . $userid . $coid
                    . '" width="190" height="60" style="border:0px solid #d3d3d3;">'
                    . get_string('browsersupport', 'report_myfeedback') . '</canvas>
        <script>
        var c = document.getElementById("myCanvas' . $userid . $coid . '");
        var ctx = c.getContext("2d");
        var min = ' . $min . ';
        var max = ' . $max . ';
        var avg = ' . $avg . ';
        var myavg = ' . $myavg . ';
        var posmin = ' . $posmin . ';
        var posmax = ' . $posmax . ';
        var posavg = ' . $posavg . ';
        var posmintext = ' . $posmintext . ';
        var posmaxtext = ' . $posmaxtext . ';
        var posavgtext = ' . $posavgtext . ';
        var posmyavg = ' . $posmyavg . ';
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
            $min = $max = $avg = $myavg = $posavg = $posmax = $posmin = $posmyavg = 0;
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
        // Create the data for each module for the user.
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
        foreach ($allcourses as $grsub) {
            if (isset($grsub['due']) && isset($grsub['non']) && isset($grsub['late']) && isset($grsub['graded'])
                && isset($grsub['low']) && isset($grsub['feed'])) {

                $csesdue += $grsub['due'];
                $csesnon += $grsub['non'];
                $cseslate += $grsub['late'];
                $csesgraded += $grsub['graded'];
                $cseslow += $grsub['low'];
                $csesfeed += $grsub['feed'];
            }
        }

        foreach ($allcourses as $k => $cse) {
            $cdue = $cnon = $clate = $cgraded = $cfeed = $clow = 0;
            $cdue = $cse['due'];
            $cnon = $cse['non'];
            $clate = $cse['late'];
            $cgraded = $cse['graded'];
            $clow = $cse['low'];
            $cse1 = 1;
            $cse2 = 1;
            $cse3 = (isset($cse['shortname']) ? substr($cse['shortname'], 0, 20) : '-');
            $csemin = 1;
            $csemax = 1;
            $csemean = 1;
            $cseminpos = round($csemin * 1.8) + 5;
            $csemaxpos = round($csemax * 1.8) + 5;
            $csemeanpos = round($csemean * 1.8) + 5;
            $csemintext = $cseminpos - 7;
            $csemeantext = $csemeanpos - 7;
            $csemaxtext = $csemaxpos - 7;

            if ($csemeantext - $csemintext < 14) {
                $csemeantext = $csemintext + 14;
            }
            if ($csemaxtext - $csemeantext < 14) {
                $csemaxtext = $csemeantext + 14;
            }
            if ($csemintext < 1) {
                $csemintext = 1;
            }
            if ($csemintext > 138) {
                $csemintext = 138;
            }
            if ($csemeantext < 14) {
                $csemeantext = 14;
            }
            if ($csemeantext > 152) {
                $csemeantext = 152;
            }
            if ($csemaxtext < 28) {
                $csemaxtext = 28;
            }
            if ($csemaxtext > 166) {
                $csemaxtext = 166;
            }

            if ($cse1 && $cse2) {
                $uavg = round($cse1 / $cse2 * 100);
                $upos = round($uavg * 1.78) + 1;
                $col = '#8bc278';
                if ($uavg < 50) {
                    $col = '#d69859';
                }
                if ($uavg < 40) {
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
                $clo .= $minortable . "&nbsp;<br><span class=$scol3>" . $clow . "</span></td></tr></table>";
                $cnam .= $minortable . $cse3 . "</td></tr></table>";
                $cvas .= '<table style="display:none" class="accord" align="center"><tr><td>
                <canvas id="myCanvas1' . $userid . $k . '" width="190" height="51" style="border:0px solid #d3d3d3;">' .
                    get_string('browsersupport', 'report_myfeedback') . '</canvas>
                <script>
                var c1 = document.getElementById("myCanvas1' . $userid . $k . '");
                var ctx1 = c1.getContext("2d");
                var min1 = ' . $csemin . ';
                var max1 = ' . $csemax . ';
                var avg1 = ' . $csemean . ';
                var myavg1 = ' . $uavg . ';
                var posmin1 = ' . $cseminpos . ';
                var posmax1 = ' . $csemaxpos . ';
                var posavg1 = ' . $csemeanpos . ';
                var posmintext1 = ' . $csemintext . ';
                var posmaxtext1 = ' . $csemaxtext . ';
                var posavgtext1 = ' . $csemeantext . ';
                var posmyavg1 = ' . $upos . ';
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
            $cses->dash .= "<td></td>";
            $cses->dash .= "<td></td>";
            $cses->dash .= "<td></td>";
            $cses->dash .= "<td></td>";
            $cses->dash .= "<td></td>";
        } else {
            $cses->dash .= "<td><table class='tutor-inner' height=$height align='center'><tr><td class=>" . $br . $csesdue
                . "</td></tr></table>" . $cd . "</td>";
            $cses->dash .= $maintable . $br . "<span class=$scol1>" . $csesnon . "</span></td></tr></table>" . $cn . "</td>";
            $cses->dash .= $maintable . $br . "<span class=$scol2>" . $cseslate . "</span></td></tr></table>" . $cl . "</td>";
            $cses->dash .= $maintable . $br . $csesgraded . "</td></tr></table>" . $cg . "</td>";
            $cses->dash .= $maintable . $br . "<span class=$scol3>" . $cseslow . "</span></td></tr></table>" . $clo . "</td>";
        }
        $allcourses['due'] = $csesdue;
        $allcourses['non'] = $csesnon;
        $allcourses['late'] = $cseslate;
        $allcourses['graded'] = $csesgraded;
        $allcourses['low'] = $cseslow;
        $cses->all = $allcourses;
        $cses->names = $cnam;

        return $cses;
    }


    /**
     * Return the arry of dept admin categories and courses
     *
     * @param object $deptprog Arry with users dept courses user has progadmin capability in
     * @param object $frommod
     * @return array|string[]
     * @throws \coding_exception
     * @throws moodle_exception
     */
    public function get_prog_admin_dept_prog($deptprog, $frommod = null): array {
        $prog = [];
        $tomod = array('dept' => '', 'prog' => '');
        foreach ($deptprog as $dp) {
            $catid = ($dp->category ? $dp->category : 0);
            if ($catid) {
                // Replacing coursecat::get with core_course_category::get to avoid deprecation messages.
                // Use strictness so even hidden category names are shown without error.
                $cat = core_course_category::get($catid, MUST_EXIST, true);
                if ($cat) {
                    $path = explode("/", $cat->path);
                    // CATALYST CUSTOM WR362236: Replace deprecated coursecat with core_course_category.
                    $parent = ($cat->parent ? core_course_category::get($cat->parent, MUST_EXIST, true) : $cat);
                    // END CATALYST CUSTOM WR362236.
                    $progcat = [];
                    if ($cat->parent) {
                        $progcat = $cat;
                    } else {
                        $progcat = new stdClass();
                        $progcat->id = 1;
                        $progcat->name = get_string('uncategorized', 'report_myfeedback');
                    }
                    if (count($path) > 3) {
                        // CATALYST CUSTOM WR362236: Replace deprecated coursecat with core_course_category.
                        $parent = core_course_category::get($path[1], MUST_EXIST, true);
                        $progcat = core_course_category::get($path[2], MUST_EXIST, true);
                        // END CATALYST CUSTOM WR362236.
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
        // Sort the categories and courses in alphabetic order but case insentive.
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
        global $currentdb;
        $sql = "SELECT DISTINCT id, finalgrade, overridden
                FROM {grade_grades}
                WHERE itemid=? AND userid=?";
        $params = array($itemid, $userid);
        $overridden = $currentdb->get_record_sql($sql, $params);
        if ($overridden && $overridden->overridden > 0) {
            return $overridden->finalgrade;
        }
        return false;
    }

    /**
     * Return the display type for the course grade
     *
     * @param int $cid The course id
     * @return string The display type numeric value for letter, percentage, number etc.
     */
    public function get_course_grade_displaytype($cid): string {
        global $currentdb;
        $sql = "SELECT DISTINCT id, value
                FROM {grade_settings}
                WHERE courseid=? AND name='displaytype'";
        $param = array($cid);
        $displaytype = $currentdb->get_record_sql($sql, $param);
        return $displaytype ? $displaytype->value : '';
    }

    /**
     * Get data from database to populate the overview and feedback table
     *
     * @param bool $archive Whether it's an archive year
     * @param int $checkdb The academic year value eg. 1415
     * @return mixed The DB/remotedb results of submission and feedback for the user referred to in the url after
     *         userid=
     */
    public function get_data($archive, $checkdb) {
        global $currentdb, $USER;
        $userid = optional_param('userid', 0, PARAM_INT); // User id.
        if (empty($userid)) {
            $userid = $USER->id;
        }
        $this->content = new stdClass();
        $params = [];
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
        if (!$archive || ($archive && $checkdb > 1112)) {
            // When the new grading_areas table came in.
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
            if (!$archive || ($archive && $checkdb > 1112)) {
                // When the new grading_areas table came in.
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
                        LEFT JOIN {assign_grades} ag ON a.id = ag.assignment AND ag.userid = ? ";
            array_push($params, $now, $userid, $now, $userid);
            if (!$archive || ($archive && $checkdb > 1314)) {
                // When the new assign_user_flags table came in.
                $sql .= "LEFT JOIN {assign_user_flags} auf ON a.id = auf.assignment AND auf.workflowstate = 'released'
                        AND  (auf.userid = $userid OR a.markingworkflow = 0) ";
            }
            if (!$archive || ($archive && $checkdb > 1112)) {
                // When the new grading_areas table came in.
                $sql .= "LEFT JOIN {grading_areas} ga ON con.id = ga.contextid ";
            }
            $sql .= "LEFT JOIN {assign_submission} su ON a.id = su.assignment AND su.userid = ?
                        WHERE c.visible=1 AND c.showgrades = 1 AND cm.visible=1 ";
            array_push($params, $userid);
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
            if (!$archive || ($archive && $checkdb > 1112)) {
                // When the new reviewattempt field was addedto the quiz table.
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
            if (!$archive || ($archive && $checkdb > 1112)) {
                // When the new grading_areas table came in.
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
            if (!$archive || ($archive && $checkdb > 1112)) {
                // When the new grading_areas table came in.
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
            if (!$archive || ($archive && $checkdb > 1112)) {
                // When the new timemodified field was added to workshop_submission.
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
            if (!$archive || ($archive && $checkdb > 1112)) {
                // When the new grading_areas table came in.
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
            if (!$archive || ($archive && $checkdb > 1112)) {
                // When the new timemodified field was added to workshop_submission.
                $sql .= "LEFT JOIN {workshop_submissions} su ON a.id = su.workshopid AND su.example=0 AND su.authorid = ? ";
            } else {
                $sql .= "LEFT JOIN {workshop_submissions} su ON a.id = su.workshopid AND su.example=0 AND su.userid = ? ";
            }
            if (!$archive || ($archive && $checkdb > 1112)) {
                // When the new grading_areas table came in.
                $sql .= "LEFT JOIN {grading_areas} ga ON con.id = ga.contextid ";
            }
            $sql .= "WHERE c.visible=1 AND c.showgrades = 1 AND cm.visible=1 ";
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
            if (!$archive || ($archive && $checkdb > 1112)) {
                // When the new grading_areas table came in.
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
                        LEFT JOIN {turnitintool_submissions} su ON t.id = su.turnitintoolid AND su.userid = ?
                        LEFT JOIN {turnitintool_parts} tp ON su.submission_part = tp.id ";
            if (!$archive || ($archive && $checkdb > 1112)) {
                // When the new grading_areas table came in.
                $sql .= "LEFT JOIN {grading_areas} ga ON con.id = ga.contextid ";
            }
            $sql .= "WHERE c.visible = 1 AND c.showgrades = 1 AND cm.visible=1 AND tp.dtpost < ? ";
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
            if (!$archive || ($archive && $checkdb > 1112)) {
                // When the new grading_areas table came in.
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
                        LEFT JOIN {turnitintooltwo_submissions} su ON t.id = su.turnitintooltwoid AND su.userid = ?
                        LEFT JOIN {turnitintooltwo_parts} tp ON su.submission_part = tp.id ";
            if (!$archive || ($archive && $checkdb > 1112)) {
                // When the new grading_areas table came in.
                $sql .= "LEFT JOIN {grading_areas} ga ON con.id = ga.contextid ";
            }
            $sql .= "WHERE c.visible = 1 AND c.showgrades = 1 AND cm.visible=1 AND tp.dtpost < ? ";
            array_push($params, $now, $userid, $now, $userid, $now);
        }

        // Get a number of records as a moodle_recordset using a SQL statement.
        $rs = $currentdb->get_recordset_sql($sql, $params, $limitfrom = 0, $limitnum = 0);
        return $rs;
    }

    /**
     * Return the overview and feedback comments tab tables with user information
     *
     * @param string $tab The current tab/dashboard we are viewing
     * @param bool $ptutor Whether the user is a personal atutor
     * @param bool $padmin Whether the user is a Dept admin
     * @param string $arch The academic year eg. 1516
     * @return stdClass A table with the content of submission and feedback for the user referred to in the url after
     *         userid=
     */
    public function get_content($tab = null, $ptutor = null, $padmin = null, $arch = null): stdClass {
        global $CFG, $OUTPUT, $USER;

        $userid = optional_param('userid', 0, PARAM_INT); // User id.
        if (empty($userid)) {
            $userid = $USER->id;
        }
        $checkdb = $arch ? $arch : 'current';
        $archive = false;
        if (isset($_SESSION['viewyear']) && $_SESSION['viewyear'] != 'current') {
            $checkdb = $_SESSION['viewyear']; // Check if previous academic year, If so change domain to archived db.
        }

        if ($checkdb != 'current') {
            $archive = true;
        }

        if (!$archivedomain = get_config('report_myfeedback', 'archivedomain')) {
            $archivedomain = get_string('archivedomaindefault', 'report_myfeedback');
        }

        $archiveyear = substr_replace($checkdb, '-', 2, 0); // For building the archive link.
        $x = 0;
        $exceltable = [];

        $tz = $this->user_timezone();

        $newwindowmsg = get_string('new_window_msg', 'report_myfeedback');
        $newwindowicon = '<img src="' . 'pix/external-link.png' . '" ' .
            ' alt="-" title="' . $newwindowmsg . '" rel="tooltip"/>';
        $coursehmsg = get_string('gradetblheader_course_info', 'report_myfeedback');
        $coursehicon = '<img src="' . 'pix/info.png' . '" ' .
            ' alt="-" title="' . $coursehmsg . '" rel="tooltip"/>';
        $assessmenthmsg = get_string('gradetblheader_assessment_info', 'report_myfeedback');
        $assessmenthicon = '<img src="' . 'pix/info.png' . '" ' .
            ' alt="-" title="' . $assessmenthmsg . '" rel="tooltip"/>';
        $typehmsg = get_string('gradetblheader_type_info', 'report_myfeedback');
        $typehicon = '<img src="' . 'pix/info.png' . '" ' .
            ' alt="-" title="' . $typehmsg . '" rel="tooltip"/>';
        $duedatehmsg = get_string('gradetblheader_duedate_info', 'report_myfeedback');
        $duedatehicon = '<img src="' . 'pix/info.png' . '" ' .
            ' alt="-" title="' . $duedatehmsg . '" rel="tooltip"/>';
        $submissionhmsg = get_string('gradetblheader_submissiondate_info', 'report_myfeedback');
        $submissionhicon = '<img src="' . 'pix/info.png' . '" ' .
            ' alt="-" title="' . $submissionhmsg . '" rel="tooltip"/>';
        $fullfeedbackhmsg = get_string('gradetblheader_feedback_info', 'report_myfeedback');
        $fullfeedbackhicon = '<img src="' . 'pix/info.png' . '" ' .
            ' alt="-" title="' . $fullfeedbackhmsg . '" rel="tooltip"/>';
        $genfeedbackhmsg = get_string('gradetblheader_generalfeedback_info', 'report_myfeedback');
        $genfeedbackhicon = '<img src="' . 'pix/info.png' . '" ' .
            ' alt="-" title="' . $genfeedbackhmsg . '" rel="tooltip"/>';
        $gradehmsg = get_string('gradetblheader_grade_info', 'report_myfeedback');
        $gradehicon = '<img src="' . 'pix/info.png' . '" ' .
            ' alt="-" title="' . $gradehmsg . '" rel="tooltip"/>';
        $rangehmsg = get_string('gradetblheader_range_info', 'report_myfeedback');
        $rangehicon = '<img src="' . 'pix/info.png' . '" ' .
            ' alt="-" title="' . $rangehmsg . '" rel="tooltip"/>';
        $barhmsg = get_string('gradetblheader_bar_info', 'report_myfeedback');
        $barhicon = '<img src="' . 'pix/info.png' . '" ' .
            ' alt="-" title="' . $barhmsg . '" rel="tooltip"/>';
        $reflecthmsg = get_string('gradetblheader_selfreflectivenotes_info', 'report_myfeedback');
        $reflecthicon = '<img src="' . 'pix/info.png' . '" ' .
            ' alt="-" title="' . $reflecthmsg . '" rel="tooltip"/>';
        $viewedhmsg = get_string('gradetblheader_viewed_info', 'report_myfeedback');
        $viewedhicon = '<img src="' . 'pix/info.png' . '" ' .
            ' alt="-" title="' . $viewedhmsg . '" rel="tooltip"/>';

        $title = "<p>" . get_string('provisional_grades', 'report_myfeedback') . "</p>";
        // Print titles for each column: Assessment, Type, Due Date, Submission Date,
        // Submission/Feedback, Grade/Relative Grade.
        $table = "<table id=\"grades\" class=\"grades\" width=\"100%\">
                    <thead>
                            <tr class=\"tableheader\">
                                <th>" .
            get_string('gradetblheader_course', 'report_myfeedback') . " $coursehicon</th>
                                <th>" .
            get_string('gradetblheader_assessment', 'report_myfeedback') . " $assessmenthicon</th>
                                <th>" .
            get_string('gradetblheader_type', 'report_myfeedback') . " $typehicon</th>
                                <th>" .
            get_string('gradetblheader_duedate', 'report_myfeedback') . " $duedatehicon</th>
                                <th>" .
            get_string('gradetblheader_submissiondate', 'report_myfeedback') . " $submissionhicon</th>
                                <th>" .
            get_string('gradetblheader_feedback', 'report_myfeedback') . " $fullfeedbackhicon</th>
                                <th>" .
            get_string('gradetblheader_grade', 'report_myfeedback') . " $gradehicon</th>
                                                            <th>" .
            get_string('gradetblheader_range', 'report_myfeedback') . " $rangehicon</th>
                            <th>" .
            get_string('gradetblheader_bar', 'report_myfeedback') . " $barhicon
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
        // Setup the heading for the Comments table.
        $usercontext = context_user::instance($userid);
        $commentstable = "<table id=\"feedbackcomments\" width=\"100%\" border=\"0\">
                <thead>
                            <tr class=\"tableheader\">
                                <th>" .
            get_string('gradetblheader_course', 'report_myfeedback') . " $coursehicon</th>
                                <th>" .
            get_string('gradetblheader_assessment', 'report_myfeedback') . " $assessmenthicon</th>
                                <th>" .
            get_string('gradetblheader_type', 'report_myfeedback') . " $typehicon</th>
                                <th>" .
            get_string('gradetblheader_submissiondate', 'report_myfeedback') . " $submissionhicon</th>
                                <th>" .
            get_string('gradetblheader_grade', 'report_myfeedback') . " $gradehicon</th>
                                <th>" .
            get_string('gradetblheader_generalfeedback', 'report_myfeedback') . " $genfeedbackhicon</th>";
        if ($USER->id == $userid || has_capability('moodle/user:viewdetails', $usercontext)) {
            $commentstable .= "<th>" .
                get_string('gradetblheader_selfreflectivenotes', 'report_myfeedback') . " $reflecthicon</th>";
        }
        $commentstable .= "<th>" .
            get_string('gradetblheader_feedback', 'report_myfeedback') . " $fullfeedbackhicon</th>
                                <th>" .
            get_string('gradetblheader_viewed', 'report_myfeedback') . " $viewedhicon</th>
                                                </tr>
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
                    </thead>
                                                <tbody>";

        $rs = $this->get_data($archive, $checkdb);

        if ($rs->valid()) {
            // The recordset contains records.
            foreach ($rs as $record) {
                // First we check if sections have any form of restrictions.
                $show = 1;
                if (!$archive || ($archive && $checkdb > 1415)) {
                    if ($record->courseid) {
                        $record->id = $record->courseid;
                        $modinfo = get_fast_modinfo($record, $userid);
                        if ($record->assignmentid >= 1) {
                            $cm = $modinfo->get_cm($record->assignmentid);
                            if (!$cm->uservisible) {
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

                    if ($archive) {// If an archive year then change the domain.
                        $assignmentname = "<a href=\"" . $archivedomain . $archiveyear . "/mod/" . $record->assessmenttype .
                            "/view.php?id=" . $record->assignmentid . "\" target=\"_blank\" title=\"" . $record->assessmentname .
                            "\" rel=\"tooltip\">" . $assignment .
                            "</a> $newwindowicon";
                    }
                    $duedate = ($record->duedate ? userdate($record->duedate) : "-");
                    $duedatesort = ($record->duedate ? $record->duedate : "-");

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
                                // Add the status as Moodle adds a Sub date when grades are overridden manually
                                // even without submission.
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
                                // Implementing the rubric guide.
                                if ($record->activemethod == "rubric") {
                                    $getrubric = $this->rubrictext($userid, $record->courseid, $record->gi_iteminstance, 'assign');
                                    if ($getrubric) {
                                        $feedbacktext .= "<br/>&nbsp;<br/><span style=\"font-weight:bold;\"><img src=\"" .
                                            $CFG->wwwroot . "/report/myfeedback/pix/rubric.png\">"
                                            . get_string('rubrictext', 'report_myfeedback') . "</span><br/>" . $getrubric;
                                    }
                                }

                                // Implementing the marking guide.
                                if ($record->activemethod == "guide") {
                                    $getguide = $this->marking_guide_text(
                                        $userid,
                                        $record->courseid,
                                        $record->gi_iteminstance,
                                        'assign'
                                    );
                                    if ($getguide) {
                                        $feedbacktext .= "<span style=\"font-weight:bold;\"><img src=\"" .
                                            $CFG->wwwroot . "/report/myfeedback/pix/guide.png\">"
                                            . get_string('markingguide', 'report_myfeedback') . "</span><br/>" . $getguide;
                                    }
                                }

                                // If there are any comments or other feedback (such as online PDF
                                // files, rubrics or marking guides).
                                if ($record->feedbacklink || $onlinepdffeedback || $feedbacktext) {
                                    $feedbacktextlink = "<a href=\"" . $CFG->wwwroot .
                                        "/mod/assign/view.php?id=" . $record->assignmentid . $assignsingle . "\">" .
                                        get_string('feedback', 'report_myfeedback') . "</a>";

                                    if ($archive) {
                                        // If an archive year then change the domain.
                                        $feedbacktextlink = "<a href=\"" . $archivedomain . $archiveyear .
                                            "/mod/assign/view.php?id=" . $record->assignmentid . $assignsingle .
                                            "\" target=\"_blank\">" .
                                            get_string('feedback', 'report_myfeedback') . "</a> $newwindowicon";
                                    }

                                    // Add an icon if has pdf file.
                                    if ($onlinepdffeedback) {
                                        $feedbackfile = get_string('hasfeedbackfile', 'report_myfeedback');
                                        $feedbackfileicon = ' <img src="' .
                                            $OUTPUT->image_url('i/report') . '" ' .
                                            'class="icon" alt="' . $feedbackfile . '" title="' . $feedbackfile . '" rel="tooltip">';
                                    }
                                }

                                if (!$archive || ($archive && $checkdb > 1314)) {
                                    // When the new assign_user_flags table came in.
                                    // Checking if the user was given an assignment extension.
                                    $checkforextension = $this->check_assign_extension($userid, $record->assignid);
                                    if ($checkforextension) {
                                        $record->duedate = $checkforextension;
                                        $duedate = userdate($checkforextension);
                                        $duedatesort = $checkforextension;
                                    }
                                }
                                break;
                            case "turnitintool":
                                // If grade is overriden then show the overridden grade and put (all parts)
                                // if multiple parts otherwise show each part grade.
                                $overridden = $this->is_grade_overridden($record->gradeitemid, $userid);
                                $record->grade = $overridden ? $overridden : $record->grade;
                                $allparts = ($record->grade && $record->nosubmissions > 1 && $overridden)
                                    ? get_string('allparts', 'report_myfeedback') : '';
                                $assignmentname .= ($record->partname && $record->nosubmissions > 1)
                                    ? " (" . $record->partname . ")" : "";
                                $assessmenttype = get_string('turnitin_assignment', 'report_myfeedback');
                                if ($record->tiiobjid) {
                                    $feedbacktextlink = "<a href=\"" . $CFG->wwwroot . "/mod/" .
                                        $record->assessmenttype . "/view.php?id=" .
                                        $record->assignmentid . "&do=" . $subs . "\" target=\"_blank\">" .
                                        get_string('feedback', 'report_myfeedback') . "</a> $newwindowicon";

                                    if ($archive) {
                                        // If an archive year then change the domain.
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

                                    if ($archive) {
                                        // If an archive year then change the domain.
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
                                // If grade is overriden then show the overridden grade and put (all parts)
                                // if multiple parts otherwise show each part grade.
                                $overridden = $this->is_grade_overridden($record->gradeitemid, $userid);
                                $record->grade = $overridden ? $overridden : $record->grade;
                                $allparts = ($record->grade && $record->nosubmissions > 1 && $overridden)
                                    ? get_string('allparts', 'report_myfeedback') : '';
                                $assignmentname .= ($record->partname && $record->nosubmissions > 1)
                                    ? " (" . $record->partname . ")" : "";
                                $assessmenttype = get_string('turnitin_assignment', 'report_myfeedback');
                                if ($record->tiiobjid) {
                                    $feedbacktextlink = "<a href=\"" . $CFG->wwwroot . "/mod/" .
                                        $record->assessmenttype . "/view.php?id=" .
                                        $record->assignmentid . "&partid=" . $record->subpart . "\" target=\"_blank\">" .
                                        get_string('feedback', 'report_myfeedback') . "</a> $newwindowicon";

                                    if ($archive) {
                                        // If an archive year then change the domain.
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

                                    if ($archive) {
                                        // If an archive year then change the domain.
                                        $feedbacktextlink = "<a href=\"" . $archivedomain . $archiveyear . "/mod/" .
                                            $record->assessmenttype . "/view.php?id=" .
                                            $record->assignmentid . "&viewcontext=box&do=grademark&submissionid="
                                            . $record->tiiobjid .
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
                                if ($record->subid > 0 && (!$archive || ($archive && $checkdb > 1213))) {
                                    // When the new feedbackauthorattachment field was added to workshop_submission.
                                    $workshopfeedbackfile = $this->has_workshop_feedback_file($userid, $record->subid);
                                    $workshopfeedback = $this->has_workshop_feedback(
                                        $userid,
                                        $record->subid,
                                        $record->assignid,
                                        $record->courseid,
                                        $record->itemnumber
                                    );
                                } else {
                                    $workshopfeedbackfile = null;
                                    $workshopfeedback = null;
                                }

                                // Add an icon if has pdf file.
                                if ($workshopfeedbackfile) {
                                    $feedbackfile = get_string('hasfeedbackfile', 'report_myfeedback');
                                    $feedbackfileicon = ' <img src="' .
                                        $OUTPUT->image_url('i/report') . '" ' .
                                        'class="icon" alt="' . $feedbackfile . '" title="' . $feedbackfile . '" rel="tooltip">';
                                }

                                $submission = "<a href=\"" . $CFG->wwwroot . "/mod/workshop/submission.php?cmid=" .
                                    $record->assignmentid . "&id=" . $record->subid . "\">" .
                                    get_string('submission', 'report_myfeedback') . "</a>";
                                if ($record->subid && (($record->feedbacklink && $record->itemnumber == 0)
                                        || $workshopfeedbackfile || $workshopfeedback)) {

                                    $feedbacktextlink = "<a href=\"" . $CFG->wwwroot . "/mod/workshop/submission.php?cmid=" .
                                        $record->assignmentid . "&id=" . $record->subid . "\">" .
                                        get_string('feedback', 'report_myfeedback') . "</a>";

                                    if ($archive) {// If an archive year then change the domain.
                                        $feedbacktextlink = "<a href=\"" . $archivedomain . $archiveyear .
                                            "/mod/workshop/submission.php?cmid=" .
                                            $record->assignmentid . "&id=" . $record->subid . "\" target=\"_blank\">" .
                                            get_string('feedback', 'report_myfeedback') . "</a> $newwindowicon";
                                    }

                                    if ($record->feedbacklink && $record->itemnumber == 0) {
                                        $feedbacktext = '<b>' . get_string('tutorfeedback', 'report_myfeedback') . '</b><br/>'
                                            . $record->feedbacklink;
                                    }
                                    $feedbacktext .= $workshopfeedback;
                                }
                                break;
                            case "quiz":
                                $assessmenttype = get_string('quiz', 'report_myfeedback');
                                $submission = "-";
                                // Checking if the user was given an overridden extension or as part of a group.
                                $overrideextension = $this->check_quiz_extension($record->assignid, $userid);
                                $overrideextensiongroup = $this->check_quiz_group_extension($record->assignid, $userid);
                                if ($overrideextension && $overrideextensiongroup) {
                                    if ($overrideextension > $overrideextensiongroup) {
                                        $record->duedate = $overrideextension;
                                        $duedate = userdate($overrideextension);
                                    } else {
                                        $record->duedate = $overrideextensiongroup;
                                        $duedate = userdate($overrideextensiongroup);
                                        $duedatesort = $overrideextensiongroup;
                                    }
                                }
                                if ($overrideextension && !($overrideextensiongroup)) {
                                    $record->duedate = $overrideextension;
                                    $duedate = userdate($overrideextension);
                                    $duedatesort = $overrideextension;
                                }
                                if ($overrideextensiongroup && !($overrideextension)) {
                                    $record->duedate = $overrideextensiongroup;
                                    $duedate = userdate($overrideextensiongroup);
                                    $duedatesort = $overrideextensiongroup;
                                }

                                // Checking for quiz last attempt date as submission date.
                                $submit = $this->get_quiz_submissiondate(
                                    $record->assignid,
                                    $userid,
                                    $record->grade,
                                    $record->highestgrade,
                                    $record->sumgrades
                                );
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

                                // Show only when quiz is still openopen.
                                if (!$record->duedate || ($record->duedate && $record->duedate > $now)) {
                                    if ($review1 == 256 || $review1 == 272 || $review1 == 4352 || $review1 == 4368 ||
                                        $review1 == 65792 || $review1 == 65808 || $review1 == 69888 || $review1 == 69904) {
                                        $reviewattempt = true;
                                    } else {
                                        $reviewattempt = false;
                                    }
                                    // Added the marks as well but if set to not show at all then this is just as hiding the grade
                                    // in the gradebook but on the result page it will allow the review links or the overall
                                    // feedback text if these are set. Will also show the overridden feedback though on result page
                                    // so independent of gradereview.
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
                                    // When the quiz is closed what do you show.
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

                                // General feedback from tutor for a quiz attempt. If there is no attempt and we have feedback from
                                // override then we set feedback link to gradebook (singleview/index for tutor). If reviewmark is
                                // set then user will link to gradebook as grades don't show on results page unless there is a
                                // submission (attempt) If there is feedback the only time user link to gradebook (user/index) is if
                                // reviewmark is not set.
                                if ($feedbacktext) {
                                    if ($USER->id == $userid) {
                                        if ($quizgrade == 'yes' && $submissiondate == '-') {
                                            $thislink = "<a href=\"" . $CFG->wwwroot . "/grade/report/user/index.php?id="
                                                . $record->courseid . "\">" . get_string('feedback', 'report_myfeedback') . "</a>";
                                        } else {
                                            $thislink = "<a href=\"" . $CFG->wwwroot . "/mod/quiz/view.php?id="
                                                . $record->assignmentid . "\">"
                                                . get_string('feedback', 'report_myfeedback') . "</a>";
                                        }
                                    } else {
                                        $thislink = "<a href=\"" . $CFG->wwwroot . "/grade/report/singleview/index.php?id="
                                            . $record->courseid .
                                            "&item=user&itemid=" . $record->userid . "\">"
                                            . get_string('feedback', 'report_myfeedback') . "</a>";
                                    }
                                } else {
                                    $thislink = '';
                                }
                                $sameuser = ($USER->id == $userid) ? true : false;
                                $feedbacktextlink = $this->get_quiz_attempts_link(
                                    $record->assignid,
                                    $userid,
                                    $record->assignmentid,
                                    $archivedomain . $archiveyear,
                                    $archive,
                                    $newwindowicon,
                                    $reviewattempt,
                                    $sameuser
                                );

                                // Implementing the overall feedback str in the quiz.
                                // If review overall feedback and there is an attempt ($submissiondate) then get overall feedback.
                                $qid = intval($record->gi_iteminstance);
                                $grade2 = floatval($record->grade);
                                $feedback = ($reviewfeedback && $submissiondate != '-')
                                    ? $this->overallfeedback($qid, $grade2) : '';

                                // If no attempts(submissiondate) and overridden feedback then use that as the link otherwise use
                                // the attempt link.
                                $feedbacktextlink = ($feedbacktextlink) ? $feedbacktextlink : $thislink;
                                $feedbacktextlink = ($feedback || $feedbacktext) ? $feedbacktextlink : '';

                                // Only if review grade will the overall feedback be shown.
                                // Overridden feedback is always show as user can see it on quiz results page.
                                if ($reviewfeedback && $feedback) {
                                    $feedbacktext .= "<span style=\"font-weight:bold;\">"
                                        . get_string('overallfeedback', 'report_myfeedback') . "</span><br/>" . $feedback;
                                }
                                break;
                        }
                    } else {
                        // The manual item is assumed to be assignment - not sure why yet.
                        $itemtype = $record->gi_itemtype;
                        if ($itemtype === "manual") {
                            $assessmenttype = get_string('manual_gradeitem', 'report_myfeedback');
                            $assessmenticon = '<img src="' .
                                $OUTPUT->image_url('i/manual_item') . '" ' .
                                'class="icon" alt="' . $itemtype . '" title="' . $itemtype . '" rel="tooltip">';
                            // Bring the student to their user report in the gradebook.
                            $assignmentname = "<a href=\"" . $CFG->wwwroot . "/grade/report/user/index.php?id="
                                . $record->courseid .
                                "&userid=" . $record->userid . "\" title=\"" . $record->assessmentname .
                                "\" rel=\"tooltip\">" . $assignment . "</a>";

                            if ($archive) {
                                // If an archive year then change the domain.
                                $assignmentname = "<a href=\"" . $archivedomain . $archiveyear
                                    . "/grade/report/user/index.php?id=" . $record->courseid .
                                    "&userid=" . $record->userid . "\" target=\"_blank\" title=\"" . $record->assessmentname .
                                    "\" rel=\"tooltip\">" . $assignment . "</a> $newwindowicon";
                            }

                            $submission = "-";
                            $submissiondate = "-";
                            $duedate = "-";
                            $duedatesort = "-";
                            if ($record->feedbacklink) {
                                $feedbacktextlink = "<a href=\"" . $CFG->wwwroot .
                                    "/grade/report/user/index.php?id=" . $record->courseid . "&userid=" . $record->userid . "\">" .
                                    get_string('feedback', 'report_myfeedback') . "</a>";
                                $feedbacktext = $record->feedbacklink;

                                if ($archive) {
                                    // If an archive year then change the domain.
                                    $feedbacktextlink = "<a href=\"" . $archivedomain . $archiveyear .
                                        "/grade/report/user/index.php?id=" . $record->courseid . "&userid="
                                        . $record->userid . "\" target=\"_blank\">" .
                                        get_string('feedback', 'report_myfeedback') . "</a> $newwindowicon";
                                    $feedbacktext = $record->feedbacklink;
                                }
                            }
                        }
                    }

                    // Add the assessment icon.
                    if ($assessmenticon == "") {
                        $assessmenticon = '<img src="' .
                            $OUTPUT->image_url('icon', $record->assessmenttype) . '" ' .
                            'class="icon" alt="' . $assessmenttype . '" title="' . $assessmenttype . '"  rel="tooltip" />';
                    }
                    // Set the sortable date before converting to d-M-y format.
                    $subdatesort = $submissiondate;
                    $submittedtime = $submissiondate;
                    // If no feedback or grade has been received don't display anything.
                    if (!($feedbacktextlink == '' && $record->grade == null)) {
                        // Mark late submissions in red.
                        $submissionmsg = "";
                        if (is_numeric($submissiondate) && (strlen($submissiondate) == 10)) {
                            $submittedtime = $submissiondate;
                            $submissiondate = userdate($submissiondate);
                        } else if (strpos($assessmenttype, get_string('offline_assignment', 'report_myfeedback')) === false
                                && strpos($assessmenttype, get_string('quiz', 'report_myfeedback')) === false
                                && strpos($record->gi_itemtype, get_string('manual_gradeitem', 'report_myfeedback')) === true) {

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
                        // Late message if submission late.
                        if ($submissiondate != "-" && $duedatesort != "-" && $submittedtime > $record->duedate) {
                            if ($submissionmsg == "" && $submissiondate != get_string('no_submission', 'report_myfeedback') &&
                                $submissiondate != get_string('draft', 'report_myfeedback')) {
                                $submissionmsg = get_string('late_submission_msg', 'report_myfeedback');
                                if ($record->duedate) {
                                    $a = new stdClass();
                                    $a->late = format_time($submittedtime - $record->duedate);
                                    $submissionmsg .= get_string('waslate', 'report_myfeedback', $a);
                                    if ($record->assessmenttype == "assign") {
                                        $alerticon = ($record->status == 'submitted' ? '<img class="smallicon" src="' .
                                            $OUTPUT->image_url('i/warning', 'core') . '" ' . 'class="icon" alt="-" title="' .
                                            $submissionmsg . '" rel="tooltip"/>' : '');
                                    } else {
                                        $alerticon = '<img class="smallicon" src="' . $OUTPUT->image_url('i/warning', 'core')
                                            . '" ' . 'class="icon" alt="-" title="' .
                                            $submissionmsg . '" rel="tooltip"/>';
                                    }
                                }
                            }
                            $submissiondate = "<span style=\"color: #990000;\">" . $submissiondate . " $alerticon</span>";
                        }
                        if ($record->status == "draft") {
                            $submissiondate = "<span style=\"color: #990000;\">"
                                . get_string('draft', 'report_myfeedback') . "</span>";
                        }
                        $shortname = $record->shortname;
                        // Display grades in the format defined within each Moodle course.
                        $realgrade = null;
                        $mingrade = null;
                        $availablegrade = null;
                        $graderange = null;
                        if ($record->gi_itemtype == 'mod') {
                            if ($quizgrade != 'noreview') {
                                // Get the grade display type and grade type.
                                // TODO: Change the functionality to display grades to just (if grade_item display is 0 then
                                // check if course grade display type is set and if so take that value.
                                // I've added that function to get_course_grade_displaytype(course id).
                                switch ($gradetype) {
                                    case GRADE_TYPE_SCALE:
                                        $realgrade = $this->get_grade_scale(
                                            $record->gradeitemid,
                                            $userid,
                                            $record->courseid,
                                            $record->grade
                                        );
                                        $mingrade = $this->get_min_grade_scale(
                                            $record->gradeitemid,
                                            $userid,
                                            $record->courseid
                                        );
                                        $graderange = $this->get_all_grade_scale(
                                            $record->gradeitemid,
                                            $userid,
                                            $record->courseid
                                        );
                                        $availablegrade = $this->get_available_grade_scale(
                                            $record->gradeitemid,
                                            $userid,
                                            $record->courseid
                                        );
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
                                            $realgrade = $this->get_grade_letter(
                                                $record->courseid,
                                                $record->grade / $record->highestgrade * 100
                                            );
                                            $mingrade = $this->get_min_grade_letter($record->gradeitemid);
                                            $graderange = $this->get_all_grade_letters($record->courseid);
                                            $availablegrade = $this->get_available_grade_letter($record->courseid);
                                        } else if (($gradedisplay == GRADE_DISPLAY_TYPE_LETTER_PERCENTAGE) ||
                                            ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                                $cfggradetype == GRADE_DISPLAY_TYPE_LETTER_PERCENTAGE)) {
                                            $realgrade = $this->get_grade_letter(
                                                $record->courseid,
                                                $record->grade / $record->highestgrade * 100
                                            );
                                            $realgrade .= " (" . $this->get_fraction(
                                                $record->grade / $record->highestgrade * 100,
                                                $record->courseid,
                                                $record->decimals
                                            ) . "%)";
                                            $mingrade = $this->get_min_grade_letter($record->gradeitemid) . " (0)";
                                            $graderange = $this->get_all_grade_letters($record->courseid);
                                            $availablegrade = $this->get_available_grade_letter($record->courseid) . " (" .
                                                $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals) /
                                                $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals)
                                                * 100 . "%)";
                                        } else if (($gradedisplay == GRADE_DISPLAY_TYPE_LETTER_REAL) ||
                                            ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                                $cfggradetype == GRADE_DISPLAY_TYPE_LETTER_REAL)) {
                                            $realgrade = $this->get_grade_letter(
                                                $record->courseid,
                                                $record->grade / $record->highestgrade * 100
                                            );
                                            $realgrade .= " (" .
                                                $this->get_fraction($record->grade, $record->courseid, $record->decimals) . ")";
                                            $mingrade = $this->get_min_grade_letter($record->courseid) . " (0)";
                                            $graderange = $this->get_all_grade_letters($record->courseid);
                                            $availablegrade = $this->get_available_grade_letter($record->courseid) . " (" .
                                                $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals)
                                                . ")";
                                        } else if (($gradedisplay == GRADE_DISPLAY_TYPE_PERCENTAGE) ||
                                            ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                                $cfggradetype == GRADE_DISPLAY_TYPE_PERCENTAGE)) {
                                            $realgrade = $this->get_fraction(
                                                $record->grade / $record->highestgrade * 100,
                                                $record->courseid,
                                                $record->decimals
                                            ) . "%";
                                            $mingrade = "0";
                                            $availablegrade = $this->get_fraction(
                                                $record->highestgrade,
                                                $record->courseid,
                                                $record->decimals) / $this->get_fraction(
                                                    $record->highestgrade,
                                                    $record->courseid,
                                                    $record->decimals
                                                ) * 100 . "%";
                                        } else if (($gradedisplay == GRADE_DISPLAY_TYPE_PERCENTAGE_LETTER) ||
                                            ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                                $cfggradetype == GRADE_DISPLAY_TYPE_PERCENTAGE_LETTER)) {
                                            $realgrade = $this->get_fraction(
                                                $record->grade / $record->highestgrade * 100,
                                                $record->courseid,
                                                $record->decimals
                                            );
                                            $realgrade .= "% (" . $this->get_grade_letter(
                                                    $record->courseid,
                                                    $record->grade / $record->highestgrade * 100
                                                ) . ")";
                                            $mingrade = "0 (" . $this->get_min_grade_letter($record->courseid) . ")";
                                            $graderange = $this->get_all_grade_letters($record->courseid);
                                            $availablegrade = $this->get_fraction(
                                                $record->highestgrade,
                                                $record->courseid,
                                                $record->decimals) / $this->get_fraction(
                                                    $record->highestgrade,
                                                    $record->courseid,
                                                    $record->decimals
                                                ) * 100 . "% (" .
                                                $this->get_available_grade_letter($record->courseid) . ")";
                                        } else if (($gradedisplay == GRADE_DISPLAY_TYPE_PERCENTAGE_REAL) ||
                                            ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                                $cfggradetype == GRADE_DISPLAY_TYPE_PERCENTAGE_REAL)) {
                                            $realgrade = $this->get_fraction(
                                                $record->grade / $record->highestgrade * 100,
                                                $record->courseid,
                                                $record->decimals
                                            );
                                            $realgrade .= "% (" .
                                                $this->get_fraction($record->grade, $record->courseid, $record->decimals) . ")";
                                            $mingrade = "0 (0)";
                                            $availablegrade = $this->get_fraction(
                                                $record->highestgrade,
                                                $record->courseid,
                                                $record->decimals) / $this->get_fraction(
                                                    $record->highestgrade,
                                                    $record->courseid,
                                                    $record->decimals) * 100;
                                            $availablegrade .= "% (" .
                                                $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals) .
                                                ")";
                                        } else if (($gradedisplay == GRADE_DISPLAY_TYPE_REAL_LETTER) ||
                                            ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                                $cfggradetype == GRADE_DISPLAY_TYPE_REAL_LETTER)) {
                                            $realgrade = $this->get_fraction($record->grade, $record->courseid, $record->decimals);
                                            $realgrade .= " (" . $this->get_grade_letter(
                                                $record->courseid,
                                                $record->grade / $record->highestgrade * 100
                                            ) . ")";
                                            $mingrade = "0 (" . $this->get_min_grade_letter($record->courseid) . ")";
                                            $graderange = $this->get_all_grade_letters($record->courseid);
                                            $availablegrade = $this->get_fraction(
                                                $record->highestgrade,
                                                $record->courseid,
                                                $record->decimals
                                            );
                                            $availablegrade .= " (" . $this->get_available_grade_letter($record->courseid) . ")";
                                        } else if (($gradedisplay == GRADE_DISPLAY_TYPE_REAL_PERCENTAGE) ||
                                            ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                                $cfggradetype == GRADE_DISPLAY_TYPE_REAL_PERCENTAGE)) {
                                            $realgrade = $this->get_fraction($record->grade, $record->courseid, $record->decimals);
                                            $realgrade .= " (" .
                                                $this->get_fraction(
                                                    $record->grade / $record->highestgrade * 100,
                                                    $record->courseid,
                                                    $record->decimals
                                                ) . "%)";
                                            $mingrade = "0 (0)";
                                            $availablegrade = $this->get_fraction(
                                                $record->highestgrade,
                                                $record->courseid,
                                                $record->decimals
                                            );
                                            $availablegrade .= " (" .
                                                $this->get_fraction(
                                                    $record->highestgrade,
                                                    $record->courseid,
                                                    $record->decimals
                                                ) / $this->get_fraction(
                                                    $record->highestgrade,
                                                    $record->courseid,
                                                    $record->decimals
                                                ) * 100 . "%)";
                                        } else if (($gradedisplay == GRADE_DISPLAY_TYPE_REAL) ||
                                            ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                                $cfggradetype == GRADE_DISPLAY_TYPE_REAL)) {
                                            $realgrade = $this->get_fraction($record->grade, $record->courseid, $record->decimals);
                                            $mingrade = "0";
                                            $availablegrade = $this->get_fraction(
                                                $record->highestgrade,
                                                $record->courseid,
                                                $record->decimals
                                            );
                                        } else {
                                            $realgrade = $this->get_fraction($record->grade, $record->courseid, $record->decimals);
                                            $mingrade = "0";
                                            $availablegrade = $this->get_fraction(
                                                $record->highestgrade,
                                                $record->courseid,
                                                $record->decimals
                                            );
                                        }
                                        break;
                                }
                            }
                        }

                        if ($record->gi_itemtype == 'manual') {
                            switch ($gradetype) {
                                case GRADE_TYPE_SCALE:
                                    $realgrade = $this->get_grade_scale(
                                        $record->gradeitemid,
                                        $userid,
                                        $record->courseid,
                                        $record->grade
                                    );
                                    $mingrade = $this->get_min_grade_scale(
                                        $record->gradeitemid,
                                        $userid,
                                        $record->courseid
                                    );
                                    $graderange = $this->get_all_grade_scale(
                                        $record->gradeitemid,
                                        $userid,
                                        $record->courseid
                                    );
                                    $availablegrade = $this->get_available_grade_scale(
                                        $record->gradeitemid,
                                        $userid,
                                        $record->courseid
                                    );
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
                                        $realgrade = $this->get_grade_letter(
                                            $record->courseid,
                                            $record->grade / $record->highestgrade * 100
                                        );
                                        $mingrade = $this->get_min_grade_letter($record->courseid);
                                        $graderange = $this->get_all_grade_letters($record->courseid);
                                        $availablegrade = $this->get_available_grade_letter($record->courseid);
                                    } else if (($gradedisplay == GRADE_DISPLAY_TYPE_LETTER_PERCENTAGE) ||
                                        ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                            $cfggradetype == GRADE_DISPLAY_TYPE_LETTER_PERCENTAGE)) {
                                        $realgrade = $this->get_grade_letter(
                                            $record->courseid,
                                            $record->grade / $record->highestgrade * 100
                                        );
                                        $realgrade .= " (" . $this->get_fraction(
                                            $record->grade / $record->highestgrade * 100,
                                            $record->courseid,
                                            $record->decimals
                                        ) . "%)";
                                        $mingrade = $this->get_min_grade_letter($record->courseid) . " (0)";
                                        $graderange = $this->get_all_grade_letters($record->courseid);
                                        $availablegrade = $this->get_available_grade_letter($record->courseid) .
                                            " (" . $this->get_fraction(
                                                $record->highestgrade,
                                                $record->courseid,
                                                $record->decimals
                                            ) / $this->get_fraction(
                                                $record->highestgrade,
                                                $record->courseid,
                                                $record->decimals
                                            ) * 100 . "%)";
                                    } else if (($gradedisplay == GRADE_DISPLAY_TYPE_LETTER_REAL) ||
                                        ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                            $cfggradetype == GRADE_DISPLAY_TYPE_LETTER_REAL)) {
                                        $realgrade = $this->get_grade_letter(
                                            $record->courseid,
                                            $record->grade / $record->highestgrade * 100
                                        );
                                        $realgrade .= " (" .
                                            $this->get_fraction($record->grade, $record->courseid, $record->decimals) . ")";
                                        $mingrade = $this->get_min_grade_letter($record->courseid) . " (0)";
                                        $graderange = $this->get_all_grade_letters($record->courseid);
                                        $availablegrade = $this->get_available_grade_letter($record->courseid) . " (" .
                                            $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals) . ")";
                                    } else if (($gradedisplay == GRADE_DISPLAY_TYPE_PERCENTAGE) ||
                                        ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                            $cfggradetype == GRADE_DISPLAY_TYPE_PERCENTAGE)) {
                                        $realgrade = $this->get_fraction(
                                            $record->grade / $record->highestgrade * 100,
                                            $record->courseid, $record->decimals) . "%";
                                        $mingrade = "0";
                                        $availablegrade = $this->get_fraction(
                                            $record->highestgrade,
                                            $record->courseid, $record->decimals
                                        ) / $this->get_fraction(
                                                $record->highestgrade,
                                                $record->courseid,
                                                $record->decimals
                                            ) * 100 . "%";
                                    } else if (($gradedisplay == GRADE_DISPLAY_TYPE_PERCENTAGE_LETTER) ||
                                        ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                            $cfggradetype == GRADE_DISPLAY_TYPE_PERCENTAGE_LETTER)) {
                                        $realgrade = $this->get_fraction(
                                            $record->grade / $record->highestgrade * 100,
                                            $record->courseid, $record->decimals
                                        );
                                        $realgrade .= "% (" .
                                            $this->get_grade_letter($record->courseid, $record->grade / $record->highestgrade * 100)
                                            . ")";
                                        $mingrade = "0 (" . $this->get_min_grade_letter($record->courseid) . ")";
                                        $graderange = $this->get_all_grade_letters($record->courseid);
                                        $availablegrade = $this->get_fraction(
                                            $record->highestgrade,
                                            $record->courseid,
                                            $record->decimals
                                        ) / $this->get_fraction(
                                                $record->highestgrade,
                                                $record->courseid,
                                                $record->decimals
                                            ) * 100 . "% (" . $this->get_available_grade_letter($record->courseid) . ")";
                                    } else if (($gradedisplay == GRADE_DISPLAY_TYPE_PERCENTAGE_REAL) ||
                                        ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                            $cfggradetype == GRADE_DISPLAY_TYPE_PERCENTAGE_REAL)) {
                                        $realgrade = $this->get_fraction(
                                            $record->grade / $record->highestgrade * 100,
                                            $record->courseid,
                                            $record->decimals
                                        );
                                        $realgrade .= "% (" .
                                            $this->get_fraction($record->grade, $record->courseid, $record->decimals) . ")";
                                        $mingrade = "0 (0)";
                                        $availablegrade = $this->get_fraction(
                                            $record->highestgrade,
                                            $record->courseid,
                                            $record->decimals
                                        ) / $this->get_fraction(
                                                $record->highestgrade,
                                                $record->courseid,
                                                $record->decimals
                                            ) * 100 . "% (" .
                                            $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals) . ")";
                                    } else if (($gradedisplay == GRADE_DISPLAY_TYPE_REAL_LETTER) ||
                                        ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                            $cfggradetype == GRADE_DISPLAY_TYPE_REAL_LETTER)) {
                                        $realgrade = $this->get_fraction($record->grade, $record->courseid, $record->decimals);

                                        $realgrade .= " (" .
                                            $this->get_grade_letter($record->courseid, $record->grade / $record->highestgrade * 100)
                                            . ")";
                                        $mingrade = "0 (" . $this->get_min_grade_letter($record->courseid) . ")";
                                        $graderange = $this->get_all_grade_letters($record->courseid);
                                        $availablegrade = $this->get_fraction(
                                            $record->highestgrade,
                                            $record->courseid,
                                            $record->decimals
                                        ) . " (" . $this->get_available_grade_letter($record->courseid) . ")";
                                    } else if (($gradedisplay == GRADE_DISPLAY_TYPE_REAL_PERCENTAGE) ||
                                        ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                            $cfggradetype == GRADE_DISPLAY_TYPE_REAL_PERCENTAGE)) {
                                        $realgrade = $this->get_fraction($record->grade, $record->courseid, $record->decimals);
                                        $realgrade .= " (" .
                                            $this->get_fraction(
                                                $record->grade / $record->highestgrade * 100,
                                                $record->courseid,
                                                $record->decimals
                                            ) . "%)";
                                        $mingrade = "0 (0)";
                                        $availablegrade = $this->get_fraction(
                                            $record->highestgrade,
                                            $record->courseid,
                                            $record->decimals
                                        ) . " (" . $this->get_fraction(
                                                $record->highestgrade / $record->highestgrade * 100,
                                                $record->courseid, $record->decimals
                                            ) . "%)";
                                    } else if (($gradedisplay == GRADE_DISPLAY_TYPE_REAL) ||
                                        ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT &&
                                            $cfggradetype == GRADE_DISPLAY_TYPE_REAL)) {
                                        $realgrade = $this->get_fraction($record->grade, $record->courseid, $record->decimals);
                                        $mingrade = "0";
                                        $availablegrade = $this->get_fraction(
                                            $record->highestgrade,
                                            $record->courseid,
                                            $record->decimals
                                        );
                                    } else {
                                        $realgrade = $this->get_fraction($record->grade, $record->courseid, $record->decimals);
                                        $mingrade = "0";
                                        $availablegrade = $this->get_fraction(
                                            $record->highestgrade,
                                            $record->courseid,
                                            $record->decimals
                                        );
                                    }
                            }
                        }

                        // Horizontal bar only needed if grade type is value and not letter or scale.
                        if ($gradetype == GRADE_TYPE_VALUE && $gradedisplay != GRADE_DISPLAY_TYPE_LETTER) {
                            $gradepercent = ($record->highestgrade ? $record->grade / $record->highestgrade * 100 : $record->grade);
                            if ($gradepercent > 100) {
                                $gradepercent = 100;
                            }

                            $horbar = "<td style=\"width:150px\"><div class=\"horizontal-bar t-rel off\">
                                <div style=\"width:" . $gradepercent . "%\" class=\"grade-bar\">&nbsp;</div></div>"
                                . "<div class=\"available-grade t-rel off\">"
                                . $this->get_fraction($record->grade, $record->courseid, $record->decimals) . "/" .
                                $this->get_fraction($record->highestgrade, $record->courseid, $record->decimals) . "</div>
                                </td>";
                        }
                        if ($gradetype != GRADE_TYPE_VALUE) {
                            $horbar = "<td>-</td>";
                        }
                        if ($gradedisplay == GRADE_DISPLAY_TYPE_LETTER || ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT
                                && $cfggradetype == GRADE_DISPLAY_TYPE_LETTER)) {
                            $horbar = "<td>-</td>";
                        }
                        if ($realgrade == "-" || $realgrade < 1) {
                            $horbar = "<td>-</td>";
                        }
                        if ($quizgrade == 'noreview') {
                            $horbar = "<td>-</td>";
                        }

                        $gradetbl2 = $realgrade;
                        if ($gradetype != GRADE_TYPE_SCALE) {
                            if (($gradedisplay == GRADE_DISPLAY_TYPE_REAL) ||
                                ($gradedisplay == GRADE_DISPLAY_TYPE_DEFAULT && $cfggradetype == GRADE_DISPLAY_TYPE_REAL)) {
                                $gradetbl2 = $realgrade . '/' . $availablegrade;
                            }
                        }

                        // If no grade is given then don't display 0.
                        if ($record->grade == null || $gradetbl2 == '/') {
                            $gradetbl2 = "-";
                            $realgrade = "-";
                        }

                        // Implement weighting.
                        $weighting = '-';
                        if ($record->weighting != 0 && $gradetype == 1) {
                            $weighting = number_format(($record->weighting * 100), 0, '.', ',') . '%';
                        }

                        // Data for the viewed feedback.
                        $viewed = "<span style=\"color:#c05756;\"> &#10006;</span>";
                        $viewexport = 'no';
                        if (!$archive || ($archive && $checkdb > 1415)) {
                            // When the new log store table came in.
                            $check = $this->check_viewed_gradereport(
                                $record->contextid,
                                $record->assignmentid,
                                $userid,
                                $record->courseid,
                                $record->assessmentname
                            );
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

                        // Only show if Module tutor or Departmental admin has their access to the specific course.
                        // Personal tutor would only get this far if they had that access so no need to set condition for them.
                        $libcoursecontext = context_course::instance($record->courseid);
                        $usercontext = context_user::instance($record->userid);

                        $modulename = ($record->coursename && $record->courseid ? "<a href=\"" . $CFG->wwwroot .
                            "/course/view.php?id=" . $record->courseid . "\" title=\"" . $record->coursename .
                            "\" rel=\"tooltip\">" . $shortname . "</a>" : "&nbsp;");
                        if ($archive) {
                            // If an archive year then change the domain.
                            $modulename = ($record->coursename && $record->courseid ? "<a href=\"" . $archivedomain . $archiveyear .
                                "/course/view.php?id=" . $record->courseid . "\" target=\"_blank\" title=\"" . $record->coursename .
                                "\" rel=\"tooltip\">" . $shortname . "</a> $newwindowicon" : "&nbsp;");
                        }

                        if ($show && ($userid == $USER->id || $USER->id == 2 ||
                                has_capability('moodle/user:viewdetails', $usercontext) ||
                                has_capability('report/myfeedback:progadmin', $libcoursecontext, $USER->id, false) ||
                                has_capability('report/myfeedback:modtutor', $libcoursecontext, $USER->id, false))) {
                            $table .= "<tr>";
                            $table .= "<td class=\"ellip\">" . $modulename . "</td>";
                            $table .= "<td>" . $assessmenticon . $assignmentname . "</td>";
                            $table .= "<td>" . $assessmenttype . "</td>";
                            $table .= "<td data-sort=$duedatesort>" . $duedate . "</td>";
                            $table .= "<td data-sort=$subdatesort>" . $submissiondate . "</td>";
                            $table .= "<td>" . $feedbacktextlink . "</td>"; // Including links to marking guide or rubric.
                            $table .= "<td>" . $realgrade . $allparts . "</td>";
                            $table .= "<td title=\"$graderange\" rel=\"tooltip\">" . $mingrade . " - " . $availablegrade . "</td>";
                            $table .= $horbar;
                            $table .= "</tr>";

                            // The full excel downloadable table.
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
                            // The reflective notes and turnitin feedback.
                            $tdid = $record->gradeitemid;
                            if (!$instn = $record->subpart) {
                                $instn = null;
                            }

                            $notes = '';
                            $noteslink = '<a href="#" class="addnote" data-target="#Abs2" data-toggle="modal" title="'
                                . get_string('addnotestitle', 'report_myfeedback') . '" rel="tooltip" data-uid="' .
                                $userid . '" data-gid="' . $tdid . '" data-inst="' . $instn . '">'
                                . get_string('addnotes', 'report_myfeedback') . '</a>';

                            if ($usernotes = $this->get_notes($userid, $record->gradeitemid, $instn)) {
                                $notes = nl2br($usernotes);
                                // Abs2.

                                $noteslink = '<a href="#" class="addnote" data-target="#Abs2" data-toggle="modal" title="'
                                    . get_string('editnotestitle', 'report_myfeedback') . '" rel="tooltip" data-uid="' .
                                    $userid . '" data-gid="' . $tdid . '" data-inst="' . $instn . '">'
                                    . get_string('editnotes', 'report_myfeedback') . '</a>';
                            }

                            // Only the user can add/edit their own self-reflective notes.
                            if ($USER->id != $userid) {
                                $noteslink = '';
                            }

                            $feedbacklink = '<i>' . get_string('addfeedback', 'report_myfeedback')
                                . '<br><a href="#" class="addfeedback" data-toggle="modal" title="'
                                . get_string('addfeedbacktitle', 'report_myfeedback') . '" rel="tooltip" data-uid="' .
                                $userid . '" data-gid="' . $tdid . '" data-inst="' . $instn . '">'
                                . get_string('copyfeedback', 'report_myfeedback') . '</a>.</i>';

                            $selfadded = 0;
                            $studentcopy = '';
                            $studentadded = 'notstudent';
                            if ($archive) {
                                // Have to be done here so feedback text don't add the link to the text.
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
                                            $studentcopy = '<b><u>' . get_string('studentaddedfeedback', 'report_myfeedback')
                                                . '</u></b><br>';
                                            if ($USER->id == $userid) {
                                                $studentcopy = '<b><u>' . get_string('selfaddedfeedback', 'report_myfeedback')
                                                    . '</u></b><br>';
                                            }
                                        }

                                        $feedbacklink = '<a href="#" class="addfeedback" data-toggle="modal" title="'
                                            . get_string('editfeedbacktitle', 'report_myfeedback') . '" rel="tooltip" data-uid="' .
                                            $userid . '" data-gid="' . $tdid . '" data-inst="' . $instn . '">' . $fileicon . '</a>';
                                        if ($archive) {
                                            // Have to be done here again so feedback text don't add the link to the text.
                                            $feedbacklink = '';
                                        }
                                        $feedbacktext = $studentcopy . "<div id=\"feed-val" . $tdid . $instn . "\">" .
                                            nl2br($nonmoodlefeedback->feedback) . "</div><div style=\"float:right;\">" .
                                            $feedbacklink . "</div>";
                                    } else {
                                        $feedbacktext = $feedbacklink;
                                    }
                                } else {
                                    $feedbacktext = $feedbacklink;
                                }
                            }

                            if (!$archive) {

                                // The self-reflective notes bootstrap modal.
                                echo $OUTPUT->render_from_template('report_myfeedback/modal', [
                                    'actionurl' => new moodle_url('/report/myfeedback/reflectivenotes.php'),
                                    'formid' => 'notesform',
                                    'gradeid' => 'gradeid',
                                    'instanceid' => 'instance1',
                                    'modalid' => 'Abs2',
                                    'submitbtnid' => 'submitnotes',
                                    'submitbtntext' => get_string('savenotes', 'report_myfeedback'),
                                    'textid' => 'notename',
                                    'title' => get_string('addeditnotes', 'report_myfeedback'),
                                    'userformid' => 'user_id',
                                    'userformname' => 'userid',
                                ]);

                                // The non-moodle(turnitin) feedback bootstrap modal.
                                echo $OUTPUT->render_from_template('report_myfeedback/modal', [
                                    'actionurl' => new moodle_url('/report/myfeedback/nonmoodlefeedback.php'),
                                    'formid' => 'feedform',
                                    'gradeid' => 'gradeid2',
                                    'instanceid' => 'instance',
                                    'modalid' => 'Abs1',
                                    'submitbtnid' => 'submitfeed',
                                    'submitbtntext' => get_string('savefeedback', 'report_myfeedback'),
                                    'textid' => 'feedname',
                                    'title' => get_string('addeditnotes', 'report_myfeedback'),
                                    'userformid' => 'user_id2',
                                    'userformname' => 'userid2',
                                ]);
                            }

                            // The feedback comments table.
                            if (trim($feedbacktext) != '' || trim($feedbackfileicon) != '' || trim($feedbacktextlink) != '') {
                                $commentstable .= "<tr>";
                                $commentstable .= "<td class=\"ellip\">" . $modulename . "</td>";
                                $commentstable .= "<td style=\"text-align:left;\">" . $assessmenticon . $assignmentname . "</td>";
                                $commentstable .= "<td style=\"text-align:left;\">" . $assessmenttype . "</td>";
                                $commentstable .= "<td data-sort=$subdatesort>" . $submissiondate . "</td>";
                                $commentstable .= "<td>" . $gradetbl2 . $allparts . "</td>";
                                $commentstable .= "<td class=\"feed-val-2 $studentadded\" width=\"400\"
                                                    style=\"border:3px solid #ccc; text-align:left;\">" . $feedbacktext . "</td>";
                                if ($USER->id == $userid || has_capability('moodle/user:viewdetails', $usercontext)) {
                                    $commentstable .= "<td class=\"note-val-2 \"><div id=\"note-val" . $tdid . $instn . "\">" .
                                        $notes . "</div><div>" . $noteslink . "</div></td>";
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
        $commentstable .= "</tbody></table>";
        $printmsg = get_string('print_msg', 'report_myfeedback');
        // Buttons for filter reset, download and print.
        $resetprintexcel = "<div style=\"float:right;\" class=\"buttonswrapper\">
                <input id=\"tableDestroy\" type=\"button\" value=\"" .
                get_string('reset_table', 'report_myfeedback') . "\">
                <input id=\"exportexcel\" type=\"button\" value=\"" . get_string('export_to_excel', 'report_myfeedback') . "\">
                <input id=\"reportPrint\" type=\"button\" value=\"" . get_string('print_report', 'report_myfeedback') .
                "\" title=\"" . $printmsg . "\" rel=\"tooltip\"></div>";

        $resetprintexcel1 = "<div style=\"float:right;\" class=\"buttonswrapper\">
                <input id=\"ftableDestroy\" type=\"button\" value=\"" .
                get_string('reset_table', 'report_myfeedback') . "\">
                <input id=\"exportexcel\" type=\"button\" value=\"" . get_string('export_to_excel', 'report_myfeedback') . "\">
                <input id=\"reportPrint\" type=\"button\" value=\"" . get_string('print_report', 'report_myfeedback') .
                "\" title=\"" . $printmsg . "\" rel=\"tooltip\"></div>";

        $_SESSION['exp_sess'] = $exceltable;
        $_SESSION['myfeedback_userid'] = $userid;
        $_SESSION['tutor'] = 'no';
        $this->content->text = $title . $resetprintexcel . $table;
        if ($tab == 'feedback') {
            $feedbackcomments = "<p>" . get_string('tabs_feedback_text', 'report_myfeedback') . "</p>";
            $this->content->text = $feedbackcomments . $resetprintexcel1 . $commentstable;
        }
        return $this->content;
    }

}
