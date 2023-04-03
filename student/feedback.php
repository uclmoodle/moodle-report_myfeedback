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
 * The main file for the feedback comments tab
 *
 * @package  report_myfeedback
 * @copyright 2022 UCL
 * @author    Delvon Forrester <delvon@esparanza.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$PAGE->requires->js_call_amd('report_myfeedback/feedback', 'init');

echo "<div class=\"ac-year-right\"><p>" . get_string('academicyear', 'report_myfeedback') . ":</p>";
require_once(dirname(__FILE__) . '/academicyear.php');
echo "</div>";
$archiveyear = substr_replace($res, '-', 2, 0); // For building the archive link.
$arch = $res;
$pos = stripos($CFG->wwwroot, $archiveyear);

if (!$personaltutor && !$progadmin && !is_siteadmin()) {
    $res = '';// If all else fails it should check only it's current database.
}
$report->setup_external_db($res);
$content = $report->get_content($currenttab, $personaltutor, $progadmin, $arch);
echo $content->text;
echo $OUTPUT->container_start('info');
echo $OUTPUT->container_end();
