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
 * This page prints a review page of a particular tcquiz attempt
 *
 * It is used either by the student whose attempts this is, after the attempt,
 * or by a teacher reviewing another's attempt during or afterwards.
 *
 * based on review.php from mod_quiz by  Martin Dougiamas
 * @package   quizaccess_tcquiz
 * @copyright 2024 Tamara Dakic @ Capilano University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use quizaccess_tcquiz\output\renderer;
use quizaccess_tcquiz\tcquiz_attempt;

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/tcquiz/locallib.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/tcquiz/classes/tcquiz_attempt.php');


$attemptid = required_param('attempt', PARAM_INT);
$page      = optional_param('page', 0, PARAM_INT);
$showall   = optional_param('showall', null, PARAM_BOOL);
$cmid      = required_param('cmid', PARAM_INT);
$sessionid = required_param('sessionid', PARAM_INT);

require_sesskey();

$url = new moodle_url('/mod/quiz/view.php', ['id' => $cmid]);
$PAGE->set_url($url);
$PAGE->set_cacheable(false);
$PAGE->set_secondary_active_tab("modulepage");

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

// Check login.
require_login($attemptobj->get_course(), false, $attemptobj->get_cm());
$attemptobj->check_review_capability();

//make sure that the user is accessing a tcq session -- resend them to the start page otherwise
if (!$tcquizsession = $DB->get_record('quizaccess_tcquiz_session', array('id' => $sessionid))){
  throw new moodle_exception('nosession', 'quizaccess_tcquiz', $attemptobj->view_url());
}

//The state of the tcquiz should be TCQUIZ_STATUS_SHOWRESULTS = 30 defined in locallib.php
if ($tcquizsession->status != TCQUIZ_STATUS_SHOWRESULTS){
  throw new moodle_exception('notrightquizstate', 'quizaccess_tcquiz', $attemptobj->view_url());
}
//if they are trying to access a different page than what the DB is allowing
if ($tcquizsession->currentpage != $page){
  throw new moodle_exception('notcurrentpage', 'quizaccess_tcquiz', $attemptobj->view_url());
}

$attemptobj->preload_all_attempt_step_users();
$page = $attemptobj->force_page_number_into_range($page);

// Create an object to manage all the other (non-roles) access rules.
$accessmanager = $attemptobj->get_access_manager(time());
$accessmanager->setup_attempt_page($PAGE);

$options = $attemptobj->get_display_options(true);

// Check permissions - warning there is similar code in reviewquestion.php and
// quiz_attempt::check_file_access. If you change on, change them all.
if ($attemptobj->is_own_attempt()) {

    if (!$options->attempt) {
        $accessmanager->back_to_view_page($PAGE->get_renderer('quizaccess_tcquiz'),$attemptobj->cannot_review_message());
    }

} else if (!$attemptobj->is_review_allowed()) {
    throw new moodle_exception('noreviewattempt', 'quiz', $attemptobj->view_url());
}

// Save the flag states, if they are being changed.
if ($options->flags == question_display_options::EDITABLE && optional_param('savingflags', false,
        PARAM_BOOL)) {
    require_sesskey();
    $attemptobj->save_question_flags();
    //redirect($attemptobj->review_url(null, $page, $showall));
}

// Work out appropriate title and whether blocks should be shown.
if ($attemptobj->is_own_preview()) {
    navigation_node::override_active_url($attemptobj->start_attempt_url());

} else {
    if (empty($attemptobj->get_quiz()->showblocks) && !$attemptobj->is_preview_user()) {
        $PAGE->blocks->show_only_fake_blocks();
    }
}

// Set up the page header.
$headtags = $attemptobj->get_html_head_contributions($page, $showall);
$PAGE->set_title($attemptobj->review_page_title($page, $showall));
$PAGE->set_heading($attemptobj->get_course()->fullname);
$PAGE->activityheader->disable();

$slots = $attemptobj->get_slots($page);
$output = $PAGE->get_renderer('quizaccess_tcquiz');

echo $output->tcq_review_page($attemptobj, $slots, $page, $showall, $options, $sessionid, sesskey());
