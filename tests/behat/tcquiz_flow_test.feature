@quizaccess @quizaccess_tcquiz @javascript
Feature: Test that the teacher can control a flow of a TCQuiz
  In order to complete a TCQuiz
  As an student
  I need to be answer the questions when they are presented by the teacher.

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
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype       | name  | questiontext               |
      | Test questions   | truefalse   | TF1   | Text of the first question |
    And the following "activities" exist:
        | activity | name   | intro              | course | idnumber | grade | navmethod  | tcqrequired | questiontime |
        | quiz     | Quiz 1 | Quiz 1 description | C1     | quiz1    | 100   | free       | 1           | 100          |
    And quiz "Quiz 1" contains the following questions:
      | question | page | maxmark |
      | TF1      | 1    |         |

  @javascript
  Scenario: Teacher creates a TCQuiz, starts it and displays the first question. The student joins the quiz
  and should see the first question.
  # The above background doesn't seem to set the quiz to be a TCQuiz
  When I am on the "Quiz 1" "quiz activity editing" page logged in as "teacher"
  And I expand all fieldsets
  And I set the field "Administer TCQuiz" to "Yes"
  And I click on "Save and display" "button"
  And I am on the "Quiz 1" "mod_quiz > View" page logged in as "teacher"
  Then I should see "Start new quiz session"
  And I set the field with xpath "//input[@type='text' and @id='id_joincode']" to "teachercode4"
  When I click on "Start new quiz session" "button"
  Then I should see "Waiting for students to connect"
  And I click on "Next >>" "button"
  #When I wait for the page to be loaded
  Then I should see "Text of the first question"
  When I log out
  And I am on the "Quiz 1" "mod_quiz > View" page logged in as "student"
  Then I should see "Wait until your teacher gives you the code."
  And "Join quiz" "button" should be visible
  And I set the field with xpath "//input[@type='text' and @id='id_joincode']" to "teachercode4"
  When I click on "Join quiz" "button"
  #When I wait for the page to be loaded
  Then I should see "Text of the first question"
  #And I click on "True" "radio" in the "TF1" "question"
  #And I click on "Submit" "button"
  #Then I should see "Question done - waiting for results."
