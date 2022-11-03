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

/*
 * An included file for getting the academic year for archive functionality
 *
 * @package  report_myfeedback
 * @author    Delvon Forrester <delvon@esparanza.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$acyears = $report->get_archived_dbs();
$res = 'current';
$siteadmin = is_siteadmin() ? 1 : 0;
$archivedinstance = $archive = false;
if (isset($_SESSION['viewyear'])) {
    $res = $_SESSION['viewyear'];
}

if (!$personaltutor && !$progadmin && !$siteadmin) {
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
$varptut = $personaltutor ? 'yes' : 'no';
$varprog = $progadmin ? 'yes' : 'no';
$varsadmin = $siteadmin ? 'yes' : 'no';
$vararchiveinst = $archivedinstance ? 'yes' : 'no';

echo '<form method="POST" id="yearform" action="">'
        . '<input type="hidden" name="sesskey" value="' . sesskey() . '" />'
        . "<input type=\"hidden\" name=\"archive\" value=\"no\"><select id=\"mySelect\" value=$res name=\"myselect\">";
foreach ($acyears as $val) {
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

// No support before academic year 1213.
if ($res != 'current' && $res < 1213) {
    echo get_string('noarchivesupporth1', 'report_myfeedback');
    echo get_string('noarchivesupporth2', 'report_myfeedback');
    $_SESSION['viewyear'] = 'current';
}

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
