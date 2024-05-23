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
 * This script deals with starting a new attempt of a tcquiz for students.
 *
* @package   quizaccess_tcquiz
* @copyright April 2024 Tamara Dakic @Capilano University
* @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/



/*  Adapted for tcquiz from mod_quiz startattempt.php by Tamara Dakic

    If a tcquiz attempt cannot be created the following errors are returned:
    (these errors should have been handeled by the form validation)

    -2  -the student tried joining a tcquiz session using the joincode, but a tcquiz_session with the
         joincode doesn't exist

    -3  -the student tried joining a tcquiz session using the joincode, but a tcquiz_session with the
         joincode has either not started or is finished

    If there is a current attempt that doesn't belong to the tcq session, the attempt is finished.
    (I assume that a student can have at most one open attempt)

*/


namespace quizaccess_tcquiz;

//use mod_quiz\quiz_attempt;
use mod_quiz\quiz_settings;

global $DB, $CFG, $PAGE;

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/tcquiz/locallib.php');
//for constants only (to finish a student attempt if needed)
//require_once($CFG->dirroot . '/mod/quiz/classes/quiz_attempt.php');

// Get submitted parameters.
$id = required_param('cmid', PARAM_INT); // Course module id
$page = optional_param('page', -1, PARAM_INT); // Page to jump to in the attempt.
$joincode = required_param('joincode', PARAM_ALPHANUM);
$forcenew = false; //this script is also used for students reconnecting to the quiz

$quizobj = quiz_settings::create_for_cmid($id, $USER->id);

$PAGE->set_url($quizobj->view_url());
$PAGE->set_cacheable(false);

// Check login and sesskey.
require_login($quizobj->get_course(), false, $quizobj->get_cm());
require_sesskey();
//$PAGE->set_heading($quizobj->get_course()->fullname);

// If no questions have been set up yet redirect to edit.php or display an error.
if (!$quizobj->has_questions()) {
    if ($quizobj->has_capability('mod/quiz:manage')) {
        redirect($quizobj->edit_url());
    } else {
        throw new \moodle_exception('cannotstartnoquestions', 'quiz', $quizobj->view_url());
    }
}

// Create an object to manage all the other (non-roles) access rules.
$timenow = time();
$accessmanager = $quizobj->get_access_manager($timenow);

$context = $quizobj->get_context();
$quiz = $quizobj->get_quiz();

$session = $DB->get_record("quizaccess_tcquiz_session", ['quizid' => $quiz->id,'joincode'=>$joincode]);


//student tried to join but either there was no tcquiz with such joincode, or the quiz was not running
if (!$session ){
    echo -2;
    return;
}
else if ($session->status == 0 || $session->status == 50) {
    echo -3;
    return;
}

// Validate permissions for creating a new attempt and start a new preview attempt if required.
list($currentattemptid, $attemptnumber, $lastattempt, $messages, $page) =
      quiz_validate_new_attempt($quizobj, $accessmanager, $forcenew, $page, false);

// Check access. OK if the only message is from TCQuiz
$key = array_search(get_string('accesserror', 'quizaccess_tcquiz'), $messages);
unset($messages[$key]);

if (!$quizobj->is_preview_user() && $messages) {
    throw new \moodle_exception('attempterror', 'quiz', $quizobj->view_url(),
            $output->access_messages($messages));
}

//This function checks if the $currentattemptid corresponds to an attempt of a TCQuiz. If yes, it returns $currentattemptid
//If no, the function closes the attempt whose id is $currentattemptid, creates a new quiz attempt and associates the
//quiz attempt with the TCQ attempt and TCQ session and returns its id
//PRE: $joincode is a code of running TCQ session

$attempt_id = setup_tcquiz_attempt($quizobj, $session, $currentattemptid, $joincode,$accessmanager,$attemptnumber, $lastattempt);

$url =  htmlspecialchars_decode(new \moodle_url('/mod/quiz/accessrule/tcquiz/wait_for_question.php',
  ['sessionid'=>$session->id, 'cmid' => $id, 'quizid'=> $quiz->id, 'attemptid'=>$attempt_id,  'sesskey' => sesskey()]));

header("Location: ". $url);
die();
