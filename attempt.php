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
 * This script displays a particular page of a tcquiz attempt that is in progress.
 * Adapted from mod_quiz/attempt.php
 *
 * @package   quizaccess_tcquiz
 * @copyright 2024 Tamara Dakic @Capilano University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


use quizaccess_tcquiz\output\renderer;
use quizaccess_tcquiz\tcquiz_attempt;

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot.'/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/tcquiz/locallib.php');

require_sesskey();
global $CFG, $DB, $PAGE;

// Get submitted parameters.
$attemptid = required_param('attempt', PARAM_INT);
$page = required_param('page', PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);
$quizid = required_param('quizid', PARAM_INT);
$sessionid = required_param('sessionid', PARAM_INT);

//$attemptobj = quiz_create_attempt_handling_errors($attemptid, $cmid);
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
if (!empty($cmid) && $attemptobj->get_cmid() != $cmid) {
    throw new moodle_exception('invalidcoursemodule');
}

//make sure that the quiz is set up as a tcquiz
if (!$tcquiz = $DB->get_record('quizaccess_tcquiz', array('quizid' => $quizid))){
  throw new moodle_exception('nottcquiz', 'quizaccess_tcquiz', $attemptobj->view_url());
}

//make sure that the user has the right sessionid
if (!$tcquizsession = $DB->get_record('quizaccess_tcquiz_session', array('id' => $sessionid))){
  throw new moodle_exception('nosession', 'quizaccess_tcquiz', $attemptobj->view_url());
}
//if the state of the quiz is different than TCQUIZ_STATUS_SHOWQUESTION =  20 or TCQUIZ_STATUS_PREVIEWQUESTION = 15 defined in locallib.php
//they shouldn't be attempting the quiz page
if ($tcquizsession->status != TCQUIZ_STATUS_PREVIEWQUESTION  && $tcquizsession->status != TCQUIZ_STATUS_SHOWQUESTION){
  throw new moodle_exception('notrightquizstate', 'quizaccess_tcquiz', $attemptobj->view_url());

}
//they are trying to access a different page than what the DB is allowing
if ($tcquizsession->currentpage != $page){
  throw new moodle_exception('notcurrentpage', 'quizaccess_tcquiz', $attemptobj->view_url());
}

$url = new moodle_url('/mod/quiz/view.php', ['id' => $cmid]);
$PAGE->set_url($url);
$PAGE->set_cacheable(false);


require_login($attemptobj->get_course(), false, $attemptobj->get_cm());

// Check that this attempt belongs to this user.
if ($attemptobj->get_userid() != $USER->id) {
    if ($attemptobj->has_capability('mod/quiz:viewreports')) {
    } else {
        throw new moodle_exception('notyourattempt', 'quiz', $attemptobj->view_url());
    }
}

// Check capabilities and block settings.
if (!$attemptobj->is_preview_user()) {
    $attemptobj->require_capability('mod/quiz:attempt');
    if (empty($attemptobj->get_quiz()->showblocks)) {
        $PAGE->blocks->show_only_fake_blocks();
    }

} else {
    //navigation_node::override_active_url($attemptobj->start_attempt_url());
}

// Check the access rules.
$accessmanager = $attemptobj->get_access_manager(time());
$messages = $accessmanager->prevent_access();

//remove the message added by tcquiz
$key = array_search(get_string('accesserror', 'quizaccess_tcquiz'), $messages);
unset($messages[$key]);

if (!$attemptobj->is_preview_user() && $messages) {
    throw new \moodle_exception('attempterror', 'quiz', $attemptobj->view_url(),
            $output->access_messages($messages));
}


$output = $PAGE->get_renderer('quizaccess_tcquiz');

// Set up auto-save if required.
$autosaveperiod = get_config('quiz', 'autosaveperiod');

if ($autosaveperiod) {
    $PAGE->requires->yui_module('moodle-mod_quiz-autosave',
            'M.mod_quiz.autosave.init', [$autosaveperiod]);
}

// Log this page view.
$attemptobj->fire_attempt_viewed_event();

// Get the list of questions needed by this page.
$slots = $attemptobj->get_slots($page);

// Check if there are questions
if (empty($slots)) {
    throw new moodle_exception('noquestionsfound', 'quiz', $attemptobj->view_url());
}

// Initialise the JavaScript.
$headtags = $attemptobj->get_html_head_contributions($page);
$PAGE->requires->js_init_call('M.mod_quiz.init_attempt_form', null, false, quiz_get_js_module()); //for the flags
\core\session\manager::keepalive(); // Try to prevent sessions expiring during quiz attempts.

$PAGE->set_title($attemptobj->attempt_page_title($page));
$PAGE->add_body_class('limitedwidth');
$PAGE->set_heading($attemptobj->get_course()->fullname);
$PAGE->activityheader->disable();

$time_left_for_question = $tcquizsession->nextendtime - time();

if ($attemptobj->is_preview_user()){
  echo $output->tcq_teacher_attempt_page($attemptobj, $page, $slots, $sessionid, sesskey(), $time_left_for_question);
}
else {
  echo $output->tcq_attempt_page($attemptobj, $page, $slots, $sessionid, sesskey(), $time_left_for_question);
}
