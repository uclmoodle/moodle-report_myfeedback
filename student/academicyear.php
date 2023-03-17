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
 * An included file for getting the academic year for archive functionality
 *
 * @package  report_myfeedback
 * @copyright 2022 UCL
 * @author    Delvon Forrester <delvon@esparanza.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$acyears = $report->get_archived_years();
$res = 'current';
$siteadmin = is_siteadmin() ? 1 : 0;
$archivedinstance = $archive = false;
if (isset($_SESSION['viewyear'])) {
    $res = $_SESSION['viewyear'];
}

if (!$personaltutor && !$progadmin && !$siteadmin) {
    if ($yrr->academicyeartext && $yrr->archivedinstance) {
        $res = $yrr->academicyeartext;
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
$varptut = $personaltutor ? 'yes' : 'no';
$varprog = $progadmin ? 'yes' : 'no';
$varsadmin = $siteadmin ? 'yes' : 'no';
$vararchiveinst = $archivedinstance ? 'yes' : 'no';

// Get the config data.
$config = get_config('report_myfeedback');

// Show the academic year menu.
 $o = '<form method="POST" id="yearform" action="">'
        . '<input type="hidden" name="sesskey" value="' . sesskey() . '" />'
        . "<input type=\"hidden\" name=\"archive\" value=\"no\">"
        . "<select id=\"mySelect\" value=$res name=\"myselect\">";
$i = 0;
foreach ($acyears as $val) {
    if ($val == 'current') {
        $url = 'current';
        $linktext = (isset($config->academicyeartext) && $config->academicyeartext != '' ? $config->academicyeartext :
            get_string('current_academic_year', 'report_myfeedback'));
    } else {
        $alink = "archivelink" . $i;
        $url = $config->$alink;
        $alt = 'archivelinktext' . $i;
        $linktext = (isset($config->$alt) ? $config->$alt : '');
    }

    if ($url != '' && $linktext != '') {
        $o .= "<option url='" . $url . "' value=" . $val .
            ($val == $res ? ' selected' : '') .
            ">" . $linktext;
    }
    $i++;
}

$o .= "</select></form>";

// No support before academic year 1213.
if ($res != 'current' && $res < 1213) {
    $o .= get_string('noarchivesupporth1', 'report_myfeedback');
    $o .= get_string('noarchivesupporth2', 'report_myfeedback');
    $_SESSION['viewyear'] = 'current';
}

echo $o;

$PAGE->requires->js_call_amd('report_myfeedback/academicyear', 'init', [
    $archivedomain,
    $userid,
    $currenttab,
    $varptut,
    $varprog,
    $varsadmin,
    $livedomain,
    $vararchiveinst
]);
