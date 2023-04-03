@mod @mod_feedback @report @report_myfeedback @javascript @staff_tests
Feature: View a student record to see grades and feedback
  In order to support students through the year
  As a tutor (i.e. personal, or module tutor) or administrator
  I need to view grades and feedback for assessment activities based on my assignment to student, course or department.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname   | email                |
      | student1 | Student   | One        | student1@example.com |
      | student2 | Student   | Two        | student2@example.com |
      | student3 | Student   | Three      | student3@example.com |
      | tutor1   | Module    | Tutor1     | tutor1@example.com   |
      | tutor2   | Module    | Tutor2     | tutor2@example.com   |
      | tutor3   | Module    | Tutor3     | tutor3@example.com   |
      | deptadmin| Department| Admin      | admin@example.com    |
    And the following "categories" exist:
      | name                     | category | idnumber          |
      | Course admins Category   | 0        | report_myfeedback |
    And the following "courses" exist:
      | fullname | shortname | category           |
      | Course 1 | C1        | report_myfeedback  |
      | Course 2 | C2        | 0                  |
      | Course 3 | C3        | report_myfeedback  |
    And the following "course enrolments" exist:
      | user     | course    | role       |
      | student1 | C1        | student    |
      | student2 | C2        | student    |
      | student3 | C2        | student    |
      | student3 | C3        | student    |
      | tutor1   | C1        | teacher    |
      | tutor2   | C2        | teacher    |
      | tutor3   | C3        | teacher    |
    And the following "roles" exist:
      | shortname       | name           | archetype      |
      | personal_tutor  | Personal Tutor | editingteacher |
    And the following personal tutors are assigned the following tutees:
      | tutor  | tutee     |
      | tutor3 | student1  |
      | tutor3 | student3  |
    And the user, "deptadmin", is granted departmental admin rights for the courses:
      | coursename |
      | C1         |
      | C3         |
    And the following "scales" exist:
      | name           | scale                                     |
      | Feedback scale | Disappointing, Good, Very good, Excellent |
    And the following "activities" exist:
      | activity   | name          | intro                     | course | idnumber    | grade          |
      | assign     | C1_1 assignment | Assignment1 for course C1  | C1     | assign1_1     | Feedback scale |
      | assign     | C1_2 assignment | Assignment2 for course C1  | C1     | assign1_2     | Feedback scale |
      | assign     | C1_3 assignment | Assignment3 for course C1  | C1     | assign1_3     | Feedback scale |
      | assign     | C2 assignment | Assignment for course C2  | C2     | assign2     | Feedback scale |
      | assign     | C3 assignment | Assignment for course C3  | C3     | assign3     | Feedback scale |
    And the following grades have been awarded to students for assignments:
      | student    | assignment     | grade  | grader |
      | student1   | C1_1 assignment  | 1      | tutor1 |
      | student1   | C1_2 assignment  | 2      | tutor1 |
      | student1   | C1_3 assignment  | 3      | tutor1 |
      | student2   | C2 assignment  | 2      | tutor2 |
      | student2   | C3 assignment  | 3      | tutor2 |
      | student3   | C3 assignment  | 2      | tutor3 |

  @javascript
  Scenario: Click the Feedback comments tab and check that grades and feedback comments shown matches that previously given in the original assessment activity.
    When I log in as "tutor1"
    And I am on "Course 1" course homepage
    And I click the tab titled "Participants"
    Then I should see "2 participants found"
    When I click on "Student One" "link"
    Then I should see "User details"
    When I click on "My feedback" "link"
    Then I should see "tutor view"
    Then "C1_1 assignment" "link" should appear before "C1_2 assignment" "link"

    # Module tutors need to be able to click on the Assessment name column header to sort alphabetically
    When I click on "Assessment (part name)" "text"
    And I click on ".sorting_1 a" "css_element"
    Then I should see "C1_1 assignment"
    When I press the "back" button in the browser
    And I click on "Assessment (part name)" "text"
    And I click on ".sorting_1 a" "css_element"
    Then I should see "C1_3 assignment"

    # Search for a unique term in one of the assignments in the Search box and confirm this is displayed as expected.
    When I press the "back" button in the browser
    And I set the field "Search:" to "C1_1"
    Then I should see "Showing 1 to 1 of 1 entries"
    And I should see "(filtered from 3 total entries)"

    # Click the Feedback comments tab and check that grades and feedback comments shown matches that previously given in the original assessment activity.
    And I click the tab titled "Feedback comments"
    Then I should see "Here you can view the general feedback"

    # Click the Assessment column to order alphabetically
    When I click on "Assessment (part name)" "text"
    Then "Disappointing" "text" should appear before "Good" "text"
    And "Good" "text" should appear before "Very good" "text"

    # Check that the link to view feedback takes you to the feedback for that student in the correct assignment.
    When I click on "a:contains('view feedback')" "css_element"
    Then I should see "C1_1 assignment"
    And I should see "Student One"
    And I should see "Submission status"

  @javascript
  Scenario: View a class' My Feedback report.
    When I log in as "tutor1"
    And I navigate to the my feedback plugin page
    Then I should see "Module tutor dashboard"
    When I click on "#modSelect option[value=C1]" "css_element"
    And I click on "Analyse" "button"
    Then I should see "Overall grade"
    And I should not see "Student One"
    When I click on "Student breakdown" "text"
    Then I should see "Student One"

  @javascript
  Scenario: Find a student from Staff My Feedback dashboard view, review feedback comments and notes.
    When I log in as "tutor1"
    And I navigate to the my feedback plugin page
    Then I should see "Module tutor dashboard"
    When I click the tab titled "My students"
    Then I should see "Any personal tutees you are allocated"
    And I should not see "Student One"
    When I set the field "searchu" to "student1@example.com"
    And I click on "Search" "button"
    Then I should see "Student One"
    And I should see "C1"
    When I click on "Student One" "link"
    Then I should see "Student One"
    And I should see "(Year 1)"
    And I should see "Moodle Courses currently enrolled on: C1"
    When I click the tab titled "Feedback comments"
    Then I should see "Behat test grade entry for C1_1 assignment"

  @javascript
  Scenario: Department admin role.
    When I log in as "deptadmin"
    And I navigate to the my feedback plugin page
    Then I should see "Departmental admin dashboard"
    When I click the tab titled "Departmental admin dashboard"
    Then I should see "This dashboard shows an overview of assessments and students for all the Moodle courses within categories"
    When I select "Course admins Category" from the "deptselect" singleselect
    Then I should see "Second level:"
    When I select "Uncategorized" from the "progselect" singleselect
    Then I should see "Course 1"
    And I should see "Course 3"
    When I select "Course 1" from the "progmodselect" singleselect
    Then I should see "C1_1 assignment"
    Then I should not see "Student One"
    And ".tutor-inner.stuRec" "css_element" should not be visible
    When I click on "Student breakdown" "text"
    Then I should see "Student One"
    And ".tutor-inner.stuRec" "css_element" should be visible
