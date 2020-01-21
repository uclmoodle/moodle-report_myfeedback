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
 * Behat student-related step definitions for My feedback plugin acceptance tests.
 *
 * @package    report_myfeedback
 * @category   test
 * @copyright  2019 Segun Babalola
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode as TableNode,
    Behat\Mink\Exception\ExpectationException as ExpectationException;

/**
 * Student-related steps definitions.
 *
 * @package    report_myfeedback
 * @category   test
 * @copyright  2019 Segun Babalola
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_myfeedback extends behat_base {

    /**
     * @Given /^I have a Moodle account with the following details:$/
     */
    public function i_have_a_moodle_account_with_the_following_details(TableNode $table) {
        $this->execute("behat_data_generators::the_following_entities_exist", ['users', $table]);
    }

    /**
     * @Given /^the following grades have been awarded to students for assignments:$/
     */
    public function assign_grades_to_students_for_assignments(TableNode $table) {
        global $DB;
        $data = $table->getRows();

        $colummappings = $this->extract_column_mappings($data);

        if (is_array($data) && (count($data) > 1)) {
            for ($i=1; $i < count($data); $i++) {
                $usernamestudent = $data[$i][$colummappings['student']];
                $usernametutor = $data[$i][$colummappings['grader']];
                $assigmentname = $data[$i][$colummappings['assignment']];
                $assignedgrade = $data[$i][$colummappings['grade']];

                $studentid = $DB->get_field('user', 'id',['username' => $usernamestudent], MUST_EXIST);
                $tutorid = $DB->get_field('user', 'id',['username' => $usernametutor], MUST_EXIST);
                $assignmentid = $DB->get_field('assign', 'id',['name' => $assigmentname], MUST_EXIST);

                // Create grade_items record
                $gradeassignment = new stdClass();
                $gradeassignment->assignment = $assignmentid;
                $gradeassignment->grade = $assignedgrade;
                $gradeassignment->userid = $studentid;
                $gradeassignment->grader = $tutorid;

                $DB->insert_record('assign_grades', $gradeassignment);

                $gradeitemid = $DB->get_field('grade_items', 'id',['itemname' => $assigmentname], MUST_EXIST);

                // Create grade_grades record
                $gradeitem = new stdClass();
                $gradeitem->itemid = $gradeitemid;
                $gradeitem->userid = $studentid;
                $gradeitem->rawgrade = $assignedgrade;
                $gradeitem->finalgrade = $assignedgrade;
                $gradeitem->feedback = 'Behat test grade entry for ' . $assigmentname;
                $DB->insert_record('grade_grades', $gradeitem);

            }
        }

    }

    private function extract_column_mappings($table) {
        $mappings = [];
        if (is_array($table) && count($table)) {
            $columnheadings = $table[0];
            if (is_array($columnheadings)) {
                foreach ($columnheadings as $key => $col) {
                    $mappings[$col] = $key;
                }
            }
        }

        return $mappings;
    }

    /**
     * @Given /^the user, "(?P<username_string>(?:[^"]|\\")*)", is granted departmental admin rights for the courses:$/
     */
    public function user_is_granted_departmental_admin_rights($username, TableNode $courses) {
        // Create departmental admin role.
        $adminroledata[] = ['shortname', 'name', 'archetype'];
        $adminroledata[] = ['departmental_admin', 'Departmental Admin', 'manager'];

        $this->execute("behat_data_generators::the_following_entities_exist", ['roles', new TableNode($adminroledata)]);

        // Assign departmental admin role to the username.
        $roleassigndata[] = ['role', 'contextlevel', 'user', 'reference'];
        $roleassigndata[] = ['departmental_admin', 'System', $username, ''];
        $this->execute("behat_data_generators::the_following_entities_exist", ['role assigns',
            new TableNode($roleassigndata)
        ]);

        // Grant the departmental admin role permissions to the appropriate capability required by my feedback plugin.
        // I'm not convinced this is the ideal way to setup dept admin link to users so will revisit in future.
        // For now, will only check that the "Departmental admin dashboard" tab.
        $coursedata = $courses->getRows();
        $coursepermissions[] = ['capability', 'permission', 'role', 'contextlevel', 'reference'];

        if (is_array($coursedata) && (count($coursedata) > 1)){
            for ($i=1; $i<count($coursedata); $i++) {
                $coursepermissions[] = [
                    'report/myfeedback:progadmin',
                    'Allow',
                    'departmental_admin',
                    'Course',
                    $coursedata[$i][0]
                ];
            }

            $this->execute("behat_data_generators::the_following_entities_exist",['permission overrides',
                new TableNode($coursepermissions)
            ]);
        }

    }

    /**
     * @Given /^the following personal tutors are assigned the following tutees:$/
     *
     * @param TableNode $table
     * @throws Exception
     */
    public function the_following_personal_tutors_are_assigned_the_following_tutees(TableNode $table) {
        // Grant "report/myfeedback:personaltutor" to the personal tutor role
        $coursepermissions[] = ['capability', 'permission', 'role', 'contextlevel', 'reference'];
        $coursepermissions[] = ['report/myfeedback:personaltutor', 'Allow', 'personal_tutor', 'System', ''];

        $this->execute("behat_data_generators::the_following_entities_exist",['permission overrides',
            new TableNode($coursepermissions)
        ]);

        // Grant listed user accounts the personal tutor role
        $data[] = ['role', 'contextlevel', 'user', 'reference'];
        foreach ($table as $relationship) {
            $data[] = ['personal_tutor', 'User', $relationship['tutor'], $relationship['tutee']];
        }
        $this->execute("behat_data_generators::the_following_entities_exist", ['role assigns', new TableNode($data)]);
    }

    /**
     * @Given /^I should see the following students listed:$/
     *
     * @param TableNode $table
     * @throws Exception
     */
    public function i_should_see_the_following_students_listed(TableNode $table) {
        $expectedstudents = $table->getRows();
        $colummappings = $this->extract_column_mappings($expectedstudents);

        if (is_array($expectedstudents) && (count($expectedstudents) > 1)) {
            for ($i=1; $i < count($expectedstudents); $i++) {
                $studentfullname = $expectedstudents[$i][$colummappings['fullname']];
                $xpath = "//tr//td//a[contains(text(),'{$studentfullname}')]";

                if (!$this->getSession()->getDriver()->find($xpath)) {
                    throw new ExpectationException($studentfullname . ' is not listed (as expected)', $this->getSession());
                }
            }
        }
    }

    /**
     * Turns editing mode on.
     * @Given /^I navigate to the My feedback plugin page$/
     */
    public function i_navigate_to_the_My_feedback_plugin_page() {
        $url = new moodle_url('/report/myfeedback/index.php', []);
        $this->getSession()->visit($this->locate_path($url->out_as_local_url(false)));
    }

    /**
     * Check expected tab exists
     *
     * @Then /^I should see a tab named "(?P<tab_name_string>(?:[^"]|\\")*)"$/
     *
     * @throws ExpectationException
     * @throws \Behat\Mink\Exception\DriverException
     * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
     */
    public function i_should_see_a_tab_named($tabname) {
        $xpath = "//div//ul//li//a[contains(text(),'{$tabname}')]";
        if (!$this->getSession()->getDriver()->find($xpath)) {
            throw new ExpectationException("Cannot find tab named " . $tabname, $this->getSession());
        }
    }

    /**
     * Click through to a named tab
     *
     * @When /^I click the tab titled "(?P<tab_name_string>(?:[^"]|\\")*)"$/
     */
    public function i_click_tab_titled($tabtitle) {
        $this->execute("behat_general::click_link", $tabtitle);
    }

    /**
     * Submit "My Students" search form
     *
     * @When /^I search for tutees using "(?P<tab_name_string>(?:[^"]|\\")*)"$/
     */
    public function i_submit_search_form_For_students($crieria) {
        $searchbox = $this->find_field("searchu");
        $searchbox->setValue($crieria);
        $submit = $this->find_button('Search');
        $submit->press();
    }
}
