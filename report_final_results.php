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
 * This script controls the display of the quiz reports.
 *
 * @package   mod_quiz
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_quiz\quiz_settings;
use html_writer;

define('NO_OUTPUT_BUFFERING', true);

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/mod/quiz/report/reportlib.php');

global $DB;

$id = optional_param('id', 0, PARAM_INT);
$q = optional_param('quizid', 0, PARAM_INT);
$sessionid = optional_param('tcqsid', 0, PARAM_INT);
//$mode = optional_param('mode', '', PARAM_ALPHA);
//TTT


$mode ="overview";

//not an tcquiz?
//make sure that the quiz is set up as a tcquiz
if (!$tcquiz = $DB->get_record('quizaccess_tcquiz', array('quizid' => $q))){
  //Add new exceptions
  throw new moodle_exception('nottcquiz', 'quizaccess_tcquiz', new moodle_url('/my/courses.php', []));
}

//make sure that the user is not trying to attempt the wrong page -- resend them to the start pages if they do
if (!$tcquizsession = $DB->get_record('quizaccess_tcquiz_session', array('id' => $sessionid))){
  throw new moodle_exception('nosession', 'quizaccess_tcquiz', new moodle_url('/my/courses.php', []));

}
//if the state of the quiz is different than TCQUIZ_STATUS_FINALRESULTS = 40 defined in locallib.php
if ($tcquizsession->status != 40){
  throw new moodle_exception('notrightquizstate', 'quizaccess_tcquiz', new moodle_url('/my/courses.php', []));

}

//$page = $attemptobj->force_page_number_into_range($page);
//$PAGE->set_url($attemptobj->attempt_url(null, $page));

$PAGE->set_cacheable(false);



if ($id) {
    $quizobj = quiz_settings::create_for_cmid($id);
} else {
    $quizobj = quiz_settings::create($q);
}
$quiz = $quizobj->get_quiz();
$cm = $quizobj->get_cm();
$course = $quizobj->get_course();

$url = new moodle_url('/mod/quiz/view.php', ['id' => $cm->id]);
if ($mode !== '') {
    $url->param('mode', $mode);
}
$PAGE->set_url($url);

require_login($course, false, $cm);

$PAGE->set_pagelayout('report');
$PAGE->activityheader->disable();
$reportlist = quiz_report_list($quizobj->get_context());
if (empty($reportlist)) {
    throw new \moodle_exception('erroraccessingreport', 'quiz');
}

// Validate the requested report name.
if ($mode == '') {
    // Default to first accessible report and redirect.
    $url->param('mode', reset($reportlist));
    redirect($url);
} else if (!in_array($mode, $reportlist)) {
    throw new \moodle_exception('erroraccessingreport', 'quiz');
}
if (!is_readable("report/$mode/report.php")) {
    throw new \moodle_exception('reportnotfound', 'quiz', '', $mode);
}

// Open the selected quiz report and display it.
$file = $CFG->dirroot . '/mod/quiz/accessrule/tcquiz/report/' . $mode . '/report.php';

if (is_readable($file)) {
    include_once($file);
}

$reportclassname = 'tcquiz_' . $mode . '_report';
if (!class_exists($reportclassname)) {
    throw new \moodle_exception('preprocesserror', 'quiz');
}

$report = new $reportclassname();


$report->tcq_display_final_graph($quiz, $cm, $course,$sessionid);

$process_url = new moodle_url(new moodle_url('/mod/quiz/accessrule/tcquiz/end_session.php'),['cmid' => $cm->id, 'id'=> $sessionid]);


$output = '';
$output .= html_writer::start_tag('form',
          ['action' => $process_url,
                 'method' => 'post',
                  'enctype' => 'multipart/form-data', 'accept-charset' => 'utf-8',
                  'id' => 'responseform']);
$output .= html_writer::start_tag('div');


$output .= html_writer::empty_tag('input', ['type' => 'submit', 'name' => 'responseformsubmit',
'value' => 'End quiz', 'class' => 'mod_quiz-next-nav btn btn-primary', 'id' => 'responseformsubmit']);


$output .= html_writer::start_tag('div');

$output .= html_writer::end_tag('form');

echo $output;


echo $OUTPUT->footer();
