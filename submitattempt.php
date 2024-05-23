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
 * This script submits an attempt of a tcquiz for a student
 *
 *
 * @package   quizaccess_tcquiz
 * @copyright 2024 Tamara Dakic @Capilano University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use quizaccess_tcquiz\tcquiz_attempt;

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/tcquiz/locallib.php');


// Remember the current time as the time any responses were submitted
// (so as to make sure students don't get penalized for slow processing on this page).
$timenow = time();

// Get submitted parameters.
$attemptid     = required_param('attempt',  PARAM_INT);
$cmid          = optional_param('cmid', null, PARAM_INT);

try {
    $attemptobj = tcquiz_attempt::create($attemptid);
} catch (moodle_exception $e) {
    if (!empty($cmid)) {
        list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'quiz');
        $continuelink = new moodle_url('/mod/quiz/view.php', ['id' => $cmid]);
        $context = context_module::instance($cm->id);
        if (has_capability('mod/quiz:preview', $context)) {
            throw new moodle_exception('attempterrorcontentchange', 'quiz', $continuelink);
        } else {
            throw new moodle_exception('attempterrorcontentchangeforuser', 'quiz', $continuelink);
        }
    } else {
        throw new moodle_exception('attempterrorinvalid', 'quiz');
    }
}

// Check login.
require_login($attemptobj->get_course(), false, $attemptobj->get_cm());
require_sesskey();

// Check that this attempt belongs to this user.
if ($attemptobj->get_userid() != $USER->id) {
    throw new moodle_exception('notyourattempt', 'quiz', $attemptobj->view_url());
}
// Check capabilities.
if (!$attemptobj->is_preview_user()) {
    $attemptobj->require_capability('mod/quiz:attempt');
}
//make sure that the quiz is set up as a tcquiz
if (!$tcq = $DB->get_record('quizaccess_tcquiz', array('quizid' => $quizid))){
  throw new moodle_exception('nottcquiz', 'quizaccess_tcquiz', $attemptobj->view_url());
}
//make sure that the user has the right sessionid
if (!$tcquizsession = $DB->get_record('quizaccess_tcquiz_session', array('id' => $sessionid))){
  throw new moodle_exception('nosession', 'quizaccess_tcquiz', $attemptobj->view_url());
}
//if the state of the quiz is different than TCQUIZ_STATUS_FINALRESULTS  defined in locallib.php
//they shouldn't be submitting the quiz
if ($tcquizsession->status != TCQUIZ_STATUS_FINALRESULTS){
  throw new moodle_exception('notrightquizstate', 'quizaccess_tcquiz', $attemptobj->view_url());
}


// Set up auto-save if required.
$autosaveperiod = get_config('quiz', 'autosaveperiod');
if ($autosaveperiod) {
    $PAGE->requires->yui_module('moodle-mod_quiz-autosave',
            'M.mod_quiz.autosave.init', [$autosaveperiod]);
}
$attemptobj->process_auto_save($timenow);

// Process the attempt, getting the FINISHED status for the attempt.
$attemptobj->process_finish_tcq($timenow);
//return 1;
