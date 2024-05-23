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
 * This script is used when the teacher starts a tcquiz and waits for the
 * students to connect
 *
 * @package   quizaccess_tcquiz
 * @copyright 2024 Tamara Dakic @Capilano University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_tcquiz;

use mod_quiz\quiz_settings;

require_once(__DIR__ . '/../../../../config.php');
global  $DB, $PAGE, $USER;

$joincode = required_param('joincode', PARAM_ALPHANUM);
$quizid = required_param('quizid', PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);
$attemptid = required_param('attemptid', PARAM_INT );
$sessionid = required_param('sessionid', PARAM_INT);

$quizobj = quiz_settings::create_for_cmid($cmid, $USER->id);
// Check login and sesskey.
require_login($quizobj->get_course(), false, $quizobj->get_cm());
require_sesskey();

$context = $quizobj->get_context();
require_capability('mod/quiz:manage', $context);

if (!$session = $DB->get_record('quizaccess_tcquiz_session', array('quizid' => $quizid,'id' => $sessionid))){
  throw new moodle_exception('nosession', 'quizaccess_tcquiz', $quizobj->view_url());
}
//make sure that the user is the owner of the session
if (!$quizobj->is_preview_user() || $session->teacherid != $USER->id){
      throw new moodle_exception('notyoursession', 'quizaccess_tcquiz', $quizobj->view_url());
}

$PAGE->set_cacheable(false);
$PAGE->set_title($SITE->fullname);
$url = htmlspecialchars_decode(new \moodle_url('/mod/quiz/accessrule/tcquiz/wait_for_students.php',['sessionid'=>$sessionid, 'attemptid' => $attemptid, 'cmid' => $cmid, 'quizid' => $quizid ]));
$PAGE->set_url($url);

$output = $PAGE->get_renderer('mod_quiz');
echo $output->header();

$POLLING_INTERVAL = get_config('quizaccess_tcquiz', 'pollinginterval');
echo $output->render_from_template('quizaccess_tcquiz/wait_for_students', ['sessionid'=>$sessionid, 'quizid'=>$quizid, 'cmid'=>$cmid,
  'attemptid'=>$attemptid, 'POLLING_INTERVAL'=>$POLLING_INTERVAL]);

echo $output->footer();
