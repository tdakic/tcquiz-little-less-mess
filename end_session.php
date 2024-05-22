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
 * This script ends a tcq session and all attempts associated with it.
 * It will put the student attempts in the FINISHED state
 *
 * @package   quizaccess_tcquiz
 * @copyright 2024 Tamara Dakic @Capilano University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_tcquiz;

use mod_quiz\quiz_settings;
use mod_quiz\quiz_attempt;

require_once(__DIR__ . '/../../../../config.php');
//for constants only
require_once($CFG->dirroot . '/mod/quiz/accessrule/tcquiz/locallib.php');

global $DB, $USER;

$sessionid = required_param('id', PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);

$quizobj = quiz_settings::create_for_cmid($cmid, $USER->id);

//make sure that the user has a valid sessionid
if (!$session = $DB->get_record('quizaccess_tcquiz_session', array('id' => $sessionid))){
  throw new moodle_exception('nosession', 'quizaccess_tcquiz', $quizobj->view_url());
}

//make sure that the user is the owner of the session
if (!$quizobj->is_preview_user() || $session->teacherid != $USER->id){
      throw new moodle_exception('notyoursession', 'quizaccess_tcquiz', $quizobj->view_url());
}

// finish the session
$session->status = TCQUIZ_STATUS_FINISHED;
$DB->update_record('quizaccess_tcquiz_session', $session);

$sql = "SELECT * FROM {quizaccess_tcquiz_attempt} qta WHERE sessionid = :sessionid";
$attemptids = $DB->get_records_sql($sql, ['sessionid' => $sessionid]);

//close al attempts associated with the session - includes STUDENT attempts
foreach($attemptids as $attemptid){
    $attempt = $DB->get_record('quiz_attempts', array('id' => intval($attemptid->attemptid)));
    if ( $attempt && $attempt->state != \mod_quiz\quiz_attempt::FINISHED){
      $attempt->state = \mod_quiz\quiz_attempt::FINISHED;
      $DB->update_record('quiz_attempts', $attempt); // FIXME - not update all fields or or add timestamps? Submit the attempts?
    }
}

redirect(new \moodle_url('/mod/quiz/view.php',['id' => $cmid ]));
die();
