@quizaccess @quizaccess_tcquiz @javascript
Feature: Test that the student needs the right code to join a TCQuiz
  In order to join a TCQuiz
  As an student
  I need to know the code set up by the teacher.

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


  @javascript
  Scenario: Teacher creates a TCQuiz and starts it. The student can't see the question if they don't know the code.
  #commented code doesn't work. Why?
  # Given the following "activities" exist:
  #  | activity   | name   | intro              | course | idnumber | tcquizrequired | questiontime |
  #  | quiz       | Quiz 1 | Quiz 1 description | C1     | quiz1    | 1              | 100          |
  # And quiz "Quiz 1" contains the following questions:
  #  | question | page |
  #  | TF1      | 1    |
  # When I am on the "Quiz 1" "mod_quiz > View" page logged in as "student"
  # Then I should see "Wait until your teacher gives you the code."
  #
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
  When I log out
  And I am on the "TCQuiz" "mod_quiz > View" page logged in as "student"
  Then I should see "Wait until your teacher gives you the code."
  And "Join quiz" "button" should be visible
  And I set the field with xpath "//input[@type='text' and @id='id_joincode']" to "mycode"
  When I click on "Join quiz" "button"
  Then I should see "-Wrong join code. Try again."

  #teacher sets the joincode
  When I log out
  And I am on the "TCQuiz" "mod_quiz > View" page logged in as "teacher"
  Then I should see "Start new quiz session"
  And "Start new quiz session" "button" should be visible
  And I set the field with xpath "//input[@type='text' and @id='id_joincode']" to "teachercode"
  When I click on "Start new quiz session" "button"
  Then I should see "Waiting for students to connect"

  #student tries to join with the wrong code
  When I log out
  And I am on the "TCQuiz" "mod_quiz > View" page logged in as "student"
  Then I should see "Wait until your teacher gives you the code."
  And "Join quiz" "button" should be visible
  And I set the field with xpath "//input[@type='text' and @id='id_joincode']" to "mycode"
  When I click on "Join quiz" "button"
  Then I should see "-Wrong join code. Try again."

  #student tries to join with the right code
  When I set the field with xpath "//input[@type='text' and @id='id_joincode']" to "teachercode"
  When I click on "Join quiz" "button"
  Then I should see "Waiting for the first question to be sent"

  #teacher's view should see that one student joined
  When I log out
  And I am on the "TCQuiz" "mod_quiz > View" page logged in as "teacher"
  Then I should see "Available session"
  And "Rejoin" "button" should be visible
  When I click on "Rejoin" "button"
  Then I should see "Waiting for students to connect"
  And I should see "Number of connected students 1"
