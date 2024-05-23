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
 * @copyright Davo Smith <moodle@davosmith.co.uk>
 * @package mod_tcquiz
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

define('AJAX_SCRIPT', true);


require_once('../../../../config.php');
global $CFG, $DB, $USER, $PAGE;

require_once($CFG->dirroot.'/mod/quiz/accessrule/tcquiz/locallib.php');
require_once($CFG->dirroot.'/mod/quiz/locallib.php');
require_once($CFG->libdir.'/filelib.php');


require_login();
require_sesskey();
$requesttype = required_param('requesttype', PARAM_ALPHA);
$quizid = required_param('quizid', PARAM_INT);
$attempt = optional_param('attempt', -1, PARAM_INT );
$joincode = optional_param('joincode', '', PARAM_ALPHANUM);
$cmid = optional_param('cmid', -1, PARAM_INT);
//$sessionid = optional_param('sessionid', -1, PARAM_INT);
$sessionid = required_param('sessionid', PARAM_INT);

/***********************************************************
 * start of main code
 ***********************************************************/



tcquiz_start_response();
//ttt
if ($requesttype == "getresults" || $requesttype == "finalresults"){
   //echo "<sleep> 1 </sleep>";
  //submits happen from js, so wait a bit
   //sleep(1);

}
//just for testing
echo "<quizid>".$quizid."</quizid>";

if (!$quiz = $DB->get_record("quiz", array('id' => $quizid))) {
    tcquiz_send_error("Quiz ID incorrect");
    tcquiz_end_response();
    die();
}
if (!$course = $DB->get_record("course", array('id' => $quiz->course))) {
    tcquiz_send_error("Course is misconfigured");
    tcquiz_end_response();
    die();
}
if (!$cm = get_coursemodule_from_instance("quiz", $quiz->id, $course->id)) {
    tcquiz_send_error("Course Module ID was incorrect");
    tcquiz_end_response();
    die();
}
if ($CFG->version < 2011120100) {
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
} else {
    $context = context_module::instance($cm->id);
}
$PAGE->set_context($context);

if (!has_capability('mod/quiz:attempt', $context)) {
    tcquiz_send_error(get_string('notallowedattempt', 'tcquiz'));
    tcquiz_end_response();
    die();
}

//get the id of the last session running
//SELECT id, MAX(timestamp) FROM quizaccess_tcquiz_session GROUP BY quizid
//$tcqid = $DB->execute("SELECT id FROM quizaccess_tcquiz_session ORDER BY timestamp DESC LIMIT 1",array('quizid' => $quizid));
//$tcqid = $DB->execute("SELECT id FROM quizaccess_tcquiz_session ORDER BY timestamp DESC LIMIT 1",array('quizid' => $quizid));
//$tcquiz = $DB->get_record_sql("SELECT * FROM {quizaccess_tcquiz_session} ORDER BY timestamp DESC LIMIT 1",array('quizid' => $quizid));
//if (!$tcquiz = $DB->get_record('quizaccess_tcquiz_session', array('joincode' => $joincode))){
if (!$tcquiz = $DB->get_record('quizaccess_tcquiz_session', array('id' => $sessionid))){
  tcquiz_send_error("TCQuiz Session incorrect");
  tcquiz_end_response();
  die();

}
echo "<tcquizid>".$tcquiz->id."</tcquizid>";
//$tcquiz = $DB->get_record("quizaccess_tcquiz", array('id' => $tcqid));

$status = $tcquiz->status;
echo '<rt_status>'.$status.'</rt_status>';

if ($status === false) {
    tcquiz_send_error(get_string('badquizid', 'tcquiz').$quizid);
}
else {

      switch ($status) {

          case TCQUIZ_STATUS_FINISHED:

          case TCQUIZ_STATUS_NOTRUNNING:   // Quiz is not running.
              tcquiz_send_not_running(); // We don't care what they asked for.
              break;

          case TCQUIZ_STATUS_READYTOSTART: // Quiz is ready to start.
              echo "<tcq_session_id>".$tcquiz->id."</tcq_session_id>";
              tcquiz_send_await_question();
              break;

          case TCQUIZ_STATUS_PREVIEWQUESTION: // Previewing question (send it out, but ask them to wait before showing).

              //break;

          case TCQUIZ_STATUS_SHOWQUESTION: // Question being displayed.

              echo '<status>showquestion</status>';
              echo '<url>';
              echo new moodle_url('/mod/quiz/accessrule/tcquiz/attempt.php',['page' => $tcquiz->currentpage, 'showall' => false, 'attempt' => $attempt,
                'quizid' => $quizid, 'cmid' => $cmid, 'sessionid' => $tcquiz->id, 'sesskey' => sesskey() ]);
              echo '</url>';

              break;

          case TCQUIZ_STATUS_SHOWRESULTS: // Results being displayed.

            echo '<status>showresults</status>';
            echo '<url>';
            echo new moodle_url('/mod/quiz/accessrule/tcquiz/review_tcq.php',['page' => $tcquiz->currentpage, 'showall' => 'false', 'attempt' => $attempt, 'quizid' => $quizid, 'cmid' => $cmid,
                                'sessionid' => $tcquiz->id, 'sesskey' => sesskey() ]);
            echo '</url>';

            /*      if ($requesttype == "getresults" ) {
                    echo '<status>showresults</status>';
                    echo '<url>';
                    echo new moodle_url('/mod/quiz/accessrule/tcquiz/review_tcq.php',['page' => $tcquiz->currentpage, 'showall' => 'false', 'attempt' => $attempt, 'quizid' => $quizid, 'cmid' => $cmid, 'sessionid' => $tcquiz->id ]);
                    echo '</url>';
                }

                else {
                  echo '<status>noaction</status>';
                }
*/
              break;

          case TCQUIZ_STATUS_FINALRESULTS: // Showing the final totals, etc.
              //$_GET["cmid"]=$cmid;
              $_GET["attemptid"]=$attempt;


              echo '<status>finalresults</status>';
              echo "<tcq_session_id>".$tcquiz->id."</tcq_session_id>";
              echo '<classresult>';
              include($CFG->dirroot.'/mod/quiz/accessrule/tcquiz/submitattempt.php');
              echo 'Well done!</classresult>';
              echo "<tcq_session_id>".$tcquiz->id."</tcq_session_id>";
              echo "<tcq_session_id>".$sessionid."</tcq_session_id>";
              echo '<url>';
              echo new moodle_url('/mod/quiz/accessrule/tcquiz/report_student_final_results.php',['attemptid' => $attempt, 'quizid' => $quizid, 'cmid' => $cmid, 'tcqsid' => $sessionid ]);
              echo '</url>';


              break;

          default:
              echo '<status>error</status>';
              tcquiz_send_error(get_string('incorrectstatus', 'tcquiz').$status.'\'');
              break;
        }
    }

tcquiz_end_response();
