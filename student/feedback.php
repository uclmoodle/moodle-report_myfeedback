<?php
 
/*
 * The main file for the feedback comments tab
 * 
 * @package  report_myfeedback
 * @author    Delvon Forrester <delvon@esparanza.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
defined('MOODLE_INTERNAL') || die;

$PAGE->requires->js_call_amd('report_myfeedback/feedback', 'init');

echo "<div class=\"ac-year-right\"><p>" . get_string('academicyear', 'report_myfeedback') . ":</p>";
require_once(dirname(__FILE__) . '/academicyear.php');
echo "</div>";
$archiveyear = substr_replace($res, '-', 2, 0); //for building the archive link
$arch = $res;
$pos = stripos($CFG->wwwroot, $archiveyear);
/*if (!$personal_tutor && !$progadmin && !is_siteadmin() && !$archivedinstance && $pos === false && $res != 'current') {
    //echo '<script>location.replace("http://127.0.0.1:88/v288/report/myfeedback/index.php?userid=' . $userid . '&currenttab=' . $currenttab . '");</script>';
    echo '<script>location.replace("'.$archivedomain.$archiveyear.'/report/myfeedback/index.php?userid='.$userid.'&currenttab='.$currenttab.'");</script>';
}
if (!$personal_tutor && !$progadmin && !is_siteadmin() && $archivedinstance && $pos === false && $res != 'current') {
    //echo '<script>location.replace("http://127.0.0.1:88/v288/report/myfeedback/index.php?userid=' . $userid . '&currenttab=' . $currenttab . '");</script>';
    echo '<script>location.replace("'.$archivedomain.$archiveyear.'/report/myfeedback/index.php?userid='.$userid.'&currenttab='.$currenttab.'");</script>';
}
if (isset($yrr->archivedinstance) && $yrr->archivedinstance && $res == 'current') {//Go back to the live domain which is the current academic year
    //echo '<script>location.replace("http://127.0.0.1:88/v2810/report/myfeedback/index.php?userid=' . $userid . '&archive=yes&currenttab=' . $currenttab . '");</script>';
    echo '<script>location.replace("'.$livedomain.'report/myfeedback/index.php?userid='.$userid.'&archive=yes&currenttab='.$currenttab.'");</script>';
}*/
if (!$personal_tutor && !$progadmin && !is_siteadmin()) {
    $res = '';//If all else fails it should check only it's current database
}
$report->setup_external_db($res);
$content = $report->get_content($currenttab, $personal_tutor, $progadmin, $arch);
echo $content->text;
echo $OUTPUT->container_start('info');
echo $OUTPUT->container_end();
