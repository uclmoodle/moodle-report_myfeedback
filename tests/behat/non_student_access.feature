# TODO: Investigate possibility of using language string variables in test steps.
@mod @mod_feedback @javascript
Feature: Tutors and administrators need access to grades and feedback to monitor student progress throughout the year
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
      | deptAdmin| Department| Admin      | admin@example.com    |
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
    And the user, "deptAdmin", is granted departmental admin rights for the courses:
      | coursename |
      | C1         |
      | C3         |
    And the following "scales" exist:
      | name           | scale                                     |
      | Feedback scale | Disappointing, Good, Very good, Excellent |
    And the following "activities" exist:
      | activity   | name          | intro                     | course | idnumber    | grade          |
      | assign     | C1 assignment | Assignment for course C1  | C1     | assign1     | Feedback scale |
      | assign     | C2 assignment | Assignment for course C2  | C2     | assign2     | Feedback scale |
      | assign     | C3 assignment | Assignment for course C3  | C3     | assign3     | Feedback scale |
    And the following grades have been awarded to students for assignments:
      | student    | assignment     | grade  | grader |
      | student1   | C1 assignment  | 1      | tutor1 |
      | student2   | C2 assignment  | 2      | tutor2 |
      | student2   | C3 assignment  | 3      | tutor2 |
      | student3   | C3 assignment  | 2      | tutor3 |

  @javascript
  Scenario: Module tutors need to be able to view grades of students taking courses they are recorded as a tutor for
    When I log in as "tutor1"
    And I navigate to the My feedback plugin page
    Then I should see a tab named "Module tutor dashboard"
    And I should see a tab named "My students"
    When I click the tab titled "My students"
    And I search for tutees using "%"
    Then I should see the following students listed:
      | username | email                | fullname    |
      | student1 | student1@example.com | Student One |

  @javascript
  Scenario: Personal tutors need to be able to view grades of their tutees. For instance, when a Personal Tutor is
  preparing for a meeting with a student during which the tutor wishes to discuss the student's performance
  (perhaps focusing on areas where they are doing well and where there is room for improvement.)
    When I log in as "tutor3"
    And I navigate to the My feedback plugin page
    Then I should see a tab named "Module tutor dashboard"
    And I should see a tab named "My students"
    When I click the tab titled "My students"
    And I search for tutees using "%"
    Then I should see the following students listed:
      | username | email                | fullname      |
      | student1 | student1@example.com | Student One   |
      | student3 | student3@example.com | Student Three |

  @javascript
  Scenario: A Departmental Admin wishes to log-in and check student performance across a cohort so that they can identify
  any potential students struggling (and subsequently raise these instances of concerning performance with module Tutors.)
    When I log in as "deptAdmin"
    And I navigate to the My feedback plugin page
    Then I should see a tab named "Departmental admin dashboard"