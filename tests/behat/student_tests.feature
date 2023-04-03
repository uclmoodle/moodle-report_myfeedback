@mod @mod_feedback @report @report_myfeedback @student_tests @javascript
Feature: View my own feedback and comment / add notes as a student
  In order to follow my progress through the year
  As a student I need to view my grades and feedback for assessment activities.

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
      | student1 | C2        | student    |
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
      | student1   | C2 assignment  | 2      | tutor2 |
      | student2   | C2 assignment  | 2      | tutor2 |
      | student2   | C3 assignment  | 3      | tutor2 |
      | student3   | C3 assignment  | 2      | tutor3 |

  @javascript
  Scenario: Viewing your own My Feedback report as a student.
    When I log in as "student 1"
    # Click the user icon
    And I click on ".userbutton" "css_element"
    Then I should see "Profile"
    When I click on "Profile" "link"
    Then I should see "User details"
    When I click on "My feedback" "link"
    Then I should see "My feedback report for Student One"
    And I should see "C1_1 assignment"
    And I should see "C1_2 assignment"
    And I should see "C1_3 assignment"
    And I should see "C2 assignment"
    And "#grades.collapsed" "css_element" should not be visible
    And I should see "Bar graph"
    When I set the browser window to "800" x "800"
    Then "#grades.collapsed" "css_element" should be visible
    And I should not see "Bar graph"
    When I click on ".ellip.dtr-control" "css_element"
    Then I should see "Bar graph"

  @javascript
  Scenario: Reviewing own Feedback comments and adding notes.
    When I log in as "student 1"
    # Click the user icon
    And I click on ".userbutton" "css_element"
    Then I should see "Profile"
    When I click on "Profile" "link"
    Then I should see "User details"
    When I click on "My feedback" "link"
    Then I should see "My feedback report for Student One"
    When I click the tab titled "Feedback comments"
    Then I should see "Here you can view the general feedback from your assessments"
    # Click the link for the "C1_3 assignment"
    And I click on "#feedbackcomments tbody tr a:contains('C1_3 assignment')" "css_element"
    Then I should see "Assignment3 for course C1"
    And I should see "Student One"
    And I should see "Submission status"
    And I should see "Grading status"
    And I should see "Graded"
    And I should see "Grade"
    And I should see "Very good"
    When I press the "back" button in the browser
    Then I should see "My feedback report for Student One"
    When I change window size to "large"
    And I click on "Add notes" "link"
    Then I should see "Add/Edit notes"
    When I set the field "notename" to "This is a test note."
    And I click on "Save notes and close" "button"
    Then I should not see "Add/Edit notes"
    And I should see "This is a test note."
    And I should see "Edit notes"
    When I click on "Edit notes" "link"
    Then I should see "Add/Edit notes"
    When I set the field "notename" to "This is a very large test note that will repeat itself to make it bigger..This is a very large test note that will repeat itself to make it bigger..This is a very large test note that will repeat itself to make it bigger..This is a very large test note that will repeat itself to make it bigger..This is a very large test note that will repeat itself to make it bigger.."
    And I click on "Save notes and close" "button"
    And I wait until the page is ready
    Then I should not see "Add/Edit notes"
    And I should see "Edit notes"
    And I should see "This is a very large test note"
