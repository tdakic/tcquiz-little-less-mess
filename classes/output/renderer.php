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
  * Renderer class for tcquiz extends mod_quiz renderer
  *
  * @package   quizaccess_tcquiz
  * @category  output
  * @copyright 2024 Tamara Dakic @ Capilano University
  * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
  */
namespace quizaccess_tcquiz\output;

use html_writer;
use mod_quiz\question\display_options;
//use mod_quiz\quiz_attempt;
use quizaccess_tcquiz\tcquiz_attempt;
use moodle_url;
use question_display_options;

class renderer extends \mod_quiz\output\renderer {

  /**
   * Outputs the form for making an attempt for student
   *
   * @param quiz_attempt $attemptobj
   * @param int $page Current page number
   * @param array $slots Array of integers relating to questions on the page
   * @param int $sessionid ID of the current session of tcquiz
   * @param string $sesskey session key
   * @param int $time_left_for_question time left to answer the question
   * @return string HTML for student attempt form.
   */
  public function tcq_attempt_form($attemptobj, $page, $slots, $sessionid, $sesskey, $time_left_for_question) {

      global $OUTPUT;
      $process_url = new moodle_url('/mod/quiz/accessrule/tcquiz/processattempt.php',['cmid' => $attemptobj->get_cmid(),'attemptid'=>$attemptobj->get_attemptid(),'sessionid'=>$sessionid]);

      $output = '';
      $output .= html_writer::start_tag('form',
                                      ['action' => $process_url,
                                       'method' => 'post',
                                       'enctype' => 'multipart/form-data', 'accept-charset' => 'utf-8',
                                       'id' => 'responseform']);
      $output .= html_writer::start_tag('div');

      // Print all the questions.
      //Are there any active questions? If not, do not display the submit button.
      $active_questions = false;

      foreach ($slots as $slot) {
          if ($attemptobj->get_question_state($slot)->is_active()){
            $active_questions = true;
          }
          $output .= $attemptobj->render_question($slot, false, $this, $attemptobj->attempt_url($slot, $page),false);
      }

      if ($active_questions){
              $output .= html_writer::empty_tag('input', ['type' => 'submit', 'name' => 'responseformsubmit',
              'value' => 'Submit', 'class' => 'mod_quiz-next-nav btn btn-primary', 'id' => 'responseformsubmit',
              'formaction' => $process_url]);
            }

     $output .= html_writer::start_tag('div');
     $output .= html_writer::end_tag('div');

      // Some hidden fields to track what is going on - likely not all needed?
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'attempt',
              'value' => $attemptobj->get_attemptid()]);
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'thispage',
              'value' => $page, 'id' => 'followingpage']);
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'timeup',
              'value' => '0', 'id' => 'timeup']);
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey',
              'value' => sesskey()]);
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'mdlscrollto',
              'value' => '', 'id' => 'mdlscrollto']);

      // Add a hidden field with questionids. Do this at the end of the form, so
      // if you navigate before the form has finished loading, it does not wipe all
      // the student's answers.
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'slots',
              'value' => implode(',', $attemptobj->get_active_slots($page))]);

      // Finish the form.
      $output .= html_writer::end_tag('div');
      $output .= html_writer::end_tag('form');

      $POLLING_INTERVAL = get_config('quizaccess_tcquiz', 'pollinginterval');

      $output .= $OUTPUT->render_from_template('quizaccess_tcquiz/student_timer', ['sessionid'=>$sessionid,
        'quizid'=>$attemptobj->get_quizid(), 'cmid'=> $attemptobj->get_cmid(), 'attemptid'=>$attemptobj->get_attemptid(),'page'=>$page,
        'time_for_question' => $time_left_for_question, 'POLLING_INTERVAL'=>$POLLING_INTERVAL]);

      $output .= $this->connection_warning();

      // This will be displayed once the student submitted their answers
      if (!$active_questions){
        $output .= "<h3>";
        $output .= get_string('questiondonewaitforresults', 'quizaccess_tcquiz');
        $output .= "<h3><br />";
      }

      return $output;
  }

 /**
  * Student attempt Page
  *
  * @param quiz_attempt $attemptobj
  * @param int $page Current page number
  * @param array $slots Array of integers relating to questions on the page
  * @param int $sessionid ID of the current session of tcquiz
  * @param string $sesskey session key
  * @param int $time_left_for_question time left to answer the question
  * @return string HTML for student attempt page.
  */
  public function tcq_attempt_page($attemptobj, $page, $slots, $sessionid, $sesskey, $time_left_for_question) {
      $output = '';
      $output .= $this->header();
      $output .= $this->tcq_attempt_form($attemptobj, $page, $slots, $sessionid, $sesskey, $time_left_for_question);
      $output .= $this->footer();
      return $output;
  }

  /**
   * Renders the attempt page for teacher.
   *
   * @param quiz_attempt $attemptobj instance of quiz_attempt
   * @param int $page current page number
   * @param array $slots of slots to be displayed.
   * @param int $sessionid ID of the current session of tcquiz
   * @param string $sesskey session key
   * @param int $time_left_for_question time left to answer the question
   * @return string HTML to display.
   */
  public function tcq_teacher_attempt_page($attemptobj, $page, $slots, $sessionid, $sesskey, $time_left_for_question) {

      global $CFG, $OUTPUT;
      $output = '';
      $output .= $this->header();

      $process_url = new moodle_url('/mod/quiz/accessrule/tcquiz/processattempt.php',
                    ['thispage'=>$page, 'sesskey' => $sesskey,'cmid' => $attemptobj->get_cmid(),
                    'attempt'=>$attemptobj->get_attemptid(),'sessionid'=>$sessionid]);

      $output .= html_writer::start_tag('form',
                                      ['action' => $process_url,
                                       'method' => 'post',
                                       'enctype' => 'multipart/form-data', 'accept-charset' => 'utf-8',
                                       'id' => 'responseform']);
      $output .= html_writer::start_tag('div');

      // Print all questions.

      foreach ($slots as $slot) {
          $output .= $attemptobj->render_question($slot, false, $this,
                  $attemptobj->attempt_url($slot, $page),true);
      }

      $output .= html_writer::end_tag('div');

      // no submit button for the teacher

      // Some hidden fields to track what is going on.
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'attempt',
              'value' => $attemptobj->get_attemptid()]);
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'thispage',
              'value' => $page, 'id' => 'followingpage']);
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'timeup',
              'value' => '0', 'id' => 'timeup']);
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey',
              'value' => sesskey()]);
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'mdlscrollto',
              'value' => '', 'id' => 'mdlscrollto']);

      // Add a hidden field with questionids. Do this at the end of the form, so
      // if you navigate before the form has finished loading, it does not wipe all
      // the student's answers.
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'slots',
              'value' => implode(',', $attemptobj->get_active_slots($page))]);

      // Finish the form.
      $output .= html_writer::end_tag('form');
      $output .= html_writer::start_tag('p',['id' => 'status']);
      $output .= html_writer::end_tag('p');

      //add the End question button for the teacher
      $POLLING_INTERVAL = get_config('quizaccess_tcquiz', 'pollinginterval'); //nedded for updating number of received answers
      $output .= $OUTPUT->render_from_template('quizaccess_tcquiz/teacher_quiz_controls', ['sessionid'=>$sessionid,
        'quizid'=>$attemptobj->get_quizid(), 'cmid'=> $attemptobj->get_cmid(), 'attemptid'=>$attemptobj->get_attemptid(),'page'=>$page,
        'time_for_question' => $time_left_for_question,'POLLING_INTERVAL'=>$POLLING_INTERVAL]);

      $output .= $this->connection_warning();

      $output .= $this->footer();
      return $output;
  }

  /**
   * Builds the review page for both teacher and student
   *
   * @param quiz_attempt $attemptobj an instance of quiz_attempt.
   * @param array $slots of slots to be displayed.
   * @param int $page the current page number
   * @param bool $showall whether to show entire attempt on one page.
   * @param display_options $displayoptions instance of display_options.
   * @return string HTML to display.
   */
  public function tcq_review_page($attemptobj, $slots, $page, $showall,
           display_options $displayoptions, $sessionid, $sesskey) {

      global $OUTPUT;

      $output = '';
      $output .= $this->header();
      $displayoptions = $attemptobj -> get_display_options(true);
      //$displayoptions->correctness = display_options::VISIBLE;
      //$displayoptions->feedback = display_options::VISIBLE;
      //$displayoptions->marks = display_options::VISIBLE;
      //$displayoptions->rightanswer = display_options::VISIBLE;

      if ($attemptobj->is_preview_user()){
        $output .= $this->tcq_teacher_review_form($attemptobj, $page, $slots, $sessionid, $sesskey);
        $output .= $OUTPUT->render_from_template('quizaccess_tcquiz/next_quiz_page', ['sessionid'=>$sessionid,
          'quizid'=>$attemptobj->get_quizid(), 'cmid'=> $attemptobj->get_cmid(), 'attemptid'=>$attemptobj->get_attemptid(),
          'page'=>$page]);
      }
      else{
        $output .= $this->tcq_review_form($page, $showall, $displayoptions,
                $this->questions($attemptobj, true, $slots, $page, $showall, $displayoptions),
                $attemptobj);
        $POLLING_INTERVAL = get_config('quizaccess_tcquiz', 'pollinginterval');
        $this->page->requires->js_call_amd('quizaccess_tcquiz/getquizpage', 'init', [$sessionid, $attemptobj->get_quizid(), $attemptobj->get_cmid(),
                $attemptobj->get_attemptid(), $page, 'POLLING_INTERVAL'=>$POLLING_INTERVAL]);
      }

      $output .= $this->footer();
      return $output;
  }

  /**
   * Renders the form part of the review page for student.
   *
   * @param int $page current page number
   * @param bool $showall if true display attempt on one page
   * @param display_options $displayoptions instance of display_options
   * @param string $content the rendered display of each question
   * @param quiz_attempt $attemptobj instance of quiz_attempt
   * @return string HTML to display.
   */
  public function tcq_review_form($page, $showall, $displayoptions, $content, $attemptobj) {
      if ($displayoptions->flags != question_display_options::EDITABLE) {
          return $content;
      }

      $this->page->requires->js_init_call('M.mod_quiz.init_review_form', null, false,
              quiz_get_js_module());

      $output = '';
      $output .= html_writer::start_tag('form', ['action' => $attemptobj->review_url(null,
              $page, $showall), 'method' => 'post', 'class' => 'questionflagsaveform']);
      $output .= html_writer::start_tag('div');
      $output .= $content;
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey',
              'value' => sesskey()]);
      $output .= html_writer::start_tag('div', ['class' => 'submitbtns']);
      $output .= html_writer::empty_tag('input', ['type' => 'submit',
              'class' => 'questionflagsavebutton btn btn-secondary', 'name' => 'savingflags',
              'value' => get_string('saveflags', 'question')]);
      $output .= html_writer::end_tag('div');
      $output .= html_writer::end_tag('div');
      $output .= html_writer::end_tag('form');

      return $output;
  }

  /**
   * Renders the form part of the review page for teacher.
   *
   * @param quiz_attempt $attemptobj instance of quiz_attempt
   * @param int $page current page number
   * @param array $slots of slots to be displayed.
   * @param int $sessionid ID of the current session of tcquiz
   * @param string $sesskey session key
   * @return string HTML to display.
   */
  public function tcq_teacher_review_form($attemptobj, $page, $slots, $sessionid, $sesskey) {
      global $CFG;

      $output = '';

      $output .= html_writer::start_tag('form',
                                       ['action' => "",
                                       'method' => 'post',
                                        'enctype' => 'multipart/form-data', 'accept-charset' => 'utf-8',
                                        'id' => 'responseform']);
      $output .= html_writer::start_tag('div');

      // Print all the questions.

      $file = $CFG->dirroot . '/mod/quiz/accessrule/tcquiz/report/statistics/report.php';
      if (is_readable($file)) {
          include_once($file);
      }
      $reportclassname = 'tcquiz_statistics_report';
      if (!class_exists($reportclassname)) {
          throw new \moodle_exception('preprocesserror', 'quiz');
      }

      $report = new $reportclassname();

      foreach ($slots as $slot) {

          $output .= $attemptobj->render_question($slot, true, $this, $attemptobj->attempt_url($slot, $page),true);
          $output .= html_writer::start_tag('div',['class' => 'questionresults']);
          $tmp_str = $report->tcq_display_question_stats($attemptobj->get_quiz(), $sessionid, $slot, $attemptobj->get_cm(), $attemptobj->get_course());
          $output .= $tmp_str;
      }
      $cmid = optional_param('cmid', 0, PARAM_INT);
      $quizid = optional_param('quizid', 0, PARAM_INT);


      $output .= html_writer::end_tag('div');

      // Some hidden fields to track what is going on.
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'attempt',
              'value' => $attemptobj->get_attemptid()]);
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'thispage',
              'value' => $page, 'id' => 'followingpage']);
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'timeup',
              'value' => '0', 'id' => 'timeup']);
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey',
              'value' => sesskey()]);
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'mdlscrollto',
              'value' => '', 'id' => 'mdlscrollto']);

      // Add a hidden field with questionids. Do this at the end of the form, so
      // if you navigate before the form has finished loading, it does not wipe all
      // the student's answers.
      $output .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'slots',
              'value' => implode(',', $attemptobj->get_active_slots($page))]);

      // Finish the form.

      $output .= html_writer::end_tag('form');

      return $output;
  }

}
