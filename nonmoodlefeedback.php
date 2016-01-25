<?php

/**
 * Add/edit users non-Moodle(turnitin) feedback turnitin grade item
 *
* @package   report_myfeedback
 * @author    Delvon Forrester <delvon@esparanza.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
if (isset($_POST['feedname']) && isset($_POST['grade_id2']) && isset($_POST['userid2'])) {
$usr_id = $_POST['userid2'];
$grade_id = $_POST['grade_id2'];
$instance = $_POST['instance'];
$f_notes = strip_tags($_POST['feedname'], '<br>');
       /* $usrid = $this->_customdata['usr'];
        $gradeitem = $this->_customdata['gradeitem'];
        $mform = $this->_form;        */
         $sql = "SELECT feedback FROM {report_myfeedback}
                    WHERE userid=? AND gradeitemid=? AND iteminstance=?";
         $sql1 = "UPDATE {report_myfeedback} 
                    SET modifierid=?, feedback=?  
                    WHERE userid=? AND gradeitemid=? AND iteminstance=?";
         $sql2 = "INSERT INTO {report_myfeedback}
                    (userid, gradeitemid, modifierid, iteminstance, feedback)
                    VALUES (?, ?, ?, ?, ?)";
         $params = array($usr_id, $grade_id, $instance);
         $params1 = array($USER->id, $f_notes, $usr_id, $grade_id, $instance);
         $params2 = array($usr_id, $grade_id, $USER->id, $instance, $f_notes);
         $userfeedback = $DB->get_record_sql($sql, $params);
         
        if ($userfeedback) {
            $DB->execute($sql1, $params1);
            echo get_string('updatesuccessful', 'report_myfeedback');
        } else {
            $DB->execute($sql2, $params2);
            echo get_string('insertsuccessful', 'report_myfeedback');
        }
        // Add some extra hidden fields.
        /*$mform->addElement()->setValue($displaynotes);
        $mform->setType('notes'.$gradeitem, PARAM_TEXT);
        $this->add_action_buttons(false, 'Save notes');*/
        header('Location: index.php?userid='.$usr_id.'&currenttab=feedback');
}