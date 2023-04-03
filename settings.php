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
 * The settings.
 *
 * @package   report_myfeedback
 * @copyright 2022 UCL
 * @author    Jessica Gramp <j.gramp@ucl.ac.uk> or <jgramp@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    // Database Settings.
    $settings->add(new admin_setting_heading('report_myfeedback_addheading', get_string('dbsettings', 'report_myfeedback'), ''));
    $settings->add(new admin_setting_configtext('report_myfeedback/dbhost', get_string('dbhost', 'report_myfeedback'),
                    get_string('dbhostinfo', 'report_myfeedback'), '', PARAM_URL, 30));
    $settings->add(new admin_setting_configtext('report_myfeedback/dbname', get_string('dbname', 'report_myfeedback'),
                    get_string('dbnameinfo', 'report_myfeedback'), '', PARAM_RAW, 30));
    $settings->add(new admin_setting_configpasswordunmask('report_myfeedback/dbuser', get_string('dbuser', 'report_myfeedback'),
                    get_string('dbuserinfo', 'report_myfeedback'), '', PARAM_RAW, 30));
    $settings->add(new admin_setting_configpasswordunmask('report_myfeedback/dbpass', get_string('dbpass', 'report_myfeedback'),
                    get_string('dbpassinfo', 'report_myfeedback'), '', PARAM_RAW, 30));

    $options = array();
    $max = 5;
    for ($i = 0; $i <= $max; $i++) {
        $options[$i] = $i;
    }

    // Archive Links.
    $settings->add(new admin_setting_heading('report_myfeedback_addheading_archivelinks',
        get_string('archivelinksheading', 'report_myfeedback'), ''));

    $settings->add(new admin_setting_configtext('report_myfeedback/academicyeartext',
        get_string('settingsacademicyeartext', 'report_myfeedback'),
        get_string('academicyeartextinfo', 'report_myfeedback'),
        get_string('current_academic_year', 'report_myfeedback'), PARAM_RAW, 30));

    $settings->add(new admin_setting_configcheckbox('report_myfeedback/archivedinstance',
        get_string('archivedinstance', 'report_myfeedback'),
        get_string('archivedinstanceinfo', 'report_myfeedback'), '0'));

    $settings->add(new admin_setting_configselect('report_myfeedback/archivedyears',
        get_string('archiveyears', 'report_myfeedback'),
        get_string('archiveyearsinfo', 'report_myfeedback'), 0, $options));

    $config = get_config('report_myfeedback');

    // Loop through the archive links.
    for ($i = 1; $i <= $max; $i++) {
        // A description field for the archive URL.
        $settings->add(new admin_setting_configtext('report_myfeedback/archivelinktext' . $i,
            get_string('archivelinktext', 'report_myfeedback', $i),
            get_string('archivelinktextinfo', 'report_myfeedback', $i),
            '', PARAM_RAW, 30));

        // The archive URL.
        $settings->add(new admin_setting_configtext('report_myfeedback/archivelink' . $i,
            get_string('archivelink', 'report_myfeedback'),
            get_string('archivelinksettings' . $i, 'report_myfeedback'),
            '', PARAM_RAW, 50));
    }

    // Dept Admin dashboard Settings.
    $settings->add(new admin_setting_heading('report_myfeedback_addheading_courselimit',
                    get_string('courselimitheading', 'report_myfeedback'), ''));
    $settings->add(new admin_setting_configtext('report_myfeedback/courselimit', get_string('courselimit', 'report_myfeedback'),
                    get_string('courselimitsettings', 'report_myfeedback'), 200, PARAM_INT));

    // Overview tab Settings.
    $settings->add(new admin_setting_heading('report_myfeedback_addheading_overviewlimit',
        get_string('overviewlimitheading', 'report_myfeedback'), ''));
    $settings->add(new admin_setting_configtext('report_myfeedback/overviewlimit', get_string('overviewlimit', 'report_myfeedback'),
        get_string('overviewlimitsettings', 'report_myfeedback'), 10, PARAM_INT));

    // Student Record System.
    $settings->add(new admin_setting_heading('report_myfeedback_addheading_studentrecordsystemlink',
        get_string('studentrecordsystemlinkheading', 'report_myfeedback'), ''));
    $settings->add(new admin_setting_configtext('report_myfeedback/studentrecordsystemlink',
        get_string('studentrecordsystemlinktext', 'report_myfeedback'),
        get_string('studentrecordsystemlinksettings', 'report_myfeedback'), '', PARAM_RAW, 30));
    $settings->add(new admin_setting_configtext('report_myfeedback/studentrecordsystem',
        get_string('studentrecordsystemtext', 'report_myfeedback'),
        get_string('studentrecordsystemsettings', 'report_myfeedback'),
        get_string('studentrecordsystem', 'report_myfeedback'), PARAM_RAW, 30));

}
