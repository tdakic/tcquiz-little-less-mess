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
 * This dynamically sends quiz data to clients
 *
 * @copyright 2024, Tamara Dakic @Capilano University
 * based on quizdatateacher.php from the realtimequiz module by Davo Smith <moodle@davosmith.co.uk>
 * @package quizaccess_tcquiz
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

define('AJAX_SCRIPT', true);

use quizaccess_tcquiz\tcquiz_attempt;

global $CFG, $DB, $USER, $PAGE;

require_once('../../../../config.php');
require_once($CFG->dirroot.'/mod/quiz/accessrule/tcquiz/locallib.php');
require_once($CFG->dirroot.'/mod/quiz/locallib.php');
require_once($CFG->libdir.'/filelib.php');

require_login();
require_sesskey();
$requesttype = required_param('requesttype', PARAM_ALPHA);
$quizid = required_param('quizid', PARAM_INT);
$attempt = required_param('attempt', PARAM_INT );
$cmid = required_param('cmid', PARAM_INT);
$sessionid = required_param('sessionid', PARAM_INT);
//there is also PARAM_TEXT
/***********************************************************
 * start of main code
 ***********************************************************/

/* Getting the number of students who are attempting
the quiz and the number of submitted answers doesn't seem to be sensitive information and a lot of
DB querying is avoided if these checks are not here

if (!$quiz = $DB->get_record("quiz", array('id' => $quizid))) {
    tcquiz_start_response();
    tcquiz_send_error("Quiz ID incorrectt ".$quizid ."!!!!!");
    tcquiz_end_response();
    die();
}

if (!$tcquiz = $DB->get_record("quizaccess_tcquiz", array('quizid' => $quizid))){
  tcquiz_start_response();
  echo "<quizid>".$quizid."</quizid>";
  tcquiz_send_error("TCQuiz ID incorrectt");
  tcquiz_end_response();
  die();

}

if (!$session = $DB->get_record('quizaccess_tcquiz_session', array('quizid' => $quizid,'id' => $sessionid))){
  tcquiz_start_response();
  echo "<quizid>".$quizid."</quizid>";

  tcquiz_send_error("TCQuiz Session incorrect");
  tcquiz_end_response();
  die();
}

if (!$course = $DB->get_record("course", array('id' => $quiz->course))) {
    tcquiz_start_response();
    tcquiz_send_error("Course is misconfigured");
    tcquiz_end_response();
    die();
}
if (!$cm = get_coursemodule_from_instance("quiz", $quiz->id, $course->id)) {
    tcquiz_start_response();
    tcquiz_send_error("Course Module ID was incorrect");
    tcquiz_end_response();
    die();
}
if ($CFG->version < 2011120100) {
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
} else {
    $context = context_module::instance($cm->id);
}
$PAGE->set_context($context);/*

if (!has_capability('mod/quiz:preview', $context)) {
    tcquiz_start_response();
    tcquiz_send_error(get_string('notallowedattempt', 'tcquiz'));
    tcquiz_end_response();
    die();
}
/*************************************************/
try {
    $attemptobj = tcquiz_attempt::create($attempt);
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

if (!$attemptobj->is_preview_user()) {
    tcquiz_start_response();
    tcquiz_send_error(get_string('notauthorised', 'tcquiz'));
    tcquiz_end_response();
    die();
}

if ($requesttype == 'getquestion'){

      $rejoin = required_param('rejoin', PARAM_BOOL);

      if (!$tcquiz = $DB->get_record("quizaccess_tcquiz", array('quizid' => $quizid))){
        tcquiz_start_response();
        echo "<quizid>".$quizid."</quizid>";
        tcquiz_send_error("TCQuiz ID incorrectt");
        tcquiz_end_response();
        die();

      }

      if (!$session = $DB->get_record('quizaccess_tcquiz_session', array('quizid' => $quizid,'id' => $sessionid))){
        tcquiz_start_response();
        echo "<quizid>".$quizid."</quizid>";

        tcquiz_send_error("TCQuiz Session incorrect");
        tcquiz_end_response();
        die();
      }

      $page_slot =  $session->currentpage;

      if ($attemptobj->is_last_page($page_slot)){
        $session->status = TCQUIZ_STATUS_FINALRESULTS;
        $DB->update_record('quizaccess_tcquiz_session', $session); // FIXME - not update all fields?
        tcquiz_start_response();
        tcquiz_get_final_results($session->id, $cmid, $quizid);
        tcquiz_end_response();
        return;
      }

      if (!$rejoin){ //not rejoining so move to the next question

        $session->currentpage = $page_slot + 1; //The only way to move to the next page
        $page_slots = $attemptobj->get_slots($session->currentpage);
        $session->currentpagestate = 1; //running
        $session->status = TCQUIZ_STATUS_PREVIEWQUESTION;

        $session->nextendtime = time() + $tcquiz->questiontime;
        $DB->update_record('quizaccess_tcquiz_session', $session); // FIXME - not update all fields?
      }

      tcquiz_start_response();
      echo '<url>';
      $url = htmlspecialchars_decode(new moodle_url('/mod/quiz/accessrule/tcquiz/attempt.php',['page' => $session->currentpage, 'showall' => 0, 'attempt' => $attempt, 'quizid' => $quizid, 'cmid' => $cmid, 'sessionid' => $session->id, 'sesskey' => $USER->sesskey ]));
      echo $url;
      echo '</url>';
      tcquiz_end_response();
}


else if ($requesttype == 'getnumberstudents') {
    tcquiz_start_response();
    echo '<status>updatenumberstudents</status>';
    tcquiz_number_students($quizid, $sessionid);
    tcquiz_end_response();
}

else if ($requesttype == 'getnumberanswers') {
    tcquiz_start_response();
    echo '<status>updatenumberanswers</status>';
    $page_slots = $attemptobj->get_slots($session->currentpage);
    $firstpagequestion = $page_slots[0];
    //one submit button per page, so count how many students submitted the first question on the page
    tcquiz_get_number_of_answers($session->id, $firstpagequestion);
    tcquiz_end_response();
}
else {
  tcquiz_start_response();
  tcquiz_send_error(get_string('incorrectrequestype', 'tcquiz').$requesttype.'\'');
  tcquiz_end_response();
}
