<?php

/*
 * The included file for for to select modules on Mod tutor dashboard
 * 
 * @package  report_myfeedback
 * @author    Delvon Forrester <delvon@esparanza.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if (!$report->get_dashboard_capability($USER->id, 'report/myfeedback:modtutor')) {
    throw new moodle_exception('nopermissions', '', $PAGE->url->out(), get_string('viewtutorreports', 'report_myfeedback'));
}

$m = '';
if (isset($_SESSION['viewmod'])) {
    $m = $_SESSION['viewmod'];
}
$modheading = get_string('mymodules', 'report_myfeedback');
$modmsg = get_string('moddescription', 'report_myfeedback');
$modicon = '<img src="' . 'pix/info.png' . '" ' .
        ' alt="-" title="' . $modmsg . '" rel="tooltip"/>';
echo "<div class=\"ac-year-right\"><p class=\"my\">" . $modheading . ": " . $modicon . '</p>'
        . '<form method="POST" id="mod_form" action="">'
        . '<input type="hidden" name="sesskey" value="' . sesskey() . '" />'
        . '<select multiple="multiple" id="modSelect" name="modselect[]">';
foreach ($my_tutor_mods as $val1) {
    echo "<option value=\"" . $val1->shortname."\"";
    foreach ($m as $v2) {
        if ($v2 == $val1->shortname) {
            echo ' selected';
        }
    }
    echo ">" . $val1->shortname;
}
echo "</select><input type='submit' value='".get_string('analyse','report_myfeedback')."'>
    </form></p></div>";

