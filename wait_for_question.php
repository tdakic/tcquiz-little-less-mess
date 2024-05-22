<?php

use mod_quiz\quiz_settings;
//namespace quizaccess_tcquiz;
require_once(__DIR__ . '/../../../../config.php');
global $CFG, $DB, $PAGE, $USER;

$sessionid = optional_param('sessionid', -1, PARAM_INT);
$joincode = required_param('joincode', PARAM_ALPHANUM);
$quizid = required_param('quizid', PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);
$attemptid = required_param('attemptid', PARAM_INT );


//$context = context_module::instance($cm->id);

$quizobj = quiz_settings::create_for_cmid($cmid, $USER->id);
// Check login and sesskey.
require_login($quizobj->get_course(), false, $quizobj->get_cm());
require_sesskey();
$context = $quizobj->get_context();
//require_capability('mod/quiz:manage', $context);

$PAGE->set_title($SITE->fullname);
$url = htmlspecialchars_decode(new moodle_url('/mod/quiz/accessrule/tcquiz/wait_for_question.php',['joincode'=>$joincode, 'sessionid' => $sessionid,  'attemptid' => $attemptid, 'cmid' => $cmid, 'quizid' => $quizid ]));
$PAGE->set_url($url);

//$PAGE->set_heading(get_string('pluginname', 'local_greetings'));
$output = $PAGE->get_renderer('mod_quiz');
echo $output->header();

//This should eventually not be here?
if (!$session = $DB->get_record('quizaccess_tcquiz_session', array('quizid' => $quizid,'joincode' => $joincode))){
//
  echo "ERRROR";
}

$POLLING_INTERVAL = get_config('quizaccess_tcquiz', 'pollinginterval');
//echo $OUTPUT->render_from_template('quizaccess_tcquiz/test', ['ttt'=>json_encode($template_data),'first'=>$template_data]);
echo $output->render_from_template('quizaccess_tcquiz/wait_for_question', ['sessionid'=>$session->id, 'joincode'=>$joincode, 'quizid'=>$quizid, 'cmid'=>$cmid,
  'attemptid'=>$attemptid, 'POLLING_INTERVAL'=>$POLLING_INTERVAL]);

echo $output->footer();
