# TODO: Investigate possibility of using language string variables in test steps.
@mod @mod_feedback @javascript
Feature: Students checking their own academic progress throughout the year
  In order to give me an accurate and holistic view of my strengths, weaknesses, academic performance and progress
  As a student
  I need to view grades and feedback for assessment activities across modules in a single-view report.

  Background:
    Given I have a Moodle account with the following details:
      | username | firstname | lastname | email                |
      | student1 | First     | Student  | student1@example.com |
      | tutor1   | Module    | Tutor1     | tutor1@example.com   |
    And the following "categories" exist:
      | name                     | category | idnumber          |
      | Course admins Category   | 0        | report_myfeedback |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
      | tutor1   | C1     | teacher |
    And the following "scales" exist:
      | name           | scale                                     |
      | Feedback scale | Disappointing, Good, Very good, Excellent |
    And the following "activities" exist:
      | activity   | name          | intro                     | course | idnumber    | grade          |
      | assign     | C1 assignment | Assignment for course C1  | C1     | assign1     | Feedback scale |
    And the following grades have been awarded to students for assignments:
      | student    | assignment     | grade  | grader |
      | student1   | C1 assignment  | 1      | tutor1 |

  @javascript
  Scenario: Access my personalised My feedback report
    When I log in as "student1"
    And I navigate to the My feedback plugin page
    Then I should see a tab named "Overview"
    And I should see a tab named "Feedback comments"

  @javascript
  Scenario: A student is preparing for a meeting with a Career Advisor or Personal Tutor, and the student wishes to use
  the comments field to reflect on employability before the meeting.
    When I log in as "student1"
    And I navigate to the My feedback plugin page
    And I should see a tab named "Feedback comments"
    When I click the tab titled "Feedback comments"
    Then I should see "C1 assignment" in the "feedbackcomments" "table"
    And I should see "Add notes" in the "feedbackcomments" "table"