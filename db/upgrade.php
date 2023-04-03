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
 * Upgrading the database.
 *
 * @package   report_myfeedback
 * @copyright 2022 UCL
 * @author    Jessica Gramp <j.gramp@ucl.ac.uk> or <jgramp@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade this My feedback instance
 *
 * @param int $oldversion The old version of the My feedback report
 * @return bool
 */
function xmldb_report_myfeedback_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2016031700) {

        // Define table report_myfeedback to be created.
        $table = new xmldb_table('report_myfeedback');

        // Adding fields to table report_myfeedback.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('gradeitemid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('modifierid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('iteminstance', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('notes', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('feedback', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table report_myfeedback.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for report_myfeedback.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        if ($oldversion == 2016012100) {
            $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $dbman->add_field($table, $field);
        }

        // Myfeedback savepoint reached.
        upgrade_plugin_savepoint(true, 2016031700, 'report', 'myfeedback');
    }
    // Optimise the log table.
    if ($oldversion < 2018031100) {
        $table = new xmldb_table('logstore_standard_log');
        $index = new xmldb_index('logsstanlog_usecou_ix', XMLDB_INDEX_NOTUNIQUE, array('userid', 'courseid'));
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Myfeedback savepoint reached.
        upgrade_plugin_savepoint(true, 2018031100, 'report', 'myfeedback');
    }

    return true;
}
