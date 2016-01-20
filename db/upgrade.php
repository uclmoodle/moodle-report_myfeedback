<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function xmldb_report_myfeedback_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2016011300) {

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

        // Adding keys to table report_myfeedback.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for report_myfeedback.
        if ($oldversion == 2015121700) {
            if ($dbman->table_exists($table)) {
                $dbman->drop_table($table);
            }
        }
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Myfeedback savepoint reached.
        upgrade_plugin_savepoint(true, 2016011300, 'report', 'myfeedback');
    }
    return true; //have to be in else get an unknown error
}
