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
 * This script is used by the teacher to start a tcquiz. The script is executed after the
 * mform that contains the joincode input text field and the start new quiz button and is
 * displayed by the description method of the rule class is validated. The validation checks
 * that the enetered joincode is not empty and that it has not already been used.
 * The script always creates a new preview as the teacher should click on the Rejoin button
 * on the mform if they want to rejoin an existing TCQ session and use an existing preview.
 * Therefore, $forcenew == true
 *
 * @package   quizaccess_tcquiz
 * @copyright April 2024 Tamara Dakic @Capilano University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_tcquiz;

//use mod_quiz\quiz_attempt;
use mod_quiz\quiz_settings;

global $DB, $CFG, $PAGE;

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot.'/mod/quiz/accessrule/tcquiz/locallib.php');
//require_once($CFG->dirroot . '/mod/quiz/classes/quiz_attempt.php');

// Get submitted parameters.
$id = required_param('cmid', PARAM_INT); // Course module id
$joincode = required_param('joincode', PARAM_ALPHANUM);
$quizid = optional_param('quizid', -1, PARAM_INT);

$forcenew = true;
$page = -1; // Page to jump to in the attempt - new attempt, so not relevant.

$quizobj = quiz_settings::create_for_cmid($id, $USER->id);

// This script should only ever be posted to, so set page URL to the view page.
$PAGE->set_url($quizobj->view_url());
// During quiz attempts, the browser back/forwards buttons should force a reload.
$PAGE->set_cacheable(false);
$PAGE->set_heading($quizobj->get_course()->fullname);


// Check login and sesskey.
require_login($quizobj->get_course(), false, $quizobj->get_cm());
require_sesskey();

if (!$quizobj->is_preview_user() || !$quizobj->has_capability('mod/quiz:manage')) {
    throw new \moodle_exception('cantstartquiz', 'quiz', $quizobj->view_url());
}

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

$tcquiz_session = $DB->get_record("quizaccess_tcquiz_session", ['quizid' => $quiz->id,'joincode'=>$joincode]);
//teacher wants to create a new session, but a session with the same name exists - this should have been
//handled by the form validation
if ($tcquiz_session){
    throw new \moodle_exception('sessionexisterror', 'quizaccess_tcquiz', $quizobj->view_url());
}

// Validate permissions for creating a new attempt and start a new preview attempt if required.
list($currentattemptid, $attemptnumber, $lastattempt, $messages, $page) =
    quiz_validate_new_attempt($quizobj, $accessmanager, $forcenew, $page, false);

list($new_attempt_id, $new_sess_id) = validate_and_start_teacher_tcq_attempt($quiz, $quizobj, $joincode, $lastattempt, $attemptnumber, $currentattemptid);
//$tcquiz_session = $DB->get_record("quizaccess_tcquiz_session", ['quizid' => $quiz->id,'joincode'=>$joincode]);

$url =  htmlspecialchars_decode(new \moodle_url('/mod/quiz/accessrule/tcquiz/wait_for_students.php',
  ['sessionid'=>$new_sess_id, 'joincode'=>$joincode, 'cmid' => $id, 'quizid'=> $quiz->id, 'attemptid'=>$new_attempt_id,  'sesskey' => sesskey()]));

header("Location: ". $url);
die();
