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
 * This script display the score for a student on a tcquiz.
 *
 * @package   mod_quiz
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_quiz\quiz_settings;
use quizaccess_tcquiz\tcquiz_attempt;
use html_writer;

define('NO_OUTPUT_BUFFERING', true);

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/mod/quiz/report/reportlib.php');



$attemptid = required_param('attemptid',PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);
$quizid = optional_param('quizid', 0, PARAM_INT);
$sessionid = optional_param('tcqsid', 0, PARAM_INT);

global $DB, $CFG, $PAGE;
//$mode = optional_param('mode', '', PARAM_ALPHA);
//TTT

//TTT add error handling
//$attemptobj = quiz_create_attempt_handling_errors($attemptid, $cmid);
$attemptobj = tcquiz_attempt::create($attemptid);

$cmid = $attemptobj->get_cmid();
require_login($attemptobj->get_course(), false, $attemptobj->get_cm());

// Check that this attempt belongs to this user.
if ($attemptobj->get_userid() != $USER->id) {
        throw new moodle_exception('notyourattempt', 'quiz', $attemptobj->view_url());
}

//make sure that the user that the quiz is configured as tcquiz
if (!$tcquizsession = $DB->get_record('quizaccess_tcquiz_session', array('id' => $sessionid))){
  //Add new exceptions
  throw new moodle_exception('nottcquiz', 'quizaccess_tcquiz', $attemptobj->view_url());
}

$marks = $attemptobj->get_sum_marks();

$quiz = $DB->get_record('quiz', array('id' => $quizid));

$multiplier = floatval($quiz->grade)/floatval($quiz->sumgrades);




$output = '';
$output .= html_writer::start_tag('h2');
$output .= "Your final score";
$output .= html_writer::end_tag('h2');
$output .= html_writer::start_tag('p');
$output .= "Your score is ". $marks * $multiplier. " / ".floatval($quiz->grade)." Your score will be changed by your teacher if they did
not ask all the questions in the quiz.";
$output .= html_writer::end_tag('p');

$process_url = new moodle_url('/mod/quiz/view.php',['id' => $cmid]);
$PAGE->set_url($process_url);

$output .= html_writer::start_tag('form',
          ['action' => $process_url,
                 'method' => 'post',
                  'enctype' => 'multipart/form-data', 'accept-charset' => 'utf-8',
                  'id' => 'responseform']);
$output .= html_writer::start_tag('div');

$output .= html_writer::empty_tag('input', ['type' => 'submit', 'name' => 'responseformsubmit',
'value' => 'Done', 'class' => 'mod_quiz-next-nav btn btn-primary', 'id' => 'responseformsubmit']);
$output .= html_writer::end_tag('form');

echo $OUTPUT->header();
echo $output;
echo $OUTPUT->footer();
