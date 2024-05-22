@quizaccess @quizaccess_tcquiz @javascript
Feature: Test the basic functionality of the TCQuiz access rule
  In order to administer a TCQuiz
  As an teacher
  I need to choose to have a quiz be a TCQuiz.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "users" exist:
      | username | firstname |
      | teacher  | Teachy    |
      | student  | Study     |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
      | student | C1     | student        |

  @javascript
  Scenario: Start a regular quiz, not a TCQuiz. When a student attempts the quiz, the first quiz question is displayed.
    # Add a quiz to a course without the TCQ condition, and verify that students can start it as normal.
    Given I am on the "Course 1" "Course" page logged in as "teacher"
    And I turn editing mode on
    And I add a "Quiz" to section "1" and I fill the form with:
      | Name        | Not a TCQuiz                                   |
      | Description | This quiz is just a regular quiz |
    And I add a "True/False" question to the "Not a TCQuiz " quiz with:
      | Question name                      | First question               |
      | Question text                      | Is this the second question? |
      | Correct answer                     | False                        |
    And I log out
    And I am on the "Not a TCQuiz " "mod_quiz > View" page logged in as "student"
    When I press "Attempt quiz"
    Then I should see "Question 1"

    # Add a TCQuiz to a course, and verify that the student can't see the questions.
    When I log out
    Given I am on the "Course 1" "Course" page logged in as "teacher"
    And I turn editing mode on
    And I add a "Quiz" to section "1" and I fill the form with:
      | Name                  | TCQuiz                        |
      | Description           | This quiz is a TCQuiz         |
      | Administer TCQuiz     | Yes                           |
      | Default question time | 60                            |
    And I add a "True/False" question to the "TCQuiz" quiz with:
      | Question name                      | First question              |
      | Question text                      | Is this the first question? |
      | Correct answer                     | True                        |
    And I am on the "TCQuiz" "mod_quiz > View" page
    #As a teacher the TCQ start screen should be displayed
    Then I should see "Start new quiz session"
    When I log out
    And I am on the "TCQuiz" "mod_quiz > View" page logged in as "student"
    Then I should see "Wait until your teacher gives you the code."
    And "Join quiz" "button" should be visible

    # Test that backup and restore keeps the setting.
    When I log out
    Given I am on the "Course 1" "Course" page logged in as "teacher"
    And I turn editing mode on
    And I duplicate "TCQuiz" activity editing the new copy with:
      | Name | TCQuiz1 |
    #And I follow "TCQuiz1"
    #What is below is the same as the mod_quiz backup feature but it doesn't work
    #There seems to be a bug in that feature as the copy of the quiz (Quiz 2) should be
    #checked in line 32
    #When I log out
    #And I am on the "TCQuiz1" "mod_quiz > View" page logged in as "student"
    #Then I should see "Wait until your teacher gives you the code."
    When I am on the "TCQuiz1" "quiz activity editing" page logged in as "teacher"
    And I expand all fieldsets
    Then the "Administer TCQuiz" select box should contain "Yes"
