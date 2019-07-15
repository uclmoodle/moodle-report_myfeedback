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
 * Privacy Subsystem implementation for report_myfeedback.
 *
 * @package    report_myfeedback
 * @copyright  2019 Segun Babalola <segun@babalola.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_myfeedback\privacy;

defined('MOODLE_INTERNAL') || die();

use \core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;

/**
 * Privacy Subsystem for report_myfeedback providing metadata about user data stored by this plugin.
 *
 * @copyright  2019 Segun Babalola <segun@babalola.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    // This plugin has data.
    \core_privacy\local\metadata\provider,

    // This plugin currently implements the original plugin\provider interface.
    \core_privacy\local\request\plugin\provider
{

    /**
     * Declare/define the scope of data this plugin is responsible for.
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection
    {
        $collection->add_database_table(
            'report_myfeedback',
            [
                'userid' => 'privacy:metadata:report_myfeedback:userid',
                'notes' => 'privacy:metadata:report_myfeedback:notes',
                'feedback' => 'privacy:metadata:report_myfeedback:feedback',
                'coursefullname' => 'privacy:metadata:report_myfeedback:coursefullname',
                'timemodified' => 'privacy:metadata:report_myfeedback:timemodified',
                'gradeitemname' => 'privacy:metadata:report_myfeedback:gradeitemname',
            ],
            'privacy:metadata:report_myfeedback'
        );

        return $collection;
    }

    /**
     *  Returns the contexts that the report_myfeedback plugin stores data for the given userid.
     *  NOTE: From reviewing the code of this plugin, there is no explicit reference to contexts when storing data,
     *  this function there takes the view that since data is stored against userid, the user context is implicitly used.
     *  One consequence of this is that if a user has been deleted before the version of the plugin with this function is
     *  used, then there is a chance that data held for the deleted user will never be advertised as being held.
     *  IT IS THEREFORE THE RESPONSIBILITY OF THE INSTITUTION TO DELETE UNREACHABLE DATA WHEN THIS FUNCTION IS PUT IN PLACE.
     *
     *  An alternative is to simply return the system context (this is not the selected approach here).
     *
     * @param int $userid
     * @return contextlist
     * @throws \dml_exception
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $contextlist = new \core_privacy\local\request\contextlist();

        $user = $DB->get_record('user', array('id' => $userid), 'id');
        if ($usercontext = \context_user::instance($user->id, IGNORE_MISSING)) {
            $contextlist->add_user_context($userid);
        }

        return $contextlist;
    }

    /**
     * Returns the list of userids for whom the plugin stores data in the context supplied.
     * Note that although we have the userid in the context, we still need to check there is actually data stored for
     * the given user, hence the SQL.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context_ (userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_user) {
            return;
        }

        $params = ['useridfromcontext'    => $context->instanceid];

        $sql = "SELECT DISTINCT userid FROM {report_myfeedback} WHERE userid = :useridfromcontext";
        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function export_user_data(approved_contextlist $contextlist)
    {
        global $DB;

        // Confirm we're exporting data for the correct component.
        if ($contextlist->get_component() != 'report_myfeedback') {
            return;
        }

        // This plugin only handles user context data, so filter out anything else.
        $validcontexts = array_filter($contextlist->get_contexts(), function($context) {
            if ($context->contextlevel == CONTEXT_USER) {
                return $context;
            }
        });

        // Get user Ids
        $userids = array_map(function($context) {
            return $context->instanceid;
        }, $validcontexts);

        // Contruct SQL. Intentionally filtering on userid from two independent sources!
        // Note, we could also use "fb.userid = :userid" as record a filter here.
        list($insql, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $sql = "SELECT
            (SELECT fullname from {course} WHERE id=(SELECT courseid from {grade_items} WHERE id = fb.gradeitemid)) as fullname,
            (SELECT itemname from {grade_items} WHERE id = fb.gradeitemid) as gradeitemname,
            fb.* FROM {report_myfeedback} fb WHERE fb.userid " . $insql;

        $params['userid'] = $contextlist->get_user()->id;
        $records = $DB->get_records_sql($sql, $params);

        $outputrecords = [];
        foreach ($records as $record) {
            // Create user-level array entry (but only once).
            $context = \context_user::instance($record->userid);
            if (!isset($outputrecords[$record->userid])) {
                $outputrecords[$record->userid] = new \stdClass();
                $outputrecords[$record->userid]->context = $context;
            }

            // Populate user-level entry with child records.
            $outputrecords[$record->userid]->entries[] = [
                'userid' => $record->userid,
                'notes' => $record->notes,
                'feedback' => $record->feedback,
                'coursefullname' => format_string($record->fullname, true, ['context' => $context]),
                'timemodified' => \core_privacy\local\request\transform::datetime($record->timemodified),
                'gradeitemname' => $record->gradeitemname
            ];
        }

        foreach ($outputrecords as $r) {
            \core_privacy\local\request\writer::with_context($r->context)->export_data([get_string('privacy:exportpath', 'report_myfeedback')],
                (object) $r->entries);
        }

    }

    /**
     * Delete data for multiple (specified in $userlist) users in the single context specified.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users_(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if ($context instanceof \context_user) {
            list($usersql, $userparams) = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
            $select = "userid = :userid OR userid {$usersql}";
            $params = ['userid' => $context->instanceid] + $userparams;

            $DB->delete_records_select('report_myfeedback', $select, $params);
        }
    }

    /**
     * Delete data for every (unspecified) user in a given single context.
     * Since this plugin stores data in the user context, this is equivalent to deleting all data for a single user.
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        if ($context->contextlevel == CONTEXT_USER) {
            static::delete_single_user_data($context->instanceid);
        }
    }

    /**
     * Delete data for single (specified) user in a specified context.
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist)
    {
        if ($contextlist->get_component() != 'report_myfeedback') {
            return;
        }

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_COURSE) {
                // $context->instanceid and $contextlist->get_user()->id should have same value, so use either.
                static::delete_single_user_data($contextlist->get_user()->id);
            }
        }
    }

    private static function delete_single_user_data(int $userid) {
        global $DB;

        if (!empty($userid)) {
            $DB->delete_records('report_myfeedback', ['userid' => $userid]);
        }
    }

}