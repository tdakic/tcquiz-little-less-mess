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
 * This script ends the tcquiz question.
 * It is called when the teacher clicks on the End button while
 * administering a tcq question.
 *
 * @package   quizaccess_tcquiz
 * @copyright 2024 Tamara Dakic @Capilano University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace quizaccess_tcquiz;

use mod_quiz\quiz_settings;

require_once(__DIR__ . '/../../../../config.php');
global $DB, $USER;

$sessionid = required_param('sessionid', PARAM_INT);
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

$session->currentpagestate = 0; //not running
$session->nextendtime = time(); //in case a student just got connected

$DB->update_record('quizaccess_tcquiz_session', $session);
