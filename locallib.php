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
 * Internal functions
 *
 * @package   mod_tcquiz
 * @copyright 2014 Davo Smith
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(__DIR__ . '/../../../../config.php');
global $CFG;


// Read the JSON file with tcq constants instead defining constants below
// So the same values can be used in JS and php
//$json = file_get_contents('./tcq_constants.json');
$json = file_get_contents($CFG->dirroot.'/mod/quiz/accessrule/tcquiz/tcq_constants.json');
$const_data = json_decode($json,true);

foreach($const_data as $key => $value){
  define($key,$value);
}

//echo(TCQUIZ_STATUS_READYTOSTART);
//echo(TCQUIZ_STATUS_PREVIEWQUESTION);

/** Quiz not running */
//define('TCQUIZ_STATUS_NOTRUNNING', 0);
/** Quiz ready to start */
//define('TCQUIZ_STATUS_READYTOSTART', 10);
/** Quiz showing 'review question' page */
//define('TCQUIZ_STATUS_PREVIEWQUESTION', 15);
/** Quiz showing a question */
//define('TCQUIZ_STATUS_SHOWQUESTION', 20);
/** Quiz showing results */
//define('TCQUIZ_STATUS_SHOWRESULTS', 30);
/** Quiz showing the final results */
//define('TCQUIZ_STATUS_FINALRESULTS', 40);
//define('TCQUIZ_STATUS_FINISHED', 50);

/**
 * Output the response start
 */
function tcquiz_start_response() {
    header('content-type: text/xml');
    echo '<?xml version="1.0"?><tcquiz>';

}

/**
 * Output the response end
 */
function tcquiz_end_response() {
    echo '</tcquiz>';
}

/**
 * Send the given error messsage
 * @param string $msg
 */
function tcquiz_send_error($msg) {
    echo "<status>error</status><message><![CDATA[{$msg}]]></message>";
}

/**
 * Count the number of students connected
 * @param int $quizid
 * @throws coding_exception
 * @throws dml_exception
 */
function tcquiz_number_students($quizid,$sessionid) {

    global $CFG, $DB, $USER;

    $quizid = required_param('quizid', PARAM_INT);
    //get open attempts that are tcqattempts associated with the joincode
    //$attempts = $DB->get_records_sql("SELECT id FROM {quiz_attempts} where quiz={$quizid} AND state='inprogress' AND preview=0");
    $attempts = $DB->get_records_sql("SELECT id FROM {quizaccess_tcquiz_attempt} where sessionid={$sessionid}");
    $num_students = sizeof($attempts);
    echo "<numberstudents>";
    //the teacher attempt should not count
    echo $num_students-1;
    echo "</numberstudents>";

}


/**
 * Send 'quiz running' status.
 */
function tcquiz_send_running() {
    echo '<status>waitforquestion</status>';

}

/**
 * Send 'quiz not running' status.
 */
function tcquiz_send_not_running() {
    echo '<status>quiznotrunning</status>';
}

/**
 * Send 'waiting for question to start' status.
 * @throws dml_exception
 */
function tcquiz_send_await_question() {
    //TTT
    $waittime = get_config('tcquiz', 'awaittime');
    //$waittime = 10;
    $waittime = 2;
    echo '<status>waitforquestion</status>';
    echo "<waittime>{$waittime}</waittime>";
    //echo add_requesttype($requesttype);
}

/**
 * Send 'waiting for results' status.
 * @param int $timeleft
 * @throws dml_exception
 */
function tcquiz_send_await_results($timeleft) {
    $waittime = (int)get_config('tcquiz', 'awaittime');
    // We need to randomise the waittime a little, otherwise all clients will
    // start sending 'waitforquestion' simulatiniously after the first question -
    // it can cause a problem is there is a large number of clients.
    // If waittime is 1 sec, there is no point to randomise it.
    $waittime = 2;
    // TTT
    //$waittime = mt_rand(1, $waittime) + $timeleft;
    echo '<status>waitforresults</status>';
    echo "<waittime>{$waittime}</waittime>";
}



/**
 * Is the quiz currently running?
 * @param int $status
 * @return bool
 */
function tcquiz_is_running($status) {
    return ($status > TCQUIZ_STATUS_NOTRUNNING && $status < TCQUIZ_STATUS_FINALRESULTS);
}

function tcquiz_get_final_results($sessionid,$cmid,$quizid){
  global $CFG;
  //$quiz->status = TCQUIZ_STATUS_FINALRESULTS;
  //$DB->update_record('tcquiz', $quiz); // FIXME - not update all fields?

  //tcquiz_send_final_results($quizid);
  //$url = htmlspecialchars_decode(new moodle_url('/mod/quiz/accessrule/tcquiz/attempt.php',['page' => $session->currentpage, 'showall' => 0, 'attempt' => $attempt, 'quizid' => $quizid, 'cmid' => $cmid, 'sessionid' => $session->id, 'sesskey' => $USER->sesskey ]));

  sleep(2);
  tcquiz_start_response();
  echo '<status>finalresults</status>';
  echo '<classresult>';
  $_GET["id"]=$cmid;
  $_GET["tcqsid"]=$sessionid;
  //$_GET["mode"]="no-cors";
  $_GET["mode"]="overview";
  echo "<url>";
  echo htmlspecialchars_decode(new moodle_url('/mod/quiz/accessrule/tcquiz/report_final_results.php',[ 'id' =>$cmid, 'tcqsid' => $sessionid, 'quizid'=>$quizid ]));
  //echo $CFG->dirroot.'/mod/quiz/accessrule/tcquiz/report_final_results.php?mode=overview&id='.$cmid.'&tcquiz='.$sessionid;
  echo "</url>";
  //$tmp = include($CFG->dirroot.'/mod/tcquiz/report_final_results.php');
  //$tmp = include('./report_final_results.php');
  //echo $tmp;
  echo '</classresult>';
  tcquiz_end_response();

}


function tcquiz_get_number_of_answers($sessoinid, $slot){
  global $DB;
  // look if the first slot on the page is submitted. Good enough?
/* original
  $sql = "SELECT COUNT(*) FROM {question_attempts} tcqa
              LEFT JOIN {quiz_attempts} qata ON qata.uniqueid = tcqa.questionusageid
              LEFT JOIN {quizaccess_tcquiz_attempt} tcta ON tcta.attemptid=qata.id
              LEFT JOIN {question_attempt_steps} tctas ON tcqa.id = tctas.questionattemptid
              WHERE tcta.sessionid=:sessionid AND tcqa.slot = :slot AND tctas.state = 'complete'" ;
*/

$sql = "SELECT COUNT(DISTINCT questionattemptid) FROM {question_attempts} tcqa
            LEFT JOIN {quiz_attempts} qata ON qata.uniqueid = tcqa.questionusageid
            LEFT JOIN {quizaccess_tcquiz_attempt} tcta ON tcta.attemptid=qata.id
            LEFT JOIN {question_attempt_steps} tctas ON tcqa.id = tctas.questionattemptid
            WHERE tcta.sessionid=:sessionid AND tcqa.slot = :slot AND (tctas.state = 'complete' OR tctas.state = 'gaveup' OR tctas.state = 'invalid')" ;

  $count = $DB->count_records_sql($sql, array('sessionid'=>$sessoinid, 'slot'=>$slot));

  //$attempts = $DB->get_records_sql("SELECT id FROM {quizaccess_tcquiz_attempt} where sessionid={$sessionid}");
  //$num_students = sizeof($attempts);
  echo "<numanswers>".$count."</numanswers>";

}

//***************************************************************************
//might not be needed now but useful in some form in the future

function add_requesttype($requesttype)
{
  echo "<requesttype>";
  echo $requesttype;
  echo "</requesttype>";
}

/**
 * Check the question requested matches the current question.
 * @param int $quizid
 * @param int $questionnumber
 * @return bool
 * @throws dml_exception
 */
/*function tcquiz_current_question($quizid, $questionnumber) {
    global $DB;


    $questionid = $DB->get_field('quizaccess_tcquiz_session', 'currentquestion', array('id' => $quizid));
    if (!$questionid) {
        return false;
    }
  if ($questionnumber != $questionid) {
        return false;
    }


    return true;
}
*/


function tcquiz_number_of_questions_in_quiz($quizid){
  global $DB;

  $questioncount = $DB->count_records('quiz_slots', ['quizid' => $quizid]);
  echo "<questioncount>{$questioncount}</questioncount>";

}


//************************************************************************

function close_running_tcq_session($quiz){

  //***************************************
  global $DB;
  global $USER;
  // check to see if an open attempt of the teacher is a tcquiz attempt
  $sql = "SELECT * FROM {quizaccess_tcquiz_attempt} qta
                  LEFT JOIN {quiz_attempts} qa ON qta.attemptid = qa.id
                  WHERE qa.state = 'inprogress' AND qa.quiz = :quizid AND qa.userid = :uid";

  //$sess = $DB->get_record_sql($sql, ['quizid' => $this->quiz->id]);

  if ($attempt = $DB->get_record_sql($sql, ['quizid' => $quiz->id, 'uid' => $USER->id])){
    //get the session assocaited with the teacher's attempt and set its status to 50
    //Should be between 10 and 40 !!!!!!!!!!
    $sql = "SELECT * FROM {quizaccess_tcquiz_session} WHERE id = :sessid AND status BETWEEN :running and :results";
    if ($sess = $DB->get_record_sql($sql, ['sessid' => $attempt->sessionid, 'running'=>TCQUIZ_STATUS_READYTOSTART, 'results'=> TCQUIZ_STATUS_FINALRESULTS])){
        $sess->status = TCQUIZ_STATUS_FINISHED;
        $DB->update_record('quizaccess_tcquiz_session', $sess);
        return $attempt->sessionid;
    }
  }
  return 0;
}


function create_new_tcq_session($joincode,$quiz){

  global $DB, $USER;

  // for now there should only be one open TCQ session

  close_all_tcq_sessions();

  $session = new stdClass();
  $session->timestamp = time();
  $session->joincode = $joincode;
  $session->quizid = $quiz->id;

  $session->status = TCQUIZ_STATUS_READYTOSTART;
  //$session->currentquestion = 0;
  $session->currentpage = -1;
  $session->currentpagestate = 1;
  $session->teacherid = $USER->id;
  $session->id = $DB->insert_record('quizaccess_tcquiz_session', $session);

  return $session->id;

}

function create_new_tcq_attempt($sessid, $new_attempt_id){

  global $DB;

  $sessatempt = new stdClass();
  $sessatempt->sessionid = $sessid;
  $sessatempt->attemptid = $new_attempt_id;
  $sessatempt->id = $DB->insert_record('quizaccess_tcquiz_attempt', $sessatempt);

  return $sessatempt->id;

}

function validate_and_start_teacher_tcq_attempt($quiz, $quizobj, $joincode, $lastattempt, $attemptnumber, $currentattemptid){
  close_running_tcq_session($quiz);
  //close_all_tcq_sessions();
  //finish the current teacher attempt
  if ($currentattemptid){
    $lastattempt->state = quiz_attempt::FINISHED;
  }
  $new_attempt = quiz_prepare_and_start_new_attempt($quizobj, $attemptnumber, $lastattempt);

  $sessid = create_new_tcq_session($joincode,$quiz);

  $sessattempt_id = create_new_tcq_attempt($sessid,$new_attempt->id);

  return [$new_attempt->id, $sessid];
}

//This function checks if the $currentattemptid corresponds to an attempt of a TCQuiz. If yes, it returns $currentattemptid
//If no, the function closes the attempt whose id is $currentattemptid, creates a new quiz attempt and associates the
//quiz attempt with the TCQ attempt and TCQ session and returns its id
//PRE: $joincode is a code of running TCQ session
function setup_tcquiz_attempt($quizobj, $session, $currentattemptid, $joincode, $accessmanager, $attemptnumber, $lastattempt)
{
  global $DB;

  //if $currentattemptid exists, there is an open student attempt. If that attempt doesn't correspond to the one with the joincode close it, else return the
  //currentattemptid
  if ($currentattemptid){
   // need to check if the currentattemptid is in quizaccess_tcquiz_attempt
    $tcqattempt = $DB->get_record("quizaccess_tcquiz_attempt", ['attemptid' => $currentattemptid]);

    if (!$tcqattempt || $tcqattempt->sessionid != $session->id){
      //finish that attempt
      //$unfinishedattempt = quiz_get_user_attempt_unfinished($quiz->id, $USER->id)


      $unfinishedattempt = $DB->get_record("quiz_attempts", ['id' => $currentattemptid]);
      $unfinishedattempt->state = mod_quiz\quiz_attempt::FINISHED;
      $unfinishedattempt->timefinish = time();
      $DB->update_record("quiz_attempts", $unfinishedattempt);

      //start afresh
      $forcenew=true;
      $page = -1;
      list($currentattemptid, $attemptnumber, $lastattempt, $messages, $page) =
          quiz_validate_new_attempt($quizobj, $accessmanager, $forcenew, $page, false);
    }
    else {
      // found an attempt that has the required joincode and and is a tcqattempt
      //echo $currentattemptid." ".$tcqattempt-> sessionid;
      //echo $currentattemptid;
    //  $url =  htmlspecialchars_decode(new moodle_url('/mod/quiz/accessrule/tcquiz/wait_for_question.php',
    //    ['joincode'=>$joincode, 'cmid' => $id, 'quizid'=> $quiz->id, 'attemptid'=>$attempt->id,  'sesskey' => sesskey()]));
      return $currentattemptid;
      //return;

    }

  }

  $attempt = quiz_prepare_and_start_new_attempt($quizobj, $attemptnumber, $lastattempt);
  create_new_tcq_attempt($session->id, $attempt->id);
  return $attempt->id;
}


/* for now there should only be one open TCQ session */
function close_all_tcq_sessions(){
  global $DB;
  $sql = "SELECT * FROM {quizaccess_tcquiz_session}";
  $allSession = $DB->get_records_sql($sql, []);
  foreach ( $allSession as $s){
    //$sess = $DB->get_record('quizaccess_tcquiz_session', array('id' => intval($s)));
    if ($s->status != TCQUIZ_STATUS_FINISHED){
      $s->status = TCQUIZ_STATUS_FINISHED;
      $DB->update_record('quizaccess_tcquiz_session', $s);

    }
  }
}
