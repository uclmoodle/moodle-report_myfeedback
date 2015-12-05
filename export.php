<?PHP

// Original PHP code by Chirp Internet: www.chirp.com.au
// Please acknowledge use of this code by including this header.
require('../../config.php');
$data = $_SESSION["exp_sess"];
$userid = $_SESSION['myfeedback_userid'];
// Trigger a table download
$event = \report_myfeedback\event\myfeedbackreport_download::create(array('context' => context_course::instance($COURSE->id), 'relateduserid' => $userid));
$event->trigger();

// file name for download
$filename = "MyFeedback_report_" . date('YmdHis') . ".csv";

header("Content-Disposition: attachment; filename=\"$filename\"");
header("Content-Type: text/csv; charset=UTF-8");
$exc = '';
$excelheader = "Module,Assessment,Type,Due date,Submission date,Grade,Available grade,General feedback,Viewed \r\n";
foreach ($data as $key => $row) {
    $row = str_replace(",", ";", $row);
    $exc.= "\r\n" . implode(",", $row);
}
echo $excelheader;
echo strip_tags($exc);
exit;
