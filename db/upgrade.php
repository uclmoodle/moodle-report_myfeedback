<?php

/*
 * Upgrade this My feedback instance
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
	//optimise the log table
	if ($oldversion < 2018031100) {
		$table = new xmldb_table('logstore_standard_log');
		//create index mdl_logsstanlog_usecou_ix on mdl_logstore_standard_log (userid, courseid);
		$index = new xmldb_index('logsstanlog_usecou_ix', XMLDB_INDEX_NOTUNIQUE, array('userid', 'courseid'));
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
		
		// Myfeedback savepoint reached.
        upgrade_plugin_savepoint(true, 2018031100, 'report', 'myfeedback');
    }
    return true; //have to be in else get an unknown error
}