<?php

/*
 * An included file for getting the academic year for archive functionality
 * 
 * @package  report_myfeedback
 * @author    Delvon Forrester <delvon@esparanza.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
$ac_years = $report->get_archived_dbs();
$res = 'current';
$siteadmin = is_siteadmin() ? 1 : 0;
$archivedinstance = $archive = false;
if (isset($_SESSION['viewyear'])) {
    $res = $_SESSION['viewyear'];
}

if (!$personal_tutor && !$progadmin && !$siteadmin) {
    if ($yrr->academicyear && $yrr->archivedinstance) {
        $res = $yrr->academicyear;
        $archivedinstance = true;
    }
}

if (isset($_REQUEST['archive']) && $_REQUEST['archive'] == 'yes') {
    $res = 'current';
    $_SESSION['viewyear'] = 'current';
}
if ($res != 'current') {
    $archive = true;
}
$varptut = $personal_tutor ? 'yes' : 'no';
$varprog = $progadmin ? 'yes' : 'no';
$varsadmin = $siteadmin ? 'yes' : 'no';
$vararchiveinst = $archivedinstance ? 'yes' : 'no';

echo "<form method=\"POST\" id=\"yearform\" action=\"\"><input type=\"hidden\" name=\"archive\" value=\"no\"><select id=\"mySelect\" value=$res name=\"myselect\">";
foreach ($ac_years as $val) {
    echo "<option value=" . $val;
    if ($res == $val) {
        echo ' selected';
    }
    if ($val == 'current') {
        echo ">" . $val;
    } else {
        echo ">" . "20" . implode('/', str_split($val, 2));
    }
}
echo "</select></form>";

//No support before academic year 1213
if ($res != 'current' && $res < 1213) {
    echo get_string('noarchivesupporth1', 'report_myfeedback');
    echo get_string('noarchivesupporth2', 'report_myfeedback');
    $_SESSION['viewyear'] = 'current';
}

echo "<script>
$('#mySelect').change(function(){
    var t = this.value.toString();
    var archivedomain = '".$archivedomain."';
    var userid = ".$userid.";
    var currenttab = '".$currenttab."';
    var ptut = ".$varptut.";
    var prog = ".$varprog.";
    var siteadmin = ".$varsadmin.";
    var livedomain = '".$livedomain."';
    var archiveinst = ".$vararchiveinst.";
    var archiveyear = t.substring(0,2)+'-'+t.substring(2);
    if (ptut == 'no' && prog == 'no' && siteadmin == 'no') {
      if (archiveinst) {
        if (t == 'current') {
          location.replace(livedomain+'/report/myfeedback/index.php?userid='+userid+'&currenttab='+currenttab);
        } else {
          location.replace(archivedomain+archiveyear+'/report/myfeedback/index.php?userid='+userid+'&currenttab='+currenttab);
        }
      } else {
        $('#yearform').submit();
      }
    } else {// if personal tutor or dept admin or site admin
      if (archiveinst == 'no') {// only if not archive instance
         $('#yearform').submit();
       } else { //if not archive insance
          if (t == 'current') {
             location.replace(livedomain+'/report/myfeedback/index.php?userid='+userid+'&currenttab='+currenttab);
          } else {
             location.replace(archivedomain+archiveyear+'/report/myfeedback/index.php?userid='+userid+'&currenttab='+currenttab);
          }
       }         
    }
});
</script>";
